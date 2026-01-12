<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Creates faceframes table with 1:1 relationship to cabinet runs.
     * A faceframe defines the frame structure (stiles, rails, material, joinery)
     * for all cabinets in a cabinet run.
     */
    public function up(): void
    {
        Schema::create('projects_faceframes', function (Blueprint $table) {
            $table->id();

            // Cabinet run relationship (1:1)
            $table->foreignId('cabinet_run_id')
                ->unique()
                ->constrained('projects_cabinet_runs')
                ->onDelete('cascade')
                ->comment('Parent cabinet run (one faceframe per run)');

            // Face Frame Type & Dimensions (all optional - can be filled in later)
            $table->string('face_frame_type', 50)
                ->nullable()
                ->comment('Face frame type (e.g., False Frame)');

            $table->string('face_frame_thickness', 50)
                ->nullable()
                ->comment('Face frame thickness (e.g., 3/4", 1")');

            $table->decimal('face_frame_linear_feet', 8, 2)
                ->nullable()
                ->comment('Estimated face frame linear feet (e.g., 6 LF for base run)');

            // Frame Dimensions
            $table->decimal('stile_width', 5, 3)
                ->nullable()
                ->comment('Vertical stile width (typically 1.5")');

            $table->decimal('rail_width', 5, 3)
                ->nullable()
                ->comment('Horizontal rail width (typically 1.5" or 2.5")');

            $table->decimal('material_thickness', 5, 3)
                ->nullable()
                ->comment('Actual thickness of face frame material');

            // Material & Finish
            $table->string('material', 100)
                ->nullable()
                ->comment('Solid wood species for face frame');

            $table->string('finish_option', 100)
                ->nullable()
                ->comment('Finish applied to face frame');

            // Construction Details
            $table->string('joinery_type', 50)
                ->nullable()
                ->comment('Joinery method: pocket_hole, dowel, mortise_tenon, butt_joint');

            $table->boolean('beaded_face_frame')
                ->default(false)
                ->comment('Beaded inset detail (affects tier pricing)');

            $table->string('overlay_type', 50)
                ->nullable()
                ->comment('Door overlay: inset, full_overlay, partial_overlay (affects door sizing)');

            // Relationship to Carcass
            $table->boolean('flush_with_carcass')
                ->default(true)
                ->comment('Frame flush with carcass edge');

            $table->decimal('overhang_left', 5, 3)
                ->nullable()
                ->comment('Overhang on left side (inches)');

            $table->decimal('overhang_right', 5, 3)
                ->nullable()
                ->comment('Overhang on right side (inches)');

            $table->decimal('overhang_top', 5, 3)
                ->nullable()
                ->comment('Overhang on top (inches)');

            $table->decimal('overhang_bottom', 5, 3)
                ->nullable()
                ->comment('Overhang on bottom (inches)');

            // Metadata
            $table->text('notes')
                ->nullable()
                ->comment('Face frame specific notes');

            $table->foreignId('creator_id')
                ->nullable()
                ->constrained('users')
                ->onDelete('set null');

            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index('cabinet_run_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('projects_faceframes');
    }
};
