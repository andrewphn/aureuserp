<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $userId = DB::table('users')->where('email', 'info@tcswoodwork.com')->value('id') ?? 1;

        $tags = [
            // Supplier Tags
            ['name' => 'Richelieu', 'color' => '#3B82F6'],
            ['name' => 'Serious Grit', 'color' => '#EF4444'],
            ['name' => 'Amana Tool', 'color' => '#10B981'],
            ['name' => 'YUEERIO', 'color' => '#F59E0B'],
            ['name' => 'Felder', 'color' => '#8B5CF6'],
            ['name' => 'Festool', 'color' => '#06B6D4'],
            ['name' => 'Amazon', 'color' => '#FF9900'],

            // Product Type Tags
            ['name' => 'Hinge', 'color' => '#6366F1'],
            ['name' => 'Clip', 'color' => '#EC4899'],
            ['name' => 'Drawer Slide', 'color' => '#14B8A6'],
            ['name' => 'Screw', 'color' => '#F97316'],
            ['name' => 'Nail', 'color' => '#84CC16'],
            ['name' => 'Glue', 'color' => '#22D3EE'],
            ['name' => 'Epoxy', 'color' => '#A855F7'],
            ['name' => 'Sanding Disc', 'color' => '#F43F5E'],
            ['name' => 'Sanding Sheet', 'color' => '#0EA5E9'],
            ['name' => 'Sanding Roll', 'color' => '#8B5CF6'],
            ['name' => 'Edge Banding', 'color' => '#D97706'],
            ['name' => 'Router Bit', 'color' => '#DC2626'],
            ['name' => 'Drill Bit', 'color' => '#059669'],
            ['name' => 'Saw Blade', 'color' => '#7C3AED'],
            ['name' => 'Planer Blade', 'color' => '#DB2777'],
            ['name' => 'Jointer Blade', 'color' => '#2563EB'],
            ['name' => 'CNC Tool', 'color' => '#9333EA'],

            // Status/Characteristic Tags
            ['name' => 'Consumable', 'color' => '#EAB308'],
            ['name' => 'Reorderable', 'color' => '#22C55E'],
            ['name' => 'High Priority', 'color' => '#EF4444'],
            ['name' => 'Low Stock', 'color' => '#F97316'],
            ['name' => 'Discontinued', 'color' => '#6B7280'],
            ['name' => 'Bulk Item', 'color' => '#0891B2'],

            // Material Tags
            ['name' => 'Metal', 'color' => '#64748B'],
            ['name' => 'Wood', 'color' => '#92400E'],
            ['name' => 'Plastic', 'color' => '#06B6D4'],
            ['name' => 'Composite', 'color' => '#7C3AED'],
            ['name' => 'Steel', 'color' => '#475569'],
            ['name' => 'Aluminum', 'color' => '#94A3B8'],
            ['name' => 'Brass', 'color' => '#D97706'],

            // Application Tags
            ['name' => 'Cabinet Hardware', 'color' => '#4F46E5'],
            ['name' => 'Finishing', 'color' => '#8B5CF6'],
            ['name' => 'Woodworking', 'color' => '#78350F'],
            ['name' => 'CNC', 'color' => '#9333EA'],
            ['name' => 'Maintenance', 'color' => '#0891B2'],
            ['name' => 'Office Supplies', 'color' => '#3B82F6'],
            ['name' => 'Shop Supplies', 'color' => '#10B981'],
            ['name' => 'Safety Equipment', 'color' => '#EF4444'],
            ['name' => 'Dust Collection', 'color' => '#6B7280'],
        ];

        foreach ($tags as $tag) {
            // Check if tag already exists
            $exists = DB::table('products_tags')->where('name', $tag['name'])->exists();

            if (!$exists) {
                DB::table('products_tags')->insert([
                    'name' => $tag['name'],
                    'color' => $tag['color'],
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
        $tagNames = [
            'Richelieu', 'Serious Grit', 'Amana Tool', 'YUEERIO', 'Felder', 'Festool', 'Amazon',
            'Hinge', 'Clip', 'Drawer Slide', 'Screw', 'Nail', 'Glue', 'Epoxy',
            'Sanding Disc', 'Sanding Sheet', 'Sanding Roll', 'Edge Banding',
            'Router Bit', 'Drill Bit', 'Saw Blade', 'Planer Blade', 'Jointer Blade', 'CNC Tool',
            'Consumable', 'Reorderable', 'High Priority', 'Low Stock', 'Discontinued', 'Bulk Item',
            'Metal', 'Wood', 'Plastic', 'Composite', 'Steel', 'Aluminum', 'Brass',
            'Cabinet Hardware', 'Finishing', 'Woodworking', 'CNC', 'Maintenance',
            'Office Supplies', 'Shop Supplies', 'Safety Equipment', 'Dust Collection',
        ];

        DB::table('products_tags')->whereIn('name', $tagNames)->delete();
    }
};