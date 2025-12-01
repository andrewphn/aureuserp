<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Backfill project_id on pdf_pages from their associated documents.
     *
     * PdfDocument uses polymorphic module_type/module_id, so we need to
     * extract the project_id for pages that belong to project documents.
     */
    public function up(): void
    {
        // Update pdf_pages with project_id from their documents
        // where the document's module_type indicates it's a project document
        DB::statement("
            UPDATE pdf_pages pp
            INNER JOIN pdf_documents pd ON pp.document_id = pd.id
            SET pp.project_id = pd.module_id
            WHERE pd.module_type LIKE '%Project%'
              AND pp.project_id IS NULL
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Can't really reverse this - data loss would occur
        // Just leave project_id as-is
    }
};
