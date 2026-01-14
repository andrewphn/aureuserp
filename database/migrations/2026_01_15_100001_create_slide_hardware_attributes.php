<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Creates slide-specific attributes for drawer hardware specifications.
     * Uses the EAV system in products_attributes table.
     */
    public function up(): void
    {
        $creatorId = DB::table('users')->where('email', 'admin@example.com')->value('id') ?? 1;
        $now = now();
        
        // Get max sort order
        $maxSort = DB::table('products_attributes')->max('sort') ?? 0;
        
        $attributes = [
            [
                'name' => 'Slide Length',
                'type' => 'dimension',
                'category' => 'hardware',
                'unit_symbol' => 'in',
                'unit_label' => 'inches',
                'min_value' => 9,
                'max_value' => 24,
                'decimal_places' => 2,
                'is_constant' => false,
                'sort' => $maxSort + 1,
                'creator_id' => $creatorId,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'Min Cabinet Depth',
                'type' => 'dimension',
                'category' => 'hardware',
                'unit_symbol' => 'in',
                'unit_label' => 'inches',
                'min_value' => 10,
                'max_value' => 30,
                'decimal_places' => 2,
                'is_constant' => false,
                'sort' => $maxSort + 2,
                'creator_id' => $creatorId,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'Weight Capacity',
                'type' => 'number',
                'category' => 'hardware',
                'unit_symbol' => 'lbs',
                'unit_label' => 'pounds',
                'min_value' => 0,
                'max_value' => 200,
                'decimal_places' => 0,
                'is_constant' => false,
                'sort' => $maxSort + 3,
                'creator_id' => $creatorId,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'Slide Side Clearance',
                'type' => 'dimension',
                'category' => 'hardware',
                'unit_symbol' => 'in',
                'unit_label' => 'inches',
                'min_value' => 0,
                'max_value' => 1,
                'decimal_places' => 4,
                'is_constant' => false,
                'sort' => $maxSort + 4,
                'creator_id' => $creatorId,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'Slide Top Clearance',
                'type' => 'dimension',
                'category' => 'hardware',
                'unit_symbol' => 'in',
                'unit_label' => 'inches',
                'min_value' => 0,
                'max_value' => 2,
                'decimal_places' => 4,
                'is_constant' => false,
                'sort' => $maxSort + 5,
                'creator_id' => $creatorId,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'Slide Bottom Clearance',
                'type' => 'dimension',
                'category' => 'hardware',
                'unit_symbol' => 'in',
                'unit_label' => 'inches',
                'min_value' => 0,
                'max_value' => 2,
                'decimal_places' => 4,
                'is_constant' => false,
                'sort' => $maxSort + 6,
                'creator_id' => $creatorId,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'Slide Rear Clearance',
                'type' => 'dimension',
                'category' => 'hardware',
                'unit_symbol' => 'in',
                'unit_label' => 'inches',
                'min_value' => 0,
                'max_value' => 2,
                'decimal_places' => 4,
                'is_constant' => false,
                'sort' => $maxSort + 7,
                'creator_id' => $creatorId,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ];
        
        foreach ($attributes as $attr) {
            // Only insert if not already exists
            $exists = DB::table('products_attributes')
                ->where('name', $attr['name'])
                ->exists();
                
            if (!$exists) {
                DB::table('products_attributes')->insert($attr);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $attributeNames = [
            'Slide Length',
            'Min Cabinet Depth',
            'Weight Capacity',
            'Slide Side Clearance',
            'Slide Top Clearance',
            'Slide Bottom Clearance',
            'Slide Rear Clearance',
        ];
        
        DB::table('products_attributes')
            ->whereIn('name', $attributeNames)
            ->delete();
    }
};
