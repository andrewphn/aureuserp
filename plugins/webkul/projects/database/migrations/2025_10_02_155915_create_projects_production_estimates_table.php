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
        Schema::create('projects_production_estimates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained('projects_projects')->onDelete('cascade');
            $table->foreignId('company_id')->constrained('companies')->onDelete('cascade');

            // Linear feet input
            $table->decimal('linear_feet', 10, 2);

            // Production time estimates
            $table->decimal('estimated_hours', 10, 2);
            $table->decimal('estimated_days', 10, 2);
            $table->decimal('estimated_weeks', 10, 2);
            $table->decimal('estimated_months', 10, 2);

            // Shop capacity at time of estimate
            $table->decimal('shop_capacity_per_day', 10, 2);
            $table->decimal('shop_capacity_per_hour', 10, 2);

            // Working schedule at time of estimate
            $table->integer('working_hours_per_day');
            $table->integer('working_days_per_week');
            $table->integer('working_days_per_month');

            // Metadata
            $table->string('company_acronym')->nullable();
            $table->text('formatted_estimate')->nullable();
            $table->boolean('is_current')->default(true); // Flag to track latest estimate

            $table->timestamps();

            // Index for performance
            $table->index(['project_id', 'is_current']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('projects_production_estimates');
    }
};
