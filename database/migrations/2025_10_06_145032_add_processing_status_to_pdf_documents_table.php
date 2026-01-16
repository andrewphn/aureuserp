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
        Schema::table('pdf_documents', function (Blueprint $table) {
            $table->enum('processing_status', ['pending', 'processing', 'completed', 'failed'])
                  ->default('pending')
                  ->after('page_count')
                  ->index();
            $table->text('processing_error')->nullable()->after('processing_status');
            $table->timestamp('processed_at')->nullable()->after('processing_error');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pdf_documents', function (Blueprint $table) {
            $table->dropColumn(['processing_status', 'processing_error', 'processed_at']);
        });
    }
};
