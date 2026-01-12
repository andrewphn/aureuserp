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
        Schema::create('projects_hardware_packages', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('is_default')->default(false);
            $table->integer('sort_order')->default(0);

            // Pricing tier
            $table->string('pricing_tier')->nullable(); // budget, standard, premium, luxury
            $table->decimal('price_multiplier', 5, 2)->default(1.00);

            // Hinge defaults
            $table->string('default_hinge_type')->nullable();
            $table->string('default_hinge_model')->nullable();
            $table->integer('hinges_per_door')->default(2);

            // Slide defaults
            $table->string('default_slide_type')->nullable();
            $table->string('default_slide_model')->nullable();
            $table->boolean('default_soft_close')->default(true);

            // Construction defaults
            $table->string('default_joinery_method')->nullable(); // dovetail, pocket_screw, dado
            $table->string('default_box_material')->nullable(); // maple, birch, baltic_birch

            // UI display
            $table->string('color')->nullable(); // Hex color for UI badges
            $table->string('icon')->nullable(); // Optional icon

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('projects_hardware_packages');
    }
};
