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
        Schema::create('projects_milestone_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name')->index();
            $table->enum('production_stage', [
                'discovery',
                'design',
                'sourcing',
                'production',
                'delivery',
            ])->index();
            $table->boolean('is_critical')->default(false);
            $table->text('description')->nullable();
            $table->integer('relative_days')->default(0)
                ->comment('Days offset from project start date (or stage start)');
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('projects_milestone_templates');
    }
};
