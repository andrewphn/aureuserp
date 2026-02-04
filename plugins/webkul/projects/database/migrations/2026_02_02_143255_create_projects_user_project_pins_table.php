<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('projects_user_project_pins', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('project_id')->constrained('projects_projects')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['user_id', 'project_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('projects_user_project_pins');
    }
};
