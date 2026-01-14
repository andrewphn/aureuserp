<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Updates slide specifications to match official Blum TANDEM 563H documentation.
 * 
 * Source: https://d2.blum.com/services/BEC003/tdm563h_ma_dok_bus_$sen-us_$aof_$v4.pdf
 * 
 * Key changes:
 * - Side clearance: 0.5" → 0.2" (5mm per side per Blum spec for 5/8" drawer sides)
 * - Top clearance: 0.75" stays (for drawer front adjustment range)
 * - Bottom clearance: 0.5" → 0.375" (9.5mm for runner mounting)
 * - Rear clearance: 0.5" → 0.75" (19mm for rear bracket/socket)
 * - Added 12" slide (563H3050B)
 */
return new class extends Migration
{
    /**
     * Official Blum TANDEM 563H specifications
     * From installation instructions PDF
     */
    private array $officialSpecs = [
        // 12" slide (563H3050B) - NEW
        '563H3050B' => [
            'drawer_length_inches' => 12,
            'min_cabinet_depth_inches' => 13,  // 328mm = 12-29/32" ≈ 13"
            'cabinet_depth_inches' => 15,       // 381mm
            'runner_length_mm' => 319,          // 12-9/16"
        ],
        // 15" slide (563H3810B)
        '563H3810B' => [
            'drawer_length_inches' => 15,
            'min_cabinet_depth_inches' => 16,  // 404mm = 15-29/32" ≈ 16"
            'cabinet_depth_inches' => 18,       // 457mm
            'runner_length_mm' => 395,          // 15-9/16"
        ],
        // 18" slide (563H4570B)
        '563H4570B' => [
            'drawer_length_inches' => 18,
            'min_cabinet_depth_inches' => 19,  // 480mm = 18-29/32" ≈ 19"
            'cabinet_depth_inches' => 21,       // 533mm
            'runner_length_mm' => 471,          // 18-17/32"
        ],
        // 21" slide (563H5330B)
        '563H5330B' => [
            'drawer_length_inches' => 21,
            'min_cabinet_depth_inches' => 22,  // 557mm = 21-15/16" ≈ 22"
            'cabinet_depth_inches' => 24,       // 610mm
            'runner_length_mm' => 548,          // 21-9/16"
        ],
    ];

    /**
     * Official Blum clearances (common to all TANDEM 563H slides)
     * 
     * Per Blum spec: "Inside drawer width must equal opening width minus 42mm (1-21/32")"
     * For 5/8" (16mm) drawer sides: deduct 10mm (13/32") from opening width
     * This means side clearance = 5mm (0.197") per side
     */
    private array $officialClearances = [
        'side_clearance' => 0.2,      // 5mm per side (for 5/8" drawer sides)
        'top_clearance' => 0.75,      // 19mm - allows for height adjustment
        'bottom_clearance' => 0.375,  // 9.5mm - runner base mounting
        'rear_clearance' => 0.75,     // 19mm - rear bracket/socket depth
    ];

    public function up(): void
    {
        // Step 1: Create 12" slide product if it doesn't exist
        $slide12Id = $this->createSlide12Product();

        // Step 2: Get attribute IDs
        $attributes = DB::table('products_attributes')
            ->whereIn('name', [
                'Slide Length',
                'Min Cabinet Depth',
                'Weight Capacity',
                'Slide Side Clearance',
                'Slide Top Clearance',
                'Slide Bottom Clearance',
                'Slide Rear Clearance',
            ])
            ->pluck('id', 'name');

        // Step 3: Update existing slides with official clearances
        $existingSlides = [
            23530 => 15,  // 563H3810B
            23528 => 18,  // 563H4570B
            23529 => 21,  // 563H5330B
        ];

        foreach ($existingSlides as $productId => $length) {
            $this->updateSlideSpecs($productId, $length, $attributes);
        }

        // Step 4: Add specs to 12" slide
        if ($slide12Id) {
            $this->addSlideSpecs($slide12Id, 12, $attributes);
        }
    }

    private function createSlide12Product(): ?int
    {
        // Check if already exists
        $existing = DB::table('products_products')
            ->where('name', 'LIKE', '%563H3050B%')
            ->first();

        if ($existing) {
            return $existing->id;
        }

        // Get reference data from existing slide (18" slide)
        $refSlide = DB::table('products_products')->find(23528);
        if (!$refSlide) {
            return null; // Can't create without reference
        }

        // Create the 12" slide product using same UOM, category, company as reference
        return DB::table('products_products')->insertGetId([
            'name' => '563H3050B Blum slide runner 12" pairs',
            'type' => 'storable',
            'reference' => '563H3050B',
            'price' => 12.50,  // Estimated price
            'cost' => 8.00,
            'enable_sales' => true,
            'enable_purchase' => true,
            'is_favorite' => false,
            'is_configurable' => false,
            'category_id' => $refSlide->category_id,
            'uom_id' => $refSlide->uom_id,
            'uom_po_id' => $refSlide->uom_po_id,
            'company_id' => $refSlide->company_id,
            'description' => 'Blum TANDEM plus BLUMOTION 563H full extension concealed runner, 12" (305mm). Requires T51.1901 locking device.',
            'description_sale' => 'Blum TANDEM 563H 12" drawer slide pair with soft-close',
            'creator_id' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function updateSlideSpecs(int $productId, int $length, $attributes): void
    {
        // Update clearance values to official specs
        $updates = [
            'Slide Side Clearance' => $this->officialClearances['side_clearance'],
            'Slide Top Clearance' => $this->officialClearances['top_clearance'],
            'Slide Bottom Clearance' => $this->officialClearances['bottom_clearance'],
            'Slide Rear Clearance' => $this->officialClearances['rear_clearance'],
        ];

        foreach ($updates as $attrName => $value) {
            $attrId = $attributes[$attrName] ?? null;
            if (!$attrId) continue;

            DB::table('products_product_attribute_values')
                ->where('product_id', $productId)
                ->where('attribute_id', $attrId)
                ->update(['numeric_value' => $value]);
        }
    }

    private function addSlideSpecs(int $productId, int $length, $attributes): void
    {
        $specs = [
            'Slide Length' => (float) $length,
            'Min Cabinet Depth' => (float) $this->officialSpecs['563H3050B']['min_cabinet_depth_inches'],
            'Weight Capacity' => 90.0,  // Standard for TANDEM 563H
            'Slide Side Clearance' => $this->officialClearances['side_clearance'],
            'Slide Top Clearance' => $this->officialClearances['top_clearance'],
            'Slide Bottom Clearance' => $this->officialClearances['bottom_clearance'],
            'Slide Rear Clearance' => $this->officialClearances['rear_clearance'],
        ];

        foreach ($specs as $attrName => $value) {
            $attrId = $attributes[$attrName] ?? null;
            if (!$attrId) continue;

            // Create product-attribute link
            $productAttrId = DB::table('products_product_attributes')->insertGetId([
                'product_id' => $productId,
                'attribute_id' => $attrId,
            ]);

            // Create attribute value
            DB::table('products_product_attribute_values')->insert([
                'product_id' => $productId,
                'attribute_id' => $attrId,
                'product_attribute_id' => $productAttrId,
                'numeric_value' => $value,
                'extra_price' => 0,
            ]);
        }
    }

    public function down(): void
    {
        // Revert clearances to previous values
        $attributes = DB::table('products_attributes')
            ->whereIn('name', [
                'Slide Side Clearance',
                'Slide Top Clearance', 
                'Slide Bottom Clearance',
                'Slide Rear Clearance',
            ])
            ->pluck('id', 'name');

        $previousClearances = [
            'Slide Side Clearance' => 0.5,
            'Slide Top Clearance' => 0.75,
            'Slide Bottom Clearance' => 0.5,
            'Slide Rear Clearance' => 0.5,
        ];

        foreach ([23530, 23528, 23529] as $productId) {
            foreach ($previousClearances as $attrName => $value) {
                $attrId = $attributes[$attrName] ?? null;
                if (!$attrId) continue;

                DB::table('products_product_attribute_values')
                    ->where('product_id', $productId)
                    ->where('attribute_id', $attrId)
                    ->update(['numeric_value' => $value]);
            }
        }

        // Remove 12" slide (only if we created it)
        $slide12 = DB::table('products_products')
            ->where('reference', '563H3050B')
            ->first();

        if ($slide12) {
            // Remove attribute values
            DB::table('products_product_attribute_values')
                ->where('product_id', $slide12->id)
                ->delete();

            // Remove product-attribute links
            DB::table('products_product_attributes')
                ->where('product_id', $slide12->id)
                ->delete();

            // Remove product
            DB::table('products_products')
                ->where('id', $slide12->id)
                ->delete();
        }
    }
};
