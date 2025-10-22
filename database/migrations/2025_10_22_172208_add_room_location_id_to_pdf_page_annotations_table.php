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
        Schema::table('pdf_page_annotations', function (Blueprint $table) {
            $table->foreignId('room_location_id')
                ->nullable()
                ->after('room_id')
                ->constrained('projects_room_locations')
                ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pdf_page_annotations', function (Blueprint $table) {
            $table->dropForeign(['room_location_id']);
            $table->dropColumn('room_location_id');
        });
    }
};
