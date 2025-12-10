<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * TCS Material Mappings Seeder
 *
 * Populates tcs_material_inventory_mappings table to link
 * sheet goods/lumber products to TCS pricing categories.
 *
 * This enables:
 * - Selecting a material auto-assigns correct pricing tier
 * - MaterialBomService can calculate requirements based on linear feet
 * - Proper BOM generation from cabinet specifications
 *
 * Must run AFTER TcsSheetGoodsSeeder to have products available
 */
class TcsMaterialMappingsSeeder extends Seeder
{
    /**
     * Run the seeder
     */
    public function run(): void
    {
        $this->command->info('Starting TCS Material Mappings Import...');

        // First check if tcs_material_inventory_mappings table exists
        if (!$this->tableExists('tcs_material_inventory_mappings')) {
            $this->command->error('Table tcs_material_inventory_mappings does not exist. Run migrations first.');
            return;
        }

        // Get all sheet goods products created by TcsSheetGoodsSeeder
        $products = DB::table('products_products')
            ->whereNotNull('material_notes')
            ->where('material_notes', '!=', '')
            ->get();

        if ($products->isEmpty()) {
            $this->command->warn('No products with material_notes found. Run TcsSheetGoodsSeeder first.');
            return;
        }

        $created = 0;
        $skipped = 0;

        foreach ($products as $product) {
            $materialNotes = json_decode($product->material_notes, true);

            // Skip if no TCS material slug (not a sheet goods product)
            if (empty($materialNotes['tcs_material_slug'])) {
                continue;
            }

            $tcsMaterialSlug = $materialNotes['tcs_material_slug'];
            $woodSpecies = $materialNotes['wood_species'] ?? 'Unknown';

            // Check if mapping already exists
            $existing = DB::table('tcs_material_inventory_mappings')
                ->where('inventory_product_id', $product->id)
                ->where('tcs_material_slug', $tcsMaterialSlug)
                ->first();

            if ($existing) {
                $this->command->warn("Skipping existing mapping: {$product->reference} -> {$tcsMaterialSlug}");
                $skipped++;
                continue;
            }

            // Create the mapping
            $mappingData = [
                'tcs_material_slug' => $tcsMaterialSlug,
                'wood_species' => $woodSpecies,
                'inventory_product_id' => $product->id,
                'material_category_id' => null, // Optional - can link to woodworking_material_categories if exists
                'board_feet_per_lf' => $materialNotes['board_feet_per_lf'] ?? 0,
                'sheet_sqft_per_lf' => $materialNotes['sheet_sqft_per_lf'] ?? 0,
                'is_box_material' => $materialNotes['is_box_material'] ?? false,
                'is_face_frame_material' => $materialNotes['is_face_frame_material'] ?? false,
                'is_door_material' => $materialNotes['is_door_material'] ?? false,
                'priority' => $this->calculatePriority($materialNotes),
                'is_active' => true,
                'notes' => $this->generateMappingNotes($product, $materialNotes),
                'created_at' => now(),
                'updated_at' => now(),
            ];

            try {
                DB::table('tcs_material_inventory_mappings')->insert($mappingData);
                $created++;
                $this->command->info("Created mapping: {$product->reference} ({$woodSpecies}) -> {$tcsMaterialSlug}");
            } catch (\Exception $e) {
                $this->command->error("Failed to create mapping for {$product->reference}: " . $e->getMessage());
                Log::error("TcsMaterialMappingsSeeder error", [
                    'product_id' => $product->id,
                    'error' => $e->getMessage(),
                ]);
                $skipped++;
            }
        }

        $this->command->info("Material mappings import complete: {$created} created, {$skipped} skipped");

        // Show summary of mappings by pricing tier
        $this->showMappingSummary();
    }

    /**
     * Check if table exists
     */
    protected function tableExists(string $table): bool
    {
        return DB::getSchemaBuilder()->hasTable($table);
    }

    /**
     * Calculate priority for material (lower = preferred)
     * Priority helps MaterialBomService choose the best material when multiple options exist
     */
    protected function calculatePriority(array $materialNotes): int
    {
        $priority = 10; // Default priority

        // Box materials get higher priority (used first in BOM)
        if ($materialNotes['is_box_material'] ?? false) {
            $priority = 1;
        }

        // Face frame materials get medium priority
        if ($materialNotes['is_face_frame_material'] ?? false) {
            $priority = min($priority, 5);
        }

        // Door materials get lower priority (often customer choice)
        if ($materialNotes['is_door_material'] ?? false) {
            $priority = min($priority, 8);
        }

        return $priority;
    }

    /**
     * Generate helpful notes for the mapping
     */
    protected function generateMappingNotes($product, array $materialNotes): string
    {
        $notes = [];

        if (!empty($materialNotes['thickness'])) {
            $notes[] = "Thickness: {$materialNotes['thickness']}";
        }

        if (!empty($materialNotes['sheet_size'])) {
            $notes[] = "Sheet size: {$materialNotes['sheet_size']}";
        }

        $usageTypes = [];
        if ($materialNotes['is_box_material'] ?? false) {
            $usageTypes[] = 'cabinet boxes';
        }
        if ($materialNotes['is_face_frame_material'] ?? false) {
            $usageTypes[] = 'face frames';
        }
        if ($materialNotes['is_door_material'] ?? false) {
            $usageTypes[] = 'doors/drawers';
        }

        if (!empty($usageTypes)) {
            $notes[] = 'Used for: ' . implode(', ', $usageTypes);
        }

        $notes[] = "Product SKU: {$product->reference}";

        return implode(' | ', $notes);
    }

    /**
     * Show summary of created mappings
     */
    protected function showMappingSummary(): void
    {
        $this->command->newLine();
        $this->command->info('=== Material Mapping Summary by Pricing Tier ===');

        $tiers = ['paint_grade', 'stain_grade', 'premium', 'custom_exotic'];

        foreach ($tiers as $tier) {
            $count = DB::table('tcs_material_inventory_mappings')
                ->where('tcs_material_slug', $tier)
                ->where('is_active', true)
                ->count();

            $boxCount = DB::table('tcs_material_inventory_mappings')
                ->where('tcs_material_slug', $tier)
                ->where('is_box_material', true)
                ->where('is_active', true)
                ->count();

            $faceFrameCount = DB::table('tcs_material_inventory_mappings')
                ->where('tcs_material_slug', $tier)
                ->where('is_face_frame_material', true)
                ->where('is_active', true)
                ->count();

            $doorCount = DB::table('tcs_material_inventory_mappings')
                ->where('tcs_material_slug', $tier)
                ->where('is_door_material', true)
                ->where('is_active', true)
                ->count();

            $this->command->line("{$tier}: {$count} materials (box: {$boxCount}, face frame: {$faceFrameCount}, door: {$doorCount})");
        }

        $this->command->newLine();
    }
}
