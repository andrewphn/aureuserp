<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Adds auto-naming code fields for hierarchical component naming:
     * TCS-0554-15WSANKATY-K1-SW-U1-A-DOOR1
     * [Project Number]-[Room]-[Location]-[Run]-[Section]-[Component]
     */
    public function up(): void
    {
        // Add room_code to rooms table
        Schema::table('projects_rooms', function (Blueprint $table) {
            $table->string('room_code', 10)->nullable()->after('name')
                ->comment('Auto-generated room code (K1, BTH1, etc.)');
        });

        // Add location_code to room_locations table
        Schema::table('projects_room_locations', function (Blueprint $table) {
            $table->string('location_code', 10)->nullable()->after('name')
                ->comment('Auto-generated location code (SW, NW, ISL, etc.)');
        });

        // Add run_code to cabinet_runs table
        Schema::table('projects_cabinet_runs', function (Blueprint $table) {
            $table->string('run_code', 10)->nullable()->after('name')
                ->comment('Auto-generated run code (U1, B2, T1, etc.)');
        });

        // Add section_code and full_code to cabinet_sections table
        Schema::table('projects_cabinet_sections', function (Blueprint $table) {
            $table->string('section_code', 5)->nullable()->after('section_number')
                ->comment('Auto-generated section code (A, B, C, etc.)');
            $table->string('full_code', 100)->nullable()->after('section_code')
                ->comment('Cached full hierarchical code');
            $table->index('full_code');
        });

        // Add full_code to cabinets table
        Schema::table('projects_cabinets', function (Blueprint $table) {
            $table->string('full_code', 100)->nullable()->after('cabinet_number')
                ->comment('Cached full hierarchical code');
            $table->index('full_code');
        });

        // Add full_code to doors table
        Schema::table('projects_doors', function (Blueprint $table) {
            $table->string('full_code', 100)->nullable()->after('door_name')
                ->comment('Cached full hierarchical code (e.g., TCS-0554-15WSANKATY-K1-SW-U1-A-DOOR1)');
            $table->index('full_code');
        });

        // Add full_code to drawers table
        Schema::table('projects_drawers', function (Blueprint $table) {
            $table->string('full_code', 100)->nullable()->after('drawer_name')
                ->comment('Cached full hierarchical code (e.g., TCS-0554-15WSANKATY-K1-SW-B1-A-DRW1)');
            $table->index('full_code');
        });

        // Add full_code to shelves table
        Schema::table('projects_shelves', function (Blueprint $table) {
            $table->string('full_code', 100)->nullable()->after('shelf_name')
                ->comment('Cached full hierarchical code (e.g., TCS-0554-15WSANKATY-K1-SW-U1-B-SHELF1)');
            $table->index('full_code');
        });

        // Add full_code to pullouts table
        Schema::table('projects_pullouts', function (Blueprint $table) {
            $table->string('full_code', 100)->nullable()->after('pullout_name')
                ->comment('Cached full hierarchical code (e.g., TCS-0554-15WSANKATY-K1-SW-B1-C-PULL1)');
            $table->index('full_code');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('projects_rooms', function (Blueprint $table) {
            $table->dropColumn('room_code');
        });

        Schema::table('projects_room_locations', function (Blueprint $table) {
            $table->dropColumn('location_code');
        });

        Schema::table('projects_cabinet_runs', function (Blueprint $table) {
            $table->dropColumn('run_code');
        });

        Schema::table('projects_cabinet_sections', function (Blueprint $table) {
            $table->dropIndex(['full_code']);
            $table->dropColumn(['section_code', 'full_code']);
        });

        Schema::table('projects_cabinets', function (Blueprint $table) {
            $table->dropIndex(['full_code']);
            $table->dropColumn('full_code');
        });

        Schema::table('projects_doors', function (Blueprint $table) {
            $table->dropIndex(['full_code']);
            $table->dropColumn('full_code');
        });

        Schema::table('projects_drawers', function (Blueprint $table) {
            $table->dropIndex(['full_code']);
            $table->dropColumn('full_code');
        });

        Schema::table('projects_shelves', function (Blueprint $table) {
            $table->dropIndex(['full_code']);
            $table->dropColumn('full_code');
        });

        Schema::table('projects_pullouts', function (Blueprint $table) {
            $table->dropIndex(['full_code']);
            $table->dropColumn('full_code');
        });
    }
};
