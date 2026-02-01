<?php

namespace Webkul\Project\Console\Commands;

use Illuminate\Console\Command;
use Webkul\Project\Services\GoogleDrive\GoogleDriveWebhookService;

/**
 * Renew Google Drive Watches Command
 *
 * Renews expiring watch channels to maintain push notifications.
 * Should be scheduled to run daily.
 */
class RenewGoogleDriveWatchesCommand extends Command
{
    protected $signature = 'google-drive:renew-watches
                            {--force : Renew all watches, not just expiring ones}';

    protected $description = 'Renew expiring Google Drive push notification watches';

    public function handle(GoogleDriveWebhookService $webhookService): int
    {
        $this->info('Checking for expiring Google Drive watches...');

        if (!$webhookService->isReady()) {
            $this->error('Google Drive service is not configured.');
            return Command::FAILURE;
        }

        $results = $webhookService->renewExpiringWatches();

        $this->info("Total expiring watches: {$results['total']}");
        $this->info("Successfully renewed: {$results['renewed']}");

        if ($results['failed'] > 0) {
            $this->warn("Failed to renew: {$results['failed']}");
        }

        return Command::SUCCESS;
    }
}
