<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tcs_materials', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('scientific_name')->nullable();
            $table->string('common_names')->nullable();
            $table->string('type')->default('wood');
            $table->longText('description')->nullable();
            $table->longText('characteristics')->nullable();
            $table->string('featured_image')->nullable();
            $table->json('gallery')->nullable();

            // Wood Properties
            $table->string('origin')->nullable();
            $table->string('hardness')->nullable();
            $table->string('density')->nullable();
            $table->string('grain_pattern')->nullable();
            $table->string('color_range')->nullable();
            $table->string('workability')->nullable();
            $table->string('durability')->nullable();
            $table->string('sustainability_rating')->nullable();

            // Applications
            $table->json('applications')->nullable();
            $table->json('best_uses')->nullable();
            $table->json('finish_recommendations')->nullable();

            $table->boolean('is_published')->default(false);
            $table->boolean('featured')->default(false);
            $table->integer('position')->default(0);
            $table->string('meta_title')->nullable();
            $table->text('meta_description')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tcs_materials');
    }
};
