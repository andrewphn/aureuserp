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
 * Rename Project Drive Folder Job
 *
 * Renames the project's Google Drive folder when project number/name changes.
 */
class RenameProjectDriveFolderJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public array $backoff = [60, 300, 900];

    public function __construct(
        public Project $project,
        public string $newName
    ) {}

    public function handle(GoogleDriveService $driveService): void
    {
        Log::info('Renaming Google Drive folder for project', [
            'project_id' => $this->project->id,
            'new_name' => $this->newName,
        ]);

        if (!$driveService->isConfigured()) {
            Log::warning('Google Drive not configured, skipping folder rename');
            return;
        }

        if (!$this->project->google_drive_root_folder_id) {
            Log::debug('Project has no Google Drive folder, skipping rename');
            return;
        }

        try {
            $driveService->renameProjectFolder($this->project, $this->newName);
        } catch (\Exception $e) {
            Log::error('Failed to rename Google Drive folder', [
                'project_id' => $this->project->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }
}
