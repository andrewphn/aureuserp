<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Schema naming consistency fixes:
 *
 * 1. Rename 'style_width_inches' to 'stile_width_inches' across tables.
 *    REASON: "Stile" is the correct woodworking term for vertical frame members
 *    on cabinet doors and drawers. "Style" was a typo/misspelling.
 *    - stile = vertical frame member (correct)
 *    - rail = horizontal frame member
 *    - style = the overall design/appearance (different concept)
 *
 * 2. Add 'toe_kick_depth_inches' to room_locations table.
 *    REASON: Room locations had toe_kick_height_inches but was missing the depth.
 *    This allows setting a default toe kick depth at the room location level
 *    that cabinets can inherit or override.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Rename in projects_doors table
        if (Schema::hasColumn('projects_doors', 'style_width_inches')) {
            Schema::table('projects_doors', function (Blueprint $table) {
                $table->renameColumn('style_width_inches', 'stile_width_inches');
            });
        }

        // Rename in projects_drawers table
        if (Schema::hasColumn('projects_drawers', 'style_width_inches')) {
            Schema::table('projects_drawers', function (Blueprint $table) {
                $table->renameColumn('style_width_inches', 'stile_width_inches');
            });
        }

        // Rename in projects_door_presets table
        if (Schema::hasColumn('projects_door_presets', 'default_style_width_inches')) {
            Schema::table('projects_door_presets', function (Blueprint $table) {
                $table->renameColumn('default_style_width_inches', 'default_stile_width_inches');
            });
        }

        // Add toe_kick_depth_inches to room_locations (was missing - only had height)
        if (!Schema::hasColumn('projects_room_locations', 'toe_kick_depth_inches')) {
            Schema::table('projects_room_locations', function (Blueprint $table) {
                $table->decimal('toe_kick_depth_inches', 8, 3)
                    ->nullable()
                    ->after('toe_kick_height_inches')
                    ->comment('Default toe kick depth/setback for cabinets in this location');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert projects_doors
        if (Schema::hasColumn('projects_doors', 'stile_width_inches')) {
            Schema::table('projects_doors', function (Blueprint $table) {
                $table->renameColumn('stile_width_inches', 'style_width_inches');
            });
        }

        // Revert projects_drawers
        if (Schema::hasColumn('projects_drawers', 'stile_width_inches')) {
            Schema::table('projects_drawers', function (Blueprint $table) {
                $table->renameColumn('stile_width_inches', 'style_width_inches');
            });
        }

        // Revert projects_door_presets
        if (Schema::hasColumn('projects_door_presets', 'default_stile_width_inches')) {
            Schema::table('projects_door_presets', function (Blueprint $table) {
                $table->renameColumn('default_stile_width_inches', 'default_style_width_inches');
            });
        }

        // Remove toe_kick_depth_inches from room_locations
        if (Schema::hasColumn('projects_room_locations', 'toe_kick_depth_inches')) {
            Schema::table('projects_room_locations', function (Blueprint $table) {
                $table->dropColumn('toe_kick_depth_inches');
            });
        }
    }
};
