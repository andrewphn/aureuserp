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
     * Adds room and cabinet run organization columns to existing cabinet specifications table.
     * This allows cabinets to be organized hierarchically within projects:
     * Project → Room → Location → Run → Cabinet
     *
     * Backwards compatible: All new columns are nullable so existing records continue to work.
     */
    public function up(): void
    {
        Schema::table('projects_cabinet_specifications', function (Blueprint $table) {
            // Room relationship (optional direct link)
            $table->foreignId('room_id')
                ->nullable()
                ->after('project_id')
                ->constrained('projects_rooms')
                ->onDelete('cascade')
                ->comment('Room where this cabinet is located');

            // Cabinet run relationship
            $table->foreignId('cabinet_run_id')
                ->nullable()
                ->after('room_id')
                ->constrained('projects_cabinet_runs')
                ->onDelete('set null')
                ->comment('Cabinet run this cabinet belongs to (if part of a series)');

            // Cabinet identification within project
            $table->string('cabinet_number')
                ->nullable()
                ->after('cabinet_run_id')
                ->comment('Cabinet identifier (e.g., "BC-1", "WC-12", "TP-3")');

            $table->integer('position_in_run')
                ->nullable()
                ->after('cabinet_number')
                ->comment('Sequential position in cabinet run (1, 2, 3... left to right)');

            $table->decimal('wall_position_start_inches', 8, 2)
                ->nullable()
                ->after('position_in_run')
                ->comment('Distance from left wall reference point (in inches)');

            // Indexes for new foreign keys and queries (with shortened names)
            $table->index('room_id', 'idx_cabinet_specs_room');
            $table->index('cabinet_run_id', 'idx_cabinet_specs_run');
            $table->index(['cabinet_run_id', 'position_in_run'], 'idx_cabinet_specs_run_position');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('projects_cabinet_specifications', function (Blueprint $table) {
            // Drop indexes first (use shortened names)
            $table->dropIndex('idx_cabinet_specs_room');
            $table->dropIndex('idx_cabinet_specs_run');
            $table->dropIndex('idx_cabinet_specs_run_position');

            // Drop foreign keys
            $table->dropForeign(['room_id']);
            $table->dropForeign(['cabinet_run_id']);

            // Drop columns
            $table->dropColumn([
                'room_id',
                'cabinet_run_id',
                'cabinet_number',
                'position_in_run',
                'wall_position_start_inches',
            ]);
        });
    }
};
