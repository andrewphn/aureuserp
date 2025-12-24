<?php

namespace Webkul\Project\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Webkul\Project\Models\Project;
use Webkul\Project\Services\GoogleDrive\GoogleDriveService;

/**
 * Create Project Drive Folders Job
 *
 * Creates the standard 5-folder structure in Google Drive for a project.
 */
class CreateProjectDriveFoldersJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * The number of seconds to wait before retrying the job.
     */
    public array $backoff = [60, 300, 900];

    /**
     * Create a new job instance.
     */
    public function __construct(
        public Project $project
    ) {}

    /**
     * Execute the job.
     */
    public function handle(GoogleDriveService $driveService): void
    {
        Log::info('Starting Google Drive folder creation for project', [
            'project_id' => $this->project->id,
            'project_number' => $this->project->project_number,
        ]);

        // Check if service is configured
        if (!$driveService->isConfigured()) {
            Log::warning('Google Drive not configured, skipping folder creation', [
                'project_id' => $this->project->id,
            ]);
            return;
        }

        // Check if project already has folders (in case of retry)
        if ($this->project->google_drive_root_folder_id) {
            Log::info('Project already has Google Drive folders, skipping', [
                'project_id' => $this->project->id,
                'folder_id' => $this->project->google_drive_root_folder_id,
            ]);
            return;
        }

        try {
            $result = $driveService->createProjectFolders($this->project);

            if ($result) {
                Log::info('Google Drive folders created successfully', [
                    'project_id' => $this->project->id,
                    'root_folder_id' => $result['root_folder_id'],
                    'folder_url' => $result['folder_url'],
                    'folders' => array_keys($result['folders']),
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Failed to create Google Drive folders', [
                'project_id' => $this->project->id,
                'error' => $e->getMessage(),
                'attempt' => $this->attempts(),
            ]);

            // Re-throw to trigger retry
            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('Google Drive folder creation job failed permanently', [
            'project_id' => $this->project->id,
            'project_number' => $this->project->project_number,
            'error' => $exception->getMessage(),
            'attempts' => $this->attempts(),
        ]);

        // Could send notification to admin here
    }
}
