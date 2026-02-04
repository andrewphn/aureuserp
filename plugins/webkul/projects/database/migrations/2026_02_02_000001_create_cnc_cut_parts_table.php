<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Create CNC Cut Parts table
 * 
 * Tracks individual cabinet parts cut on each CNC sheet.
 * Part labels like "BS-1", "DR-2", "SH-3" identify specific cabinet components.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('projects_cnc_cut_parts', function (Blueprint $table) {
            $table->id();
            
            // Parent sheet (CncProgramPart = one sheet file)
            $table->foreignId('cnc_program_part_id')
                ->constrained('projects_cnc_program_parts')
                ->cascadeOnDelete();
            
            // Part identification
            $table->string('part_label', 50);  // e.g., "BS-1", "DR-2", "SH-3"
            $table->string('part_type', 50)->nullable();  // e.g., "side", "door", "drawer_front"
            $table->string('description')->nullable();  // e.g., "Base Cabinet Left Side"
            
            // Link to cabinet component (optional)
            $table->string('component_type', 100)->nullable();  // Model class
            $table->unsignedBigInteger('component_id')->nullable();  // Model ID
            
            // Position on sheet (from VCarve SVG)
            $table->decimal('x_position', 10, 3)->nullable();
            $table->decimal('y_position', 10, 3)->nullable();
            $table->decimal('width', 10, 3)->nullable();
            $table->decimal('height', 10, 3)->nullable();
            $table->decimal('rotation', 10, 3)->default(0);
            
            // Dimensions (actual part size)
            $table->decimal('part_width', 10, 3)->nullable();  // inches
            $table->decimal('part_height', 10, 3)->nullable();  // inches
            $table->decimal('part_thickness', 10, 3)->nullable();  // inches
            
            // Status tracking
            $table->enum('status', ['pending', 'cut', 'passed', 'failed', 'recut_needed', 'scrapped'])
                ->default('pending');
            $table->text('failure_reason')->nullable();
            $table->text('notes')->nullable();
            
            // Quality tracking
            $table->boolean('inspected')->default(false);
            $table->timestamp('inspected_at')->nullable();
            $table->foreignId('inspected_by')->nullable()
                ->constrained('users')
                ->nullOnDelete();
            
            // Recut tracking
            $table->boolean('is_recut')->default(false);
            $table->foreignId('original_part_id')->nullable()
                ->constrained('projects_cnc_cut_parts')
                ->nullOnDelete();
            $table->unsignedInteger('recut_count')->default(0);
            
            $table->timestamps();
            
            // Indexes
            $table->index(['cnc_program_part_id', 'part_label']);
            $table->index(['status']);
            $table->index(['component_type', 'component_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('projects_cnc_cut_parts');
    }
};
