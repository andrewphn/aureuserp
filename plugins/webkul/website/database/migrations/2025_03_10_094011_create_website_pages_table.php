<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('website_pages', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('content');
            $table->string('slug')->unique();
            $table->boolean('is_published')->default(0);
            $table->boolean('is_header_visible')->default(0);
            $table->boolean('is_footer_visible')->default(0);
            $table->datetime('published_at')->nullable();
            $table->string('meta_title')->nullable();
            $table->string('meta_keywords')->nullable();
            $table->text('meta_description')->nullable();

            $table->foreignId('creator_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->softDeletes();
            $table->timestamps();
        });

        DB::table('website_pages')->insert([
            [
                'title'             => 'Home',
                'content'           => 'Home Content',
                'slug'              => 'home',
                'is_published'      => 1,
                'is_header_visible' => 0,
                'is_footer_visible' => 0,
                'published_at'      => now(),
                'meta_title'        => 'Home',
                'meta_keywords'     => 'home',
                'meta_description'  => 'Home Description',
                'creator_id'        => 1,
                'created_at'        => now(),
                'updated_at'        => now(),
            ],
            [
                'title'             => 'About Us',
                'content'           => 'About Us Content',
                'slug'              => 'about-us',
                'is_published'      => 1,
                'is_header_visible' => 1,
                'is_footer_visible' => 1,
                'published_at'      => now(),
                'meta_title'        => 'About Us',
                'meta_keywords'     => 'about us',
                'meta_description'  => 'About Us Description',
                'creator_id'        => 1,
                'created_at'        => now(),
                'updated_at'        => now(),
            ],
            [
                'title'             => 'Privacy Policy',
                'content'           => 'Privacy Policy Content',
                'slug'              => 'privacy-policy',
                'is_published'      => 1,
                'is_header_visible' => 0,
                'is_footer_visible' => 1,
                'published_at'      => now(),
                'meta_title'        => 'Privacy Policy',
                'meta_keywords'     => 'privacy policy',
                'meta_description'  => 'Privacy Policy Description',
                'creator_id'        => 1,
                'created_at'        => now(),
                'updated_at'        => now(),
            ],
            [
                'title'             => 'Terms & Conditions',
                'content'           => 'Terms & Conditions Content',
                'slug'              => 'terms-conditions',
                'is_published'      => 1,
                'is_header_visible' => 0,
                'is_footer_visible' => 1,
                'published_at'      => now(),
                'meta_title'        => 'Terms & Conditions',
                'meta_keywords'     => 'terms & conditions',
                'meta_description'  => 'Terms & Conditions Description',
                'creator_id'        => 1,
                'created_at'        => now(),
                'updated_at'        => now(),
            ],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('website_pages');
    }
};
