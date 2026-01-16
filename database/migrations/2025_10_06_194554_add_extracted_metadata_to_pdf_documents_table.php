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
            // JSON field for extracted metadata
            $table->json('extracted_metadata')->nullable()->after('processing_error');

            // Flag to track if extraction has been reviewed
            $table->boolean('metadata_reviewed')->default(false)->after('extracted_metadata');

            // Timestamp for when extraction was performed
            $table->timestamp('extracted_at')->nullable()->after('metadata_reviewed');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pdf_documents', function (Blueprint $table) {
            $table->dropColumn(['extracted_metadata', 'metadata_reviewed', 'extracted_at']);
        });
    }
};
