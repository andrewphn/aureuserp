<?php

namespace Webkul\Project\Console\Commands;

use Illuminate\Console\Command;
use Webkul\Project\Models\ProjectDraft;

/**
 * Clean up expired project drafts
 *
 * Removes draft records that have passed their expiration date.
 * This helps keep the database clean and prevents stale data accumulation.
 *
 * Schedule: Runs daily at 2:00 AM
 */
class CleanupExpiredDrafts extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'projects:cleanup-drafts
                            {--dry-run : Show what would be deleted without actually deleting}
                            {--older-than= : Delete drafts older than X days (default: uses expires_at)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Remove expired project drafts from the database';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $isDryRun = $this->option('dry-run');
        $olderThan = $this->option('older-than');

        $this->info('Starting project draft cleanup...');

        // Build the query
        $query = ProjectDraft::query();

        if ($olderThan) {
            // Delete drafts older than X days regardless of expires_at
            $cutoffDate = now()->subDays((int) $olderThan);
            $query->where('created_at', '<', $cutoffDate);
            $this->info("Looking for drafts created before: {$cutoffDate->toDateTimeString()}");
        } else {
            // Default: delete expired drafts based on expires_at column
            $query->where('expires_at', '<', now());
            $this->info("Looking for drafts expired before: " . now()->toDateTimeString());
        }

        $count = $query->count();

        if ($count === 0) {
            $this->info('No expired drafts found. Database is clean!');
            return Command::SUCCESS;
        }

        if ($isDryRun) {
            $this->warn("[DRY RUN] Would delete {$count} expired draft(s):");

            $drafts = $query->with('user')->limit(20)->get();
            $this->table(
                ['ID', 'User', 'Created', 'Expires', 'Step'],
                $drafts->map(fn ($draft) => [
                    $draft->id,
                    $draft->user?->name ?? 'Unknown',
                    $draft->created_at->toDateTimeString(),
                    $draft->expires_at?->toDateTimeString() ?? 'N/A',
                    $draft->current_step,
                ])
            );

            if ($count > 20) {
                $this->info("... and " . ($count - 20) . " more");
            }

            return Command::SUCCESS;
        }

        // Actually delete the drafts
        $deleted = $query->delete();

        $this->info("Successfully deleted {$deleted} expired draft(s).");

        return Command::SUCCESS;
    }
}
