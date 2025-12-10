<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * TCS Shop Inventory Seeder
 *
 * Imports shop consumables from inventory_complete_final.csv
 * These are hardware, fasteners, CNC bits, sanding supplies, etc.
 * NOT sheet goods/lumber (those are in TcsSheetGoodsSeeder)
 */
class TcsShopInventorySeeder extends Seeder
{
    /**
     * Category mapping from CSV categories to product category names
     */
    protected array $categoryMapping = [
        'Hardware' => 'Cabinet Hardware',
        'Fasteners' => 'Fasteners & Screws',
        'CNC' => 'CNC Router Bits',
        'Sanding' => 'Abrasives & Sanding',
        'Adhesives' => 'Adhesives & Glue',
        'Edge Banding' => 'Edge Banding',
        'Blades' => 'Saw Blades',
        'Tools' => 'Shop Tools',
        'Shop Supplies' => 'Shop Supplies',
        'Maintenance' => 'Maintenance & Lubricants',
    ];

    /**
     * Run the seeder
     */
    public function run(): void
    {
        $this->command->info('Starting TCS Shop Inventory Import...');

        $csvPath = base_path('inventory_complete_final.csv');

        if (!file_exists($csvPath)) {
            $this->command->error("CSV file not found: {$csvPath}");
            return;
        }

        // Get or create company
        $companyId = $this->getCompanyId();
        $creatorId = $this->getCreatorId();

        // Get or create default UOM (Each)
        $eachUomId = $this->getOrCreateUom('Each', 'Unit');

        // Parse CSV
        $rows = $this->parseCsv($csvPath);
        $this->command->info("Found " . count($rows) . " items in CSV");

        $imported = 0;
        $skipped = 0;

        foreach ($rows as $row) {
            $itemId = trim($row['Item ID'] ?? '');
            $itemName = trim($row['Item Name'] ?? '');

            if (empty($itemId) || empty($itemName)) {
                $skipped++;
                continue;
            }

            // Check if product already exists
            $existing = DB::table('products_products')
                ->where('reference', $itemId)
                ->first();

            if ($existing) {
                $this->command->warn("Skipping existing product: {$itemId}");
                $skipped++;
                continue;
            }

            // Get or create category
            $categoryId = $this->getOrCreateCategory($row['Category'] ?? 'Shop Supplies', $companyId);

            // Parse cost
            $unitCost = $this->parseCost($row['Unit Cost'] ?? '0');

            // Build product data
            $productData = [
                'type' => 'goods',
                'name' => $itemName,
                'reference' => $itemId,
                'barcode' => $row['Product Number'] ?? null,
                'cost' => $unitCost,
                'price' => $unitCost * 1.3, // 30% markup as default
                'description' => $this->buildDescription($row),
                'description_purchase' => $row['Notes'] ?? null,
                'enable_sales' => false,
                'enable_purchase' => true,
                'is_storable' => true,
                'tracking' => 'lot',
                'uom_id' => $this->getUomForUnit($row['Unit'] ?? 'Each', $eachUomId),
                'uom_po_id' => $this->getUomForUnit($row['Unit'] ?? 'Each', $eachUomId),
                'category_id' => $categoryId,
                'company_id' => $companyId,
                'creator_id' => $creatorId,
                'created_at' => now(),
                'updated_at' => now(),
                'minimum_stock_level' => (int) ($row['Min Quantity'] ?? 1),
                'reorder_quantity' => (int) ($row['Reorder Quantity'] ?? 2),
                // Store specifications in material_notes as JSON
                'material_notes' => $this->extractSpecifications($row),
            ];

            try {
                DB::table('products_products')->insert($productData);
                $imported++;
                $this->command->info("Imported: {$itemId} - {$itemName}");
            } catch (\Exception $e) {
                $this->command->error("Failed to import {$itemId}: " . $e->getMessage());
                Log::error("TcsShopInventorySeeder error", [
                    'item_id' => $itemId,
                    'error' => $e->getMessage(),
                ]);
                $skipped++;
            }
        }

        $this->command->info("Import complete: {$imported} imported, {$skipped} skipped");
    }

    /**
     * Parse CSV file
     */
    protected function parseCsv(string $path): array
    {
        $rows = [];
        $handle = fopen($path, 'r');

        if ($handle === false) {
            return [];
        }

        $headers = fgetcsv($handle);

        while (($data = fgetcsv($handle)) !== false) {
            if (count($data) === count($headers)) {
                $rows[] = array_combine($headers, $data);
            }
        }

        fclose($handle);
        return $rows;
    }

