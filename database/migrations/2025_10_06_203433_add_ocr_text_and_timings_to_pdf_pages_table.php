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
        Schema::table('pdf_pages', function (Blueprint $table) {
            // OCR extracted text (for comparison with native extraction)
            $table->longText('ocr_text')->nullable()->after('extracted_text');

            // Timing metrics for performance analysis
            $table->integer('extraction_time_ms')->nullable()->after('ocr_text')
                ->comment('Native PDF text extraction time in milliseconds');
            $table->integer('ocr_time_ms')->nullable()->after('extraction_time_ms')
                ->comment('Tesseract OCR extraction time in milliseconds');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pdf_pages', function (Blueprint $table) {
            $table->dropColumn(['ocr_text', 'extraction_time_ms', 'ocr_time_ms']);
        });
    }
};
