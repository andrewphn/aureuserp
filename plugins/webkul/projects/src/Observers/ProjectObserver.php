<?php

namespace Webkul\Project\Observers;

use Illuminate\Support\Facades\Log;
use Webkul\Project\Jobs\ArchiveProjectDriveFolderJob;
use Webkul\Project\Jobs\CreateProjectDriveFoldersJob;
use Webkul\Project\Jobs\DeleteProjectDriveFolderJob;
use Webkul\Project\Jobs\RenameProjectDriveFolderJob;
use Webkul\Project\Jobs\RestoreProjectDriveFolderJob;
use Webkul\Project\Models\Project;
use Webkul\Project\Services\ProjectMilestoneService;

/**
 * Project Observer
 *
 * Handles lifecycle events for projects:
 * - Create: Creates milestones from templates, creates Google Drive folder structure
 * - Update: Creates Google Drive folders if enabled but missing, renames folder when project number changes
 * - Delete (soft): Archives folder (moves to Archived folder)
 * - Restore: Reactivates folder (moves back to Active folder)
 * - Force Delete: Permanently deletes folder
 */
class ProjectObserver
{
    /**
     * Handle the Project "created" event.
     *
     * - Creates milestones from templates (if allow_milestones is enabled)
     * - Dispatches job to create Google Drive folders
     */
    public function created(Project $project): void
    {
        // Create milestones from templates (if allow_milestones is enabled)
        if ($project->allow_milestones) {
            try {
                $milestoneService = app(ProjectMilestoneService::class);

                // Use selected_milestone_templates if set, otherwise create all
                $templateIds = $project->selected_milestone_templates;

                $result = $milestoneService->createMilestonesFromTemplates(
                    $project,
                    null,
                    $templateIds
                );

                Log::info('Created milestones for new project', [
                    'project_id' => $project->id,
                    'milestones_created' => $result['milestones_created'],
                    'selected_templates' => $templateIds,
                ]);
            } catch (\Exception $e) {
                Log::error('Failed to create milestones for project', [
                    'project_id' => $project->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        // Skip Google Drive if disabled for this project
        if (!$project->google_drive_enabled) {
            Log::debug('Google Drive disabled for project, skipping folder creation', [
                'project_id' => $project->id,
            ]);
            return;
        }

        // Skip if project already has Google Drive folders
        if ($project->google_drive_root_folder_id) {
            Log::debug('Project already has Google Drive folders', [
                'project_id' => $project->id,
                'folder_id' => $project->google_drive_root_folder_id,
            ]);
            return;
        }

        // Dispatch job to create folders asynchronously
        try {
            CreateProjectDriveFoldersJob::dispatch($project);

            Log::info('Dispatched Google Drive folder creation job for project', [
                'project_id' => $project->id,
                'project_number' => $project->project_number,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to dispatch Google Drive folder creation job', [
                'project_id' => $project->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Handle the Project "updated" event.
     *
     * - Creates Google Drive folders if enabled and not yet created
     * - Renames Google Drive folder when project number changes
     */
    public function updated(Project $project): void
    {
        // Check if Google Drive should be enabled but folders don't exist yet
        // This handles the case where google_drive_enabled is toggled ON for an existing project
        if ($project->google_drive_enabled && !$project->google_drive_root_folder_id) {
            try {
                CreateProjectDriveFoldersJob::dispatch($project);

                Log::info('Dispatched Google Drive folder creation job for existing project (enabled on save)', [
                    'project_id' => $project->id,
                    'project_number' => $project->project_number,
                    'google_drive_enabled_changed' => $project->wasChanged('google_drive_enabled'),
                ]);
            } catch (\Exception $e) {
                Log::error('Failed to dispatch Google Drive folder creation job on update', [
                    'project_id' => $project->id,
                    'error' => $e->getMessage(),
                ]);
            }

            return; // Don't try to rename a folder that's being created
        }

        // Skip remaining logic if no Google Drive folder exists
        if (!$project->google_drive_root_folder_id) {
            return;
        }

        // Skip if Google Drive is disabled
        if (!$project->google_drive_enabled) {
            return;
        }

        // Check if project_number changed
        if ($project->wasChanged('project_number') && $project->project_number) {
            try {
                RenameProjectDriveFolderJob::dispatch($project, $project->project_number);

                Log::info('Dispatched Google Drive folder rename job', [
                    'project_id' => $project->id,
                    'old_number' => $project->getOriginal('project_number'),
                    'new_number' => $project->project_number,
                ]);
            } catch (\Exception $e) {
                Log::error('Failed to dispatch Google Drive folder rename job', [
                    'project_id' => $project->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    /**
     * Handle the Project "deleted" event (soft delete).
     *
     * Archives Google Drive folder by moving it to the Archived folder.
     */
    public function deleted(Project $project): void
    {
        // Skip if no Google Drive folder exists
        if (!$project->google_drive_root_folder_id) {
            return;
        }

        // Skip if Google Drive is disabled
        if (!$project->google_drive_enabled) {
            return;
        }

        // For soft deletes, archive the folder (move to Archived)
        if ($project->isForceDeleting()) {
            // Force delete is handled by forceDeleted event
            return;
        }

        try {
            ArchiveProjectDriveFolderJob::dispatch($project);

            Log::info('Dispatched Google Drive folder archive job', [
                'project_id' => $project->id,
                'project_number' => $project->project_number,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to dispatch Google Drive folder archive job', [
                'project_id' => $project->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Handle the Project "restored" event (undelete).
     *
     * Reactivates Google Drive folder by moving it back to Active folder.
     */
    public function restored(Project $project): void
    {
        // Skip if no Google Drive folder exists
        if (!$project->google_drive_root_folder_id) {
            return;
        }

        // Skip if Google Drive is disabled
        if (!$project->google_drive_enabled) {
            return;
        }

        try {
            RestoreProjectDriveFolderJob::dispatch($project);

            Log::info('Dispatched Google Drive folder restore job', [
                'project_id' => $project->id,
                'project_number' => $project->project_number,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to dispatch Google Drive folder restore job', [
                'project_id' => $project->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Handle the Project "forceDeleted" event (permanent delete).
     *
     * Permanently deletes the Google Drive folder.
     */
    public function forceDeleted(Project $project): void
    {
        // Skip if no Google Drive folder exists
        if (!$project->google_drive_root_folder_id) {
            return;
        }

        try {
            // Pass folder ID directly since project will be gone
            DeleteProjectDriveFolderJob::dispatch(
                $project->google_drive_root_folder_id,
                $project->id,
                $project->project_number
            );

            Log::info('Dispatched Google Drive folder delete job', [
                'project_id' => $project->id,
                'project_number' => $project->project_number,
                'folder_id' => $project->google_drive_root_folder_id,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to dispatch Google Drive folder delete job', [
                'project_id' => $project->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
