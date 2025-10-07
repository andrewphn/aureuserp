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
        Schema::create('pdf_document_tag', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('pdf_document_id');
            $table->unsignedBigInteger('tag_id');
            $table->timestamps();

            // Foreign keys
            $table->foreign('pdf_document_id')->references('id')->on('pdf_documents')->onDelete('cascade');
            $table->foreign('tag_id')->references('id')->on('projects_tags')->onDelete('cascade');

            // Unique constraint to prevent duplicate tag assignments
            $table->unique(['pdf_document_id', 'tag_id']);

            // Indexes
            $table->index('pdf_document_id');
            $table->index('tag_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pdf_document_tag');
    }
};
