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
            // Versioning fields
            $table->integer('version_number')->default(1)->after('page_count');
            $table->unsignedBigInteger('parent_document_id')->nullable()->after('version_number');
            $table->boolean('is_current_version')->default(true)->after('parent_document_id');
            $table->timestamp('version_created_at')->nullable()->after('is_current_version');
            $table->string('version_notes', 500)->nullable()->after('version_created_at');

            // Foreign key for parent document
            $table->foreign('parent_document_id')
                ->references('id')
                ->on('pdf_documents')
                ->onDelete('set null');

            // Index for performance
            $table->index(['module_type', 'module_id', 'is_current_version'], 'idx_current_version');
            $table->index('parent_document_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pdf_documents', function (Blueprint $table) {
            $table->dropForeign(['parent_document_id']);
            $table->dropIndex('idx_current_version');
            $table->dropIndex(['parent_document_id']);

            $table->dropColumn([
                'version_number',
                'parent_document_id',
                'is_current_version',
                'version_created_at',
                'version_notes',
            ]);
        });
    }
};
