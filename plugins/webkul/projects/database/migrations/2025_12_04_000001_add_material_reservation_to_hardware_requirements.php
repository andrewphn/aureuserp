<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Connects hardware requirements to material reservations (inventory allocations).
     * This bridges the gap between "what hardware is needed" and "where it's allocated from".
     */
    public function up(): void
    {
        Schema::table('hardware_requirements', function (Blueprint $table) {
            // Link to material reservation (inventory allocation)
            $table->foreignId('material_reservation_id')
                ->nullable()
                ->after('hardware_issued_at')
                ->comment('Links to inventory allocation record');

            $table->foreign('material_reservation_id')
                ->references('id')
                ->on('projects_material_reservations')
                ->onDelete('set null');

            // Add index for faster lookups
            $table->index('material_reservation_id', 'idx_hardware_reservation');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('hardware_requirements', function (Blueprint $table) {
            $table->dropForeign(['material_reservation_id']);
            $table->dropIndex('idx_hardware_reservation');
            $table->dropColumn('material_reservation_id');
        });
    }
};
