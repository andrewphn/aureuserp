<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $userId = DB::table('users')->where('email', 'info@tcswoodwork.com')->value('id') ?? 1;

        // Main categories with their subcategories
        $categories = [
            'Hardware' => ['Hinges', 'Clips', 'Drawer Slides', 'Storage Systems'],
            'Fasteners' => ['Screws', 'Nails'],
            'Adhesives' => ['Glue', 'Epoxy', 'Edge Banding Adhesive'],
            'Sanding' => ['Discs', 'Sheets', 'Rolls', 'Various Grits'],
            'Edge Banding' => ['Wood Veneer', 'Unfinished'],
            'CNC' => ['Router Bits', 'Specialty Bits'],
            'Shop Supplies' => ['Dust Collection', 'Safety Equipment', 'Cleaning Supplies'],
            'Tools' => ['Drill Bits', 'Router Bits', 'Measurement Tools', 'CNC Parts'],
            'Maintenance' => ['Lubricants', 'Machine Oil', 'Grease'],
            'Blades' => ['Saw Blades', 'Planer Blades', 'Jointer Blades'],
            'Office Supplies' => ['Printer Cartridges', 'Paper Products', 'Writing Supplies', 'Computer Accessories'],
            'Shop Consumables' => ['Toilet Paper', 'Paper Towels', 'Cleaning Products', 'First Aid'],
        ];

        foreach ($categories as $mainCategory => $subcategories) {
            // Create main category - parent_path is just "/" for root level
            $parentId = DB::table('products_categories')->insertGetId([
                'name' => $mainCategory,
                'full_name' => $mainCategory,
                'parent_path' => '/',
                'parent_id' => null,
                'creator_id' => $userId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Create subcategories - parent_path includes only parent's ID, not its own
            foreach ($subcategories as $subcategory) {
                DB::table('products_categories')->insert([
                    'name' => $subcategory,
                    'full_name' => "{$mainCategory} / {$subcategory}",
                    'parent_path' => "/{$parentId}/",
                    'parent_id' => $parentId,
                    'creator_id' => $userId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove only the TCS categories we created
        DB::table('products_categories')
            ->whereIn('name', [
                'Hardware', 'Fasteners', 'Adhesives', 'Sanding', 'Edge Banding',
                'CNC', 'Shop Supplies', 'Tools', 'Maintenance', 'Blades',
                'Office Supplies', 'Shop Consumables',
                // Subcategories
                'Hinges', 'Clips', 'Drawer Slides', 'Storage Systems',
                'Screws', 'Nails',
                'Glue', 'Epoxy', 'Edge Banding Adhesive',
                'Discs', 'Sheets', 'Rolls', 'Various Grits',
                'Wood Veneer', 'Unfinished',
                'Router Bits', 'Specialty Bits',
                'Dust Collection', 'Safety Equipment', 'Cleaning Supplies',
                'Drill Bits', 'Measurement Tools', 'CNC Parts',
                'Lubricants', 'Machine Oil', 'Grease',
                'Saw Blades', 'Planer Blades', 'Jointer Blades',
                'Printer Cartridges', 'Paper Products', 'Writing Supplies', 'Computer Accessories',
                'Toilet Paper', 'Paper Towels', 'Cleaning Products', 'First Aid',
            ])
            ->delete();
    }
};