    /**
     * Get company ID (TCS Woodwork)
     */
    protected function getCompanyId(): int
    {
        $company = DB::table('companies')->where('name', 'like', '%TCS%')->first();
        return $company?->id ?? 1;
    }

    /**
     * Get creator ID
     */
    protected function getCreatorId(): int
    {
        $user = DB::table('users')->first();
        return $user?->id ?? 1;
    }

    /**
     * Get or create UOM
     */
    protected function getOrCreateUom(string $name, string $category): int
    {
        $uom = DB::table('unit_of_measures')->where('name', $name)->first();

        if ($uom) {
            return $uom->id;
        }

        // Get or create UOM category
        $categoryRecord = DB::table('unit_of_measure_categories')->where('name', $category)->first();
        $categoryId = $categoryRecord?->id;

        if (!$categoryId) {
            $categoryId = DB::table('unit_of_measure_categories')->insertGetId([
                'name' => $category,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        return DB::table('unit_of_measures')->insertGetId([
            'name' => $name,
            'category_id' => $categoryId,
            'factor' => 1,
            'type' => 'reference',
            'rounding' => 0.01,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Get UOM ID for a given unit name
     */
    protected function getUomForUnit(string $unit, int $defaultId): int
    {
        $unitMapping = [
            'Each' => 'Each',
            'Box' => 'Box',
            'Pack' => 'Pack',
            'Roll' => 'Roll',
            'Pair' => 'Pair',
            'Bottle' => 'Bottle',
            'Kit' => 'Kit',
            'Bag' => 'Bag',
            'Tube' => 'Tube',
            'Set' => 'Set',
        ];

        $uomName = $unitMapping[$unit] ?? 'Each';
        $uom = DB::table('unit_of_measures')->where('name', $uomName)->first();

        if ($uom) {
            return $uom->id;
        }

        // Create the UOM if it doesn't exist
        return $this->getOrCreateUom($uomName, 'Unit');
    }

    /**
     * Get or create product category
     */
    protected function getOrCreateCategory(string $csvCategory, int $companyId): ?int
    {
        $categoryName = $this->categoryMapping[$csvCategory] ?? $csvCategory;

        $category = DB::table('products_categories')
            ->where('name', $categoryName)
            ->first();

        if ($category) {
            return $category->id;
        }

        // Create category
        return DB::table('products_categories')->insertGetId([
            'name' => $categoryName,
            'full_name' => $categoryName,
            'creator_id' => $this->getCreatorId(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Parse cost from CSV (handles $xx.xx format)
     */
    protected function parseCost(string $cost): float
    {
        // Remove $ and commas
        $cleaned = preg_replace('/[^0-9.]/', '', $cost);
        return (float) $cleaned;
    }

    /**
     * Build description from CSV row
     */
    protected function buildDescription(array $row): string
    {
        $parts = [];

        if (!empty($row['Subcategory'])) {
            $parts[] = "Type: {$row['Subcategory']}";
        }

        if (!empty($row['Supplier'])) {
            $parts[] = "Supplier: {$row['Supplier']}";
        }

        if (!empty($row['Location'])) {
            $parts[] = "Location: {$row['Location']}";
        }

        if (!empty($row['Product Number'])) {
            $parts[] = "Product #: {$row['Product Number']}";
        }

        if (!empty($row['Reorder URL'])) {
            $parts[] = "Reorder URL: {$row['Reorder URL']}";
        }

        return implode("\n", $parts);
    }

    /**
     * Extract specifications JSON from CSV
     */
    protected function extractSpecifications(array $row): ?string
    {
        $specs = [];

        // Add key fields
        $specs['csv_category'] = $row['Category'] ?? null;
        $specs['subcategory'] = $row['Subcategory'] ?? null;
        $specs['supplier'] = $row['Supplier'] ?? null;
        $specs['location'] = $row['Location'] ?? null;
        $specs['product_number'] = $row['Product Number'] ?? null;
        $specs['order_number'] = $row['Order Number'] ?? null;
        $specs['package_size'] = $row['Package Size'] ?? null;
        $specs['is_sharpenable'] = ($row['Is Sharpenable'] ?? '') === 'Yes';
        $specs['is_consumable'] = ($row['Is Consumable'] ?? '') === 'Yes';
        $specs['reorder_url'] = $row['Reorder URL'] ?? null;

        // Parse the Specifications JSON column if present
        if (!empty($row['Specifications'])) {
            $jsonSpecs = json_decode($row['Specifications'], true);
            if (is_array($jsonSpecs)) {
                $specs['detailed_specifications'] = $jsonSpecs;
            }
        }

        // Filter out null values
        $specs = array_filter($specs, fn($v) => $v !== null && $v !== '');

        return !empty($specs) ? json_encode($specs) : null;
    }
}
