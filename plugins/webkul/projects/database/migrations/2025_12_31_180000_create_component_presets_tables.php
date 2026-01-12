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
        // Door Presets
        Schema::create('projects_door_presets', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);

            // Door specifications
            $table->string('profile_type')->nullable(); // shaker, flat_panel, beaded, raised_panel, slab
            $table->string('fabrication_method')->nullable(); // cnc, five_piece_manual, slab
            $table->string('hinge_type')->nullable(); // blind_inset, half_overlay, euro_concealed, specialty
            $table->integer('default_hinge_quantity')->default(2);

            // Glass options
            $table->boolean('has_glass')->default(false);
            $table->string('glass_type')->nullable(); // clear, seeded, frosted, mullioned, leaded
            $table->boolean('has_check_rail')->default(false);

            // Default dimensions
            $table->decimal('default_rail_width_inches', 8, 3)->nullable();
            $table->decimal('default_style_width_inches', 8, 3)->nullable();

            // Complexity
            $table->decimal('estimated_complexity_score', 8, 2)->nullable();

            $table->timestamps();
        });

        // Drawer Presets
        Schema::create('projects_drawer_presets', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);

            // Drawer specifications
            $table->string('profile_type')->nullable(); // shaker, flat_panel, slab
            $table->string('box_material')->nullable(); // maple, birch, baltic_birch, plywood
            $table->string('joinery_method')->nullable(); // dovetail, pocket_screw, dado, finger

            // Slides
            $table->string('slide_type')->nullable(); // blum_tandem, undermount, full_extension, side_mount
            $table->string('slide_model')->nullable();
            $table->boolean('soft_close')->default(true);

            // Complexity
            $table->decimal('estimated_complexity_score', 8, 2)->nullable();

            $table->timestamps();
        });

        // Shelf Presets
        Schema::create('projects_shelf_presets', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);

            // Shelf specifications
            $table->string('shelf_type')->nullable(); // fixed, adjustable, roll_out, pull_down, corner, floating
            $table->string('material')->nullable(); // plywood, melamine, solid_wood
            $table->string('edge_treatment')->nullable(); // edge_banded, solid_edge, veneer

            // For roll-out/pull-down shelves
            $table->string('slide_type')->nullable();
            $table->string('slide_model')->nullable();
            $table->boolean('soft_close')->default(false);

            // Complexity
            $table->decimal('estimated_complexity_score', 8, 2)->nullable();

            $table->timestamps();
        });

        // Pullout Presets
        Schema::create('projects_pullout_presets', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);

            // Pullout specifications
            $table->string('pullout_type')->nullable(); // trash, spice_rack, lazy_susan, mixer_lift, blind_corner, pantry
            $table->string('manufacturer')->nullable(); // Rev-a-Shelf, Hafele, etc.
            $table->string('model_number')->nullable();

            // Mounting and slides
            $table->string('mounting_type')->nullable(); // bottom_mount, side_mount, door_mount
            $table->string('slide_type')->nullable();
            $table->string('slide_model')->nullable();
            $table->boolean('soft_close')->default(true);

            // Link to inventory product
            $table->foreignId('product_id')->nullable()->constrained('products_products')->nullOnDelete();

            // Complexity
            $table->decimal('estimated_complexity_score', 8, 2)->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('projects_pullout_presets');
        Schema::dropIfExists('projects_shelf_presets');
        Schema::dropIfExists('projects_drawer_presets');
        Schema::dropIfExists('projects_door_presets');
    }
};
