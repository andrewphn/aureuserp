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
     * Drops the old generic pdf_annotations system that was not used by any code.
     * This makes way for the correct pdf_page_annotations system with cabinet/room relationships.
     */
    public function up(): void
    {
        // Drop history table first (has foreign key to pdf_annotations)
        Schema::dropIfExists('pdf_annotation_history');

        // Drop old generic annotations table
        Schema::dropIfExists('pdf_annotations');
    }

    /**
     * Reverse the migrations.
     *
     * Cannot restore - this is a one-way migration.
     * The old system was not used and contained no data.
     */
    public function down(): void
    {
        // Cannot restore old system - it was wrong and not used
        // The correct system (pdf_page_annotations) will be created in subsequent migrations
    }
};
