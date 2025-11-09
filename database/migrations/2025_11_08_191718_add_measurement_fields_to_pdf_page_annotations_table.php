<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Add measurement_width and measurement_height fields for cabinet/cabinet_run annotations
     * These store physical dimensions in inches as input by users in the annotation editor
     */
    public function up(): void
    {
        Schema::table('pdf_page_annotations', function (Blueprint $table) {
            $table->decimal('measurement_width', 10, 2)
                ->nullable()
                ->after('height')
                ->comment('Actual measured width in inches (for cabinets/cabinet runs)');

            $table->decimal('measurement_height', 10, 2)
                ->nullable()
                ->after('measurement_width')
                ->comment('Actual measured height in inches (for cabinets/cabinet runs)');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pdf_page_annotations', function (Blueprint $table) {
            $table->dropColumn(['measurement_width', 'measurement_height']);
        });
    }
};
