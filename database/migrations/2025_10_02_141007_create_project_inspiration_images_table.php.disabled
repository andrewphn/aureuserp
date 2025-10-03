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
        Schema::create('project_inspiration_images', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('project_id');
            $table->string('file_name', 255);
            $table->string('file_path', 500);
            $table->integer('file_size');
            $table->string('mime_type', 100);
            $table->integer('width')->nullable();
            $table->integer('height')->nullable();
            $table->unsignedBigInteger('uploaded_by');
            $table->text('description')->nullable();
            $table->json('tags')->nullable();
            $table->json('metadata')->nullable();
            $table->integer('sort_order')->default(0);
            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index('project_id');
            $table->index('uploaded_by');
            $table->index('sort_order');

            // Foreign keys
            $table->foreign('project_id')
                  ->references('id')
                  ->on('projects_projects')
                  ->onDelete('cascade');

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
        Schema::dropIfExists('project_inspiration_images');
    }
};
