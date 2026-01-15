<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Add Cabinet Configurator Fields
 *
 * Adds fields to support the Cabinet Configurator feature:
 * - Face frame construction fields on cabinets table
 * - Section positioning fields on cabinet_sections table
 *
 * @see app/Services/CabinetConfiguratorService.php
 * @see docs/OPENING_CONFIGURATOR_SYSTEM.md
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Add face frame configurator fields to cabinets table
        Schema::table('projects_cabinets', function (Blueprint $table) {
            if (!Schema::hasColumn('projects_cabinets', 'construction_type')) {
                $table->string('construction_type', 50)
                    ->default('face_frame')
                    ->after('cabinet_level')
                    ->comment('Construction type: face_frame or frameless');
            }

            if (!Schema::hasColumn('projects_cabinets', 'section_layout_type')) {
                $table->string('section_layout_type', 50)
                    ->default('horizontal')
                    ->after('construction_type')
                    ->comment('Section layout: horizontal or vertical');
            }

            if (!Schema::hasColumn('projects_cabinets', 'face_frame_stile_width_inches')) {
                $table->decimal('face_frame_stile_width_inches', 5, 3)
                    ->default(1.5)
                    ->after('section_layout_type')
                    ->comment('Face frame stile width in inches');
            }

            if (!Schema::hasColumn('projects_cabinets', 'face_frame_rail_width_inches')) {
                $table->decimal('face_frame_rail_width_inches', 5, 3)
                    ->default(1.5)
                    ->after('face_frame_stile_width_inches')
                    ->comment('Face frame rail width in inches');
            }

            if (!Schema::hasColumn('projects_cabinets', 'face_frame_mid_stile_count')) {
                $table->unsignedInteger('face_frame_mid_stile_count')
                    ->default(0)
                    ->after('face_frame_rail_width_inches')
                    ->comment('Number of mid-stiles (vertical dividers) in face frame');
            }
        });

        // Add section positioning fields to cabinet_sections table
        Schema::table('projects_cabinet_sections', function (Blueprint $table) {
            if (!Schema::hasColumn('projects_cabinet_sections', 'cabinet_position_from_left_inches')) {
                $table->decimal('cabinet_position_from_left_inches', 8, 4)
                    ->nullable()
                    ->after('position_from_bottom_inches')
                    ->comment('Position from left edge of cabinet face');
            }

            if (!Schema::hasColumn('projects_cabinet_sections', 'cabinet_position_from_top_inches')) {
                $table->decimal('cabinet_position_from_top_inches', 8, 4)
                    ->nullable()
                    ->after('cabinet_position_from_left_inches')
                    ->comment('Position from top edge of cabinet face');
            }

            if (!Schema::hasColumn('projects_cabinet_sections', 'section_width_ratio')) {
                $table->decimal('section_width_ratio', 5, 3)
                    ->nullable()
                    ->after('cabinet_position_from_top_inches')
                    ->comment('Width ratio relative to total opening width (0.0 to 1.0)');
            }

            if (!Schema::hasColumn('projects_cabinet_sections', 'section_height_ratio')) {
                $table->decimal('section_height_ratio', 5, 3)
                    ->nullable()
                    ->after('section_width_ratio')
                    ->comment('Height ratio for vertically-divided sections (0.0 to 1.0)');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('projects_cabinets', function (Blueprint $table) {
            $columns = [
                'construction_type',
                'section_layout_type',
                'face_frame_stile_width_inches',
                'face_frame_rail_width_inches',
                'face_frame_mid_stile_count',
            ];

            foreach ($columns as $column) {
                if (Schema::hasColumn('projects_cabinets', $column)) {
                    $table->dropColumn($column);
                }
            }
        });

        Schema::table('projects_cabinet_sections', function (Blueprint $table) {
            $columns = [
                'cabinet_position_from_left_inches',
                'cabinet_position_from_top_inches',
                'section_width_ratio',
                'section_height_ratio',
            ];

            foreach ($columns as $column) {
                if (Schema::hasColumn('projects_cabinet_sections', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
