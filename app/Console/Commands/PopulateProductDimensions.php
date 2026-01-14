<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Webkul\Product\Models\Product;

class PopulateProductDimensions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'products:populate-dimensions 
                            {--type=sheet-goods : Type of products to populate (sheet-goods, lumber, all)}
                            {--dry-run : Show what would be updated without actually updating}
                            {--force : Update even if values already exist}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Populate product dimensions (thickness, sheet size, sqft) by parsing product names';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $type = $this->option('type');
        $dryRun = $this->option('dry-run');
        $force = $this->option('force');

        if ($dryRun) {
            $this->warn('DRY RUN MODE - No changes will be made');
        }

        $this->info("Populating dimensions for: {$type}");
        $this->newLine();

        $query = Product::query();

        // Filter by type
        if ($type === 'sheet-goods') {
            $query->where(function ($q) {
                $q->where('name', 'like', '%Plywood%')
                  ->orWhere('name', 'like', '%MDF%')
                  ->orWhere('name', 'like', '%Medex%')
                  ->orWhere('name', 'like', '%Panel%')
                  ->orWhere('name', 'like', '%Sheet%')
                  ->orWhere('name', 'like', '%Particleboard%')
                  ->orWhere('name', 'like', '%Melamine%');
            });
        } elseif ($type === 'lumber') {
            $query->where(function ($q) {
                $q->where('name', 'like', '%Lumber%')
                  ->orWhere('name', 'like', '%Board%')
                  ->orWhere('name', 'like', '%Poplar%')
                  ->orWhere('name', 'like', '%Oak%')
                  ->orWhere('name', 'like', '%Maple%')
                  ->orWhere('name', 'like', '%Cherry%')
                  ->orWhere('name', 'like', '%Walnut%');
            });
        }

        // Only get products that need updating (unless force)
        if (!$force) {
            $query->where(function ($q) {
                $q->whereNull('thickness_inches')
                  ->orWhereNull('sheet_size')
                  ->orWhereNull('sqft_per_sheet');
            });
        }

        $products = $query->get();

        if ($products->isEmpty()) {
            $this->info('No products found matching criteria that need dimension updates.');
            return 0;
        }

        $this->info("Found {$products->count()} products to process.");
        $this->newLine();

        $updated = 0;
        $skipped = 0;

        foreach ($products as $product) {
            $dimensions = $this->parseDimensions($product->name);
            
            // Check if we found any dimensions
            if (!$dimensions['thickness'] && !$dimensions['sheet_size'] && !$dimensions['sqft_per_sheet']) {
                $this->warn("  ⚠️  Could not parse: {$product->name}");
                $skipped++;
                continue;
            }

            // Determine what needs updating
            $updates = [];
            $changes = [];

            if ($dimensions['thickness'] && ($force || !$product->thickness_inches)) {
                $updates['thickness_inches'] = $dimensions['thickness'];
                $changes[] = "thickness={$dimensions['thickness']}\"";
            }

            if ($dimensions['sheet_size'] && ($force || !$product->sheet_size)) {
                $updates['sheet_size'] = $dimensions['sheet_size'];
                $changes[] = "size={$dimensions['sheet_size']}";
            }

            if ($dimensions['sqft_per_sheet'] && ($force || !$product->sqft_per_sheet)) {
                $updates['sqft_per_sheet'] = $dimensions['sqft_per_sheet'];
                $changes[] = "sqft={$dimensions['sqft_per_sheet']}";
            }

            if (empty($updates)) {
                $this->line("  ⏭️  {$product->name} - already has values");
                $skipped++;
                continue;
            }

            if (!$dryRun) {
                $product->update($updates);
            }

            $this->info("  ✅ {$product->name}");
            $this->line("     " . implode(', ', $changes));
            $updated++;
        }

        $this->newLine();
        $this->table(
            ['Status', 'Count'],
            [
                ['Updated', $updated],
                ['Skipped', $skipped],
            ]
        );

        if ($dryRun) {
            $this->warn('DRY RUN - No changes were made.');
        }

        return 0;
    }

    /**
     * Parse dimensions from product name
     */
    protected function parseDimensions(string $name): array
    {
        $result = [
            'thickness' => null,
            'sheet_size' => null,
            'sqft_per_sheet' => null,
        ];

        $name = strtoupper($name);

        // Parse thickness (fractions like 3/4, 1/2, 1/4)
        if (preg_match('/(\d+)\/(\d+)/', $name, $matches)) {
            $numerator = intval($matches[1]);
            $denominator = intval($matches[2]);
            if ($denominator > 0 && $denominator <= 16) {
                $result['thickness'] = round($numerator / $denominator, 3);
            }
        }

        // Parse sheet size from name
        if (preg_match('/48\s*[Xx]\s*96|4\s*[Xx]\s*8/i', $name)) {
            $result['sheet_size'] = '4x8';
            $result['sqft_per_sheet'] = 32.0;
        } elseif (preg_match('/48\s*[Xx]\s*120|4\s*[Xx]\s*10/i', $name)) {
            $result['sheet_size'] = '4x10';
            $result['sqft_per_sheet'] = 40.0;
        } elseif (preg_match('/60\s*[Xx]\s*120|5\s*[Xx]\s*10/i', $name)) {
            $result['sheet_size'] = '5x10';
            $result['sqft_per_sheet'] = 50.0;
        } else {
            // Default to 4x8 for sheet goods
            $sheetKeywords = ['PLYWOOD', 'MDF', 'MEDEX', 'PANEL', 'SHEET', 'PARTICLEBOARD', 'MELAMINE'];
            foreach ($sheetKeywords as $keyword) {
                if (str_contains($name, $keyword)) {
                    $result['sheet_size'] = '4x8';
                    $result['sqft_per_sheet'] = 32.0;
                    break;
                }
            }
        }

        return $result;
    }
}
