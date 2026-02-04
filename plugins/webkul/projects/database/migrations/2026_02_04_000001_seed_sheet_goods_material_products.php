<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Seed sheet goods material products with parent/variant hierarchy.
 *
 * Creates:
 * - 3 new Panel Thickness attribute options (1/4", 3/8", 1")
 * - 10 parent products (material families)
 * - ~25 thickness variant products under each parent
 * - Product attribute links for Panel Thickness
 * - Product attribute value records for each variant
 *
 * This migration is idempotent - it checks for existing records before inserting.
 * It only INSERTS new data; it does NOT modify or delete existing products/inventory.
 */
return new class extends Migration
{
    /**
     * UOM "Sheet" ID - must exist on target database.
     */
    private const UOM_SHEET_ID = 29;

    /**
     * Category "Lumber & Sheet Goods" ID - must exist on target database.
     */
    private const CATEGORY_ID = 63;

    /**
     * Attribute "Panel Thickness" ID - must exist on target database.
     */
    private const THICKNESS_ATTR_ID = 26;

    /**
     * Thickness option definitions.
     * Maps display name => [thickness_inches, bf_per_unit for 4x8 sheet]
     */
    private const THICKNESS_MAP = [
        '1/4"'  => ['thickness' => 0.250, 'bf' => 8.00],
        '3/8"'  => ['thickness' => 0.375, 'bf' => 12.00],
        '1/2"'  => ['thickness' => 0.500, 'bf' => 16.00],
        '3/4"'  => ['thickness' => 0.750, 'bf' => 24.00],
        '1"'    => ['thickness' => 1.000, 'bf' => 32.00],
    ];

    /**
     * Material family definitions.
     * Each entry creates a parent product and variants for specified thicknesses.
     */
    private function getMaterialFamilies(): array
    {
        return [
            [
                'name'       => 'Medex',
                'code'       => 'MEDEX',
                'core_type'  => 'mdf',
                'cost_3_4'   => 72.00,
                'thicknesses' => ['1/4"', '3/8"', '1/2"', '3/4"', '1"'],
            ],
            [
                'name'       => 'Pre-finished Maple',
                'code'       => 'PREFIN',
                'core_type'  => 'plywood',
                'cost_3_4'   => 95.00,
                'thicknesses' => ['1/4"', '3/8"', '1/2"', '3/4"'],
            ],
            [
                'name'       => 'Rift White Oak Plywood',
                'code'       => 'RIFTWOPLY',
                'core_type'  => 'plywood',
                'cost_3_4'   => 185.00,
                'thicknesses' => ['1/4"', '3/8"', '1/2"', '3/4"'],
            ],
            [
                'name'       => 'MDF Rift White Oak Veneer',
                'code'       => 'MDF-RIFTWO',
                'core_type'  => 'mdf',
                'cost_3_4'   => 165.00,
                'thicknesses' => ['1/4"', '3/8"', '1/2"', '3/4"'],
            ],
            [
                'name'       => 'White Melamine',
                'code'       => 'MELAMINE',
                'core_type'  => 'particle_board',
                'cost_3_4'   => 48.00,
                'thicknesses' => ['1/2"', '3/4"'],
            ],
            [
                'name'       => 'Birch Plywood',
                'code'       => 'BIRCHPLY',
                'core_type'  => 'plywood',
                'cost_3_4'   => 65.00,
                'thicknesses' => ['1/4"', '3/8"', '1/2"', '3/4"'],
            ],
            [
                'name'       => 'Maple Plywood',
                'code'       => 'MAPLEPLY',
                'core_type'  => 'plywood',
                'cost_3_4'   => 110.00,
                'thicknesses' => ['1/4"', '3/8"', '1/2"', '3/4"'],
            ],
            [
                'name'       => 'HPL Laminate',
                'code'       => 'LAMINATE',
                'core_type'  => 'particle_board',
                'cost_3_4'   => 85.00,
                'thicknesses' => ['3/4"'],
            ],
            [
                'name'       => 'Black Walnut Plywood',
                'code'       => 'BW',
                'core_type'  => 'plywood',
                'cost_3_4'   => 245.00,
                'thicknesses' => ['1/4"', '3/4"'],
            ],
            [
                'name'       => 'Unfinished Plywood',
                'code'       => 'UNFINPLY',
                'core_type'  => 'plywood',
                'cost_3_4'   => null,
                'thicknesses' => ['1/4"', '3/8"', '1/2"', '3/4"'],
            ],
        ];
    }

    public function up(): void
    {
        // Verify prerequisites exist
        $this->verifyPrerequisites();

        DB::beginTransaction();

        try {
            // Step 1: Ensure thickness attribute options exist
            $thicknessOptionIds = $this->ensureThicknessOptions();

            // Step 2: Create parent products and variants for each material family
            foreach ($this->getMaterialFamilies() as $family) {
                $this->createMaterialFamily($family, $thicknessOptionIds);
            }

            DB::commit();
            Log::info('[MaterialSeeder] Sheet goods material products seeded successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('[MaterialSeeder] Failed to seed materials: ' . $e->getMessage());
            throw $e;
        }
    }

    public function down(): void
    {
        // We don't delete products on rollback to protect inventory data.
        // Instead, log what would need manual cleanup.
        Log::warning('[MaterialSeeder] Rollback requested. Products are NOT deleted to protect inventory. Manual cleanup required if needed.');
    }

    /**
     * Verify that required reference data exists.
     */
    private function verifyPrerequisites(): void
    {
        $uom = DB::table('unit_of_measures')->where('id', self::UOM_SHEET_ID)->exists();
        if (! $uom) {
            throw new \RuntimeException('UOM "Sheet" (id=' . self::UOM_SHEET_ID . ') not found. Run product seeders first.');
        }

        $cat = DB::table('products_categories')->where('id', self::CATEGORY_ID)->exists();
        if (! $cat) {
            throw new \RuntimeException('Category "Lumber & Sheet Goods" (id=' . self::CATEGORY_ID . ') not found.');
        }

        $attr = DB::table('products_attributes')->where('id', self::THICKNESS_ATTR_ID)->exists();
        if (! $attr) {
            throw new \RuntimeException('Attribute "Panel Thickness" (id=' . self::THICKNESS_ATTR_ID . ') not found.');
        }
    }

    /**
     * Ensure the 5 thickness attribute options exist (1/4", 3/8", 1/2", 3/4", 1").
     * Returns map of option name => option ID.
     */
    private function ensureThicknessOptions(): array
    {
        $optionIds = [];
        $now = now();

        foreach (array_keys(self::THICKNESS_MAP) as $optionName) {
            $existing = DB::table('products_attribute_options')
                ->where('attribute_id', self::THICKNESS_ATTR_ID)
                ->where('name', $optionName)
                ->first();

            if ($existing) {
                $optionIds[$optionName] = $existing->id;
            } else {
                $optionIds[$optionName] = DB::table('products_attribute_options')->insertGetId([
                    'name'         => $optionName,
                    'attribute_id' => self::THICKNESS_ATTR_ID,
                    'creator_id'   => 1,
                    'created_at'   => $now,
                    'updated_at'   => $now,
                ]);
                Log::info("[MaterialSeeder] Created thickness option: {$optionName} (id={$optionIds[$optionName]})");
            }
        }

        return $optionIds;
    }

    /**
     * Create a material family: parent product + thickness variants.
     */
    private function createMaterialFamily(array $family, array $thicknessOptionIds): void
    {
        $now = now();

        // Step 1: Find or create parent product
        $parentId = $this->findOrCreateParent($family, $now);

        // Step 2: Create product_attribute link (parent <-> Panel Thickness)
        $productAttributeId = $this->ensureProductAttribute($parentId, $now);

        // Step 3: Create each thickness variant
        foreach ($family['thicknesses'] as $thicknessName) {
            $this->ensureVariant($family, $parentId, $productAttributeId, $thicknessName, $thicknessOptionIds[$thicknessName], $now);
        }
    }

    /**
     * Find or create the parent product for a material family.
     */
    private function findOrCreateParent(array $family, $now): int
    {
        // Look for existing parent by name (exact match, no parent_id, type=goods)
        $existing = DB::table('products_products')
            ->where('name', $family['name'])
            ->whereNull('parent_id')
            ->where('type', 'goods')
            ->where('material_type', 'sheet_goods')
            ->whereNull('deleted_at')
            ->first();

        if ($existing) {
            Log::info("[MaterialSeeder] Found existing parent: {$family['name']} (id={$existing->id})");
            return $existing->id;
        }

        $parentId = DB::table('products_products')->insertGetId([
            'name'          => $family['name'],
            'type'          => 'goods',
            'uom_id'        => self::UOM_SHEET_ID,
            'uom_po_id'     => self::UOM_SHEET_ID,
            'category_id'   => self::CATEGORY_ID,
            'creator_id'    => 1,
            'is_storable'   => 1,
            'is_favorite'   => 0,
            'sales_ok'      => 0,
            'purchase_ok'   => 0,
            'suitable_for_paint' => 0,
            'suitable_for_stain' => 0,
            'requires_locking_device' => 0,
            'material_type' => 'sheet_goods',
            'core_type'     => $family['core_type'],
            'service_tracking' => '',
            'created_at'    => $now,
            'updated_at'    => $now,
        ]);

        Log::info("[MaterialSeeder] Created parent: {$family['name']} (id={$parentId})");

        return $parentId;
    }

    /**
     * Ensure product_attribute link exists (parent product <-> Panel Thickness attribute).
     */
    private function ensureProductAttribute(int $parentId, $now): int
    {
        $existing = DB::table('products_product_attributes')
            ->where('product_id', $parentId)
            ->where('attribute_id', self::THICKNESS_ATTR_ID)
            ->first();

        if ($existing) {
            return $existing->id;
        }

        return DB::table('products_product_attributes')->insertGetId([
            'sort'         => 1,
            'product_id'   => $parentId,
            'attribute_id' => self::THICKNESS_ATTR_ID,
            'creator_id'   => 1,
            'created_at'   => $now,
            'updated_at'   => $now,
        ]);
    }

    /**
     * Ensure a thickness variant product exists under the parent.
     */
    private function ensureVariant(
        array $family,
        int $parentId,
        int $productAttributeId,
        string $thicknessName,
        int $thicknessOptionId,
        $now
    ): void {
        $thicknessData = self::THICKNESS_MAP[$thicknessName];
        $thicknessCode = str_replace('.', '', number_format($thicknessData['thickness'], 3));
        // Build reference code: TCS-MAT-{CODE} for 3/4", TCS-MAT-{CODE}-{THICKNESS} for others
        $reference = $thicknessName === '3/4"'
            ? "TCS-MAT-{$family['code']}"
            : "TCS-MAT-{$family['code']}-{$thicknessCode}";

        $variantName = "{$thicknessName} {$family['name']} 4x8";
        $cost = $thicknessName === '3/4"' ? $family['cost_3_4'] : null;

        // Check if variant already exists by reference code
        $existing = DB::table('products_products')
            ->where('reference', $reference)
            ->whereNull('deleted_at')
            ->first();

        if ($existing) {
            // If it exists but doesn't have a parent_id, link it
            if (! $existing->parent_id) {
                DB::table('products_products')
                    ->where('id', $existing->id)
                    ->update([
                        'parent_id'        => $parentId,
                        'thickness_inches' => $thicknessData['thickness'],
                        'width_inches'     => 48.000,
                        'length_inches'    => 96.000,
                        'sheet_size'       => '4x8',
                        'bf_per_unit'      => $thicknessData['bf'],
                        'core_type'        => $family['core_type'],
                        'material_type'    => 'sheet_goods',
                        'updated_at'       => $now,
                    ]);
                Log::info("[MaterialSeeder] Linked existing product to parent: {$reference} (id={$existing->id})");
            }

            // Ensure attribute value exists
            $this->ensureAttributeValue($existing->id, $productAttributeId, $thicknessOptionId);

            return;
        }

        // Also check by name pattern (for pre-existing products without reference)
        $existingByName = DB::table('products_products')
            ->where('name', $variantName)
            ->where('parent_id', $parentId)
            ->whereNull('deleted_at')
            ->first();

        if ($existingByName) {
            // Update reference if missing
            if (! $existingByName->reference) {
                DB::table('products_products')
                    ->where('id', $existingByName->id)
                    ->update(['reference' => $reference, 'updated_at' => $now]);
            }
            $this->ensureAttributeValue($existingByName->id, $productAttributeId, $thicknessOptionId);
            return;
        }

        // Create the variant product
        $variantId = DB::table('products_products')->insertGetId([
            'name'             => $variantName,
            'reference'        => $reference,
            'type'             => 'goods',
            'parent_id'        => $parentId,
            'uom_id'           => self::UOM_SHEET_ID,
            'uom_po_id'        => self::UOM_SHEET_ID,
            'category_id'      => self::CATEGORY_ID,
            'creator_id'       => 1,
            'is_storable'      => 1,
            'is_favorite'      => 0,
            'sales_ok'         => 0,
            'purchase_ok'      => 0,
            'suitable_for_paint' => 0,
            'suitable_for_stain' => 0,
            'requires_locking_device' => 0,
            'material_type'    => 'sheet_goods',
            'core_type'        => $family['core_type'],
            'thickness_inches' => $thicknessData['thickness'],
            'width_inches'     => 48.000,
            'length_inches'    => 96.000,
            'sheet_size'       => '4x8',
            'bf_per_unit'      => $thicknessData['bf'],
            'cost'             => $cost,
            'service_tracking' => '',
            'created_at'       => $now,
            'updated_at'       => $now,
        ]);

        Log::info("[MaterialSeeder] Created variant: {$variantName} (id={$variantId}, ref={$reference})");

        // Create attribute value
        $this->ensureAttributeValue($variantId, $productAttributeId, $thicknessOptionId);
    }

    /**
     * Ensure a product_attribute_value record exists for the variant.
     */
    private function ensureAttributeValue(int $productId, int $productAttributeId, int $thicknessOptionId): void
    {
        $exists = DB::table('products_product_attribute_values')
            ->where('product_id', $productId)
            ->where('attribute_id', self::THICKNESS_ATTR_ID)
            ->exists();

        if ($exists) {
            return;
        }

        // This table has no timestamps
        DB::table('products_product_attribute_values')->insert([
            'product_id'          => $productId,
            'attribute_id'        => self::THICKNESS_ATTR_ID,
            'product_attribute_id' => $productAttributeId,
            'attribute_option_id' => $thicknessOptionId,
        ]);
    }
};
