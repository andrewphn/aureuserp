<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tcs_portfolio_projects', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('summary')->nullable();
            $table->longText('description')->nullable();

            // Classification
            $table->string('category')->nullable();
            $table->string('project_type')->nullable();
            $table->string('style_classification')->nullable();
            $table->string('client_name')->nullable();
            $table->string('client_type')->nullable();
            $table->string('location')->nullable();

            // Materials & Specifications
            $table->json('materials')->nullable();
            $table->json('techniques')->nullable();
            $table->json('dimensions')->nullable();
            $table->json('hardware_specifications')->nullable();
            $table->decimal('square_footage', 10, 2)->nullable();
            $table->decimal('weight', 10, 2)->nullable();

            // Case Study Content
            $table->longText('overview')->nullable();
            $table->longText('discovery')->nullable();
            $table->longText('design')->nullable();
            $table->longText('production')->nullable();
            $table->longText('installation')->nullable();
            $table->longText('client_testimonial')->nullable();

            // Media
            $table->string('featured_image')->nullable();
            $table->string('cover_image')->nullable();
            $table->json('gallery')->nullable();
            $table->json('process_photos')->nullable();
            $table->json('before_after_images')->nullable();

            // Status & Visibility
            $table->boolean('featured')->default(false);
            $table->boolean('is_published')->default(false);
            $table->string('status')->default('draft');
            $table->date('completion_date')->nullable();
            $table->integer('portfolio_order')->default(0);

            // SEO
            $table->string('meta_title')->nullable();
            $table->text('meta_description')->nullable();
            $table->json('seo_tags')->nullable();

            $table->foreignId('creator_id')->nullable()->constrained('users')->nullOnDelete();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tcs_portfolio_projects');
    }
};
