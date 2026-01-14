<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Webkul\Partner\Models\Partner;
use Webkul\Product\Models\Product;
use Webkul\Product\Models\ProductSupplier;

class PopulateVendorSkus extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'inventory:populate-vendor-skus 
                            {--vendor= : Vendor ID to populate SKUs for}
                            {--from-csv= : Path to CSV file with vendor SKU mappings}
                            {--interactive : Run in interactive mode to manually enter SKUs}
                            {--dry-run : Show what would be created without actually creating}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Populate vendor SKU mappings for products';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $vendorId = $this->option('vendor');
        $csvPath = $this->option('from-csv');
        $interactive = $this->option('interactive');
        $dryRun = $this->option('dry-run');

        if ($dryRun) {
            $this->warn('DRY RUN MODE - No changes will be made');
        }

        // Get vendor
        if (!$vendorId) {
            $vendors = Partner::where('sub_type', 'supplier')
                ->select('id', 'name')
                ->orderBy('name')
                ->get();

            if ($vendors->isEmpty()) {
                $this->error('No vendors found in the system.');
                return 1;
            }

            $vendorId = $this->choice(
                'Select vendor to populate SKUs for:',
                $vendors->pluck('name', 'id')->toArray()
            );

            // Find actual vendor ID from name selection
            $vendor = $vendors->firstWhere('name', $vendorId);
            $vendorId = $vendor ? $vendor->id : null;
        }

        $vendor = Partner::find($vendorId);
        if (!$vendor) {
            $this->error("Vendor ID {$vendorId} not found.");
            return 1;
        }

        $this->info("Populating vendor SKUs for: {$vendor->name} (ID: {$vendor->id})");

        // Get currency ID
        $currencyId = DB::table('currencies')->where('code', 'USD')->value('id') ?? 1;

        if ($csvPath) {
            return $this->processFromCsv($vendor, $csvPath, $currencyId, $dryRun);
        }

        if ($interactive) {
            return $this->processInteractive($vendor, $currencyId, $dryRun);
        }

        // Default: list products without vendor mappings
        return $this->listUnmappedProducts($vendor);
    }

    /**
     * Process vendor SKUs from CSV file
     */
    protected function processFromCsv(Partner $vendor, string $csvPath, int $currencyId, bool $dryRun): int
    {
        if (!file_exists($csvPath)) {
            $this->error("CSV file not found: {$csvPath}");
            return 1;
        }

        $handle = fopen($csvPath, 'r');
        $header = fgetcsv($handle);
        $headerLower = array_map('strtolower', array_map('trim', $header));

        // Find columns
        $vendorSkuCol = array_search('vendor_sku', $headerLower);
        $refCol = array_search('our_product_reference', $headerLower) !== false 
            ? array_search('our_product_reference', $headerLower) 
            : array_search('product_reference', $headerLower);
        $priceCol = array_search('price', $headerLower);
        $qtyCol = array_search('min_qty', $headerLower);

        if ($vendorSkuCol === false || $refCol === false) {
            $this->error('CSV must have vendor_sku and our_product_reference (or product_reference) columns.');
            fclose($handle);
            return 1;
        }

        $created = 0;
        $skipped = 0;
        $errors = 0;

        $this->newLine();
        $this->info('Processing CSV file...');
        $this->newLine();

        while (($row = fgetcsv($handle)) !== false) {
            $vendorSku = trim($row[$vendorSkuCol] ?? '');
            $productRef = trim($row[$refCol] ?? '');
            $price = $priceCol !== false ? floatval($row[$priceCol] ?? 0) : 0;
            $minQty = $qtyCol !== false ? intval($row[$qtyCol] ?? 0) : 0;

            if (empty($vendorSku) || empty($productRef)) {
                $skipped++;
                continue;
            }

            // Find product
            $product = Product::where('reference', $productRef)->first();
            if (!$product) {
                $product = Product::where('reference', 'like', '%' . $productRef . '%')->first();
            }

            if (!$product) {
                $this->warn("  ⚠️  Product not found: {$productRef}");
                $errors++;
                continue;
            }

            // Check if mapping exists
            $exists = ProductSupplier::where('product_id', $product->id)
                ->where('partner_id', $vendor->id)
                ->where('product_code', $vendorSku)
                ->exists();

            if ($exists) {
                $this->line("  ⏭️  Skipping {$vendorSku} -> {$product->name} (already exists)");
                $skipped++;
                continue;
            }

            if (!$dryRun) {
                ProductSupplier::create([
                    'product_id' => $product->id,
                    'partner_id' => $vendor->id,
                    'product_code' => $vendorSku,
                    'product_name' => $product->name,
                    'price' => $price,
                    'min_qty' => $minQty,
                    'delay' => 1,
                    'currency_id' => $currencyId,
                    'company_id' => 1,
                    'creator_id' => 1,
                ]);
            }

            $this->info("  ✅ {$vendorSku} -> {$product->name}");
            $created++;
        }

        fclose($handle);

        $this->newLine();
        $this->table(
            ['Status', 'Count'],
            [
                ['Created', $created],
                ['Skipped', $skipped],
                ['Errors', $errors],
            ]
        );

        if ($dryRun) {
            $this->warn('DRY RUN - No changes were made.');
        }

        return 0;
    }

    /**
     * Interactive mode to manually enter SKUs
     */
    protected function processInteractive(Partner $vendor, int $currencyId, bool $dryRun): int
    {
        // Get products without vendor mapping
        $products = Product::where('purchase_ok', true)
            ->whereNotIn('id', function ($query) use ($vendor) {
                $query->select('product_id')
                    ->from('products_product_suppliers')
                    ->where('partner_id', $vendor->id);
            })
            ->select('id', 'name', 'reference')
            ->orderBy('name')
            ->get();

        if ($products->isEmpty()) {
            $this->info('All purchasable products already have vendor SKU mappings.');
            return 0;
        }

        $this->info("Found {$products->count()} products without vendor SKU mappings.");
        $this->newLine();

        $created = 0;

        foreach ($products as $product) {
            $this->line("Product: {$product->name}");
            $this->line("Reference: " . ($product->reference ?: 'N/A'));
            
            $vendorSku = $this->ask("Enter vendor SKU (or press Enter to skip)", '');
            
            if (empty($vendorSku)) {
                $this->line('  Skipped.');
                $this->newLine();
                continue;
            }

            $price = $this->ask('Enter price (optional)', '0');
            
            if (!$dryRun) {
                ProductSupplier::create([
                    'product_id' => $product->id,
                    'partner_id' => $vendor->id,
                    'product_code' => $vendorSku,
                    'product_name' => $product->name,
                    'price' => floatval($price),
                    'min_qty' => 0,
                    'delay' => 1,
                    'currency_id' => $currencyId,
                    'company_id' => 1,
                    'creator_id' => 1,
                ]);
            }

            $this->info("  ✅ Created: {$vendorSku} -> {$product->name}");
            $created++;
            $this->newLine();

            if (!$this->confirm('Continue to next product?', true)) {
                break;
            }
        }

        $this->newLine();
        $this->info("Created {$created} vendor SKU mappings.");

        return 0;
    }

    /**
     * List products without vendor mappings
     */
    protected function listUnmappedProducts(Partner $vendor): int
    {
        $products = Product::where('purchase_ok', true)
            ->whereNotIn('id', function ($query) use ($vendor) {
                $query->select('product_id')
                    ->from('products_product_suppliers')
                    ->where('partner_id', $vendor->id);
            })
            ->select('id', 'name', 'reference')
            ->orderBy('name')
            ->get();

        if ($products->isEmpty()) {
            $this->info('All purchasable products have vendor SKU mappings for this vendor.');
            return 0;
        }

        $this->warn("Found {$products->count()} products without vendor SKU mappings:");
        $this->newLine();

        $rows = $products->map(fn ($p) => [
            $p->id,
            substr($p->name, 0, 50),
            $p->reference ?: '-',
        ])->toArray();

        $this->table(['ID', 'Name', 'Reference'], $rows);

        $this->newLine();
        $this->info('Use --interactive to manually add SKUs, or --from-csv to import from file.');
        $this->line('CSV format: vendor_sku,our_product_reference,price,min_qty');

        return 0;
    }
}
