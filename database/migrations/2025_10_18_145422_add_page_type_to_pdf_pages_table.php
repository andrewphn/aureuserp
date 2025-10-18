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
            $table->string('page_type')->nullable()->after('page_number')
                ->comment('cover_page, floor_plan, elevation, section, detail, schedule, etc.');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pdf_pages', function (Blueprint $table) {
            $table->dropColumn('page_type');
        });
    }
};
