<?php

namespace Webkul\Project\Observers;

use Illuminate\Support\Facades\Log;
use Webkul\Project\Models\Project;
use Webkul\Project\Services\GoogleDrive\GoogleDriveService;
use Webkul\Project\Jobs\CreateProjectDriveFoldersJob;

/**
 * Project Observer
 *
 * Handles lifecycle events for projects, including Google Drive folder creation.
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
     * Handle the Project "deleted" event.
     *
     * Optionally archive Google Drive folders when project is deleted.
     */
    public function deleted(Project $project): void
    {
        // Just log for now - we don't want to auto-delete Drive folders
        if ($project->google_drive_root_folder_id) {
            Log::info('Project with Google Drive folders deleted', [
                'project_id' => $project->id,
                'folder_id' => $project->google_drive_root_folder_id,
                'folder_url' => $project->google_drive_folder_url,
            ]);
        }
    }
}
