<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration to enhance pdf_pages table with proper page classification
 *
 * Based on analysis of architectural drawing PDFs (e.g., 25 Friendship Lane),
 * pages are not simple types but composite documents that contain:
 * - Multiple view types (elevation, plan view, sections)
 * - Location references (which cabinet runs are documented)
 * - Hardware schedules and material specifications
 *
 * This migration adds fields to properly classify PDF pages as containers
 * for location/cabinet run documentation rather than simple page types.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('pdf_pages', function (Blueprint $table) {
            // Primary purpose of the page (replaces simple page_type)
            $table->string('primary_purpose')->nullable()->after('page_type')
                ->comment('cover, plan_view, overview, location_detail, multi_location, millwork, reference');

            // Page label from the drawing (e.g., "Sink Wall", "Fridge Wall", "Island")
            $table->string('page_label')->nullable()->after('primary_purpose')
                ->comment('Label shown in drawing title block');

            // Drawing number (e.g., "A-101", "3 of 6")
            $table->string('drawing_number')->nullable()->after('page_label')
                ->comment('Drawing/detail number from title block');

            // View types present on this page (JSON array)
            $table->json('view_types')->nullable()->after('drawing_number')
                ->comment('Array of view types: elevation, upper_plan, lower_plan, sections, etc.');

            // Section letters/numbers present (e.g., ["A", "B", "C", "D"])
            $table->json('section_labels')->nullable()->after('view_types')
                ->comment('Section cut labels present on page');

            // Does this page have a hardware schedule?
            $table->boolean('has_hardware_schedule')->default(false)->after('section_labels');

            // Does this page have material specifications?
            $table->boolean('has_material_spec')->default(false)->after('has_hardware_schedule');

            // Locations documented on this page (JSON array of location references)
            // Structure: [{"name": "Sink Wall", "linear_feet": 8.25, "pricing_tier": 4}]
            $table->json('locations_documented')->nullable()->after('has_material_spec')
                ->comment('Array of cabinet run/locations detailed on this page');

            // Appliances referenced on this page (JSON array)
            $table->json('appliances')->nullable()->after('locations_documented')
                ->comment('Array of appliance callouts: [{model, brand, type}]');

            // Finish/Material info extracted from page
            $table->string('face_frame_material')->nullable()->after('appliances')
                ->comment('Door/face frame material (e.g., Paint Grade: Maple/Medex)');

            $table->string('interior_material')->nullable()->after('face_frame_material')
                ->comment('Interior plywood material (e.g., Prefinished Maple/Birch)');

            // Is this a detail page for a specific location?
            $table->boolean('is_location_detail')->default(false)->after('interior_material')
                ->comment('True if page contains full fabrication details for a location');

            // Notes/comments about this page
            $table->text('page_notes')->nullable()->after('is_location_detail');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pdf_pages', function (Blueprint $table) {
            $table->dropColumn([
                'primary_purpose',
                'page_label',
                'drawing_number',
                'view_types',
                'section_labels',
                'has_hardware_schedule',
                'has_material_spec',
                'locations_documented',
                'appliances',
                'face_frame_material',
                'interior_material',
                'is_location_detail',
                'page_notes',
            ]);
        });
    }
};
