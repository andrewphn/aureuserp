<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Skip if products table doesn't exist yet (plugin not installed)
        if (!Schema::hasTable('products_products')) {
            return;
        }

        $now = now();

        $cabinetProduct = DB::table('products_products')
            ->where('reference', 'CABINET')
            ->first();
            
        if (!$cabinetProduct) {
            echo "Cabinet product not found!\n";
            return;
        }
        
        echo "Found Cabinet product (ID: {$cabinetProduct->id})\n";
        
        $productAttributes = DB::table('products_product_attributes')
            ->where('product_id', $cabinetProduct->id)
            ->get();
            
        echo "Found " . $productAttributes->count() . " attributes\n";
        
        $valuesToInsert = [];
        
        foreach ($productAttributes as $productAttr) {
            $options = DB::table('products_attribute_options')
                ->where('attribute_id', $productAttr->attribute_id)
                ->get();
                
            $attribute = DB::table('products_attributes')
                ->where('id', $productAttr->attribute_id)
                ->first();
                
            echo "  - {$attribute->name}: " . $options->count() . " options\n";
            
            foreach ($options as $option) {
                $exists = DB::table('products_product_attribute_values')
                    ->where('product_id', $cabinetProduct->id)
                    ->where('attribute_id', $productAttr->attribute_id)
                    ->where('attribute_option_id', $option->id)
                    ->exists();
                    
                if (!$exists) {
                    $valuesToInsert[] = [
                        'product_id' => $cabinetProduct->id,
                        'attribute_id' => $productAttr->attribute_id,
                        'product_attribute_id' => $productAttr->id,
                        'attribute_option_id' => $option->id,
                        'extra_price' => $option->extra_price ?? 0,
                    ];
                }
            }
        }
        
        if (!empty($valuesToInsert)) {
            DB::table('products_product_attribute_values')->insert($valuesToInsert);
            echo "\n✓ Inserted " . count($valuesToInsert) . " attribute values\n";
        } else {
            echo "\n✓ All attribute values already populated\n";
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('products_products')) {
            return;
        }

        $cabinetProduct = DB::table('products_products')
            ->where('reference', 'CABINET')
            ->first();
            
        if ($cabinetProduct) {
            DB::table('products_product_attribute_values')
                ->where('product_id', $cabinetProduct->id)
                ->delete();
        }
    }
};
