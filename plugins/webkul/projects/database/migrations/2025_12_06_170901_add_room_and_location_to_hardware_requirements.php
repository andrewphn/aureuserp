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
        Schema::table('hardware_requirements', function (Blueprint $table) {
            // Add room_id and room_location_id for multi-level hardware assignment
            $table->foreignId('room_id')->nullable()->after('id')->constrained('projects_rooms')->nullOnDelete();
            $table->foreignId('room_location_id')->nullable()->after('room_id')->constrained('projects_room_locations')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('hardware_requirements', function (Blueprint $table) {
            $table->dropForeign(['room_id']);
            $table->dropForeign(['room_location_id']);
            $table->dropColumn(['room_id', 'room_location_id']);
        });
    }
};
