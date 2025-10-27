<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Cabinet Run production details (Levi's CNC grouping level)
     * Example runs: "Upper Cabinets", "Base Cabinets", "Pantry Tall Units"
     * This groups cabinets that share materials and get machined together.
     */
    public function up(): void
    {
        Schema::table('projects_cabinet_runs', function (Blueprint $table) {
            // Production Grouping
            // NOTE: run_type and total_linear_feet already exist from previous migration
            $table->integer('cabinet_count')->default(0)
                ->comment('Number of individual cabinets in this run');

            // Material Consistency (all cabinets in run use same materials)
            $table->string('material_type', 50)->nullable()
                ->comment('Inherited from location: paint_grade, stain_grade, premium');
            $table->string('wood_species', 100)->nullable()
                ->comment('All cabinets use same species for efficiency');
            $table->string('finish_type', 100)->nullable()
                ->comment('Finish type for batch finishing');
            $table->decimal('sheet_goods_required_sqft', 10, 2)->nullable()
                ->comment('Calculated plywood/MDF needed for all cabinets');
            $table->decimal('solid_wood_required_bf', 10, 2)->nullable()
                ->comment('Board feet of solid wood for face frames, doors');

            // CNC Programming & Machining
            $table->string('cnc_program_file', 255)->nullable()
                ->comment('CNC program filename for this run');
            $table->timestamp('cnc_program_generated_at')->nullable()
                ->comment('When CNC file was generated');
            $table->decimal('cnc_machine_time_minutes', 8, 2)->nullable()
                ->comment('Estimated CNC runtime for all parts');
            $table->text('cnc_notes')->nullable()
                ->comment('Special CNC considerations, tool changes, etc.');

            // Production Scheduling (Levi's workflow)
            $table->string('production_status', 50)->default('pending')
                ->comment('pending, material_ordered, cnc_cut, assembly, finishing, complete');
            $table->timestamp('material_ordered_at')->nullable()
                ->comment('When materials were ordered for this run');
            $table->timestamp('cnc_started_at')->nullable()
                ->comment('When CNC cutting started');
            $table->timestamp('cnc_completed_at')->nullable()
                ->comment('When CNC cutting finished');
            $table->timestamp('assembly_started_at')->nullable()
                ->comment('When cabinet assembly started');
            $table->timestamp('assembly_completed_at')->nullable()
                ->comment('When cabinets assembled and sanded');
            $table->timestamp('finishing_started_at')->nullable()
                ->comment('When finishing (paint/stain) started');
            $table->timestamp('finishing_completed_at')->nullable()
                ->comment('When finishing completed and cured');

            // Labor Tracking
            $table->decimal('estimated_labor_hours', 8, 2)->nullable()
                ->comment('Estimated shop hours (2.65 hrs/LF baseline)');
            $table->decimal('actual_labor_hours', 8, 2)->nullable()
                ->comment('Actual hours logged (from timesheet system)');
            $table->foreignId('lead_craftsman_id')->nullable()->constrained('users')
                ->comment('Assigned lead (typically Levi or designated craftsman)');
            $table->text('labor_notes')->nullable()
                ->comment('Production challenges, learning opportunities');

            // Hardware Kitting (aggregated from individual cabinets)
            $table->text('hardware_kit_json')->nullable()
                ->comment('JSON: aggregated hardware list for this run');
            $table->integer('blum_hinges_total')->default(0)
                ->comment('Total Blum hinges needed for run');
            $table->integer('blum_slides_total')->default(0)
                ->comment('Total Blum drawer slides needed');
            $table->integer('shelf_pins_total')->default(0)
                ->comment('Total shelf support pins needed');
            $table->boolean('hardware_kitted')->default(false)
                ->comment('Hardware kit prepared and ready');
            $table->timestamp('hardware_kitted_at')->nullable()
                ->comment('When hardware was kitted');

            // Quality Control
            $table->boolean('qc_passed')->nullable()
                ->comment('Quality control inspection passed');
            $table->timestamp('qc_inspected_at')->nullable()
                ->comment('When QC inspection performed');
            $table->foreignId('qc_inspector_id')->nullable()->constrained('users')
                ->comment('Who performed QC inspection');
            $table->text('qc_notes')->nullable()
                ->comment('QC findings, corrections needed');

            // Batch Finishing Details
            $table->string('primer_type', 100)->nullable()
                ->comment('Primer product if paint grade');
            $table->integer('primer_coats')->nullable()
                ->comment('Number of primer coats applied');
            $table->string('topcoat_type', 100)->nullable()
                ->comment('Paint or stain product used');
            $table->integer('topcoat_coats')->nullable()
                ->comment('Number of topcoat applications');
            $table->string('sheen_level', 50)->nullable()
                ->comment('Finish sheen: matte, satin, semi_gloss');
            $table->text('finishing_notes')->nullable()
                ->comment('Color matching, touch-ups, cure time');

            // Shipping/Delivery Prep
            $table->boolean('ready_for_delivery')->default(false)
                ->comment('Cabinets complete and ready to ship');
            $table->timestamp('ready_for_delivery_at')->nullable()
                ->comment('When marked ready for delivery');
            $table->integer('packaging_boxes_needed')->nullable()
                ->comment('Number of boxes/crates for shipping');
            $table->text('delivery_notes')->nullable()
                ->comment('Special handling, fragile items, installation notes');

            // Cost Tracking
            $table->decimal('material_cost_actual', 10, 2)->nullable()
                ->comment('Actual material costs for this run');
            $table->decimal('hardware_cost_actual', 10, 2)->nullable()
                ->comment('Actual hardware costs');
            $table->decimal('labor_cost_actual', 10, 2)->nullable()
                ->comment('Calculated: actual_hours × shop_rate');
            $table->decimal('finishing_cost_actual', 10, 2)->nullable()
                ->comment('Finishing materials and labor');
            $table->decimal('total_production_cost', 10, 2)->nullable()
                ->comment('Sum of all actual costs');

            // Indexes for production queries
            $table->index(['production_status', 'lead_craftsman_id'], 'idx_run_production');
            $table->index(['ready_for_delivery', 'ready_for_delivery_at'], 'idx_run_delivery');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('projects_cabinet_runs', function (Blueprint $table) {
            $table->dropIndex('idx_run_production');
            $table->dropIndex('idx_run_delivery');

            $table->dropForeign(['lead_craftsman_id']);
            $table->dropForeign(['qc_inspector_id']);

            $table->dropColumn([
                'cabinet_count',
                'material_type',
                'wood_species',
                'finish_type',
                'sheet_goods_required_sqft',
                'solid_wood_required_bf',
                'cnc_program_file',
                'cnc_program_generated_at',
                'cnc_machine_time_minutes',
                'cnc_notes',
                'production_status',
                'material_ordered_at',
                'cnc_started_at',
                'cnc_completed_at',
                'assembly_started_at',
                'assembly_completed_at',
                'finishing_started_at',
                'finishing_completed_at',
                'estimated_labor_hours',
                'actual_labor_hours',
                'lead_craftsman_id',
                'labor_notes',
                'hardware_kit_json',
                'blum_hinges_total',
                'blum_slides_total',
                'shelf_pins_total',
                'hardware_kitted',
                'hardware_kitted_at',
                'qc_passed',
                'qc_inspected_at',
                'qc_inspector_id',
                'qc_notes',
                'primer_type',
                'primer_coats',
                'topcoat_type',
                'topcoat_coats',
                'sheen_level',
                'finishing_notes',
                'ready_for_delivery',
                'ready_for_delivery_at',
                'packaging_boxes_needed',
                'delivery_notes',
                'material_cost_actual',
                'hardware_cost_actual',
                'labor_cost_actual',
                'finishing_cost_actual',
                'total_production_cost',
            ]);
        });
    }
};
