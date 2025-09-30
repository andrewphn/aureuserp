<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Carbon\Carbon;

return new class extends Migration
{
    private $userId;
    private $companyId;
    private $amazonPartnerId;
    private $uomId;
    private $categoryMap = [];
    private $tagMap = [];

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Initialize IDs
        $this->initializeIds();

        // Create temporary mapping table
        $this->createTempMappingTable();

        // Read and parse Amazon CSV
        $csvPath = base_path('orders_from_20250901_to_20250930_20250930_0935.csv');

        if (!file_exists($csvPath)) {
            echo "ERROR: Amazon CSV file not found at: {$csvPath}\n";
            return;
        }

        $csvData = array_map('str_getcsv', file($csvPath));
        $headers = array_shift($csvData); // Remove header row

        $productsCreated = 0;
        $vendorPricingCreated = 0;
        $tagsLinked = 0;

        // Track unique products by ASIN (avoid duplicates in CSV)
        $processedAsins = [];

        foreach ($csvData as $index => $row) {
            try {
                // Parse row data
                $asin = $row[28] ?? '';
                $title = $row[29] ?? '';
                $brand = $row[36] ?? '';
                $partNumber = $row[40] ?? '';
                $itemPrice = $this->parseDecimal($row[44] ?? '0');
                $amazonCategory = $row[27] ?? '';
                $orderDate = $this->parseDate($row[0] ?? '');

                // Skip if no ASIN or already processed
                if (empty($asin) || in_array($asin, $processedAsins)) {
                    continue;
                }

                $processedAsins[] = $asin;

                // Determine category
                $categoryId = $this->determineCategory($title, $amazonCategory, $brand);

                // Create product
                $productId = DB::table('products_products')->insertGetId([
                    'type' => 'goods',
                    'name' => mb_substr($title, 0, 255), // Truncate to 255 chars
                    'reference' => "AMZ-{$asin}",
                    'barcode' => $asin,
                    'price' => $itemPrice,
                    'cost' => $itemPrice,
                    'uom_id' => $this->uomId,
                    'uom_po_id' => $this->uomId,
                    'category_id' => $categoryId,
                    'enable_purchase' => true,
                    'enable_sales' => false, // Amazon purchases are for internal use
                    'company_id' => $this->companyId,
                    'creator_id' => $this->userId,
                    'created_at' => $orderDate ?? now(),
                    'updated_at' => now(),
                ]);

                $productsCreated++;

                // Store in temp mapping
                DB::table('temp_amazon_mapping')->insert([
                    'csv_index' => $index,
                    'product_id' => $productId,
                    'asin' => $asin,
                    'title' => $title,
                    'brand' => $brand,
                    'part_number' => $partNumber,
                    'item_price' => $itemPrice,
                    'amazon_category' => $amazonCategory,
                    'order_date' => $orderDate,
                    'created_at' => now(),
                ]);

                // Create vendor pricing
                DB::table('products_product_suppliers')->insert([
                    'partner_id' => $this->amazonPartnerId,
                    'product_id' => $productId,
                    'product_code' => $asin,
                    'price' => $itemPrice,
                    'currency_id' => 1, // USD
                    'starts_at' => $orderDate ? $orderDate->format('Y-m-d') : now()->format('Y-m-d'),
                    'ends_at' => $orderDate ? $orderDate->addYear()->format('Y-m-d') : now()->addYear()->format('Y-m-d'),
                    'delay' => 2, // Amazon 2-day delivery
                    'vendor_url' => "https://www.amazon.com/dp/{$asin}",
                    'company_id' => $this->companyId,
                    'creator_id' => $this->userId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                $vendorPricingCreated++;

                // Assign tags
                $tags = $this->determineTags($title, $brand, $categoryId);
                foreach ($tags as $tagName) {
                    if (isset($this->tagMap[$tagName])) {
                        DB::table('products_product_tag')->insert([
                            'product_id' => $productId,
                            'tag_id' => $this->tagMap[$tagName],
                        ]);
                        $tagsLinked++;
                    }
                }

                echo "Imported: {$title} (ASIN: {$asin})\n";

            } catch (\Exception $e) {
                echo "ERROR on CSV row {$index}: " . $e->getMessage() . "\n";
                continue;
            }
        }

        echo "\n========== AMAZON IMPORT SUMMARY ==========\n";
        echo "Products created: {$productsCreated}\n";
        echo "Vendor pricing records created: {$vendorPricingCreated}\n";
        echo "Tag links created: {$tagsLinked}\n";
        echo "===========================================\n\n";
    }

    /**
     * Initialize required IDs
     */
    private function initializeIds(): void
    {
        $this->userId = DB::table('users')->where('email', 'info@tcswoodwork.com')->value('id') ?? 1;
        $this->companyId = DB::table('companies')->where('name', "The Carpenter's Son LLC")->value('id') ?? 1;
        $this->amazonPartnerId = DB::table('partners_partners')->where('name', 'Amazon Business')->value('id');
        $this->uomId = DB::table('unit_of_measures')->where('name', 'Each')->value('id') ?? 1;

        // Build category map
        $categories = DB::table('products_categories')->get(['id', 'full_name']);
        foreach ($categories as $cat) {
            $this->categoryMap[$cat->full_name] = $cat->id;
        }

        // Build tag map
        $tags = DB::table('products_tags')->get(['id', 'name']);
        foreach ($tags as $tag) {
            $this->tagMap[$tag->name] = $tag->id;
        }
    }

    /**
     * Create temporary mapping table for tracking imports
     */
    private function createTempMappingTable(): void
    {
        if (!Schema::hasTable('temp_amazon_mapping')) {
            Schema::create('temp_amazon_mapping', function (Blueprint $table) {
                $table->id();
                $table->integer('csv_index');
                $table->bigInteger('product_id');
                $table->string('asin');
                $table->text('title');
                $table->string('brand')->nullable();
                $table->string('part_number')->nullable();
                $table->decimal('item_price', 15, 4)->default(0);
                $table->string('amazon_category')->nullable();
                $table->date('order_date')->nullable();
                $table->timestamps();
            });
        }
    }

    /**
     * Determine product category based on title and hints
     */
    private function determineCategory(string $title, string $amazonCategory, string $brand): int
    {
        $titleLower = strtolower($title);

        // High priority: Product name keywords
        if (str_contains($titleLower, 'ink') || str_contains($titleLower, 'cartridge')) {
            return $this->getCategoryId('Office Supplies / Printer Cartridges');
        }

        if (str_contains($titleLower, 'sanding disc')) {
            return $this->getCategoryId('Sanding / Discs');
        }

        if (str_contains($titleLower, 'sanding roll') || str_contains($titleLower, 'sandpaper roll')) {
            return $this->getCategoryId('Sanding / Rolls');
        }

        if (str_contains($titleLower, 'label') && (str_contains($titleLower, 'tape') || str_contains($titleLower, 'maker'))) {
            return $this->getCategoryId('Office Supplies / Writing Supplies');
        }

        if (str_contains($titleLower, 'tramming') || str_contains($titleLower, 'calibration')) {
            return $this->getCategoryId('Tools / Measurement Tools');
        }

        if (str_contains($titleLower, 'table stiffener')) {
            return $this->getCategoryId('Hardware / Storage Systems');
        }

        if (str_contains($titleLower, 'clamp') && (str_contains($titleLower, 'router') || str_contains($titleLower, 'base'))) {
            return $this->getCategoryId('Tools / CNC Parts');
        }

        if (str_contains($titleLower, 'iso30') || str_contains($titleLower, 'tool holder')) {
            return $this->getCategoryId('CNC / CNC Parts');
        }

        if (str_contains($titleLower, 'bungee') || str_contains($titleLower, 'd-ring') || str_contains($titleLower, 'd ring')) {
            return $this->getCategoryId('Shop Supplies / Safety Equipment');
        }

        if (str_contains($titleLower, 'dust collector') || str_contains($titleLower, 'dust collection')) {
            return $this->getCategoryId('Shop Supplies / Dust Collection');
        }

        // Medium priority: Amazon category hints
        if (str_contains(strtolower($amazonCategory), 'office')) {
            return $this->getCategoryId('Office Supplies / Paper Products');
        }

        // Default fallback
        return $this->getCategoryId('Shop Supplies / Cleaning Supplies');
    }

    /**
     * Determine tags for a product
     */
    private function determineTags(string $title, string $brand, int $categoryId): array
    {
        $tags = [];
        $titleLower = strtolower($title);

        // Always add vendor tag
        $tags[] = 'Amazon';

        // Product type tags
        if (str_contains($titleLower, 'sanding disc')) $tags[] = 'Sanding Disc';
        if (str_contains($titleLower, 'sanding roll')) $tags[] = 'Sanding Roll';
        if (str_contains($titleLower, 'router bit')) $tags[] = 'Router Bit';

        // Consumable items
        if (str_contains($titleLower, 'sanding') || str_contains($titleLower, 'ink')) {
            $tags[] = 'Consumable';
        }

        // Application tags
        if (str_contains($titleLower, 'cnc') || str_contains($titleLower, 'iso30')) {
            $tags[] = 'CNC';
        }

        if (str_contains($titleLower, 'wood') || str_contains($titleLower, 'sanding')) {
            $tags[] = 'Woodworking';
        }

        if (str_contains($titleLower, 'office') || str_contains($titleLower, 'ink') || str_contains($titleLower, 'label')) {
            $tags[] = 'Office Supplies';
        }

        if (str_contains($titleLower, 'shop') || str_contains($titleLower, 'dust') || str_contains($titleLower, 'bungee')) {
            $tags[] = 'Shop Supplies';
        }

        // Always reorderable
        $tags[] = 'Reorderable';

        return array_unique($tags);
    }

    /**
     * Get category ID by full name path
     */
    private function getCategoryId(string $fullName): int
    {
        if (isset($this->categoryMap[$fullName])) {
            return $this->categoryMap[$fullName];
        }

        // Fallback to a default category if not found
        return $this->categoryMap['Shop Supplies / Cleaning Supplies'] ?? 1;
    }

    /**
     * Parse decimal value from CSV (removes quotes and commas)
     */
    private function parseDecimal(string $value): float
    {
        $cleaned = str_replace(['"', ','], '', $value);
        return floatval($cleaned);
    }

    /**
     * Parse date from MM/DD/YYYY format
     */
    private function parseDate(string $date): ?Carbon
    {
        try {
            return Carbon::createFromFormat('m/d/Y', trim($date));
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Get all product IDs from temp mapping
        $productIds = DB::table('temp_amazon_mapping')->pluck('product_id');

        // Remove tag links
        DB::table('products_product_tag')->whereIn('product_id', $productIds)->delete();

        // Remove vendor pricing
        DB::table('products_product_suppliers')->whereIn('product_id', $productIds)->delete();

        // Remove products
        DB::table('products_products')->whereIn('id', $productIds)->delete();

        // Drop temp table
        Schema::dropIfExists('temp_amazon_mapping');

        echo "Rolled back Amazon product import\n";
    }
};
