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
        Schema::create('projects_milestone_template_tag', function (Blueprint $table) {
            $table->id();
            $table->foreignId('milestone_template_id')
                ->constrained('projects_milestone_templates')
                ->cascadeOnDelete();
            $table->foreignId('tag_id')
                ->constrained('projects_tags')
                ->cascadeOnDelete();
            $table->timestamps();

            // Prevent duplicate tag assignments
            $table->unique(['milestone_template_id', 'tag_id'], 'milestone_template_tag_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('projects_milestone_template_tag');
    }
};
