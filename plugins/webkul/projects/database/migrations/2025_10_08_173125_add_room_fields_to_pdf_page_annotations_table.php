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
        Schema::table('pdf_page_annotations', function (Blueprint $table) {
            $table->string('room_type')->nullable()->after('label');
            $table->string('color', 20)->nullable()->after('room_type');
            $table->unsignedBigInteger('room_id')->nullable()->after('cabinet_run_id');
            $table->json('metadata')->nullable()->after('notes');
            $table->unsignedBigInteger('created_by')->nullable()->after('metadata');

            // Add index for room_type for performance
            $table->index('room_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pdf_page_annotations', function (Blueprint $table) {
            $table->dropIndex(['room_type']);
            $table->dropColumn(['room_type', 'color', 'room_id', 'metadata', 'created_by']);
        });
    }
};
