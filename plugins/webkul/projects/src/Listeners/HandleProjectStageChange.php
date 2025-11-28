<?php

namespace Webkul\Project\Listeners;

use Illuminate\Support\Facades\Log;
use Webkul\Project\Events\ProjectStageChanged;
use Webkul\Project\Services\InventoryReservationService;

/**
 * Handles project stage changes and triggers inventory operations.
 *
 * Stage Key Mappings:
 * - sourcing: Project is in procurement phase
 * - material_reserved: Materials should be reserved in inventory
 * - material_issued: Materials should be issued (moved from stock)
 * - production: Building phase begins
 */
class HandleProjectStageChange
{
    protected InventoryReservationService $reservationService;

    public function __construct(InventoryReservationService $reservationService)
    {
        $this->reservationService = $reservationService;
    }

    /**
     * Handle the event.
     */
    public function handle(ProjectStageChanged $event): void
    {
        $project = $event->project;
        $newStageKey = $event->getNewStageKey();
        $previousStageKey = $event->getPreviousStageKey();

        Log::info('Project stage changed', [
            'project_id' => $project->id,
            'project_number' => $project->project_number,
            'from_stage' => $previousStageKey,
            'to_stage' => $newStageKey,
        ]);

        // Handle stage-specific actions
        match ($newStageKey) {
            'material_reserved' => $this->handleMaterialReserved($event),
            'material_issued' => $this->handleMaterialIssued($event),
            'sourcing' => $this->handleSourcing($event),
            'production' => $this->handleProduction($event),
            default => null,
        };

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
}
