<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new /**
 * extends class
 *
 */
class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Add indexes for rooms
        Schema::table('projects_rooms', function (Blueprint $table) {
            $table->index('name', 'rooms_name_index');
            $table->index(['project_id', 'name'], 'rooms_project_name_index');
        });

        // Add indexes for room locations
        Schema::table('projects_room_locations', function (Blueprint $table) {
            $table->index('name', 'locations_name_index');
            $table->index(['room_id', 'name'], 'locations_room_name_index');
        });

        // Add indexes for cabinet runs
        Schema::table('projects_cabinet_runs', function (Blueprint $table) {
            $table->index('name', 'runs_name_index');
            $table->index(['room_location_id', 'name'], 'runs_location_name_index');
        });

        // Add indexes for cabinet specifications
        Schema::table('projects_cabinet_specifications', function (Blueprint $table) {
            $table->index('cabinet_number', 'cabinets_number_index');
            $table->index(['cabinet_run_id', 'cabinet_number'], 'cabinets_run_number_index');
            $table->index(['project_id', 'cabinet_number'], 'cabinets_project_number_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('projects_rooms', function (Blueprint $table) {
            $table->dropIndex('rooms_name_index');
            $table->dropIndex('rooms_project_name_index');
        });

        Schema::table('projects_room_locations', function (Blueprint $table) {
            $table->dropIndex('locations_name_index');
            $table->dropIndex('locations_room_name_index');
        });

        Schema::table('projects_cabinet_runs', function (Blueprint $table) {
            $table->dropIndex('runs_name_index');
            $table->dropIndex('runs_location_name_index');
        });

        Schema::table('projects_cabinet_specifications', function (Blueprint $table) {
            $table->dropIndex('cabinets_number_index');
            $table->dropIndex('cabinets_run_number_index');
            $table->dropIndex('cabinets_project_number_index');
        });
    }
};
