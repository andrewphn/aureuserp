<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private $userId;
    private $companyId;
    private $uomId;
    private $warehouseId;
    private $locationId;
    private $attributeIds = [];
    private $categoryMap = [];
    private $tagMap = [];
    private $variantMapping = [];

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $this->initializeIds();

        echo "\n";
        echo "╔═══════════════════════════════════════════════════════════════════════════╗\n";
        echo "║       IMPORTING INVENTORY PRODUCTS WITH VARIANTS & QUANTITIES             ║\n";
        echo "╚═══════════════════════════════════════════════════════════════════════════╝\n";
        echo "\n";

        // Load variant mapping
        $mappingPath = base_path('inventory-variant-mapping.json');
        if (file_exists($mappingPath)) {
            $this->variantMapping = json_decode(file_get_contents($mappingPath), true);
            echo "✓ Loaded variant mapping with " . count($this->variantMapping['variant_groups']) . " variant groups\n\n";
        }

        // Parse CSV
        $csvPath = base_path('inventory_import_ready.csv');
        if (!file_exists($csvPath)) {
            echo "ERROR: CSV file not found at: {$csvPath}\n";
            return;
        }

        $csvData = array_map(fn($line) => str_getcsv($line, ',', '"', '\\'), file($csvPath));
        $headers = array_shift($csvData);

        // Build column map
        $columnMap = [];
        foreach ($headers as $index => $header) {
            $columnMap[$header] = $index;
        }

        // Build product lookup from CSV
        $productLookup = $this->buildProductLookup($csvData, $columnMap);

        echo "✓ Loaded " . count($productLookup) . " products from CSV\n\n";

        // Create variant groups
        $this->createVariantGroups($productLookup);

        // Create standalone products
        $this->createStandaloneProducts($productLookup);

        echo "\n";
        echo "╔═══════════════════════════════════════════════════════════════════════════╗\n";
        echo "║                INVENTORY IMPORT COMPLETE                                  ║\n";
        echo "╚═══════════════════════════════════════════════════════════════════════════╝\n";
        echo "\n";
    }

    /**
     * Initialize required IDs
     */
    private function initializeIds(): void
    {
        $this->userId = DB::table('users')->where('email', 'info@tcswoodwork.com')->value('id') ?? 1;
        $this->companyId = DB::table('companies')->where('name', "The Carpenter's Son LLC")->value('id') ?? 1;
        $this->uomId = DB::table('unit_of_measures')->where('name', 'Each')->value('id') ?? 1;

        // Get warehouse and location
        $this->warehouseId = DB::table('inventories_warehouses')->where('company_id', $this->companyId)->value('id');
        if (!$this->warehouseId) {
            $this->warehouseId = DB::table('inventories_warehouses')->insertGetId([
                'name' => 'Main Warehouse',
                'code' => 'WH-MAIN',
                'company_id' => $this->companyId,
                'creator_id' => $this->userId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $this->locationId = DB::table('inventories_locations')
            ->where('warehouse_id', $this->warehouseId)
            ->where('name', 'Stock')
            ->value('id');

        if (!$this->locationId) {
            $this->locationId = DB::table('inventories_locations')->insertGetId([
                'name' => 'Stock',
                'warehouse_id' => $this->warehouseId,
                'usage' => 'internal',
                'creator_id' => $this->userId,
                'company_id' => $this->companyId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Get attribute IDs
        $attributes = DB::table('products_attributes')->whereIn('name', ['Grit', 'Size'])->get(['id', 'name']);
        foreach ($attributes as $attr) {
            $this->attributeIds[$attr->name] = $attr->id;
        }

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
     * Build product lookup from CSV
     */
    private function buildProductLookup(array $csvData, array $columnMap): array
    {
        $lookup = [];

        foreach ($csvData as $row) {
            $sku = $row[$columnMap['sku']] ?? '';
            if (empty($sku)) continue;

            $lookup[$sku] = [
                'name' => $row[$columnMap['name']] ?? '',
                'sku' => $sku,
                'description' => $row[$columnMap['description']] ?? '',
                'category' => $row[$columnMap['category']] ?? '',
                'unit_of_measure' => $row[$columnMap['unit_of_measure']] ?? 'each',
                'cost_per_unit' => floatval($row[$columnMap['cost_per_unit']] ?? 0),
                'selling_price' => floatval($row[$columnMap['selling_price']] ?? 0),
                'quantity_on_hand' => floatval($row[$columnMap['quantity_on_hand']] ?? 0),
                'location_code' => $row[$columnMap['location_code']] ?? '',
                'location_description' => $row[$columnMap['location_description']] ?? '',
                'reorder_level' => floatval($row[$columnMap['reorder_level']] ?? 0),
            ];
        }

        return $lookup;
    }

    /**
     * Create variant groups
     */
    private function createVariantGroups(array &$productLookup): void
    {
        if (empty($this->variantMapping['variant_groups'])) {
            return;
        }

        foreach ($this->variantMapping['variant_groups'] as $group) {
            echo "Creating variant group: {$group['parent_name']}\n";

            $attributeName = $group['attribute'];
            $attributeId = $this->attributeIds[$attributeName] ?? null;

            if (!$attributeId) {
                echo "  ⚠️  Attribute '{$attributeName}' not found, skipping\n\n";
                continue;
            }

            // Get first product for parent data
            $firstSku = $group['products'][0]['item_id'];
            $firstProduct = $productLookup[$firstSku] ?? null;

            if (!$firstProduct) {
                echo "  ⚠️  Product {$firstSku} not found in CSV, skipping\n\n";
                continue;
            }

            // Create parent product
            $categoryId = $this->getCategoryId($firstProduct['category'], $firstProduct['sku'], $firstProduct['name']);

            $parentId = DB::table('products_products')->insertGetId([
                'type' => 'goods',
                'name' => $group['parent_name'],
                'reference' => $this->generateParentReference($group['parent_name']),
                'price' => $firstProduct['selling_price'],
                'cost' => $firstProduct['cost_per_unit'],
                'uom_id' => $this->uomId,
                'uom_po_id' => $this->uomId,
                'category_id' => $categoryId,
                'enable_purchase' => true,
                'enable_sales' => true,
                'company_id' => $this->companyId,
                'creator_id' => $this->userId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            echo "  ✓ Created parent product (ID: {$parentId})\n";

            // Link parent to attribute
            DB::table('products_product_attributes')->insert([
                'product_id' => $parentId,
                'attribute_id' => $attributeId,
                'creator_id' => $this->userId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $productAttributeId = DB::getPdo()->lastInsertId();

            // Create variants
            foreach ($group['products'] as $variantData) {
                $sku = $variantData['item_id'];
                $product = $productLookup[$sku] ?? null;

                if (!$product) {
                    echo "  ⚠️  Variant {$sku} not found in CSV, skipping\n";
                    continue;
                }

                // Create variant product
                $variantId = DB::table('products_products')->insertGetId([
                    'parent_id' => $parentId,
                    'type' => 'goods',
                    'name' => $product['name'],
                    'reference' => $product['sku'],
                    'barcode' => $product['sku'],
                    'price' => $product['selling_price'],
                    'cost' => $product['cost_per_unit'],
                    'uom_id' => $this->uomId,
                    'uom_po_id' => $this->uomId,
                    'category_id' => $categoryId,
                    'enable_purchase' => true,
                    'enable_sales' => true,
                    'company_id' => $this->companyId,
                    'creator_id' => $this->userId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                echo "  ✓ Created variant (ID: {$variantId}) - {$attributeName}: {$variantData['attribute_value']}\n";

                // Link to attribute value
                $attrValue = $variantData['attribute_value'];
                $optionId = $this->getAttributeOptionId($attributeId, $attrValue);

                if ($optionId) {
                    DB::table('products_product_attribute_values')->insert([
                        'product_id' => $variantId,
                        'attribute_id' => $attributeId,
                        'product_attribute_id' => $productAttributeId,
                        'attribute_option_id' => $optionId,
                    ]);
                }

                // Create inventory quantity
                if ($product['quantity_on_hand'] != 0) {
                    $this->createInventoryQuantity($variantId, $product['quantity_on_hand']);
                    echo "  ✓ Set inventory quantity: {$product['quantity_on_hand']}\n";
                }

                // Mark as processed
                unset($productLookup[$sku]);
            }

            echo "\n";
        }
    }

    /**
     * Create standalone products (non-variants)
     */
    private function createStandaloneProducts(array $productLookup): void
    {
        echo "Creating standalone products...\n\n";

        $count = 0;
        foreach ($productLookup as $sku => $product) {
            $categoryId = $this->getCategoryId($product['category'], $product['sku'], $product['name']);

            $productId = DB::table('products_products')->insertGetId([
                'type' => 'goods',
                'name' => $product['name'],
                'reference' => $product['sku'],
                'barcode' => $product['sku'],
                'price' => $product['selling_price'],
                'cost' => $product['cost_per_unit'],
                'uom_id' => $this->uomId,
                'uom_po_id' => $this->uomId,
                'category_id' => $categoryId,
                'enable_purchase' => true,
                'enable_sales' => true,
                'company_id' => $this->companyId,
                'creator_id' => $this->userId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Create inventory quantity
            if ($product['quantity_on_hand'] != 0) {
                $this->createInventoryQuantity($productId, $product['quantity_on_hand']);
            }

            $count++;
            if ($count % 10 == 0) {
                echo "  Created {$count} standalone products...\n";
            }
        }

        echo "  ✓ Created {$count} standalone products\n\n";
    }

    /**
     * Create inventory quantity record
     */
    private function createInventoryQuantity(int $productId, float $quantity): void
    {
        DB::table('inventories_product_quantities')->insert([
            'quantity' => $quantity,
            'reserved_quantity' => 0,
            'counted_quantity' => 0,
            'difference_quantity' => 0,
            'inventory_diff_quantity' => 0,
            'inventory_quantity_set' => true,
            'product_id' => $productId,
            'location_id' => $this->locationId,
            'company_id' => $this->companyId,
            'creator_id' => $this->userId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Get category ID from product data
     */
    private function getCategoryId(string $categoryName, string $sku = '', string $productName = ''): int
    {
        // Map SKU prefixes to proper subcategories
        $skuMap = [
            'TCS-ADH-EPOXY' => 'Adhesives / Epoxy',
            'TCS-ADH-GLUE' => 'Adhesives / Glue',
            'TCS-ADH-PELLET' => 'Adhesives / Edge Banding Adhesive',
            'TCS-BLADE-SAW' => 'Blades / Saw Blades',
            'TCS-BLADE-PLANER' => 'Blades / Planer Blades',
            'TCS-BLADE-JOINTER' => 'Blades / Jointer Blades',
            'TCS-CNC-BIT' => 'CNC / Router Bits',
            'TCS-EDGE-UNF' => 'Edge Banding / Unfinished',
            'TCS-EDGE-WOOD' => 'Edge Banding / Wood Veneer',
            'TCS-FAST-KREG' => 'Fasteners / Screws',
            'TCS-FAST-NAIL' => 'Fasteners / Nails',
            'TCS-FAST-PIN' => 'Fasteners / Nails',
            'TCS-FAST-SCREW' => 'Fasteners / Screws',
            'TCS-HW-HINGE' => 'Hardware / Hinges',
            'TCS-HW-SLIDE' => 'Hardware / Drawer Slides',
            'TCS-HW-CLIP' => 'Hardware / Clips',
            'TCS-HW-PADDLE' => 'Hardware / Clips',
            'TCS-HW-PLATE' => 'Hardware / Clips',
            'TCS-HW-LEMANS' => 'Hardware / Storage Systems',
            'TCS-HW-TRASH' => 'Hardware / Storage Systems',
            'TCS-HW-RECYCLE' => 'Hardware / Storage Systems',
            'TCS-MAINT-GREASE' => 'Maintenance / Grease',
            'TCS-MAINT-OIL' => 'Maintenance / Machine Oil',
            'TCS-MAINT-LUB' => 'Maintenance / Lubricants',
            'TCS-SAND-DISC' => 'Sanding / Discs',
            'TCS-SAND-GRIT' => 'Sanding / Discs',
            'TCS-SAND-ROLL' => 'Sanding / Rolls',
            'TCS-SAND-SHEET' => 'Sanding / Sheets',
            'TCS-SHOP-DUST' => 'Shop Supplies / Dust Collection',
            'TCS-TOOL-BIT' => 'Tools / Drill Bits',
            'TCS-TOOL-CAL' => 'Tools / Measurement Tools',
            'TCS-TOOL-COLL' => 'Tools / CNC Parts',
            'TCS-TOOL-ROUTE' => 'Tools / Router Bits',
        ];

        // Try to match SKU prefix
        foreach ($skuMap as $prefix => $fullCategoryPath) {
            if (str_starts_with($sku, $prefix)) {
                if (isset($this->categoryMap[$fullCategoryPath])) {
                    return $this->categoryMap[$fullCategoryPath];
                }
            }
        }

        // Fallback: try exact match with category name from CSV
        if (isset($this->categoryMap[$categoryName])) {
            return $this->categoryMap[$categoryName];
        }

        // Try to find by last segment
        foreach ($this->categoryMap as $fullName => $id) {
            if (str_ends_with($fullName, '/ ' . $categoryName)) {
                return $id;
            }
        }

        // Default fallback
        return $this->categoryMap['Shop Supplies / Cleaning Supplies'] ?? 1;
    }

    /**
     * Get attribute option ID by attribute ID and value
     */
    private function getAttributeOptionId(int $attributeId, string $value): ?int
    {
        // Clean up the value
        $cleanValue = trim(str_replace('"', '', $value));

        return DB::table('products_attribute_options')
            ->where('attribute_id', $attributeId)
            ->where('name', $cleanValue)
            ->value('id');
    }

    /**
     * Generate parent product reference
     */
    private function generateParentReference(string $name): string
    {
        $cleaned = preg_replace('/[^A-Za-z0-9-]/', '-', $name);
        $cleaned = preg_replace('/-+/', '-', $cleaned);
        $cleaned = strtoupper(substr($cleaned, 0, 50));
        return trim($cleaned, '-');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        echo "\nRolling back inventory product import...\n\n";

        // Get all inventory product IDs from this import
        $productIds = DB::table('products_products')
            ->where('reference', 'LIKE', 'TCS-%')
            ->pluck('id');

        // Delete inventory quantities
        DB::table('inventories_product_quantities')
            ->whereIn('product_id', $productIds)
            ->delete();

        // Delete attribute value links
        DB::table('products_product_attribute_values')
            ->whereIn('product_id', $productIds)
            ->delete();

        // Delete product attribute links
        DB::table('products_product_attributes')
            ->whereIn('product_id', $productIds)
            ->delete();

        // Delete tag links
        DB::table('products_product_tag')
            ->whereIn('product_id', $productIds)
            ->delete();

        // Delete products
        DB::table('products_products')
            ->whereIn('id', $productIds)
            ->delete();

        echo "Rollback complete\n";
    }
};
