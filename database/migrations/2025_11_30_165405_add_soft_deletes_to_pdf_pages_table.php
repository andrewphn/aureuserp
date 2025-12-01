<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Add soft deletes to pdf_pages for cascade delete support with PdfDocument.
     */
    public function up(): void
    {
        Schema::table('pdf_pages', function (Blueprint $table) {
            if (!Schema::hasColumn('pdf_pages', 'deleted_at')) {
                $table->softDeletes();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pdf_pages', function (Blueprint $table) {
            if (Schema::hasColumn('pdf_pages', 'deleted_at')) {
                $table->dropSoftDeletes();
            }
        });
    }
};
