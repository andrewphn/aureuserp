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
        Schema::create('pdf_page_rooms', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('pdf_page_id');
            $table->unsignedBigInteger('room_id')->nullable()->comment('Link to projects_rooms if applicable');
            $table->string('room_number')->nullable()->comment('Room number/identifier');
            $table->string('room_type')->nullable()->comment('kitchen, bathroom, bedroom, etc.');
            $table->timestamps();

            // Foreign keys
            $table->foreign('pdf_page_id')->references('id')->on('pdf_page_metadata')->onDelete('cascade');
            $table->foreign('room_id')->references('id')->on('projects_rooms')->onDelete('set null');

            // Indexes
            $table->index('pdf_page_id');
            $table->index('room_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pdf_page_rooms');
    }
};
