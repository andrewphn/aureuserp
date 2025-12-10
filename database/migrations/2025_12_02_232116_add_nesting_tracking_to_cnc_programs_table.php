<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Add nesting tracking fields to CNC programs
 *
 * Workflow: LF → BOM (sqft estimate) → CNC Nesting → Actual Sheet Count
 *
 * This enables:
 * - Tracking estimated vs actual material usage
 * - Nesting efficiency metrics
 * - Better future estimates based on historical data
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('projects_cnc_programs', function (Blueprint $table) {
            // Rename existing sheet_count to sheets_actual for clarity
            // (will handle this separately if data exists)

            // BOM Estimate (calculated from LF before nesting)
            $table->integer('sheets_estimated')->nullable()->after('sheet_count')
                ->comment('Estimated sheets from BOM calculation before nesting');
            $table->decimal('sqft_estimated', 10, 2)->nullable()->after('sheets_estimated')
                ->comment('Estimated square feet from LF calculation');

            // Actual from Nesting (from VCarve output)
            $table->integer('sheets_actual')->nullable()->after('sqft_estimated')
                ->comment('Actual sheets used after CNC nesting optimization');
            $table->decimal('sqft_actual', 10, 2)->nullable()->after('sheets_actual')
                ->comment('Actual square feet used from nested sheets');

            // Nesting Efficiency Metrics
            $table->decimal('utilization_percentage', 5, 2)->nullable()->after('sqft_actual')
                ->comment('Sheet utilization % (used area / total sheet area)');
            $table->decimal('waste_sqft', 10, 2)->nullable()->after('utilization_percentage')
                ->comment('Waste square feet from cutoffs');

            // Nesting Details (from VCarve reference sheets)
            $table->json('nesting_details')->nullable()->after('waste_sqft')
                ->comment('JSON: per-sheet breakdown, part counts, layouts');

            // Variance Tracking
            $table->integer('sheets_variance')->nullable()->after('nesting_details')
                ->comment('Difference: sheets_actual - sheets_estimated (negative = saved)');

            // Nesting Completion
            $table->timestamp('nested_at')->nullable()->after('sheets_variance')
                ->comment('When nesting was completed in VCarve');
            $table->foreignId('nested_by_user_id')->nullable()->after('nested_at')
                ->constrained('users')->nullOnDelete()
                ->comment('Who performed the nesting');
        });
    }

    public function down(): void
    {
        Schema::table('projects_cnc_programs', function (Blueprint $table) {
            $table->dropForeign(['nested_by_user_id']);
            $table->dropColumn([
                'sheets_estimated',
                'sqft_estimated',
                'sheets_actual',
                'sqft_actual',
                'utilization_percentage',
                'waste_sqft',
                'nesting_details',
                'sheets_variance',
                'nested_at',
                'nested_by_user_id',
            ]);
        });
    }
};
