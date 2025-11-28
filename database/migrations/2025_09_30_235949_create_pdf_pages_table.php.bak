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
        Schema::create('pdf_pages', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('document_id');
            $table->integer('page_number');
            $table->integer('width')->nullable();
            $table->integer('height')->nullable();
            $table->integer('rotation')->default(0);
            $table->string('thumbnail_path', 500)->nullable();
            $table->longText('extracted_text')->nullable();
            $table->json('page_metadata')->nullable();
            $table->timestamps();

            // Foreign key with cascade delete
            $table->foreign('document_id')
                  ->references('id')
                  ->on('pdf_documents')
                  ->onDelete('cascade');

            // Composite index for efficient page lookup
            $table->index(['document_id', 'page_number']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pdf_pages');
    }
};
