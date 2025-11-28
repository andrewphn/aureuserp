<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new /**
 * extends class
 *
 */
class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('pdf_annotations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('document_id');
            $table->integer('page_number');
            $table->string('annotation_type', 50);
            $table->json('annotation_data');
            $table->unsignedBigInteger('author_id');
            $table->string('author_name', 255);
            $table->timestamps();
            $table->softDeletes();

            // Foreign keys
            $table->foreign('document_id')
                  ->references('id')
                  ->on('pdf_documents')
                  ->onDelete('cascade');

            $table->foreign('author_id')
                  ->references('id')
                  ->on('users')
                  ->onDelete('cascade');

            // Indexes for efficient querying
            $table->index(['document_id', 'page_number']);
            $table->index('author_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pdf_annotations');
    }
};
