<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Webkul\Product\Models\Product;

class MigrateProductImagesToSpatie extends Command
{
    protected $signature = 'products:migrate-images-to-spatie {--dry-run : Show what would be migrated without making changes}';

    protected $description = 'Migrate existing product images from JSON column to Spatie Media Library';

    public function handle()
    {
        $dryRun = $this->option('dry-run');

        if ($dryRun) {
            $this->info('DRY RUN - No changes will be made');
        }

        $products = Product::whereNotNull('images')
            ->where('images', '!=', '[]')
            ->where('images', '!=', 'null')
            ->get();

        $this->info("Found {$products->count()} products with images to migrate");

        $migrated = 0;
        $skipped = 0;
        $failed = 0;

        $bar = $this->output->createProgressBar($products->count());
        $bar->start();

        foreach ($products as $product) {
            $images = $product->images;

            if (empty($images) || !is_array($images)) {
                $bar->advance();
                continue;
            }

            // Check if product already has Spatie media
            if ($product->hasMedia('product-images')) {
                $this->line("\n  Product #{$product->id} already has Spatie media, skipping...");
                $skipped++;
                $bar->advance();
                continue;
            }

            foreach ($images as $imagePath) {
                // Handle both full paths and just filenames
                $possiblePaths = [
                    storage_path('app/public/products/images/' . basename($imagePath)),
                    storage_path('app/public/products/images/' . $imagePath),
                    storage_path('app/public/' . $imagePath),
                    storage_path('app/public/products/' . $imagePath),
                ];

                $fullPath = null;
                foreach ($possiblePaths as $path) {
                    if (file_exists($path)) {
                        $fullPath = $path;
                        break;
                    }
                }

                if (!$fullPath) {
                    $this->warn("\n  Image not found for product #{$product->id}: {$imagePath}");
                    $failed++;
                    continue;
                }

                if ($dryRun) {
                    $this->line("\n  Would migrate: {$fullPath} -> Spatie media for product #{$product->id}");
                } else {
                    try {
                        $product->addMedia($fullPath)
                            ->preservingOriginal()
                            ->toMediaCollection('product-images');

                        $this->line("\n  Migrated: {$fullPath} for product #{$product->id}");
                        $migrated++;
                    } catch (\Exception $e) {
                        $this->error("\n  Failed to migrate {$fullPath}: {$e->getMessage()}");
                        $failed++;
                    }
                }
            }

            // Clear the old images column after successful migration (not in dry-run)
            if (!$dryRun && $product->hasMedia('product-images')) {
                $product->images = null;
                $product->save();
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        $this->info("Migration complete!");
        $this->table(
            ['Metric', 'Count'],
            [
                ['Products processed', $products->count()],
                ['Images migrated', $migrated],
                ['Products skipped (already have media)', $skipped],
                ['Images failed/not found', $failed],
            ]
        );

        if ($dryRun) {
            $this->warn('This was a dry run. Run without --dry-run to perform the actual migration.');
        }

        return Command::SUCCESS;
    }
}
