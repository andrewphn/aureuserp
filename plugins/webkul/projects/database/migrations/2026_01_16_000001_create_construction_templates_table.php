<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Creates the construction_templates table for configurable cabinet construction standards.
     * Follows DoorPreset pattern for template management.
     *
     * TCS Standards documented by Bryan Patton (Jan 2025)
     */
    public function up(): void
    {
        Schema::create('projects_construction_templates', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->softDeletes();

            // Identity (follows DoorPreset pattern)
            $table->string('name')->unique();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('is_default')->default(false);
            $table->integer('sort_order')->default(0);

            // Cabinet Heights (inches)
            $table->decimal('base_cabinet_height', 8, 4)->default(34.75)
                ->comment('Base cabinet height (TCS: 34 3/4")');
            $table->decimal('wall_cabinet_30_height', 8, 4)->default(30.0)
                ->comment('Wall cabinet 30" height');
            $table->decimal('wall_cabinet_36_height', 8, 4)->default(36.0)
                ->comment('Wall cabinet 36" height');
            $table->decimal('wall_cabinet_42_height', 8, 4)->default(42.0)
                ->comment('Wall cabinet 42" height');
            $table->decimal('tall_cabinet_84_height', 8, 4)->default(84.0)
                ->comment('Tall cabinet 84" height');
            $table->decimal('tall_cabinet_96_height', 8, 4)->default(96.0)
                ->comment('Tall cabinet 96" height');

            // Toe Kick (inches)
            $table->decimal('toe_kick_height', 8, 4)->default(4.5)
                ->comment('Toe kick height (TCS: 4 1/2")');
            $table->decimal('toe_kick_recess', 8, 4)->default(3.0)
                ->comment('Toe kick recess from face (TCS: 3")');

            // Stretchers (inches)
            $table->decimal('stretcher_depth', 8, 4)->default(3.0)
                ->comment('Stretcher depth (TCS: 3")');
            $table->decimal('stretcher_thickness', 8, 4)->default(0.75)
                ->comment('Stretcher thickness (3/4")');
            $table->decimal('stretcher_min_depth', 8, 4)->default(2.5)
                ->comment('Minimum stretcher depth');
            $table->decimal('stretcher_max_depth', 8, 4)->default(4.0)
                ->comment('Maximum stretcher depth');

            // Face Frame (inches)
            $table->decimal('face_frame_stile_width', 8, 4)->default(1.5)
                ->comment('Face frame stile width (TCS: 1 1/2")');
            $table->decimal('face_frame_rail_width', 8, 4)->default(1.5)
                ->comment('Face frame rail width (TCS: 1 1/2")');
            $table->decimal('face_frame_door_gap', 8, 4)->default(0.125)
                ->comment('Gap between face frame and door (TCS: 1/8")');
            $table->decimal('face_frame_thickness', 8, 4)->default(0.75)
                ->comment('Face frame thickness (3/4")');

            // Default Materials - FK to products_products
            // Thickness is pulled from Product attributes, with fallback override
            $table->unsignedBigInteger('default_box_material_product_id')->nullable()
                ->comment('Default sheet goods for cabinet box');
            $table->foreign('default_box_material_product_id', 'ct_box_material_fk')
                ->references('id')->on('products_products')->nullOnDelete();

            $table->unsignedBigInteger('default_back_material_product_id')->nullable()
                ->comment('Default sheet goods for cabinet back');
            $table->foreign('default_back_material_product_id', 'ct_back_material_fk')
                ->references('id')->on('products_products')->nullOnDelete();

            $table->unsignedBigInteger('default_face_frame_material_product_id')->nullable()
                ->comment('Default lumber for face frames');
            $table->foreign('default_face_frame_material_product_id', 'ct_face_frame_fk')
                ->references('id')->on('products_products')->nullOnDelete();

            $table->unsignedBigInteger('default_edge_banding_product_id')->nullable()
                ->comment('Default edge banding product');
            $table->foreign('default_edge_banding_product_id', 'ct_edge_banding_fk')
                ->references('id')->on('products_products')->nullOnDelete();

            // Material thickness overrides (fallback if not pulled from Product)
            $table->decimal('box_material_thickness', 8, 4)->default(0.75)
                ->comment('Box material thickness override (3/4")');
            $table->decimal('back_panel_thickness', 8, 4)->default(0.75)
                ->comment('Back panel thickness override (TCS: 3/4" full backs)');
            $table->decimal('side_panel_thickness', 8, 4)->default(0.75)
                ->comment('Side panel thickness override (3/4")');

            // Sink Cabinet
            $table->decimal('sink_side_extension', 8, 4)->default(0.75)
                ->comment('Sink cabinet side extension for countertop support (TCS: 3/4")');

            // Section Layout Ratios
            $table->decimal('drawer_bank_ratio', 8, 4)->default(0.40)
                ->comment('Default drawer bank width ratio (40%)');
            $table->decimal('door_section_ratio', 8, 4)->default(0.60)
                ->comment('Default door section width ratio (60%)');
            $table->decimal('equal_section_ratio', 8, 4)->default(0.50)
                ->comment('Equal section split ratio (50%)');

            // Countertop
            $table->decimal('countertop_thickness', 8, 4)->default(1.25)
                ->comment('Countertop thickness (TCS: 1 1/4")');
            $table->decimal('finished_counter_height', 8, 4)->default(36.0)
                ->comment('Finished counter height from floor (36")');

            // Metadata
            $table->unsignedBigInteger('creator_id')->nullable();
            $table->foreign('creator_id', 'ct_creator_fk')
                ->references('id')->on('users')->nullOnDelete();

            // Indexes
            $table->index('is_active');
            $table->index('is_default');
            $table->index('sort_order');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('projects_construction_templates');
    }
};
