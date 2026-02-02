<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Webkul\Product\Models\Product;
use Webkul\Security\Models\User;

/**
 * CNC Material Products Seeder
 *
 * Creates sheet goods products for CNC material codes and links them
 * to TCS material inventory mappings. This enables:
 * - CNC programs to track material usage in inventory
 * - BOM generation from CNC nesting data
 * - Material cost tracking per project
 *
 * CNC Material Codes -> Sheet Goods Products -> TCS Material Mappings
 *
 * IMPORTANT: This seeder will NOT overwrite existing products.
 * It only creates products if they don't exist, preserving inventory data.
 *
 * Run with: php artisan db:seed --class=CncMaterialProductsSeeder
 */
class CncMaterialProductsSeeder extends Seeder
{
    /**
     * CNC material codes mapped to product specifications
     * These come from the Notion Cut File Log
     */
    protected array $cncMaterials = [
        'PreFin' => [
            'name' => '3/4" Pre-finished Maple 4x8',
            'description' => 'Pre-finished Maple plywood sheet goods for cabinet boxes and components',
            'species' => 'hard_maple',
            'tcs_tier' => 'paint_grade',
            'thickness' => 0.75,
            'sheet_size' => '4x8',
            'sqft_per_sheet' => 32.00,
            'cost_per_sheet' => 95.00,
            'is_box_material' => true,
            'is_face_frame' => false,
        ],
        'Medex' => [
            'name' => '3/4" Medex 4x8',
            'description' => 'Medex moisture-resistant MDF for paint-grade cabinets and wet areas',
            'species' => 'mdf',
            'tcs_tier' => 'paint_grade',
            'thickness' => 0.75,
            'sheet_size' => '4x8',
            'sqft_per_sheet' => 32.00,
            'cost_per_sheet' => 72.00,
            'is_box_material' => true,
            'is_face_frame' => false,
        ],
        'RiftWOPly' => [
            'name' => '3/4" Rift White Oak Plywood 4x8',
            'description' => 'Rift-cut White Oak plywood for premium stain-grade cabinets',
            'species' => 'white_oak',
            'tcs_tier' => 'premium',
            'thickness' => 0.75,
            'sheet_size' => '4x8',
            'sqft_per_sheet' => 32.00,
            'cost_per_sheet' => 185.00,
            'is_box_material' => true,
            'is_face_frame' => false,
        ],
        'MDF_RiftWO' => [
            'name' => '3/4" MDF Rift White Oak Veneer 4x8',
            'description' => 'MDF core with Rift White Oak veneer for premium cabinet doors',
            'species' => 'white_oak',
            'tcs_tier' => 'premium',
            'thickness' => 0.75,
            'sheet_size' => '4x8',
            'sqft_per_sheet' => 32.00,
            'cost_per_sheet' => 165.00,
            'is_box_material' => false,
            'is_face_frame' => false,
            'is_door_material' => true,
        ],
        'Melamine' => [
            'name' => '3/4" White Melamine 4x8',
            'description' => 'White melamine coated particle board for cabinet interiors',
            'species' => 'particle_board',
            'tcs_tier' => 'paint_grade',
            'thickness' => 0.75,
            'sheet_size' => '4x8',
            'sqft_per_sheet' => 32.00,
            'cost_per_sheet' => 48.00,
            'is_box_material' => true,
            'is_face_frame' => false,
        ],
        'Laminate' => [
            'name' => '3/4" HPL Laminate 4x8',
            'description' => 'High-pressure laminate for countertops and durable surfaces',
            'species' => 'laminate',
            'tcs_tier' => 'stain_grade',
            'thickness' => 0.75,
            'sheet_size' => '4x8',
            'sqft_per_sheet' => 32.00,
            'cost_per_sheet' => 85.00,
            'is_box_material' => false,
            'is_face_frame' => false,
        ],
        'FL' => [
            'name' => '4/4 Furniture Lumber (Solid)',
            'description' => 'Solid wood lumber for face frames and furniture components',
            'species' => 'mixed',
            'tcs_tier' => 'stain_grade',
            'thickness' => 1.0,
            'sheet_size' => null,
            'sqft_per_sheet' => null,
            'bf_per_unit' => 1.0,
            'cost_per_bf' => 8.50,
            'is_box_material' => false,
            'is_face_frame' => true,
        ],
        'BW' => [
            'name' => '3/4" Black Walnut Plywood 4x8',
            'description' => 'Black Walnut plywood for premium exotic cabinets',
            'species' => 'black_walnut',
            'tcs_tier' => 'custom_exotic',
            'thickness' => 0.75,
            'sheet_size' => '4x8',
            'sqft_per_sheet' => 32.00,
            'cost_per_sheet' => 245.00,
            'is_box_material' => true,
            'is_face_frame' => false,
        ],
        'BirchPly' => [
            'name' => '3/4" Birch Plywood 4x8',
            'description' => 'Baltic Birch plywood for paint-grade cabinet boxes',
            'species' => 'birch',
            'tcs_tier' => 'paint_grade',
            'thickness' => 0.75,
            'sheet_size' => '4x8',
            'sqft_per_sheet' => 32.00,
            'cost_per_sheet' => 65.00,
            'is_box_material' => true,
            'is_face_frame' => false,
        ],
        'MaplePly' => [
            'name' => '3/4" Maple Plywood 4x8',
            'description' => 'Hard Maple plywood for paint or stain-grade cabinets',
            'species' => 'hard_maple',
            'tcs_tier' => 'stain_grade',
            'thickness' => 0.75,
            'sheet_size' => '4x8',
            'sqft_per_sheet' => 32.00,
            'cost_per_sheet' => 110.00,
            'is_box_material' => true,
            'is_face_frame' => false,
        ],
    ];

