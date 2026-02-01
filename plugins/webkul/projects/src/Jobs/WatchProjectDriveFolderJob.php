<?php

namespace Webkul\Project\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Webkul\Project\Models\Project;
use Webkul\Project\Services\GoogleDrive\GoogleDriveWebhookService;

/**
 * Watch Project Drive Folder Job
 *
 * Sets up a push notification channel (watch) for a project's Google Drive folder.
 */
class WatchProjectDriveFolderJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public array $backoff = [60, 300, 900];

    public function __construct(
        public Project $project
    ) {}

    public function handle(GoogleDriveWebhookService $webhookService): void
    {
        Log::info('Setting up Google Drive watch for project', [
            'project_id' => $this->project->id,
            'project_number' => $this->project->project_number,
        ]);

        if (!$webhookService->isReady()) {
            Log::warning('Google Drive webhook service not ready');
            return;
        }

        if (!$this->project->google_drive_root_folder_id) {
            Log::debug('Project has no Google Drive folder, cannot set up watch');
            return;
        }

        try {
            $watch = $webhookService->watchProject($this->project);

            if ($watch) {
                Log::info('Google Drive watch created successfully', [
                    'project_id' => $this->project->id,
                    'channel_id' => $watch['channel_id'],
                ]);
            } else {
                Log::warning('Failed to create Google Drive watch', [
                    'project_id' => $this->project->id,
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Error creating Google Drive watch', [
                'project_id' => $this->project->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }
}
