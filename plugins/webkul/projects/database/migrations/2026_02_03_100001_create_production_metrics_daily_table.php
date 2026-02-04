<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('projects_production_metrics_daily', function (Blueprint $table) {
            $table->id();
            $table->date('metrics_date')->index();
            $table->foreignId('company_id')->nullable()->constrained('companies')->nullOnDelete();

            // Production counts
            $table->unsignedInteger('sheets_completed')->default(0)->comment('Unique sheets cut');
            $table->unsignedInteger('parts_completed')->default(0)->comment('Total parts processed');
            $table->decimal('board_feet', 10, 2)->default(0)->comment('Board feet produced');
            $table->decimal('sqft_processed', 10, 2)->default(0)->comment('Square feet processed');

            // Utilization and waste
            $table->decimal('utilization_avg', 5, 2)->nullable()->comment('Average material utilization %');
            $table->decimal('waste_sqft', 10, 2)->default(0)->comment('Waste in square feet');

            // Detailed breakdowns (JSON)
            $table->json('operator_breakdown')->nullable()->comment('Per-operator stats');
            $table->json('material_breakdown')->nullable()->comment('Per-material stats');

            // Time tracking
            $table->unsignedInteger('total_run_minutes')->default(0)->comment('Total CNC run time');
            $table->decimal('avg_minutes_per_sheet', 8, 2)->nullable()->comment('Efficiency metric');
            $table->unsignedInteger('programs_completed')->default(0)->comment('CNC programs finished');

            // Computed throughput metrics
            $table->decimal('sheets_per_hour', 8, 2)->nullable()->comment('Computed throughput');
            $table->decimal('bf_per_hour', 8, 2)->nullable()->comment('Board feet throughput');

            // Metadata
            $table->timestamp('computed_at')->nullable()->comment('When metrics were aggregated');
            $table->boolean('is_complete')->default(false)->comment('Day finalized flag');

            $table->timestamps();

            // Ensure one record per day per company
            $table->unique(['metrics_date', 'company_id'], 'unique_daily_metrics');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('projects_production_metrics_daily');
    }
};