    /**
     * Run the seeder
     */
    public function run(): void
    {
        $this->command->info('');
        $this->command->info('╔═══════════════════════════════════════════════════════════╗');
        $this->command->info('║      CNC MATERIAL PRODUCTS - SHEET GOODS SEEDER           ║');
        $this->command->info('╚═══════════════════════════════════════════════════════════╝');

        $user = User::first();
        if (!$user) {
            $this->command->error('No user found. Please run UserSeeder first.');
            return;
        }

        // Get the "Sheet" UOM for sheet goods, or "Units" as fallback
        $sheetUom = DB::table('unit_of_measures')->where('name', 'Sheet')->first();
        $unitUom = DB::table('unit_of_measures')->where('name', 'Units')->first();
        $defaultUomId = $sheetUom->id ?? $unitUom->id ?? 1;

        // Get the "Lumber & Sheet Goods" category, or first category as fallback
        $sheetCategory = DB::table('products_categories')->where('name', 'Lumber & Sheet Goods')->first();
        $defaultCategoryId = $sheetCategory->id ?? 1;

        $createdProducts = 0;
        $updatedProducts = 0;
        $createdMappings = 0;
        $updatedMappings = 0;

        foreach ($this->cncMaterials as $cncCode => $specs) {
            $this->command->info("Processing CNC material: {$cncCode}");

            // Check if product exists by name or create new
            $product = $this->findOrCreateProduct($cncCode, $specs, $user, $defaultUomId, $defaultCategoryId);

            if ($product->wasRecentlyCreated ?? false) {
                $createdProducts++;
                $this->command->info("  ✓ Created product: {$specs['name']}");
            } else {
                $updatedProducts++;
                $this->command->info("  ↻ Updated product: {$specs['name']}");
            }

            // Update/create TCS material mapping
            $mappingResult = $this->updateOrCreateMapping($cncCode, $specs, $product);
            if ($mappingResult === 'created') {
                $createdMappings++;
            } else {
                $updatedMappings++;
            }
        }

        // Summary
        $this->command->info('');
        $this->command->info('═══════════════════════════════════════════════════════════');
        $this->command->info("CNC Material Products Import Complete:");
        $this->command->info("  Products created: {$createdProducts}");
        $this->command->info("  Products updated: {$updatedProducts}");
        $this->command->info("  Mappings created: {$createdMappings}");
        $this->command->info("  Mappings updated: {$updatedMappings}");
        $this->command->info('═══════════════════════════════════════════════════════════');

        // Show final mapping summary
        $this->showMappingSummary();
    }

