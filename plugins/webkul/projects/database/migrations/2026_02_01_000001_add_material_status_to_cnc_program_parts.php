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
        Schema::table('projects_cnc_program_parts', function (Blueprint $table) {
            $table->enum('material_status', ['ready', 'pending_material', 'ordered', 'received'])
                ->default('ready')
                ->after('status')
                ->comment('Material availability status');
        });

        // Add missing columns to cnc_programs for better tracking
        Schema::table('projects_cnc_programs', function (Blueprint $table) {
            // Add columns only if they don't exist
            if (!Schema::hasColumn('projects_cnc_programs', 'sheets_estimated')) {
                $table->integer('sheets_estimated')->nullable()->after('sheet_count');
            }
            if (!Schema::hasColumn('projects_cnc_programs', 'sqft_estimated')) {
                $table->decimal('sqft_estimated', 10, 2)->nullable()->after('sheets_estimated');
            }
            if (!Schema::hasColumn('projects_cnc_programs', 'sheets_actual')) {
                $table->integer('sheets_actual')->nullable()->after('sqft_estimated');
            }
            if (!Schema::hasColumn('projects_cnc_programs', 'sqft_actual')) {
                $table->decimal('sqft_actual', 10, 2)->nullable()->after('sheets_actual');
            }
            if (!Schema::hasColumn('projects_cnc_programs', 'utilization_percentage')) {
                $table->decimal('utilization_percentage', 5, 2)->nullable()->after('sqft_actual');
            }
            if (!Schema::hasColumn('projects_cnc_programs', 'waste_sqft')) {
                $table->decimal('waste_sqft', 10, 2)->nullable()->after('utilization_percentage');
            }
            if (!Schema::hasColumn('projects_cnc_programs', 'nesting_details')) {
                $table->json('nesting_details')->nullable()->after('waste_sqft');
            }
            if (!Schema::hasColumn('projects_cnc_programs', 'sheets_variance')) {
                $table->integer('sheets_variance')->nullable()->after('nesting_details');
            }
            if (!Schema::hasColumn('projects_cnc_programs', 'nested_at')) {
                $table->timestamp('nested_at')->nullable()->after('sheets_variance');
            }
            if (!Schema::hasColumn('projects_cnc_programs', 'nested_by_user_id')) {
                $table->foreignId('nested_by_user_id')->nullable()->after('nested_at')->constrained('users')->nullOnDelete();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('projects_cnc_program_parts', function (Blueprint $table) {
            $table->dropColumn('material_status');
        });

        Schema::table('projects_cnc_programs', function (Blueprint $table) {
            $columns = [
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
            ];

            foreach ($columns as $column) {
                if (Schema::hasColumn('projects_cnc_programs', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
