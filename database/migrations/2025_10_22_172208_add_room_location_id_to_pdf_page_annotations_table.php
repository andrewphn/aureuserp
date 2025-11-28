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
     */
    public function up(): void
    {
        // Only run if table exists
        if (Schema::hasTable('pdf_page_annotations')) {
            Schema::table('pdf_page_annotations', function (Blueprint $table) {
                // Only add column if it doesn't already exist
                if (!Schema::hasColumn('pdf_page_annotations', 'room_location_id')) {
                    $table->foreignId('room_location_id')
                        ->nullable()
                        ->after('room_id')
                        ->constrained('projects_room_locations')
                        ->onDelete('set null');
                }
            });
        }
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
