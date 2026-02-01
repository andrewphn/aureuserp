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
        Schema::create('projects_google_drive_watches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained('projects_projects')->onDelete('cascade');
            $table->string('channel_id', 255)->unique();
            $table->string('resource_id', 255);
            $table->string('resource_uri', 500)->nullable();
            $table->string('folder_id', 255);
            $table->timestamp('expires_at');
            $table->timestamps();

            $table->index('project_id');
            $table->index('expires_at');
            $table->index(['channel_id', 'resource_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('projects_google_drive_watches');
    }
};
