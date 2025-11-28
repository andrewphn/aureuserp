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
        // Only run if sales_orders table exists (created by sales plugin)
        if (!Schema::hasTable('sales_orders')) {
            return;
        }

        // Only add column if it doesn't already exist
        if (Schema::hasColumn('sales_orders', 'project_id')) {
            return;
        }

        Schema::table('sales_orders', function (Blueprint $table) {
            $table->foreignId('project_id')
                ->nullable()
                ->after('campaign_id')
                ->constrained('projects_projects')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sales_orders', function (Blueprint $table) {
            $table->dropForeign(['project_id']);
            $table->dropColumn('project_id');
        });
    }
};
