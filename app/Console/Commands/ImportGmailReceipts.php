<?php

namespace App\Console\Commands;

use App\Services\Receipts\ReceiptEmailFetcher;
use App\Services\Receipts\ReceiptEmailProcessor;
use Illuminate\Console\Command;

/**
 * Import Gmail receipts for QC scanning.
 */
class ImportGmailReceipts extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'receipts:import-gmail
        {--max= : Max messages to scan}
        {--days= : Max age in days to scan}
        {--dry-run : Only list matched messages}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import receipt emails from Gmail and enqueue scans for QC review';

    public function __construct(
        private readonly ReceiptEmailFetcher $fetcher,
        private readonly ReceiptEmailProcessor $processor
    ) {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $maxMessages = $this->option('max');
        $maxDays = $this->option('days');
        $dryRun = (bool) $this->option('dry-run');

        if ($maxDays !== null) {
            config(['receipts.gmail.max_age_days' => (int) $maxDays]);
        }

        if ($maxMessages !== null) {
            $maxMessages = (int) $maxMessages;
        }

        $messages = $this->fetcher->listReceiptMessages($maxMessages);

        if ($dryRun) {
            $this->info('Matched messages: ' . count($messages));
            foreach ($messages as $message) {
                $this->line(sprintf(
                    '- %s | %s',
                    $message['from_email'] ?? 'unknown',
                    $message['subject'] ?? 'no subject'
                ));
            }
            return 0;
        }

        $results = $this->processor->processMessages($messages);

        $this->info('Receipt import complete');
        $this->table(
            ['processed', 'skipped', 'failed'],
            [[
                $results['processed'] ?? 0,
                $results['skipped'] ?? 0,
                $results['failed'] ?? 0,
            ]]
        );

        return ($results['failed'] ?? 0) > 0 ? 1 : 0;
    }
}
