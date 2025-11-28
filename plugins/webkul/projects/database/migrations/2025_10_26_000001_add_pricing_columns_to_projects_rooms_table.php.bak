<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Room-level pricing aggregates (Bryan's view - business/quote generation)
     * This migration adds columns to track pricing totals and estimates at the room level.
     * Data flows from cabinet specifications → runs → locations → rooms for aggregation.
     */
    public function up(): void
    {
        Schema::table('projects_rooms', function (Blueprint $table) {
            // Linear Feet by Complexity Tier (from cover page analysis)
            // Example: 25 Friendship Lane had Tier 2: 11.5 LF, Tier 4: 35.25 LF
            $table->decimal('total_linear_feet_tier_1', 8, 2)->nullable()
                ->comment('Total LF at Tier 1 ($138/LF base - open boxes, no doors)');
            $table->decimal('total_linear_feet_tier_2', 8, 2)->nullable()
                ->comment('Total LF at Tier 2 ($168/LF base - paint grade, flat/shaker doors)');
            $table->decimal('total_linear_feet_tier_3', 8, 2)->nullable()
                ->comment('Total LF at Tier 3 ($192/LF base - stain grade, semi-complicated)');
            $table->decimal('total_linear_feet_tier_4', 8, 2)->nullable()
                ->comment('Total LF at Tier 4 ($210/LF base - beaded face frames, specialty)');
            $table->decimal('total_linear_feet_tier_5', 8, 2)->nullable()
                ->comment('Total LF at Tier 5 ($225/LF base - custom, paneling, reeded)');

            // Additional Products (non-cabinet items from price sheet)
            $table->decimal('floating_shelves_lf', 8, 2)->nullable()
                ->comment('Linear feet of floating shelves ($18-$24/LF)');
            $table->decimal('countertop_sqft', 8, 2)->nullable()
                ->comment('Square footage of countertops ($17.68/BF for fabrication)');
            $table->decimal('trim_millwork_lf', 8, 2)->nullable()
                ->comment('Linear feet of trim/millwork ($6-$25/LF)');

            // Material Type Override (typically set at location/run level, but can override here)
            $table->string('material_type', 50)->nullable()
                ->comment('Room-level material override: paint_grade, stain_grade, premium');
            $table->decimal('material_upgrade_rate', 8, 2)->nullable()
                ->comment('Material upgrade $/LF: $138 (paint), $156 (stain), $185 (premium)');

            // Pricing Calculations & Estimates
            $table->decimal('estimated_cabinet_value', 10, 2)->nullable()
                ->comment('Calculated: sum(tier_LF × (base_rate + material_rate))');
            $table->decimal('estimated_additional_products', 10, 2)->nullable()
                ->comment('Calculated: shelves + countertops + trim + millwork');
            $table->decimal('estimated_finish_value', 10, 2)->nullable()
                ->comment('Finishing costs ($60-$255/LF based on finish type)');
            $table->decimal('estimated_project_value', 10, 2)->nullable()
                ->comment('Total estimate: cabinets + additional + finish');

            // Business Metrics (Bryan's perspective)
            $table->integer('pricing_tier_override')->nullable()
                ->comment('1-5 override for entire room (rare - usually set per cabinet)');
            $table->decimal('quoted_price', 10, 2)->nullable()
                ->comment('Actual price quoted to client (may differ from estimate)');
            $table->decimal('margin_percentage', 5, 2)->nullable()
                ->comment('Target profit margin % for this room');
            $table->decimal('labor_hours_estimate', 8, 2)->nullable()
                ->comment('Estimated shop hours (2.65 hrs/LF baseline from metrics)');

            // Quote Generation Metadata
            $table->timestamp('last_pricing_calculation')->nullable()
                ->comment('When pricing totals were last recalculated');
            $table->text('pricing_notes')->nullable()
                ->comment('Special pricing considerations, client negotiation notes');

            // Index for quick pricing queries
            $table->index(['estimated_project_value', 'quoted_price'], 'idx_room_pricing');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('projects_rooms', function (Blueprint $table) {
            $table->dropIndex('idx_room_pricing');

            $table->dropColumn([
                'total_linear_feet_tier_1',
                'total_linear_feet_tier_2',
                'total_linear_feet_tier_3',
                'total_linear_feet_tier_4',
                'total_linear_feet_tier_5',
                'floating_shelves_lf',
                'countertop_sqft',
                'trim_millwork_lf',
                'material_type',
                'material_upgrade_rate',
                'estimated_cabinet_value',
                'estimated_additional_products',
                'estimated_finish_value',
                'estimated_project_value',
                'pricing_tier_override',
                'quoted_price',
                'margin_percentage',
                'labor_hours_estimate',
                'last_pricing_calculation',
                'pricing_notes',
            ]);
        });
    }
};
