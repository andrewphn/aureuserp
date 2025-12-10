<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Category mapping based on existing product references
     */
    private array $categoryMap = [
        53 => ['name' => 'Adhesives', 'code' => 'ADH'],
        54 => ['name' => 'Blades', 'code' => 'BLADE'],
        55 => ['name' => 'CNC Bits', 'code' => 'CNC'],
        56 => ['name' => 'Edgebanding', 'code' => 'EDGE'],
        57 => ['name' => 'Fasteners', 'code' => 'FAST'],
        58 => ['name' => 'Hardware', 'code' => 'HW'],
        59 => ['name' => 'Maintenance', 'code' => 'MAINT'],
        60 => ['name' => 'Sandpaper', 'code' => 'SAND'],
        61 => ['name' => 'Shop Supplies', 'code' => 'SHOP'],
        62 => ['name' => 'Tools', 'code' => 'TOOL'],
    ];

    /**
     * Type code mapping based on existing references
     */
    private array $typeCodeMap = [
        'ADH' => [
            'EPOXY' => 'Epoxy',
            'GLUE' => 'Glue',
            'PELLET' => 'Pellets',
        ],
        'BLADE' => [
            'SAW' => 'Saw Blade',
        ],
        'CNC' => [
            'BIT' => 'Router Bit',
        ],
        'EDGE' => [
            'WOOD' => 'Wood Veneer',
        ],
        'FAST' => [
            'KREG' => 'Kreg Screws',
            'NAIL' => 'Nails',
            'SCREW' => 'Screws',
        ],
        'HW' => [
            'HINGE' => 'Hinges',
            'LEMANS' => 'LeMans System',
            'PADDLE' => 'Drawer Paddles',
            'PLATE' => 'Plates',
            'SLIDE' => 'Drawer Slides',
            'TRASH' => 'Trash Systems',
        ],
        'MAINT' => [
            'LUB' => 'Lubricant',
            'OIL' => 'Oil',
        ],
        'SAND' => [
            'DISC' => 'Sanding Discs',
            'GRIT' => 'Grit Paper',
            'ROLL' => 'Sanding Rolls',
            'SHEET' => 'Sanding Sheets',
        ],
        'SHOP' => [
            'DUST' => 'Dust Collection',
        ],
        'TOOL' => [
            'BIT' => 'Drill Bits',
            'CAL' => 'Calibration',
            'ROUTE' => 'Router',
        ],
    ];

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // First, create missing categories
        foreach ($this->categoryMap as $id => $data) {
            $exists = DB::table('products_categories')->where('id', $id)->exists();

            if (!$exists) {
                // Get the "All" category as parent
                $parentId = DB::table('products_categories')->where('name', 'All')->value('id') ?? 1;

                DB::table('products_categories')->insert([
                    'id' => $id,
                    'name' => $data['name'],
                    'code' => $data['code'],
                    'full_name' => 'All / ' . $data['name'],
                    'parent_path' => '/' . $parentId . '/',
                    'parent_id' => $parentId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            } else {
                // Just update the code
                DB::table('products_categories')
                    ->where('id', $id)
                    ->update(['code' => $data['code']]);
            }
        }

        // Also update existing "Consumable" category with ADH code if it's category 2
        DB::table('products_categories')
            ->where('id', 2)
            ->whereNull('code')
            ->update(['code' => 'CONS']);

        // Create type codes for each category
        foreach ($this->typeCodeMap as $catCode => $types) {
            $categoryId = DB::table('products_categories')
                ->where('code', $catCode)
                ->value('id');

            if (!$categoryId) {
                continue;
            }

            foreach ($types as $code => $name) {
                $exists = DB::table('products_reference_type_codes')
                    ->where('category_id', $categoryId)
                    ->where('code', $code)
                    ->exists();

                if (!$exists) {
                    DB::table('products_reference_type_codes')->insert([
                        'code' => $code,
                        'name' => $name,
                        'category_id' => $categoryId,
                        'is_active' => true,
                        'sort' => 0,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }
        }

        // Update existing products with reference_type_code_id based on their reference
        $products = DB::table('products_products')
            ->whereNotNull('reference')
            ->where('reference', 'like', 'TCS-%')
            ->get(['id', 'reference', 'category_id']);

        foreach ($products as $product) {
            // Parse reference: TCS-CAT-TYPE-##
            $parts = explode('-', $product->reference);
            if (count($parts) >= 3) {
                $typeCode = $parts[2];

                $typeCodeId = DB::table('products_reference_type_codes')
                    ->where('category_id', $product->category_id)
                    ->where('code', $typeCode)
                    ->value('id');

                if ($typeCodeId) {
                    DB::table('products_products')
                        ->where('id', $product->id)
                        ->update([
                            'reference_type_code_id' => $typeCodeId,
                            'type_code' => $typeCode,
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
        // Remove the type codes
        DB::table('products_reference_type_codes')->truncate();

        // Clear the codes from categories
        DB::table('products_categories')->update(['code' => null]);

        // Remove category IDs 53-62 if they were created by this migration
        DB::table('products_categories')
            ->whereIn('id', array_keys($this->categoryMap))
            ->delete();

        // Clear product type code references
        DB::table('products_products')
            ->whereNotNull('reference_type_code_id')
            ->update([
                'reference_type_code_id' => null,
                'type_code' => null,
            ]);
    }
};
