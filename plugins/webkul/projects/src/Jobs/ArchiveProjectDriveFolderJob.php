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
 * Archive Project Drive Folder Job
 *
 * Moves project folder to Archived when project is soft-deleted.
 */
class ArchiveProjectDriveFolderJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public array $backoff = [60, 300, 900];

    public function __construct(
        public Project $project
    ) {}

    public function handle(GoogleDriveService $driveService): void
    {
        Log::info('Archiving Google Drive folder for project', [
            'project_id' => $this->project->id,
            'project_number' => $this->project->project_number,
        ]);

        if (!$driveService->isConfigured()) {
            Log::warning('Google Drive not configured, skipping folder archive');
            return;
        }

        if (!$this->project->google_drive_root_folder_id) {
            Log::debug('Project has no Google Drive folder, skipping archive');
            return;
        }

        try {
            $driveService->archiveProject($this->project);
        } catch (\Exception $e) {
            Log::error('Failed to archive Google Drive folder', [
                'project_id' => $this->project->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }
}
