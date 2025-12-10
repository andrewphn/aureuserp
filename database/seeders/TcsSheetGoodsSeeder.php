<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * TCS Sheet Goods & Lumber Seeder
 *
 * Creates material products (sheet goods, lumber) that link to TCS pricing categories.
 * These are the materials used in cabinet construction, mapped to pricing tiers:
 * - paint_grade: Maple, Poplar, MDF
 * - stain_grade: Oak, Maple (natural finish)
 * - premium: Rift White Oak, Black Walnut
 * - custom_exotic: Specialty woods
 */
class TcsSheetGoodsSeeder extends Seeder
{
    /**
     * Sheet goods and lumber products to create
     * Each links to a TCS pricing category (tcs_material_slug)
     */
    protected array $materials = [
        // ===========================================
        // PAINT GRADE MATERIALS ($0/LF base)
        // ===========================================

        // Sheet Goods - Paint Grade
        [
            'name' => 'Prefinished Maple Plywood 3/4"',
            'reference' => 'PLY-MAPLE-PF-34',
            'tcs_material_slug' => 'paint_grade',
            'wood_species' => 'Hard Maple',
            'sheet_sqft_per_lf' => 4.5,
            'board_feet_per_lf' => 0,
            'is_box_material' => true,
            'is_face_frame_material' => false,
            'is_door_material' => false,
            'cost' => 85.00, // Per sheet (4x8)
            'description' => 'Cabinet box material - prefinished maple veneer plywood',
            'thickness' => '3/4"',
            'sheet_size' => '4x8',
        ],
        [
            'name' => 'Prefinished Maple Plywood 1/2"',
            'reference' => 'PLY-MAPLE-PF-12',
            'tcs_material_slug' => 'paint_grade',
            'wood_species' => 'Hard Maple',
            'sheet_sqft_per_lf' => 3.0,
            'board_feet_per_lf' => 0,
            'is_box_material' => true,
            'is_face_frame_material' => false,
            'is_door_material' => false,
            'cost' => 65.00,
            'description' => 'Drawer box and shelf material - prefinished maple veneer plywood',
            'thickness' => '1/2"',
            'sheet_size' => '4x8',
        ],
        [
            'name' => 'MDF 3/4"',
            'reference' => 'MDF-34',
            'tcs_material_slug' => 'paint_grade',
            'wood_species' => 'MDF',
            'sheet_sqft_per_lf' => 4.0,
            'board_feet_per_lf' => 0,
            'is_box_material' => false,
            'is_face_frame_material' => false,
            'is_door_material' => true,
            'cost' => 45.00,
            'description' => 'Paint-grade door panels and flat surfaces',
            'thickness' => '3/4"',
            'sheet_size' => '4x8',
        ],
        [
            'name' => 'Medex MDF 3/4"',
            'reference' => 'MEDEX-34',
            'tcs_material_slug' => 'paint_grade',
            'wood_species' => 'Medex',
            'sheet_sqft_per_lf' => 4.0,
            'board_feet_per_lf' => 0,
            'is_box_material' => true,
            'is_face_frame_material' => false,
            'is_door_material' => true,
            'cost' => 65.00,
            'description' => 'Moisture-resistant MDF for paint-grade applications',
            'thickness' => '3/4"',
            'sheet_size' => '4x8',
        ],
        [
            'name' => 'Prefinished Birch Plywood 3/4"',
            'reference' => 'PLY-BIRCH-PF-34',
            'tcs_material_slug' => 'paint_grade',
            'wood_species' => 'Baltic Birch',
            'sheet_sqft_per_lf' => 4.5,
            'board_feet_per_lf' => 0,
            'is_box_material' => true,
            'is_face_frame_material' => false,
            'is_door_material' => false,
            'cost' => 90.00,
            'description' => 'Premium cabinet box material - Baltic birch plywood',
            'thickness' => '3/4"',
            'sheet_size' => '4x8',
        ],

        // Solid Lumber - Paint Grade
        [
            'name' => 'Hard Maple S4S 4/4',
            'reference' => 'LBR-MAPLE-44',
            'tcs_material_slug' => 'paint_grade',
            'wood_species' => 'Hard Maple',
            'sheet_sqft_per_lf' => 0,
            'board_feet_per_lf' => 1.2,
            'is_box_material' => false,
            'is_face_frame_material' => true,
            'is_door_material' => true,
            'cost' => 8.50, // Per board foot
            'description' => 'Face frame and door rail/stile material - surfaced hard maple',
            'thickness' => '4/4 (1")',
            'sheet_size' => null,
        ],
        [
            'name' => 'Poplar S4S 4/4',
            'reference' => 'LBR-POPLAR-44',
            'tcs_material_slug' => 'paint_grade',
            'wood_species' => 'Poplar',
            'sheet_sqft_per_lf' => 0,
            'board_feet_per_lf' => 1.0,
            'is_box_material' => false,
            'is_face_frame_material' => true,
            'is_door_material' => false,
            'cost' => 5.50, // Per board foot
            'description' => 'Paint-grade face frame material - economical option',
            'thickness' => '4/4 (1")',
            'sheet_size' => null,
        ],

        // ===========================================
        // STAIN GRADE MATERIALS ($156/LF extra)
        // ===========================================

        // Sheet Goods - Stain Grade
        [
            'name' => 'White Oak Plywood 3/4"',
            'reference' => 'PLY-WO-34',
            'tcs_material_slug' => 'stain_grade',
            'wood_species' => 'White Oak',
            'sheet_sqft_per_lf' => 4.5,
            'board_feet_per_lf' => 0,
            'is_box_material' => true,
            'is_face_frame_material' => false,
            'is_door_material' => false,
            'cost' => 125.00,
            'description' => 'Stain-grade cabinet box material - white oak veneer plywood',
            'thickness' => '3/4"',
            'sheet_size' => '4x8',
        ],
        [
            'name' => 'White Oak Plywood 1/2"',
            'reference' => 'PLY-WO-12',
            'tcs_material_slug' => 'stain_grade',
            'wood_species' => 'White Oak',
            'sheet_sqft_per_lf' => 3.0,
            'board_feet_per_lf' => 0,
            'is_box_material' => true,
            'is_face_frame_material' => false,
            'is_door_material' => false,
            'cost' => 95.00,
            'description' => 'Stain-grade drawer box and shelf material',
            'thickness' => '1/2"',
            'sheet_size' => '4x8',
        ],
        [
            'name' => 'Red Oak Plywood 3/4"',
            'reference' => 'PLY-RO-34',
            'tcs_material_slug' => 'stain_grade',
            'wood_species' => 'Red Oak',
            'sheet_sqft_per_lf' => 4.5,
            'board_feet_per_lf' => 0,
            'is_box_material' => true,
            'is_face_frame_material' => false,
            'is_door_material' => false,
            'cost' => 110.00,
            'description' => 'Stain-grade cabinet box material - red oak veneer plywood',
            'thickness' => '3/4"',
            'sheet_size' => '4x8',
        ],
        [
            'name' => 'Cherry Plywood 3/4"',
            'reference' => 'PLY-CHERRY-34',
            'tcs_material_slug' => 'stain_grade',
            'wood_species' => 'Cherry',
            'sheet_sqft_per_lf' => 4.5,
            'board_feet_per_lf' => 0,
            'is_box_material' => true,
            'is_face_frame_material' => false,
            'is_door_material' => false,
            'cost' => 145.00,
            'description' => 'Stain-grade cabinet box material - cherry veneer plywood',
            'thickness' => '3/4"',
            'sheet_size' => '4x8',
        ],

        // Solid Lumber - Stain Grade
        [
            'name' => 'White Oak S4S 4/4',
            'reference' => 'LBR-WO-44',
            'tcs_material_slug' => 'stain_grade',
            'wood_species' => 'White Oak',
            'sheet_sqft_per_lf' => 0,
            'board_feet_per_lf' => 1.5,
            'is_box_material' => false,
            'is_face_frame_material' => true,
            'is_door_material' => true,
            'cost' => 12.00, // Per board foot
            'description' => 'Stain-grade face frame and door material - white oak',
            'thickness' => '4/4 (1")',
            'sheet_size' => null,
        ],
        [
            'name' => 'White Oak S4S 5/4',
            'reference' => 'LBR-WO-54',
            'tcs_material_slug' => 'stain_grade',
            'wood_species' => 'White Oak',
            'sheet_sqft_per_lf' => 0,
            'board_feet_per_lf' => 1.8,
            'is_box_material' => false,
            'is_face_frame_material' => true,
            'is_door_material' => true,
            'cost' => 14.00,
            'description' => 'Thick stain-grade face frame material - white oak',
            'thickness' => '5/4 (1-1/4")',
            'sheet_size' => null,
        ],
        [
            'name' => 'Red Oak S4S 4/4',
            'reference' => 'LBR-RO-44',
            'tcs_material_slug' => 'stain_grade',
            'wood_species' => 'Red Oak',
            'sheet_sqft_per_lf' => 0,
            'board_feet_per_lf' => 1.4,
            'is_box_material' => false,
            'is_face_frame_material' => true,
            'is_door_material' => true,
            'cost' => 9.50,
            'description' => 'Stain-grade face frame and door material - red oak',
            'thickness' => '4/4 (1")',
            'sheet_size' => null,
        ],
        [
            'name' => 'Cherry S4S 4/4',
            'reference' => 'LBR-CHERRY-44',
            'tcs_material_slug' => 'stain_grade',
            'wood_species' => 'Cherry',
            'sheet_sqft_per_lf' => 0,
            'board_feet_per_lf' => 1.4,
            'is_box_material' => false,
            'is_face_frame_material' => true,
            'is_door_material' => true,
            'cost' => 14.00,
            'description' => 'Stain-grade face frame and door material - cherry',
            'thickness' => '4/4 (1")',
            'sheet_size' => null,
        ],

        // ===========================================
        // PREMIUM MATERIALS ($192/LF extra)
        // ===========================================

        // Sheet Goods - Premium
        [
            'name' => 'Rift White Oak Plywood 3/4"',
            'reference' => 'PLY-RWO-34',
            'tcs_material_slug' => 'premium',
            'wood_species' => 'Rift White Oak',
            'sheet_sqft_per_lf' => 5.0,
            'board_feet_per_lf' => 0,
            'is_box_material' => true,
            'is_face_frame_material' => false,
            'is_door_material' => false,
            'cost' => 185.00,
            'description' => 'Premium cabinet box material - rift-cut white oak plywood',
            'thickness' => '3/4"',
            'sheet_size' => '4x8',
        ],
        [
            'name' => 'Rift White Oak Plywood 1/2"',
            'reference' => 'PLY-RWO-12',
            'tcs_material_slug' => 'premium',
            'wood_species' => 'Rift White Oak',
            'sheet_sqft_per_lf' => 3.5,
            'board_feet_per_lf' => 0,
            'is_box_material' => true,
            'is_face_frame_material' => false,
            'is_door_material' => false,
            'cost' => 145.00,
            'description' => 'Premium drawer box and shelf material - rift-cut white oak',
            'thickness' => '1/2"',
            'sheet_size' => '4x8',
        ],
        [
            'name' => 'Walnut Plywood 3/4"',
            'reference' => 'PLY-WAL-34',
            'tcs_material_slug' => 'premium',
            'wood_species' => 'Black Walnut',
            'sheet_sqft_per_lf' => 5.0,
            'board_feet_per_lf' => 0,
            'is_box_material' => true,
            'is_face_frame_material' => false,
            'is_door_material' => false,
            'cost' => 195.00,
            'description' => 'Premium cabinet box material - black walnut veneer plywood',
            'thickness' => '3/4"',
            'sheet_size' => '4x8',
        ],

        // Solid Lumber - Premium
        [
            'name' => 'Rift White Oak S4S 4/4',
            'reference' => 'LBR-RWO-44',
            'tcs_material_slug' => 'premium',
            'wood_species' => 'Rift White Oak',
            'sheet_sqft_per_lf' => 0,
            'board_feet_per_lf' => 2.0,
            'is_box_material' => false,
            'is_face_frame_material' => true,
            'is_door_material' => true,
            'cost' => 18.00, // Per board foot
            'description' => 'Premium face frame and door material - rift-cut white oak',
            'thickness' => '4/4 (1")',
            'sheet_size' => null,
        ],
        [
            'name' => 'Black Walnut S4S 4/4',
            'reference' => 'LBR-WAL-44',
            'tcs_material_slug' => 'premium',
            'wood_species' => 'Black Walnut',
            'sheet_sqft_per_lf' => 0,
            'board_feet_per_lf' => 2.0,
            'is_box_material' => false,
            'is_face_frame_material' => true,
            'is_door_material' => true,
            'cost' => 22.00,
            'description' => 'Premium face frame and door material - black walnut',
            'thickness' => '4/4 (1")',
            'sheet_size' => null,
        ],
        [
            'name' => 'Black Walnut S4S 5/4',
            'reference' => 'LBR-WAL-54',
            'tcs_material_slug' => 'premium',
            'wood_species' => 'Black Walnut',
            'sheet_sqft_per_lf' => 0,
            'board_feet_per_lf' => 2.5,
            'is_box_material' => false,
            'is_face_frame_material' => true,
            'is_door_material' => true,
            'cost' => 26.00,
            'description' => 'Thick premium face frame material - black walnut',
            'thickness' => '5/4 (1-1/4")',
            'sheet_size' => null,
        ],
        [
            'name' => 'Quarter Sawn White Oak S4S 4/4',
            'reference' => 'LBR-QSWO-44',
            'tcs_material_slug' => 'premium',
            'wood_species' => 'Quarter Sawn White Oak',
            'sheet_sqft_per_lf' => 0,
            'board_feet_per_lf' => 2.2,
            'is_box_material' => false,
            'is_face_frame_material' => true,
            'is_door_material' => true,
            'cost' => 20.00,
            'description' => 'Premium face frame material - quarter-sawn white oak with ray fleck',
            'thickness' => '4/4 (1")',
            'sheet_size' => null,
        ],

        // ===========================================
        // CUSTOM/EXOTIC MATERIALS ($240/LF extra)
        // ===========================================

        // Sheet Goods - Custom/Exotic
        [
            'name' => 'Sapele Plywood 3/4"',
            'reference' => 'PLY-SAPELE-34',
            'tcs_material_slug' => 'custom_exotic',
            'wood_species' => 'Sapele',
            'sheet_sqft_per_lf' => 5.5,
            'board_feet_per_lf' => 0,
            'is_box_material' => true,
            'is_face_frame_material' => false,
            'is_door_material' => false,
            'cost' => 225.00,
            'description' => 'Exotic cabinet box material - sapele veneer plywood',
            'thickness' => '3/4"',
            'sheet_size' => '4x8',
        ],
        [
            'name' => 'White Ash Plywood 3/4"',
            'reference' => 'PLY-ASH-34',
            'tcs_material_slug' => 'custom_exotic',
            'wood_species' => 'White Ash',
            'sheet_sqft_per_lf' => 5.0,
            'board_feet_per_lf' => 0,
            'is_box_material' => true,
            'is_face_frame_material' => false,
            'is_door_material' => false,
            'cost' => 175.00,
            'description' => 'Specialty cabinet box material - white ash veneer plywood',
            'thickness' => '3/4"',
            'sheet_size' => '4x8',
        ],

        // Solid Lumber - Custom/Exotic
        [
            'name' => 'Sapele S4S 4/4',
            'reference' => 'LBR-SAPELE-44',
            'tcs_material_slug' => 'custom_exotic',
            'wood_species' => 'Sapele',
            'sheet_sqft_per_lf' => 0,
            'board_feet_per_lf' => 2.5,
            'is_box_material' => false,
            'is_face_frame_material' => true,
            'is_door_material' => true,
            'cost' => 18.00, // Per board foot
            'description' => 'Exotic face frame and door material - sapele',
            'thickness' => '4/4 (1")',
            'sheet_size' => null,
        ],
        [
            'name' => 'White Ash S4S 4/4',
            'reference' => 'LBR-ASH-44',
            'tcs_material_slug' => 'custom_exotic',
            'wood_species' => 'White Ash',
            'sheet_sqft_per_lf' => 0,
            'board_feet_per_lf' => 2.0,
            'is_box_material' => false,
            'is_face_frame_material' => true,
            'is_door_material' => true,
            'cost' => 14.00,
            'description' => 'Specialty face frame and door material - white ash',
            'thickness' => '4/4 (1")',
            'sheet_size' => null,
        ],
        [
            'name' => 'Hickory S4S 4/4',
            'reference' => 'LBR-HICKORY-44',
            'tcs_material_slug' => 'custom_exotic',
            'wood_species' => 'Hickory',
            'sheet_sqft_per_lf' => 0,
            'board_feet_per_lf' => 2.2,
            'is_box_material' => false,
            'is_face_frame_material' => true,
            'is_door_material' => true,
            'cost' => 15.00,
            'description' => 'Specialty face frame and door material - hickory',
            'thickness' => '4/4 (1")',
            'sheet_size' => null,
        ],
        [
            'name' => 'Teak S4S 4/4',
            'reference' => 'LBR-TEAK-44',
            'tcs_material_slug' => 'custom_exotic',
            'wood_species' => 'Teak',
            'sheet_sqft_per_lf' => 0,
            'board_feet_per_lf' => 3.0,
            'is_box_material' => false,
            'is_face_frame_material' => true,
            'is_door_material' => true,
            'cost' => 35.00,
            'description' => 'Exotic face frame and door material - teak (marine grade)',
            'thickness' => '4/4 (1")',
            'sheet_size' => null,
        ],
    ];

