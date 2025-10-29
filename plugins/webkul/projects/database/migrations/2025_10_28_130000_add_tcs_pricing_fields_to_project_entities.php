<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Add TCS pricing fields to all project entity tables to support
     * hierarchical pricing inheritance: Room > Location > Cabinet Run > Cabinet
     */
    public function up(): void
    {
        // Add pricing fields to rooms (top level)
        Schema::table('projects_rooms', function (Blueprint $table) {
            $table->string('cabinet_level', 10)->nullable()->after('name');
            $table->string('material_category', 50)->nullable()->after('cabinet_level');
            $table->string('finish_option', 50)->nullable()->after('material_category');
        });

        // Add pricing fields to room locations
        Schema::table('projects_room_locations', function (Blueprint $table) {
            $table->string('cabinet_level', 10)->nullable()->after('name');
            $table->string('material_category', 50)->nullable()->after('cabinet_level');
            $table->string('finish_option', 50)->nullable()->after('material_category');
        });

        // Add pricing fields to cabinet runs
        Schema::table('projects_cabinet_runs', function (Blueprint $table) {
            $table->string('cabinet_level', 10)->nullable()->after('name');
            $table->string('material_category', 50)->nullable()->after('cabinet_level');
            $table->string('finish_option', 50)->nullable()->after('material_category');
        });

        // Add pricing fields to cabinet specifications
        Schema::table('projects_cabinet_specifications', function (Blueprint $table) {
            $table->string('cabinet_level', 10)->nullable()->after('cabinet_number');
            $table->string('material_category', 50)->nullable()->after('cabinet_level');
            $table->string('finish_option', 50)->nullable()->after('material_category');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('projects_rooms', function (Blueprint $table) {
            $table->dropColumn(['cabinet_level', 'material_category', 'finish_option']);
        });

        Schema::table('projects_room_locations', function (Blueprint $table) {
            $table->dropColumn(['cabinet_level', 'material_category', 'finish_option']);
        });

        Schema::table('projects_cabinet_runs', function (Blueprint $table) {
            $table->dropColumn(['cabinet_level', 'material_category', 'finish_option']);
        });

        Schema::table('projects_cabinet_specifications', function (Blueprint $table) {
            $table->dropColumn(['cabinet_level', 'material_category', 'finish_option']);
        });
    }
};
