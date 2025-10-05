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
    private $attributeMap = [];
    private $variantMapping;

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Load variant mapping
        $mappingPath = base_path('variant-mapping.json');
        if (!file_exists($mappingPath)) {
            echo "ERROR: variant-mapping.json not found. Run analyze-variant-candidates.php first.\n";
            return;
        }

        $this->variantMapping = json_decode(file_get_contents($mappingPath), true);

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

        echo "\n========== AMAZON IMPORT WITH VARIANTS ==========\n\n";

        // Build ASIN lookup from CSV
        $asinLookup = $this->buildAsinLookup($csvData);

        // Create variant groups
        $this->createVariantGroups($asinLookup);

        // Create standalone products (non-variants)
        $this->createStandaloneProducts($asinLookup);

        echo "\n========== IMPORT COMPLETE ==========\n\n";
    }

    /**
     * Build ASIN lookup from CSV data
     */
    private function buildAsinLookup(array $csvData): array
    {
        $lookup = [];
        $processedAsins = [];

        foreach ($csvData as $row) {
            $asin = $row[28] ?? '';
            $title = $row[29] ?? '';
            $brand = $row[36] ?? '';
            $itemPrice = $this->parseDecimal($row[44] ?? '0');
            $amazonCategory = $row[27] ?? '';
            $orderDate = $this->parseDate($row[0] ?? '');

            if (empty($asin) || in_array($asin, $processedAsins)) {
                continue;
            }

            $processedAsins[] = $asin;

            $lookup[$asin] = [
                'title' => $title,
                'brand' => $brand,
                'price' => $itemPrice,
                'amazon_category' => $amazonCategory,
                'order_date' => $orderDate,
            ];
        }

        return $lookup;
    }

    /**
     * Create variant groups (parent + children)
     */
    private function createVariantGroups(array $asinLookup): void
    {
        foreach ($this->variantMapping['variant_groups'] as $group) {
            echo "Creating variant group: {$group['parent_name']}\n";

            $attributeName = $group['attribute'];
            $attributeId = $this->attributeMap[$attributeName] ?? null;

            if (!$attributeId) {
                echo "  WARNING: Attribute '{$attributeName}' not found, skipping group\n\n";
                continue;
            }

            // Get category from first product
            $firstAsin = $group['products'][0]['asin'];
            $firstProduct = $asinLookup[$firstAsin] ?? null;

            if (!$firstProduct) {
                echo "  WARNING: Product data not found for ASIN {$firstAsin}, skipping group\n\n";
                continue;
            }

            $categoryId = $this->determineCategory($firstProduct['title'], $firstProduct['amazon_category'], $firstProduct['brand']);

            // Create parent product
            $parentId = DB::table('products_products')->insertGetId([
                'type' => 'goods',
                'name' => $group['parent_name'],
                'reference' => $this->generateParentReference($group['parent_name']),
                'price' => $firstProduct['price'], // Use first variant's price as base
                'cost' => $firstProduct['price'],
                'uom_id' => $this->uomId,
                'uom_po_id' => $this->uomId,
                'category_id' => $categoryId,
                'enable_purchase' => true,
                'enable_sales' => false,
                'company_id' => $this->companyId,
                'creator_id' => $this->userId,
                'created_at' => $firstProduct['order_date'] ?? now(),
                'updated_at' => now(),
            ]);

            echo "  Created parent product (ID: {$parentId})\n";

            // Link parent to attribute
            DB::table('products_product_attributes')->insert([
                'product_id' => $parentId,
                'attribute_id' => $attributeId,
                'creator_id' => $this->userId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $productAttributeId = DB::getPdo()->lastInsertId();

            // Create each variant
            foreach ($group['products'] as $variantData) {
                $asin = $variantData['asin'];
                $productData = $asinLookup[$asin] ?? null;

                if (!$productData) {
                    echo "  WARNING: Product data not found for ASIN {$asin}, skipping variant\n";
                    continue;
                }

                // Create variant product
                $variantId = DB::table('products_products')->insertGetId([
                    'parent_id' => $parentId,
                    'type' => 'goods',
                    'name' => $productData['title'],
                    'reference' => "AMZ-{$asin}",
                    'barcode' => $asin,
                    'price' => $productData['price'],
                    'cost' => $productData['price'],
                    'uom_id' => $this->uomId,
                    'uom_po_id' => $this->uomId,
                    'category_id' => $categoryId,
                    'enable_purchase' => true,
                    'enable_sales' => false,
                    'company_id' => $this->companyId,
                    'creator_id' => $this->userId,
                    'created_at' => $productData['order_date'] ?? now(),
                    'updated_at' => now(),
                ]);

                echo "    Created variant (ID: {$variantId}) - {$attributeName}: {$variantData['attribute_value']}\n";

                // Store in temp mapping
                DB::table('temp_amazon_mapping')->insert([
                    'csv_index' => 0,
                    'product_id' => $variantId,
                    'asin' => $asin,
                    'title' => $productData['title'],
                    'brand' => $productData['brand'],
                    'item_price' => $productData['price'],
                    'amazon_category' => $productData['amazon_category'],
                    'order_date' => $productData['order_date'],
                    'created_at' => now(),
                ]);

                // Link to attribute value
                $optionId = $this->getAttributeOptionId($attributeId, $variantData['attribute_value']);
                if ($optionId) {
                    DB::table('products_product_attribute_values')->insert([
                        'product_id' => $variantId,
                        'attribute_id' => $attributeId,
                        'product_attribute_id' => $productAttributeId,
                        'attribute_option_id' => $optionId,
                    ]);
                }

                // Create vendor pricing
                DB::table('products_product_suppliers')->insert([
                    'partner_id' => $this->amazonPartnerId,
                    'product_id' => $variantId,
                    'product_code' => $asin,
                    'price' => $productData['price'],
                    'currency_id' => 1, // USD
                    'starts_at' => $productData['order_date'] ? $productData['order_date']->format('Y-m-d') : now()->format('Y-m-d'),
                    'ends_at' => $productData['order_date'] ? $productData['order_date']->addYear()->format('Y-m-d') : now()->addYear()->format('Y-m-d'),
                    'delay' => 2,
                    'vendor_url' => "https://www.amazon.com/dp/{$asin}",
                    'company_id' => $this->companyId,
                    'creator_id' => $this->userId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                // Assign tags
                $this->assignTags($variantId, $productData['title'], $productData['brand'], $categoryId);
            }

            // Assign tags to parent
            $this->assignTags($parentId, $firstProduct['title'], $firstProduct['brand'], $categoryId);

            echo "\n";
        }
    }

    /**
     * Create standalone products (not part of variant groups)
     */
    private function createStandaloneProducts(array $asinLookup): void
    {
        // Get ASINs that are in variant groups
        $variantAsins = [];
        foreach ($this->variantMapping['variant_groups'] as $group) {
            foreach ($group['products'] as $product) {
                $variantAsins[] = $product['asin'];
            }
        }

        foreach ($asinLookup as $asin => $productData) {
            if (in_array($asin, $variantAsins)) {
                continue; // Skip variants, already created
            }

            $categoryId = $this->determineCategory($productData['title'], $productData['amazon_category'], $productData['brand']);

            // Create standalone product
            $productId = DB::table('products_products')->insertGetId([
                'type' => 'goods',
                'name' => mb_substr($productData['title'], 0, 255),
                'reference' => "AMZ-{$asin}",
                'barcode' => $asin,
                'price' => $productData['price'],
                'cost' => $productData['price'],
                'uom_id' => $this->uomId,
                'uom_po_id' => $this->uomId,
                'category_id' => $categoryId,
                'enable_purchase' => true,
                'enable_sales' => false,
                'company_id' => $this->companyId,
                'creator_id' => $this->userId,
                'created_at' => $productData['order_date'] ?? now(),
                'updated_at' => now(),
            ]);

            echo "Created standalone product: {$productData['title']}\n";

            // Store in temp mapping
            DB::table('temp_amazon_mapping')->insert([
                'csv_index' => 0,
                'product_id' => $productId,
                'asin' => $asin,
                'title' => $productData['title'],
                'brand' => $productData['brand'],
                'item_price' => $productData['price'],
                'amazon_category' => $productData['amazon_category'],
                'order_date' => $productData['order_date'],
                'created_at' => now(),
            ]);

            // Create vendor pricing
            DB::table('products_product_suppliers')->insert([
                'partner_id' => $this->amazonPartnerId,
                'product_id' => $productId,
                'product_code' => $asin,
                'price' => $productData['price'],
                'currency_id' => 1,
                'starts_at' => $productData['order_date'] ? $productData['order_date']->format('Y-m-d') : now()->format('Y-m-d'),
                'ends_at' => $productData['order_date'] ? $productData['order_date']->addYear()->format('Y-m-d') : now()->addYear()->format('Y-m-d'),
                'delay' => 2,
                'vendor_url' => "https://www.amazon.com/dp/{$asin}",
                'company_id' => $this->companyId,
                'creator_id' => $this->userId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Assign tags
            $this->assignTags($productId, $productData['title'], $productData['brand'], $categoryId);
        }
    }

    /**
     * Initialize required IDs and maps
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

        // Build attribute map
        $attributes = DB::table('products_attributes')->get(['id', 'name']);
        foreach ($attributes as $attr) {
            $this->attributeMap[$attr->name] = $attr->id;
        }
    }

    /**
     * Create temporary mapping table
     */
    private function createTempMappingTable(): void
    {
        Schema::dropIfExists('temp_amazon_mapping');

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

    /**
     * Get attribute option ID by name
     */
    private function getAttributeOptionId(int $attributeId, ?string $value): ?int
    {
        if (empty($value)) {
            return null;
        }

        return DB::table('products_attribute_options')
            ->where('attribute_id', $attributeId)
            ->where('name', $value)
            ->value('id');
    }

    /**
     * Generate parent product reference code
     */
    private function generateParentReference(string $name): string
    {
        $cleaned = preg_replace('/[^A-Za-z0-9]/', '-', $name);
        $cleaned = preg_replace('/-+/', '-', $cleaned);
        $cleaned = trim($cleaned, '-');
        return strtoupper(substr($cleaned, 0, 50));
    }

    /**
     * Assign tags to product
     */
    private function assignTags(int $productId, string $title, string $brand, int $categoryId): void
    {
        $tags = $this->determineTags($title, $brand, $categoryId);
        foreach ($tags as $tagName) {
            if (isset($this->tagMap[$tagName])) {
                DB::table('products_product_tag')->insert([
                    'product_id' => $productId,
                    'tag_id' => $this->tagMap[$tagName],
                ]);
            }
        }
    }

    // Copy helper methods from original import migration
    private function determineCategory(string $title, string $amazonCategory, string $brand): int
    {
        $titleLower = strtolower($title);

        if (str_contains($titleLower, 'ink') || str_contains($titleLower, 'cartridge')) {
            return $this->getCategoryId('Office Supplies / Printer Cartridges');
        }

        if (str_contains($titleLower, 'sanding disc')) {
            return $this->getCategoryId('Sanding / Discs');
        }

        if (str_contains($titleLower, 'sanding roll')) {
            return $this->getCategoryId('Sanding / Rolls');
        }

        if (str_contains($titleLower, 'label') && str_contains($titleLower, 'tape')) {
            return $this->getCategoryId('Office Supplies / Writing Supplies');
        }

        if (str_contains($titleLower, 'bungee')) {
            return $this->getCategoryId('Shop Supplies / Safety Equipment');
        }

        return $this->getCategoryId('Shop Supplies / Cleaning Supplies');
    }

    private function determineTags(string $title, string $brand, int $categoryId): array
    {
        $tags = ['Amazon', 'Reorderable'];
        $titleLower = strtolower($title);

        if (str_contains($titleLower, 'sanding disc')) $tags[] = 'Sanding Disc';
        if (str_contains($titleLower, 'sanding')) $tags[] = 'Consumable';
        if (str_contains($titleLower, 'wood') || str_contains($titleLower, 'sanding')) $tags[] = 'Woodworking';
        if (str_contains($titleLower, 'office') || str_contains($titleLower, 'ink')) $tags[] = 'Office Supplies';
        if (str_contains($titleLower, 'shop') || str_contains($titleLower, 'bungee')) $tags[] = 'Shop Supplies';
        if (str_contains($titleLower, 'cnc')) $tags[] = 'CNC';

        return array_unique($tags);
    }

    private function getCategoryId(string $fullName): int
    {
        return $this->categoryMap[$fullName] ?? $this->categoryMap['Shop Supplies / Cleaning Supplies'] ?? 1;
    }

    private function parseDecimal(string $value): float
    {
        return floatval(str_replace(['"', ','], '', $value));
    }

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
        $productIds = DB::table('temp_amazon_mapping')->pluck('product_id');

        // Remove tag links
        DB::table('products_product_tag')->whereIn('product_id', $productIds)->delete();

        // Remove vendor pricing
        DB::table('products_product_suppliers')->whereIn('product_id', $productIds)->delete();

        // Remove attribute value links
        DB::table('products_product_attribute_values')->whereIn('product_id', $productIds)->delete();

        // Remove products
        DB::table('products_products')->whereIn('id', $productIds)->delete();

        // Drop temp table
        Schema::dropIfExists('temp_amazon_mapping');

        echo "Rolled back Amazon product import with variants\n";
    }
};
