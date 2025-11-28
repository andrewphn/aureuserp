<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new /**
 * extends class
 *
 */
class extends Migration
{
    /**
     * Run the migrations.
     *
     * Adds Cabinet Type attribute with Base/Wall/Tall options
     */
    public function up(): void
    {
        $now = now();

        // Create Cabinet Type attribute
        DB::table('products_attributes')->insert([
            'name' => 'Cabinet Type',
            'type' => 'radio',
            'sort' => 1, // First attribute - most important choice
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        $cabinetTypeId = DB::getPdo()->lastInsertId();

        // Cabinet Type options (from cabinet-product-attributes-mapping.md)
        DB::table('products_attribute_options')->insert([
            // Base Cabinets
            ['name' => 'Base Standard (12-36" width)', 'extra_price' => 0, 'sort' => 1, 'attribute_id' => $cabinetTypeId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Base Sink (30-36" width)', 'extra_price' => 25, 'sort' => 2, 'attribute_id' => $cabinetTypeId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Base Drawer Stack (3-4 drawers)', 'extra_price' => 35, 'sort' => 3, 'attribute_id' => $cabinetTypeId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Base Cooktop (drop-in range)', 'extra_price' => 30, 'sort' => 4, 'attribute_id' => $cabinetTypeId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Base Corner Lazy Susan', 'extra_price' => 75, 'sort' => 5, 'attribute_id' => $cabinetTypeId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Base Corner Blind', 'extra_price' => 45, 'sort' => 6, 'attribute_id' => $cabinetTypeId, 'created_at' => $now, 'updated_at' => $now],

            // Wall Cabinets
            ['name' => 'Wall Standard (12-36" width, 12" depth)', 'extra_price' => -23, 'sort' => 10, 'attribute_id' => $cabinetTypeId, 'created_at' => $now, 'updated_at' => $now], // $145 vs $168 base
            ['name' => 'Wall Corner Diagonal', 'extra_price' => 12, 'sort' => 11, 'attribute_id' => $cabinetTypeId, 'created_at' => $now, 'updated_at' => $now], // $145 + $35 - $168 base
            ['name' => 'Wall Over Fridge (24" depth)', 'extra_price' => -8, 'sort' => 12, 'attribute_id' => $cabinetTypeId, 'created_at' => $now, 'updated_at' => $now], // $160 - $168

            // Tall Cabinets
            ['name' => 'Tall Pantry (84-96" height)', 'extra_price' => 50, 'sort' => 20, 'attribute_id' => $cabinetTypeId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Tall Oven (wall oven housing)', 'extra_price' => 60, 'sort' => 21, 'attribute_id' => $cabinetTypeId, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Tall Utility (broom storage)', 'extra_price' => 35, 'sort' => 22, 'attribute_id' => $cabinetTypeId, 'created_at' => $now, 'updated_at' => $now],
        ]);

        echo "✓ Added Cabinet Type attribute with 12 options\n";
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $cabinetTypeId = DB::table('products_attributes')
            ->where('name', 'Cabinet Type')
            ->value('id');

        if ($cabinetTypeId) {
            DB::table('products_attribute_options')
                ->where('attribute_id', $cabinetTypeId)
                ->delete();

            DB::table('products_attributes')
                ->where('id', $cabinetTypeId)
                ->delete();
        }
    }
};
