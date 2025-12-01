<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Adds room_id foreign key and title field to inspiration images.
     * Enables filtering images by room and providing explicit titles.
     */
    public function up(): void
    {
        Schema::table('project_inspiration_images', function (Blueprint $table) {
            // Add title field for explicit image naming
            $table->string('title', 255)
                ->nullable()
                ->after('file_name')
                ->comment('User-defined title for the image');

            // Add room relationship for filtering/organization
            $table->foreignId('room_id')
                ->nullable()
                ->after('project_id')
                ->constrained('projects_rooms')
                ->nullOnDelete()
                ->comment('Optional room association for filtering');

            // Index for room filtering
            $table->index('room_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('project_inspiration_images', function (Blueprint $table) {
            $table->dropForeign(['room_id']);
            $table->dropIndex(['room_id']);
            $table->dropColumn(['room_id', 'title']);
        });
    }
};
