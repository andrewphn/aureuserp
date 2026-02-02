<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Adds Google Drive integration columns to CNC program parts table:
     * - nc_drive_id: Google Drive file ID for the G-code file
     * - nc_drive_url: Direct URL to the G-code file in Drive
     * - reference_photo_drive_id: Google Drive file ID for reference photo
     * - reference_photo_url: Direct URL to reference photo in Drive
     * - component_type: Type of cabinet component (door, drawer, panel, etc.)
     * - component_id: ID of the related component model
     * - part_label: Human-readable label for the part
     * - position_data: JSON data for part positioning/orientation
     */
    public function up(): void
    {
        Schema::table('projects_cnc_program_parts', function (Blueprint $table) {
            // Google Drive file tracking for G-code
            if (!Schema::hasColumn('projects_cnc_program_parts', 'nc_drive_id')) {
                $table->string('nc_drive_id')->nullable()->after('file_path')
                    ->comment('Google Drive file ID for G-code file');
            }
            if (!Schema::hasColumn('projects_cnc_program_parts', 'nc_drive_url')) {
                $table->string('nc_drive_url')->nullable()->after('nc_drive_id')
                    ->comment('Direct URL to G-code file in Drive');
            }

            // Reference photo tracking
            if (!Schema::hasColumn('projects_cnc_program_parts', 'reference_photo_drive_id')) {
                $table->string('reference_photo_drive_id')->nullable()->after('nc_drive_url')
                    ->comment('Google Drive file ID for reference photo');
            }
            if (!Schema::hasColumn('projects_cnc_program_parts', 'reference_photo_url')) {
                $table->string('reference_photo_url')->nullable()->after('reference_photo_drive_id')
                    ->comment('Direct URL to reference photo in Drive');
            }

            // Component linking (polymorphic relationship)
            if (!Schema::hasColumn('projects_cnc_program_parts', 'component_type')) {
                $table->string('component_type')->nullable()->after('notes')
                    ->comment('Type of component: door, drawer, panel, shelf, etc.');
            }
            if (!Schema::hasColumn('projects_cnc_program_parts', 'component_id')) {
                $table->unsignedBigInteger('component_id')->nullable()->after('component_type')
                    ->comment('ID of the related component');
            }

            // Part identification
            if (!Schema::hasColumn('projects_cnc_program_parts', 'part_label')) {
                $table->string('part_label')->nullable()->after('component_id')
                    ->comment('Human-readable label for the part');
            }
            if (!Schema::hasColumn('projects_cnc_program_parts', 'position_data')) {
                $table->json('position_data')->nullable()->after('part_label')
                    ->comment('JSON data for part positioning/orientation');
            }
        });

        // Add indexes if they don't exist (check via raw query)
        $existingIndexes = collect(DB::select("SHOW INDEX FROM projects_cnc_program_parts"))
            ->pluck('Key_name')->unique()->toArray();

        Schema::table('projects_cnc_program_parts', function (Blueprint $table) use ($existingIndexes) {
            if (!in_array('projects_cnc_program_parts_nc_drive_id_index', $existingIndexes)) {
                $table->index('nc_drive_id');
            }
            if (!in_array('projects_cnc_program_parts_reference_photo_drive_id_index', $existingIndexes)) {
                $table->index('reference_photo_drive_id');
            }
            if (!in_array('projects_cnc_program_parts_component_type_component_id_index', $existingIndexes)) {
                $table->index(['component_type', 'component_id']);
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('projects_cnc_program_parts', function (Blueprint $table) {
            $table->dropIndex(['nc_drive_id']);
            $table->dropIndex(['reference_photo_drive_id']);
            $table->dropIndex(['component_type', 'component_id']);

            $table->dropColumn([
                'nc_drive_id',
                'nc_drive_url',
                'reference_photo_drive_id',
                'reference_photo_url',
                'component_type',
                'component_id',
                'part_label',
                'position_data',
            ]);
        });
    }
};
