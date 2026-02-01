<?php

namespace Webkul\Project\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Webkul\Project\Services\GoogleDrive\GoogleDriveService;

/**
 * Delete Project Drive Folder Job
 *
 * Permanently deletes project folder when project is force-deleted.
 * Note: Uses folder ID directly since project may already be deleted.
 */
class DeleteProjectDriveFolderJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public array $backoff = [60, 300, 900];

    public function __construct(
        public string $folderId,
        public int $projectId,
        public ?string $projectNumber = null
    ) {}

    public function handle(GoogleDriveService $driveService): void
    {
        Log::info('Permanently deleting Google Drive folder for project', [
            'project_id' => $this->projectId,
            'project_number' => $this->projectNumber,
            'folder_id' => $this->folderId,
        ]);

        if (!$driveService->isConfigured()) {
            Log::warning('Google Drive not configured, skipping folder delete');
            return;
        }

        try {
            $driveService->folders()->deleteFolder($this->folderId);

            Log::info('Google Drive folder permanently deleted', [
                'project_id' => $this->projectId,
                'folder_id' => $this->folderId,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to delete Google Drive folder', [
                'project_id' => $this->projectId,
                'folder_id' => $this->folderId,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }
}
