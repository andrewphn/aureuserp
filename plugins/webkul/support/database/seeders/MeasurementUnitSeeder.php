<?php

namespace Webkul\Support\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class MeasurementUnitSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $units = [
            // Base unit
            [
                'unit_code' => 'in',
                'unit_name' => 'inches',
                'unit_symbol' => 'in',
                'unit_type' => 'linear',
                'conversion_factor' => 1.0,
                'is_base_unit' => true,
                'display_order' => 1,
                'is_active' => true,
                'description' => 'Base unit - all measurements stored in inches',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            // Imperial units
            [
                'unit_code' => 'ft',
                'unit_name' => 'feet',
                'unit_symbol' => 'ft',
                'unit_type' => 'linear',
                'conversion_factor' => 12.0, // 1 foot = 12 inches
                'is_base_unit' => false,
                'display_order' => 2,
                'is_active' => true,
                'description' => '1 foot = 12 inches',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'unit_code' => 'yd',
                'unit_name' => 'yards',
                'unit_symbol' => 'yd',
                'unit_type' => 'linear',
                'conversion_factor' => 36.0, // 1 yard = 36 inches
                'is_base_unit' => false,
                'display_order' => 3,
                'is_active' => true,
                'description' => '1 yard = 36 inches',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            // Metric units
            [
                'unit_code' => 'mm',
                'unit_name' => 'millimeters',
                'unit_symbol' => 'mm',
                'unit_type' => 'linear',
                'conversion_factor' => 0.0393701, // 1 mm = 0.0393701 inches (1/25.4)
                'is_base_unit' => false,
                'display_order' => 4,
                'is_active' => true,
                'description' => '1 millimeter = 0.0393701 inches',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'unit_code' => 'cm',
                'unit_name' => 'centimeters',
                'unit_symbol' => 'cm',
                'unit_type' => 'linear',
                'conversion_factor' => 0.393701, // 1 cm = 0.393701 inches (1/2.54)
                'is_base_unit' => false,
                'display_order' => 5,
                'is_active' => true,
                'description' => '1 centimeter = 0.393701 inches',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'unit_code' => 'm',
                'unit_name' => 'meters',
                'unit_symbol' => 'm',
                'unit_type' => 'linear',
                'conversion_factor' => 39.3701, // 1 m = 39.3701 inches (1/0.0254)
                'is_base_unit' => false,
                'display_order' => 6,
                'is_active' => true,
                'description' => '1 meter = 39.3701 inches',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        foreach ($units as $unit) {
            DB::table('measurements')->updateOrInsert(
                ['unit_code' => $unit['unit_code']],
                $unit
            );
        }
    }
}
