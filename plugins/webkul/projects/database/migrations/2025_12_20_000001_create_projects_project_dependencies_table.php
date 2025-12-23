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
        Schema::create('projects_project_dependencies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')
                ->constrained('projects_projects')
                ->cascadeOnDelete();
            $table->foreignId('depends_on_id')
                ->constrained('projects_projects')
                ->cascadeOnDelete();
            $table->string('dependency_type')->default('finish_to_start');
            $table->integer('lag_days')->default(0);
            $table->timestamps();

            // Prevent duplicate dependencies
            $table->unique(['project_id', 'depends_on_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('projects_project_dependencies');
    }
};
