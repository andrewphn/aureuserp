<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new /**
 * extends class
 *
 */
class extends Migration
{
    /**
     * Run the migrations.
     *
     * Creates cabinet runs table to organize series of cabinets along a location.
     * A cabinet run is a continuous series of cabinets (base, wall, or tall).
     *
     * Examples:
     * - "Base Run 1" - Lower cabinets along north wall
     * - "Upper Cabinets A" - Wall cabinets above base run
     * - "Tall Pantry Section" - Full-height cabinet section
     *
     * Workflow:
     * 1. Room location is identified (e.g., "North Wall")
     * 2. Create run for each continuous cabinet series
     * 3. Individual cabinets are then assigned to the run
     * 4. Total linear feet is calculated from cabinet sum
     */
    public function up(): void
    {
        Schema::create('projects_cabinet_runs', function (Blueprint $table) {
            $table->id();

            // Room location relationship
            $table->foreignId('room_location_id')
                ->constrained('projects_room_locations')
                ->onDelete('cascade')
                ->comment('Parent room location');

            // Run identification
            $table->string('name')
                ->comment('Run name (e.g., "Base Run 1", "Upper Cabinets")');

            $table->string('run_type')
                ->nullable()
                ->comment('Type: base, wall, tall, specialty');

            // Measurements
            $table->decimal('total_linear_feet', 8, 2)
                ->default(0)
                ->comment('Total linear feet (calculated from cabinets in run)');

            $table->decimal('start_wall_measurement', 8, 2)
                ->nullable()
                ->comment('Distance in inches from left reference point');

            $table->decimal('end_wall_measurement', 8, 2)
                ->nullable()
                ->comment('Distance in inches from right reference point');

            // General info
            $table->text('notes')
                ->nullable()
                ->comment('Run-specific notes and specifications');

            $table->integer('sort_order')
                ->default(0)
                ->comment('Display order in lists');

            // Metadata
            $table->foreignId('creator_id')
                ->nullable()
                ->constrained('users')
                ->onDelete('set null');
            $table->timestamps();
            $table->softDeletes();

            // Indexes for common queries
            $table->index('room_location_id');
            $table->index(['room_location_id', 'sort_order']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('projects_cabinet_runs');
    }
};