    /**
     * Find or create a product for the CNC material
     *
     * IMPORTANT: This will NOT overwrite existing products to preserve inventory data.
     * Only creates new products if they don't exist.
     */
    protected function findOrCreateProduct(string $cncCode, array $specs, User $user, int $uomId, int $categoryId): Product
    {
        $sku = 'TCS-MAT-' . strtoupper($cncCode);

        // Try to find existing product by SKU or name
        $product = Product::where('reference', $sku)->first();

        if (!$product) {
            $product = Product::where('name', $specs['name'])->first();
        }

        // If product exists, DO NOT update it - preserve existing data
        if ($product) {
            $product->wasRecentlyCreated = false;
            return $product;
        }

        // Only create if product doesn't exist
        $productData = [
            'name' => $specs['name'],
            'type' => 'goods',
            'reference' => $sku,
            'description' => $specs['description'],
            'material_type' => 'sheet_goods',
            'wood_species' => $specs['species'],
            'thickness_inches' => $specs['thickness'],
            'sheet_size' => $specs['sheet_size'],
            'sqft_per_sheet' => $specs['sqft_per_sheet'] ?? null,
            'bf_per_unit' => $specs['bf_per_unit'] ?? null,
            'cost' => $specs['cost_per_sheet'] ?? $specs['cost_per_bf'] ?? 0,
            'price' => ($specs['cost_per_sheet'] ?? $specs['cost_per_bf'] ?? 0) * 1.35, // 35% markup
            'suitable_for_paint' => in_array($specs['tcs_tier'], ['paint_grade']),
            'suitable_for_stain' => in_array($specs['tcs_tier'], ['stain_grade', 'premium', 'custom_exotic']),
            'is_storable' => true,
            'tracking' => 'lot',
            'uom_id' => $uomId,
            'uom_po_id' => $uomId,
            'category_id' => $categoryId,
            'material_notes' => json_encode([
                'cnc_material_code' => $cncCode,
                'tcs_material_slug' => $specs['tcs_tier'],
                'wood_species' => $specs['species'],
                'is_box_material' => $specs['is_box_material'],
                'is_face_frame_material' => $specs['is_face_frame'] ?? false,
                'is_door_material' => $specs['is_door_material'] ?? false,
                'sheet_sqft_per_lf' => ($specs['sqft_per_sheet'] ?? 0) > 0 ? 6.0 : 0,
                'board_feet_per_lf' => $specs['bf_per_unit'] ?? 0,
            ]),
            'creator_id' => $user->id,
        ];

        $product = Product::create($productData);
        $product->wasRecentlyCreated = true;

        return $product;
    }

