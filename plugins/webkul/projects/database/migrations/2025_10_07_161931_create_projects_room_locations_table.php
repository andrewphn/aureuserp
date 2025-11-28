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
     * Creates room locations table to organize specific areas within a room.
     * Each room can have multiple locations (walls, islands, corners).
     *
     * Examples:
     * - Kitchen: "North Wall", "Island", "Peninsula", "Corner Pantry"
     * - Bathroom: "Vanity Wall", "Linen Closet", "Tub Surround"
     *
     * Workflow:
     * 1. Room is created from architectural PDF
     * 2. Identify distinct locations within room
     * 3. Create location for each wall/area with cabinets
     * 4. Cabinet runs are then created for each location
     */
    public function up(): void
    {
        Schema::create('projects_room_locations', function (Blueprint $table) {
            $table->id();

            // Room relationship
            $table->foreignId('room_id')
                ->constrained('projects_rooms')
                ->onDelete('cascade')
                ->comment('Parent room');

            // Location identification
            $table->string('name')
                ->comment('Location name (e.g., "North Wall", "Island", "Corner Unit")');

            $table->string('location_type')
                ->nullable()
                ->comment('Type: wall, island, peninsula, standalone, corner');

            $table->integer('sequence')
                ->default(0)
                ->comment('Left-to-right or clockwise order within room');

            $table->string('elevation_reference')
                ->nullable()
                ->comment('Architectural elevation reference if available');

            // General info
            $table->text('notes')
                ->nullable()
                ->comment('Location-specific notes and specifications');

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
            $table->index('room_id');
            $table->index(['room_id', 'sort_order']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('projects_room_locations');
    }
};