    /**
     * Run the seeder
     */
    public function run(): void
    {
        $this->command->info('Starting TCS Sheet Goods & Lumber Import...');

        $companyId = $this->getCompanyId();
        $creatorId = $this->getCreatorId();
        $categoryId = $this->getOrCreateCategory('Sheet Goods & Lumber', $companyId);

        $imported = 0;
        $skipped = 0;

        foreach ($this->materials as $material) {
            // Check if product already exists
            $existing = DB::table('products_products')
                ->where('reference', $material['reference'])
                ->first();

            if ($existing) {
                $this->command->warn("Skipping existing product: {$material['reference']}");
                $skipped++;
                continue;
            }

            // Get or create appropriate UOM
            $uomId = $this->getUomForMaterial($material);

            // Build product data
            $productData = [
                'type' => 'goods',
                'name' => $material['name'],
                'reference' => $material['reference'],
                'cost' => $material['cost'],
                'price' => $material['cost'] * 1.25, // 25% markup
                'description' => $material['description'],
                'enable_sales' => false,
                'enable_purchase' => true,
                'is_storable' => true,
                'tracking' => 'lot',
                'uom_id' => $uomId,
                'uom_po_id' => $uomId,
                'category_id' => $categoryId,
                'company_id' => $companyId,
                'creator_id' => $creatorId,
                'created_at' => now(),
                'updated_at' => now(),
                // Store material specs for BOM calculations
                'material_notes' => json_encode([
                    'tcs_material_slug' => $material['tcs_material_slug'],
                    'wood_species' => $material['wood_species'],
                    'sheet_sqft_per_lf' => $material['sheet_sqft_per_lf'],
                    'board_feet_per_lf' => $material['board_feet_per_lf'],
                    'is_box_material' => $material['is_box_material'],
                    'is_face_frame_material' => $material['is_face_frame_material'],
                    'is_door_material' => $material['is_door_material'],
                    'thickness' => $material['thickness'] ?? null,
                    'sheet_size' => $material['sheet_size'] ?? null,
                ]),
            ];

            try {
                DB::table('products_products')->insert($productData);
                $imported++;
                $this->command->info("Imported: {$material['reference']} - {$material['name']} ({$material['tcs_material_slug']})");
            } catch (\Exception $e) {
                $this->command->error("Failed to import {$material['reference']}: " . $e->getMessage());
                Log::error("TcsSheetGoodsSeeder error", [
                    'reference' => $material['reference'],
                    'error' => $e->getMessage(),
                ]);
                $skipped++;
            }
        }

        $this->command->info("Sheet goods import complete: {$imported} imported, {$skipped} skipped");
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
     * Get or create product category
     */
    protected function getOrCreateCategory(string $categoryName, int $companyId): int
    {
        $category = DB::table('products_categories')
            ->where('name', $categoryName)
            ->first();

        if ($category) {
            return $category->id;
        }

        return DB::table('products_categories')->insertGetId([
            'name' => $categoryName,
            'full_name' => $categoryName,
            'creator_id' => $this->getCreatorId(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Get appropriate UOM for material type
     */
    protected function getUomForMaterial(array $material): int
    {
        // Sheet goods use "Sheet" UOM
        if ($material['sheet_sqft_per_lf'] > 0) {
            return $this->getOrCreateUom('Sheet', 'Unit');
        }

        // Lumber uses "Board Foot" UOM
        if ($material['board_feet_per_lf'] > 0) {
            return $this->getOrCreateUom('Board Foot', 'Volume');
        }

        // Default to Each
        return $this->getOrCreateUom('Each', 'Unit');
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
}
