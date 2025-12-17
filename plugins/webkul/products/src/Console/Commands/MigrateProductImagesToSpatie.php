<?php

namespace Webkul\Product\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Webkul\Inventory\Models\Product;

class MigrateProductImagesToSpatie extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'products:migrate-images-to-spatie
                            {--dry-run : Show what would be migrated without making changes}
                            {--product-id= : Migrate only a specific product}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Migrate product images from old JSON column to Spatie Media Library';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $dryRun = $this->option('dry-run');
        $productId = $this->option('product-id');

        if ($dryRun) {
            $this->info('DRY RUN MODE - No changes will be made');
        }

        // Get products with images in the old format
        $query = Product::query()
            ->whereNotNull('images')
            ->where('images', '!=', '[]')
            ->where('images', '!=', '');

        if ($productId) {
            $query->where('id', $productId);
        }

        $products = $query->get();

        if ($products->isEmpty()) {
            $this->info('No products found with old-format images to migrate.');
            return self::SUCCESS;
        }

        $this->info("Found {$products->count()} product(s) with images to migrate.");

        // Debug: show products found
        if ($dryRun) {
            foreach ($products as $p) {
                $this->line("  - Product #{$p->id}: {$p->name} - images: " . json_encode($p->images));
            }
        }

        $bar = $this->output->createProgressBar($products->count());
        $bar->start();

        $migrated = 0;
        $skipped = 0;
        $errors = [];

        foreach ($products as $product) {
            try {
                $result = $this->migrateProductImages($product, $dryRun);
                if ($result) {
                    $migrated++;
                } else {
                    $skipped++;
                }
            } catch (\Exception $e) {
                $errors[] = "Product {$product->id} ({$product->name}): {$e->getMessage()}";
            }
            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        $this->info("Migration complete:");
        $this->line("  - Migrated: {$migrated}");
        $this->line("  - Skipped: {$skipped}");
        $this->line("  - Errors: " . count($errors));

        if (!empty($errors)) {
            $this->newLine();
            $this->error('Errors:');
            foreach ($errors as $error) {
                $this->line("  - {$error}");
            }
        }

        return self::SUCCESS;
    }

    /**
     * Migrate images for a single product
     */
    protected function migrateProductImages(Product $product, bool $dryRun): bool
    {
        $images = $product->images;

        // Handle various empty states
        if (!is_array($images) || empty($images)) {
            return false;
        }

        // Filter out empty strings from the array
        $images = array_filter($images, fn($img) => !empty($img) && is_string($img));
        if (empty($images)) {
            return false;
        }

        // Skip if product already has Spatie media
        if ($product->hasMedia('product-images')) {
            $this->line("\n  Product {$product->id} already has Spatie media - skipping");
            return false;
        }

        $migratedCount = 0;

        foreach ($images as $imagePath) {
            // Try multiple possible locations for the image
            $possiblePaths = [
                storage_path("app/public/products/images/{$imagePath}"),  // Staging location
                storage_path("app/private/{$imagePath}"),
                storage_path("app/public/{$imagePath}"),
                storage_path("app/{$imagePath}"),
                public_path("storage/{$imagePath}"),
                public_path("storage/products/images/{$imagePath}"),
            ];

            $foundPath = null;
            foreach ($possiblePaths as $path) {
                if ($dryRun) {
                    $this->line("  Checking path: {$path} - " . (file_exists($path) ? 'EXISTS' : 'not found'));
                }
                if (file_exists($path)) {
                    $foundPath = $path;
                    break;
                }
            }

            if (!$foundPath) {
                $this->warn("\n  Image not found for product #{$product->id} ({$product->name}): {$imagePath}");
                $this->warn("  Searched in: " . implode(', ', $possiblePaths));
                continue;
            }

            if ($dryRun) {
                $this->line("\n  Would migrate: {$foundPath} -> Spatie media for product {$product->id}");
                $migratedCount++;
            } else {
                // Add the image to Spatie Media Library
                $product->addMedia($foundPath)
                    ->preservingOriginal() // Keep the original file for now
                    ->toMediaCollection('product-images');

                $migratedCount++;
                $this->line("\n  Migrated: {$imagePath} for product {$product->id} ({$product->name})");
            }
        }

        // Clear the old images column after successful migration (only if not dry run)
        if (!$dryRun && $migratedCount > 0) {
            $product->update(['images' => null]);
            $this->info("  Cleared old images column for product {$product->id}");
        }

        return $migratedCount > 0;
    }
}
