<?php

namespace Webkul\Project\Listeners;

use Illuminate\Support\Facades\Log;
use Webkul\Project\Events\ProjectStageChanged;
use Webkul\Project\Services\GoogleDrive\GoogleDriveService;
use Webkul\Project\Services\InventoryReservationService;

/**
 * Handles project stage changes and triggers inventory operations.
 *
 * Stage Key Mappings:
 * - sourcing: Project is in procurement phase
 * - material_reserved: Materials should be reserved in inventory
 * - material_issued: Materials should be issued (moved from stock)
 * - production: Building phase begins
 * - delivery: Final stage before completion
 * - completed: Project is done (archived in Google Drive)
 */
class HandleProjectStageChange
{
    protected InventoryReservationService $reservationService;
    protected GoogleDriveService $driveService;

    public function __construct(
        InventoryReservationService $reservationService,
        GoogleDriveService $driveService
    ) {
        $this->reservationService = $reservationService;
        $this->driveService = $driveService;
    }

    /**
     * Handle the event.
     */
    public function handle(ProjectStageChanged $event): void
    {
        $project = $event->project;
        $newStageKey = $event->getNewStageKey();
        $previousStageKey = $event->getPreviousStageKey();

        // Get stage names for checking completed/cancelled
        $newStageName = strtolower($event->newStage?->name ?? '');
        $previousStageName = strtolower($event->previousStage?->name ?? '');

        // Track when project entered this stage (for expiry warnings)
        $project->update(['stage_entered_at' => now()]);

        Log::info('Project stage changed', [
            'project_id' => $project->id,
            'project_number' => $project->project_number,
            'from_stage' => $previousStageKey ?: $previousStageName,
            'to_stage' => $newStageKey ?: $newStageName,
        ]);

        // Handle stage-specific actions
        match ($newStageKey) {
            'material_reserved' => $this->handleMaterialReserved($event),
            'material_issued' => $this->handleMaterialIssued($event),
            'sourcing' => $this->handleSourcing($event),
            'production' => $this->handleProduction($event),
            'completed' => $this->handleCompleted($event),
            default => null,
        };

        // Check for Done/Cancelled by stage name (when stage_key is empty)
        if (in_array($newStageName, ['done', 'cancelled', 'completed'])) {
            $this->handleCompleted($event);
        }

        // Handle reactivation: moving FROM done/cancelled to active stage
        if (in_array($previousStageName, ['done', 'cancelled', 'completed'])
            && !in_array($newStageName, ['done', 'cancelled', 'completed'])) {
            $this->handleReactivated($event);
        }

        // Handle transitions away from certain stages (cleanup)
        if ($previousStageKey === 'material_reserved' && $newStageKey !== 'material_issued') {
            // Moving backward from material_reserved - release reservations
            $this->handleReleaseReservations($event);
        }
    }

    /**
     * Handle transition to Material Reserved stage.
     * Attempts to reserve all BOM materials from inventory.
     */
    protected function handleMaterialReserved(ProjectStageChanged $event): void
    {
        $project = $event->project;

        // Skip if no warehouse assigned
        if (!$project->warehouse_id) {
            Log::warning('Cannot reserve materials - no warehouse assigned', [
                'project_id' => $project->id,
            ]);
            return;
        }

        $result = $this->reservationService->reserveMaterialsForProject($project);

        if ($result['success']) {
            Log::info('Materials reserved successfully', [
                'project_id' => $project->id,
                'reservations_created' => $result['reservations']->count(),
            ]);
        } else {
            Log::warning('Material reservation had errors', [
                'project_id' => $project->id,
                'errors' => $result['errors'],
            ]);
        }
    }

    /**
     * Handle transition to Material Issued stage.
     * Issues all reserved materials (creates inventory moves).
     */
    protected function handleMaterialIssued(ProjectStageChanged $event): void
    {
        $project = $event->project;

        $result = $this->reservationService->issueAllMaterialsForProject($project);

        if ($result['success']) {
            Log::info('Materials issued successfully', [
                'project_id' => $project->id,
                'moves_created' => $result['moves']->count(),
            ]);
        } else {
            Log::warning('Material issuance had errors', [
                'project_id' => $project->id,
                'errors' => $result['errors'],
            ]);
        }
    }

    /**
     * Handle transition to Sourcing stage.
     * This is where procurement planning begins.
     */
    protected function handleSourcing(ProjectStageChanged $event): void
    {
        $project = $event->project;

        // Log for tracking - could trigger procurement notifications
        Log::info('Project entered Sourcing stage', [
            'project_id' => $project->id,
            'project_number' => $project->project_number,
        ]);

        // Future: Generate purchase requisitions for out-of-stock items
        // Future: Send notifications to procurement team
    }

    /**
     * Handle transition to Production stage.
     * Verify all materials are issued before production begins.
     */
    protected function handleProduction(ProjectStageChanged $event): void
    {
        $project = $event->project;
        $previousStageKey = $event->getPreviousStageKey();

        // If coming directly from sourcing (skipping material stages), warn
        if ($previousStageKey === 'sourcing') {
            Log::warning('Project moved to Production without material reservation', [
                'project_id' => $project->id,
                'project_number' => $project->project_number,
            ]);
        }

        Log::info('Project entered Production stage', [
            'project_id' => $project->id,
            'project_number' => $project->project_number,
        ]);
    }

    /**
     * Release reservations when moving backward from material_reserved.
     */
    protected function handleReleaseReservations(ProjectStageChanged $event): void
    {
        $project = $event->project;

        $released = $this->reservationService->releaseAllReservationsForProject(
            $project,
            'Stage reverted from Material Reserved'
        );

        if ($released > 0) {
            Log::info('Released material reservations due to stage change', [
                'project_id' => $project->id,
                'reservations_released' => $released,
            ]);
        }
    }

    /**
     * Handle project completion - archive Google Drive folder.
     */
    protected function handleCompleted(ProjectStageChanged $event): void
    {
        $project = $event->project;

        // Skip if Google Drive not configured or no folder
        if (!$project->google_drive_enabled || !$project->google_drive_root_folder_id) {
            return;
        }

        Log::info('Project completed - archiving Google Drive folder', [
            'project_id' => $project->id,
            'project_number' => $project->project_number,
        ]);

        $this->driveService->archiveProject($project);
    }

    /**
     * Handle project reactivation - move folder back to Active.
     */
    protected function handleReactivated(ProjectStageChanged $event): void
    {
        $project = $event->project;

        // Skip if Google Drive not configured or no folder
        if (!$project->google_drive_enabled || !$project->google_drive_root_folder_id) {
            return;
        }

        Log::info('Project reactivated - moving folder back to Active', [
            'project_id' => $project->id,
            'project_number' => $project->project_number,
        ]);

        $this->driveService->reactivateProject($project);
    }
}
