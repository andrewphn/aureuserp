<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Cabinet Calculation Audit Table
 *
 * Tracks calculation history and discrepancies between:
 * - Cabinet stored values vs calculated values
 * - Cabinet values vs ConstructionTemplate standards
 * - Material-specific thickness values
 *
 * Used for:
 * - Quality control before production
 * - Change tracking when templates are modified
 * - Validation before CNC/cut list generation
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('projects_cabinet_calculation_audits', function (Blueprint $table) {
            $table->id();

            // References
            $table->unsignedBigInteger('cabinet_id');
            $table->unsignedBigInteger('construction_template_id')->nullable();
            $table->unsignedBigInteger('audited_by_user_id')->nullable();

            $table->foreign('cabinet_id', 'cab_calc_audit_cabinet_fk')
                ->references('id')->on('projects_cabinets')->cascadeOnDelete();
            $table->foreign('construction_template_id', 'cab_calc_audit_template_fk')
                ->references('id')->on('projects_construction_templates')->nullOnDelete();
            $table->foreign('audited_by_user_id', 'cab_calc_audit_user_fk')
                ->references('id')->on('users')->nullOnDelete();

            // Audit type and status
            $table->enum('audit_type', [
                'initial_calculation',    // First calculation when cabinet created
                'recalculation',          // Manual recalculation triggered
                'template_change',        // Template values changed, audit triggered
                'material_change',        // Material product changed
                'dimension_change',       // Cabinet dimensions changed
                'validation',             // Pre-production validation
            ])->default('initial_calculation');

            $table->enum('audit_status', [
                'passed',       // All values match within tolerance
                'warning',      // Minor discrepancies (< 0.125")
                'failed',       // Major discrepancies (>= 0.125")
                'override',     // Discrepancy acknowledged and overridden
            ])->default('passed');

            // Stored values (what was in cabinet at audit time)
            $table->json('stored_values')->nullable()->comment('Cabinet depth breakdown values at audit time');

            // Calculated values (what service calculated)
            $table->json('calculated_values')->nullable()->comment('Values calculated from template/materials');

            // Template values (source of truth at audit time)
            $table->json('template_values')->nullable()->comment('ConstructionTemplate values used for calculation');

            // Discrepancies found
            $table->json('discrepancies')->nullable()->comment('Array of field differences with amounts');

            // Summary metrics
            $table->integer('discrepancy_count')->default(0);
            $table->decimal('max_discrepancy_inches', 8, 4)->nullable()->comment('Largest discrepancy found');
            $table->string('max_discrepancy_field', 100)->nullable()->comment('Field with largest discrepancy');

            // Override tracking
            $table->boolean('is_overridden')->default(false);
            $table->unsignedBigInteger('override_by_user_id')->nullable();
            $table->foreign('override_by_user_id', 'cab_calc_audit_override_user_fk')
                ->references('id')->on('users')->nullOnDelete();
            $table->timestamp('override_at')->nullable();
            $table->text('override_reason')->nullable();

            // Notes and context
            $table->text('notes')->nullable();
            $table->string('trigger_source', 100)->nullable()->comment('What triggered this audit (API, UI, Batch, etc.)');

            $table->timestamps();

            // Indexes for common queries
            $table->index(['cabinet_id', 'created_at'], 'cab_audit_cab_date_idx');
            $table->index(['audit_status', 'created_at'], 'cab_audit_status_date_idx');
            $table->index('construction_template_id', 'cab_audit_template_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('projects_cabinet_calculation_audits');
    }
};