    /**
     * Update or create TCS material mapping
     *
     * IMPORTANT: Does not modify existing mappings to preserve staging data.
     * Only creates new mappings or links products to unlinked mappings.
     */
    protected function updateOrCreateMapping(string $cncCode, array $specs, Product $product): string
    {
        // First, check if we have an existing mapping for this product
        $existingByProduct = DB::table('tcs_material_inventory_mappings')
            ->where('inventory_product_id', $product->id)
            ->first();

        if ($existingByProduct) {
            // Already linked - don't modify existing mapping
            $this->command->line("  ↻ Mapping exists: {$cncCode} -> {$specs['tcs_tier']}");
            return 'skipped';
        }

        // Check if we have a mapping for this tier + species without product
        $existingByTier = DB::table('tcs_material_inventory_mappings')
            ->where('tcs_material_slug', $specs['tcs_tier'])
            ->whereNull('inventory_product_id')
            ->where('wood_species', 'like', '%' . $this->getWoodSpeciesLabel($specs['species']) . '%')
            ->first();

        if ($existingByTier) {
            // Link the product to existing mapping (safe - just adding a link)
            DB::table('tcs_material_inventory_mappings')
                ->where('id', $existingByTier->id)
                ->update([
                    'inventory_product_id' => $product->id,
                    'notes' => "CNC Code: {$cncCode} | {$specs['description']}",
                    'updated_at' => now(),
                ]);
            $this->command->info("  ✓ Linked product to existing mapping: {$cncCode} -> {$specs['tcs_tier']}");
            return 'updated';
        }

        // Check if a mapping with this tier + species already exists (with a product)
        $existingWithProduct = DB::table('tcs_material_inventory_mappings')
            ->where('tcs_material_slug', $specs['tcs_tier'])
            ->where('wood_species', $this->getWoodSpeciesLabel($specs['species']))
            ->first();

        if ($existingWithProduct) {
            // Mapping exists with different product - don't duplicate
            $this->command->line("  ↻ Mapping exists for {$specs['tcs_tier']}/{$specs['species']} - skipped");
            return 'skipped';
        }

        // Create new mapping only if none exists
        try {
            DB::table('tcs_material_inventory_mappings')->insert([
                'tcs_material_slug' => $specs['tcs_tier'],
                'wood_species' => $this->getWoodSpeciesLabel($specs['species']),
                'inventory_product_id' => $product->id,
                'sheet_sqft_per_lf' => ($specs['sqft_per_sheet'] ?? 0) > 0 ? 6.0 : 0,
                'board_feet_per_lf' => $specs['bf_per_unit'] ?? 0,
                'is_box_material' => $specs['is_box_material'],
                'is_face_frame_material' => $specs['is_face_frame'] ?? false,
                'is_door_material' => $specs['is_door_material'] ?? false,
                'priority' => $this->calculatePriority($specs),
                'is_active' => true,
                'notes' => "CNC Code: {$cncCode} | {$specs['description']}",
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $this->command->info("  ✓ Created new mapping: {$cncCode} -> {$specs['tcs_tier']}");
            return 'created';
        } catch (\Illuminate\Database\UniqueConstraintViolationException $e) {
            // Unique constraint - mapping already exists
            $this->command->line("  ↻ Mapping already exists: {$cncCode} -> {$specs['tcs_tier']}");
            return 'skipped';
        }
    }

    /**
     * Get human-readable wood species label
     */
    protected function getWoodSpeciesLabel(string $species): string
    {
        return match ($species) {
            'hard_maple' => 'Hard Maple',
            'white_oak' => 'White Oak',
            'black_walnut' => 'Black Walnut',
            'birch' => 'Birch',
            'mdf' => 'MDF',
            'particle_board' => 'Particle Board',
            'laminate' => 'HPL Laminate',
            'mixed' => 'Mixed Hardwood',
            default => ucfirst($species),
        };
    }

    /**
     * Calculate priority for material selection
     */
    protected function calculatePriority(array $specs): int
    {
        if ($specs['is_box_material']) {
            return 1;
        }
        if ($specs['is_face_frame'] ?? false) {
            return 5;
        }
        if ($specs['is_door_material'] ?? false) {
            return 8;
        }
        return 10;
    }

    /**
     * Show summary of mappings
     */
    protected function showMappingSummary(): void
    {
        $this->command->info('');
        $this->command->info('=== TCS Material Mappings Summary ===');

        $mappings = DB::table('tcs_material_inventory_mappings')
            ->leftJoin('products_products', 'tcs_material_inventory_mappings.inventory_product_id', '=', 'products_products.id')
            ->select([
                'tcs_material_inventory_mappings.*',
                'products_products.name as product_name',
                'products_products.cost as product_cost',
            ])
            ->where('tcs_material_inventory_mappings.is_active', true)
            ->orderBy('tcs_material_inventory_mappings.tcs_material_slug')
            ->get();

        $tiers = ['paint_grade', 'stain_grade', 'premium', 'custom_exotic'];

        foreach ($tiers as $tier) {
            $tierMappings = $mappings->where('tcs_material_slug', $tier);
            $linkedCount = $tierMappings->whereNotNull('inventory_product_id')->count();
            $totalCount = $tierMappings->count();

            $this->command->info("  {$tier}: {$linkedCount}/{$totalCount} linked to products");

            foreach ($tierMappings as $m) {
                $productInfo = $m->product_name
                    ? "{$m->product_name} (\${$m->product_cost})"
                    : '(no product linked)';
                $this->command->line("    - {$m->wood_species}: {$productInfo}");
            }
        }
    }
}
