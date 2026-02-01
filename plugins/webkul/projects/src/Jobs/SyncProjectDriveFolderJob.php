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
 * Sync Project Drive Folder Job
 *
 * Syncs project files with Google Drive after receiving a webhook notification.
 * Detects changes and logs them to project chatter.
 */
class SyncProjectDriveFolderJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public array $backoff = [30, 60, 120];

    /**
     * Delay before processing to batch rapid changes
     */
    public int $delay = 5;

    public function __construct(
        public Project $project,
        public array $webhookData = []
    ) {}

    public function handle(GoogleDriveService $driveService): void
    {
        $resourceState = $this->webhookData['resource_state'] ?? 'unknown';
        $changed = $this->webhookData['changed'] ?? null;

        Log::info('Processing Google Drive sync for project', [
            'project_id' => $this->project->id,
            'project_number' => $this->project->project_number,
            'resource_state' => $resourceState,
            'changed' => $changed,
        ]);

        if (!$driveService->isConfigured()) {
            Log::warning('Google Drive not configured, skipping sync');
            return;
        }

        if (!$this->project->google_drive_root_folder_id) {
            Log::debug('Project has no Google Drive folder, skipping sync');
            return;
        }

        try {
            // Use the sync service to detect and log changes
            $syncResult = $driveService->syncProject($this->project);

            if ($syncResult['success']) {
                Log::info('Google Drive sync completed', [
                    'project_id' => $this->project->id,
                    'files_added' => $syncResult['added'] ?? 0,
                    'files_removed' => $syncResult['removed'] ?? 0,
                    'files_modified' => $syncResult['modified'] ?? 0,
                ]);
            } else {
                Log::warning('Google Drive sync completed with issues', [
                    'project_id' => $this->project->id,
                    'message' => $syncResult['message'] ?? 'Unknown issue',
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Failed to sync Google Drive folder', [
                'project_id' => $this->project->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Get unique job ID to prevent duplicate syncs
     */
    public function uniqueId(): string
    {
        return 'google-drive-sync-' . $this->project->id;
    }

    /**
     * Determine if the job should be unique
     */
    public function shouldBeUnique(): bool
    {
        return true;
    }

    /**
     * Get the number of seconds until the unique lock is released
     */
    public function uniqueFor(): int
    {
        return 30; // Prevent duplicate syncs within 30 seconds
    }
}
