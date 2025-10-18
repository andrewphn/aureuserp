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
        Schema::table('pdf_annotation_history', function (Blueprint $table) {
            // Drop the incorrect foreign key constraint
            $table->dropForeign(['annotation_id']);

            // Add the correct foreign key constraint pointing to pdf_page_annotations
            $table->foreign('annotation_id')
                ->references('id')
                ->on('pdf_page_annotations')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pdf_annotation_history', function (Blueprint $table) {
            // Drop the correct foreign key
            $table->dropForeign(['annotation_id']);

            // Restore the incorrect foreign key (for rollback)
            $table->foreign('annotation_id')
                ->references('id')
                ->on('pdf_annotations')
                ->onDelete('cascade');
        });
    }
};
