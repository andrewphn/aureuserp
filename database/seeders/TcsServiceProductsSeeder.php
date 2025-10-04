<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Webkul\Product\Models\Product;
use Webkul\Product\Models\ProductCategory;

class TcsServiceProductsSeeder extends Seeder
{
    /**
     * Run the database seeders.
     *
     * Based on TCS Wholesale Pricing Sheets (Jan 2025)
     */
    public function run(): void
    {
        // Get or create "Services" category
        $servicesCategory = ProductCategory::firstOrCreate(
            ['name' => 'Woodwork Services'],
            ['name' => 'Woodwork Services', 'creator_id' => 1]
        );

        // Get TCS company
        $company = DB::table('companies')->where('name', 'The Carpenter\'s Son')->first();
        if (!$company) {
            $company = DB::table('companies')->where('acronym', 'TCS')->first();
        }

        $now = now();

        $services = [
            // CABINET PRICING - Base Levels
            [
                'name' => 'Cabinet Level 1 - Open Boxes',
                'description' => 'Paint grade construction, open cabinet boxes only, no doors or drawers. Ideal for utility spaces.',
                'type' => 'service',
                'price' => 138.00,
                'cost' => 0,
                'reference' => 'CAB-L1',
                'category_id' => $servicesCategory->id,
                'company_id' => $company->id ?? 1,
                'creator_id' => 1,
                'unit' => 'linear_foot',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'Cabinet Level 2 - Semi European',
                'description' => 'Paint grade construction, semi European style, flat or shaker doors.',
                'type' => 'service',
                'price' => 168.00,
                'cost' => 0,
                'reference' => 'CAB-L2',
                'category_id' => $servicesCategory->id,
                'company_id' => $company->id ?? 1,
                'creator_id' => 1,
                'unit' => 'linear_foot',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'Cabinet Level 3 - Stain Grade',
                'description' => 'Stain grade construction, semi complicated paint grade, enhanced details.',
                'type' => 'service',
                'price' => 192.00,
                'cost' => 0,
                'reference' => 'CAB-L3',
                'category_id' => $servicesCategory->id,
                'company_id' => $company->id ?? 1,
                'creator_id' => 1,
                'unit' => 'linear_foot',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'Cabinet Level 4 - Enhanced Details',
                'description' => 'Beaded face frames, specialty doors, added mouldings.',
                'type' => 'service',
                'price' => 210.00,
                'cost' => 0,
                'reference' => 'CAB-L4',
                'category_id' => $servicesCategory->id,
                'company_id' => $company->id ?? 1,
                'creator_id' => 1,
                'unit' => 'linear_foot',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'Cabinet Level 5 - Custom Work',
                'description' => 'Unique, custom work - paneling, reeded, rattan, complex details.',
                'type' => 'service',
                'price' => 225.00,
                'cost' => 0,
                'reference' => 'CAB-L5',
                'category_id' => $servicesCategory->id,
                'company_id' => $company->id ?? 1,
                'creator_id' => 1,
                'unit' => 'linear_foot',
                'created_at' => $now,
                'updated_at' => $now,
            ],

            // MATERIAL CATEGORY UPGRADES
            [
                'name' => 'Material Upgrade - Paint Grade',
                'description' => 'Hard Maple or Poplar construction. Add to base cabinet price.',
                'type' => 'service',
                'price' => 138.00,
                'cost' => 0,
                'reference' => 'MAT-PAINT',
                'category_id' => $servicesCategory->id,
                'company_id' => $company->id ?? 1,
                'creator_id' => 1,
                'unit' => 'linear_foot',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'Material Upgrade - Stain Grade',
                'description' => 'Oak or Maple construction. Add to base cabinet price.',
                'type' => 'service',
                'price' => 156.00,
                'cost' => 0,
                'reference' => 'MAT-STAIN',
                'category_id' => $servicesCategory->id,
                'company_id' => $company->id ?? 1,
                'creator_id' => 1,
                'unit' => 'linear_foot',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'Material Upgrade - Premium',
                'description' => 'Rifted White Oak or Black Walnut. Add to base cabinet price.',
                'type' => 'service',
                'price' => 185.00,
                'cost' => 0,
                'reference' => 'MAT-PREMIUM',
                'category_id' => $servicesCategory->id,
                'company_id' => $company->id ?? 1,
                'creator_id' => 1,
                'unit' => 'linear_foot',
                'created_at' => $now,
                'updated_at' => $now,
            ],

            // CLOSET SYSTEMS
            [
                'name' => 'Closet System - Paint Grade',
                'description' => 'Includes labor ($92/LF) + paint grade materials ($75.44/LF). Hardware not included.',
                'type' => 'service',
                'price' => 167.44,
                'cost' => 75.44,
                'reference' => 'CLOSET-PAINT',
                'category_id' => $servicesCategory->id,
                'company_id' => $company->id ?? 1,
                'creator_id' => 1,
                'unit' => 'linear_foot',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'Closet System - Stain Grade',
                'description' => 'Includes labor ($92/LF) + stain grade materials ($96.38/LF). Hardware not included.',
                'type' => 'service',
                'price' => 188.38,
                'cost' => 96.38,
                'reference' => 'CLOSET-STAIN',
                'category_id' => $servicesCategory->id,
                'company_id' => $company->id ?? 1,
                'creator_id' => 1,
                'unit' => 'linear_foot',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'Closet Shelf & Rod',
                'description' => 'Paint grade only, wood only - no hardware included.',
                'type' => 'service',
                'price' => 28.00,
                'cost' => 0,
                'reference' => 'CLOSET-SHELF',
                'category_id' => $servicesCategory->id,
                'company_id' => $company->id ?? 1,
                'creator_id' => 1,
                'unit' => 'linear_foot',
                'created_at' => $now,
                'updated_at' => $now,
            ],

            // FLOATING SHELVES
            [
                'name' => 'Floating Shelf - Paint Grade',
                'description' => 'Hard Maple or Poplar, 1.75" thick solid wood, ready for mounting. Wood only - no hardware. Standard: 1.75" × 10" × up to 120" long.',
                'type' => 'service',
                'price' => 18.00,
                'cost' => 0,
                'reference' => 'SHELF-PAINT',
                'category_id' => $servicesCategory->id,
                'company_id' => $company->id ?? 1,
                'creator_id' => 1,
                'unit' => 'linear_foot',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'Floating Shelf - Premium',
                'description' => 'White Oak or Walnut, premium hardwood, ready for mounting. Wood only - no hardware. Standard: 1.75" × 10" × up to 120" long.',
                'type' => 'service',
                'price' => 24.00,
                'cost' => 0,
                'reference' => 'SHELF-PREMIUM',
                'category_id' => $servicesCategory->id,
                'company_id' => $company->id ?? 1,
                'creator_id' => 1,
                'unit' => 'linear_foot',
                'created_at' => $now,
                'updated_at' => $now,
            ],

            // CUSTOMIZATIONS
            [
                'name' => 'Shelf Customization - Custom Depth',
                'description' => 'Additional charge for custom shelf depths beyond standard 10".',
                'type' => 'service',
                'price' => 3.00,
                'cost' => 0,
                'reference' => 'CUSTOM-DEPTH',
                'category_id' => $servicesCategory->id,
                'company_id' => $company->id ?? 1,
                'creator_id' => 1,
                'unit' => 'linear_foot',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'Shelf Customization - Extended Length',
                'description' => 'Additional charge for shelves over 120" in length.',
                'type' => 'service',
                'price' => 2.00,
                'cost' => 0,
                'reference' => 'CUSTOM-LENGTH',
                'category_id' => $servicesCategory->id,
                'company_id' => $company->id ?? 1,
                'creator_id' => 1,
                'unit' => 'linear_foot',
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ];

        foreach ($services as $service) {
            $exists = DB::table('products_products')
                ->where('reference', $service['reference'])
                ->exists();

            if (!$exists) {
                DB::table('products_products')->insert($service);
                echo "Created: {$service['name']}\n";
            } else {
                echo "Already exists: {$service['name']}\n";
            }
        }

        echo "\nTotal TCS service products processed: " . count($services) . "\n";
    }
}
