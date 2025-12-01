<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * MEDIUM PRIORITY Stage Fields for TCS Production Workflow:
     * - Design: designer_id, rhino_file_path
     * - Sourcing: purchasing_manager_id
     * - Production: pocket_holes_at
     * - Delivery: ferry_booking_date, ferry_confirmation, install_support_completed_at
     */
    public function up(): void
    {
        // Skip if projects_projects table doesn't exist (plugin not installed)
        if (!Schema::hasTable('projects_projects')) {
            return;
        }

        // Projects table - MEDIUM priority fields
        Schema::table('projects_projects', function (Blueprint $table) {
            // Design stage
            $table->foreignId('designer_id')->nullable()->after('user_id')
                ->constrained('users')->nullOnDelete()
                ->comment('Assigned designer for this project');
            $table->string('rhino_file_path', 500)->nullable()->after('designer_id')
                ->comment('Path to Rhino 3D model file');

            // Sourcing stage
            $table->foreignId('purchasing_manager_id')->nullable()->after('rhino_file_path')
                ->constrained('users')->nullOnDelete()
                ->comment('Assigned purchasing manager');

            // Delivery stage - Nantucket ferry coordination
            $table->date('ferry_booking_date')->nullable()->after('customer_signoff_at')
                ->comment('Ferry reservation date for Nantucket delivery');
            $table->string('ferry_confirmation', 100)->nullable()->after('ferry_booking_date')
                ->comment('Ferry booking confirmation number');
            $table->timestamp('install_support_completed_at')->nullable()->after('ferry_confirmation')
                ->comment('On-site installation support completed');
        });

        // Cabinet Specifications table - Additional production tracking
        Schema::table('projects_cabinet_specifications', function (Blueprint $table) {
            $table->timestamp('pocket_holes_at')->nullable()->after('hardware_installed_at')
                ->comment('Pocket hole drilling completed');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('projects_projects', function (Blueprint $table) {
            $table->dropForeign(['designer_id']);
            $table->dropForeign(['purchasing_manager_id']);
            $table->dropColumn([
                'designer_id',
                'rhino_file_path',
                'purchasing_manager_id',
                'ferry_booking_date',
                'ferry_confirmation',
                'install_support_completed_at',
            ]);
        });

        Schema::table('projects_cabinet_specifications', function (Blueprint $table) {
            $table->dropColumn('pocket_holes_at');
        });
    }
};
