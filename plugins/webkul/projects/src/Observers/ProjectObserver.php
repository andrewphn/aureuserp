<?php

namespace Webkul\Project\Observers;

use Illuminate\Support\Facades\Log;
use Webkul\Project\Models\Project;
use Webkul\Project\Jobs\CreateProjectDriveFoldersJob;
use Webkul\Project\Jobs\RenameProjectDriveFolderJob;
use Webkul\Project\Jobs\ArchiveProjectDriveFolderJob;
use Webkul\Project\Jobs\RestoreProjectDriveFolderJob;
use Webkul\Project\Jobs\DeleteProjectDriveFolderJob;

/**
 * Project Observer
 *
 * Handles lifecycle events for projects, including full Google Drive CRUD:
 * - Create: Creates folder structure when project is created
 * - Update: Renames folder when project number changes
 * - Delete (soft): Archives folder (moves to Archived folder)
 * - Restore: Reactivates folder (moves back to Active folder)
 * - Force Delete: Permanently deletes folder
 */
class ProjectObserver
{
    /**
     * Handle the Project "created" event.
     *
     * Dispatches a job to create Google Drive folders for the new project.
     */
    public function created(Project $project): void
    {
        // Skip if Google Drive is disabled for this project
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
     * Renames Google Drive folder when project number changes.
     */
    public function updated(Project $project): void
    {
        // Skip if no Google Drive folder exists
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
