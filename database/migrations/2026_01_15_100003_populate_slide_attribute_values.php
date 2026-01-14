<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Blum Tandem 563H slide specifications.
     * Based on manufacturer specifications for full-extension concealed slides.
     */
    private array $slideSpecs = [
        // 15" slide (563H3810B)
        23530 => [
            'Slide Length' => 15.0,
            'Min Cabinet Depth' => 16.0,
            'Weight Capacity' => 90,
            'Slide Side Clearance' => 0.5,      // 12.7mm per side
            'Slide Top Clearance' => 0.75,      // 19mm
            'Slide Bottom Clearance' => 0.5,    // 12.7mm
            'Slide Rear Clearance' => 0.5,      // 12.7mm for rear bracket
        ],
        // 18" slide (563H4570B)
        23528 => [
            'Slide Length' => 18.0,
            'Min Cabinet Depth' => 19.0,
            'Weight Capacity' => 90,
            'Slide Side Clearance' => 0.5,
            'Slide Top Clearance' => 0.75,
            'Slide Bottom Clearance' => 0.5,
            'Slide Rear Clearance' => 0.5,
        ],
        // 21" slide (563H5330B)
        23529 => [
            'Slide Length' => 21.0,
            'Min Cabinet Depth' => 22.0,
            'Weight Capacity' => 90,
            'Slide Side Clearance' => 0.5,
            'Slide Top Clearance' => 0.75,
            'Slide Bottom Clearance' => 0.5,
            'Slide Rear Clearance' => 0.5,
        ],
    ];

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Get attribute IDs by name
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

        foreach ($this->slideSpecs as $productId => $specs) {
            // Verify product exists
            $product = DB::table('products_products')->find($productId);
            if (!$product) {
                continue;
            }

            foreach ($specs as $attrName => $value) {
                $attrId = $attributes[$attrName] ?? null;
                if (!$attrId) {
                    continue;
                }

                // Check if product_attribute link exists, create if not
                $productAttr = DB::table('products_product_attributes')
                    ->where('product_id', $productId)
                    ->where('attribute_id', $attrId)
                    ->first();

                if (!$productAttr) {
                    $productAttrId = DB::table('products_product_attributes')->insertGetId([
                        'product_id' => $productId,
                        'attribute_id' => $attrId,
                    ]);
                } else {
                    $productAttrId = $productAttr->id;
                }

                // Check if value already exists
                $existingValue = DB::table('products_product_attribute_values')
                    ->where('product_id', $productId)
                    ->where('attribute_id', $attrId)
                    ->first();

                if ($existingValue) {
                    // Update existing value
                    DB::table('products_product_attribute_values')
                        ->where('id', $existingValue->id)
                        ->update([
                            'numeric_value' => $value,
                            'product_attribute_id' => $productAttrId,
                        ]);
                } else {
                    // Insert new value
                    DB::table('products_product_attribute_values')->insert([
                        'product_id' => $productId,
                        'attribute_id' => $attrId,
                        'product_attribute_id' => $productAttrId,
                        'numeric_value' => $value,
                        'extra_price' => 0,
                    ]);
                }
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $productIds = array_keys($this->slideSpecs);

        // Get the attribute IDs
        $attrIds = DB::table('products_attributes')
            ->whereIn('name', [
                'Slide Length',
                'Min Cabinet Depth',
                'Weight Capacity',
                'Slide Side Clearance',
                'Slide Top Clearance',
                'Slide Bottom Clearance',
                'Slide Rear Clearance',
            ])
            ->pluck('id');

        // Remove attribute values
        DB::table('products_product_attribute_values')
            ->whereIn('product_id', $productIds)
            ->whereIn('attribute_id', $attrIds)
            ->delete();

        // Remove product-attribute links
        DB::table('products_product_attributes')
            ->whereIn('product_id', $productIds)
            ->whereIn('attribute_id', $attrIds)
            ->delete();
    }
};
