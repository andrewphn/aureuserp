<?php

namespace App\Console\Commands;

use App\Models\PdfDocument;
use App\Models\PdfPage;
use Illuminate\Console\Command;

class PopulateMissingPdfPages extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'pdf:populate-pages {--force : Skip confirmation}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Populate pdf_pages table for PDF documents that are missing page records';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Checking for PDF documents with missing pages...');

        // Find all PDF documents
        $documents = PdfDocument::all();

        if ($documents->isEmpty()) {
            $this->warn('No PDF documents found in the database.');
            return 0;
        }

        $this->info("Found {$documents->count()} PDF documents.");

        // Find documents without pages
        $documentsWithoutPages = $documents->filter(function ($doc) {
            return $doc->pages()->count() === 0 && $doc->page_count > 0;
        });

        if ($documentsWithoutPages->isEmpty()) {
            $this->info('All PDF documents already have pages. Nothing to do.');
            return 0;
        }

        $this->warn("Found {$documentsWithoutPages->count()} documents without pages:");

        foreach ($documentsWithoutPages as $doc) {
            $this->line("  - ID {$doc->id}: {$doc->file_name} ({$doc->page_count} pages)");
        }

        if (!$this->option('force')) {
            if (!$this->confirm('Do you want to create page records for these documents?')) {
                $this->info('Operation cancelled.');
                return 0;
            }
        }

        $this->info('Creating page records...');
        $totalPages = 0;

        foreach ($documentsWithoutPages as $document) {
            $this->line("Processing: {$document->file_name}...");

            for ($i = 1; $i <= $document->page_count; $i++) {
                PdfPage::create([
                    'document_id' => $document->id,
                    'page_number' => $i,
                    'width' => 612,  // Standard US Letter width in points (8.5 inches)
                    'height' => 792, // Standard US Letter height in points (11 inches)
                    'rotation' => 0,
                    'thumbnail_path' => null, // Will be generated on-demand
                    'extracted_text' => null, // Can be extracted later if needed
                    'page_metadata' => [
                        'populated_by' => 'populate-pages-command',
                        'populated_at' => now()->toDateTimeString(),
                    ],
                ]);
                $totalPages++;
            }

            $this->info("  ✓ Created {$document->page_count} pages for document #{$document->id}");
        }

        $this->info('');
        $this->info("✅ Successfully created {$totalPages} page records for {$documentsWithoutPages->count()} documents!");

        // Show summary
        $this->info('');
        $this->info('Summary:');
        $this->table(
            ['Metric', 'Count'],
            [
                ['Total PDF Documents', PdfDocument::count()],
                ['Total PDF Pages', PdfPage::count()],
                ['Documents Processed', $documentsWithoutPages->count()],
                ['Pages Created', $totalPages],
            ]
        );

        return 0;
    }
}
