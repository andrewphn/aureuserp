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
            $table->unsignedBigInteger('pdf_document_id');
            $table->integer('page_number');
            $table->string('page_type')->nullable()->comment('floor_plan, elevation, section, detail, schedule, etc.');
            $table->unsignedBigInteger('room_id')->nullable()->comment('Link to projects_rooms if applicable');
            $table->string('room_name')->nullable();
            $table->string('room_type')->nullable()->comment('kitchen, bathroom, bedroom, etc.');
            $table->string('detail_number')->nullable()->comment('A-101, D-3, etc.');
            $table->text('notes')->nullable();
            $table->json('metadata')->nullable()->comment('Additional extracted data');
            $table->unsignedBigInteger('creator_id')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Foreign keys
            $table->foreign('pdf_document_id')->references('id')->on('pdf_documents')->onDelete('cascade');
            $table->foreign('room_id')->references('id')->on('projects_rooms')->onDelete('set null');
            $table->foreign('creator_id')->references('id')->on('users')->onDelete('set null');

            // Indexes
            $table->index(['pdf_document_id', 'page_number']);
            $table->index('page_type');
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
