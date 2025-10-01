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
        Schema::create('pdf_documents', function (Blueprint $table) {
            $table->id();
            $table->string('module_type', 50)->index();
            $table->unsignedBigInteger('module_id')->index();
            $table->string('file_name', 255);
            $table->string('file_path', 500);
            $table->integer('file_size');
            $table->string('mime_type', 100)->default('application/pdf');
            $table->integer('page_count')->default(1);
            $table->unsignedBigInteger('uploaded_by');
            $table->json('tags')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index(['module_type', 'module_id']);
            $table->index('uploaded_by');

            // Foreign keys
            $table->foreign('uploaded_by')
                  ->references('id')
                  ->on('users')
                  ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pdf_documents');
    }
};
