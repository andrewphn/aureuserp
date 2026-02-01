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
 * Restore Project Drive Folder Job
 *
 * Moves project folder back to Active when project is restored from soft-delete.
 */
class RestoreProjectDriveFolderJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public array $backoff = [60, 300, 900];

    public function __construct(
        public Project $project
    ) {}

    public function handle(GoogleDriveService $driveService): void
    {
        Log::info('Restoring Google Drive folder for project', [
            'project_id' => $this->project->id,
            'project_number' => $this->project->project_number,
        ]);

        if (!$driveService->isConfigured()) {
            Log::warning('Google Drive not configured, skipping folder restore');
            return;
        }

        if (!$this->project->google_drive_root_folder_id) {
            Log::debug('Project has no Google Drive folder, skipping restore');
            return;
        }

        try {
            $driveService->reactivateProject($this->project);
        } catch (\Exception $e) {
            Log::error('Failed to restore Google Drive folder', [
                'project_id' => $this->project->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }
}
