<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Add missing fields from original TCS Website schema
 * to ensure data migration compatibility.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Home Sections - add CTA fields from original
        Schema::table('tcs_home_sections', function (Blueprint $table) {
            $table->string('cta_text')->nullable()->after('content');
            $table->string('cta_link')->nullable()->after('cta_text');
        });

        // Portfolio Projects - add published_at and timeline from original
        Schema::table('tcs_portfolio_projects', function (Blueprint $table) {
            $table->timestamp('published_at')->nullable()->after('is_published');
            $table->string('timeline')->nullable()->after('dimensions');
        });

        // Services - add published_at from original
        Schema::table('tcs_services', function (Blueprint $table) {
            $table->timestamp('published_at')->nullable()->after('is_published');
        });

        // Materials - add content and properties from original (for data migration)
        Schema::table('tcs_materials', function (Blueprint $table) {
            $table->longText('content')->nullable()->after('description');
            $table->json('properties')->nullable()->after('gallery');
            $table->text('sustainability')->nullable()->after('sustainability_rating');
        });

        // Team Members - add Zoho integration fields from original
        Schema::table('tcs_team_members', function (Blueprint $table) {
            $table->string('zoho_employee_id')->nullable()->unique()->after('employee_id');
            $table->string('zoho_department')->nullable()->after('zoho_employee_id');
            $table->string('zoho_role')->nullable()->after('zoho_department');
            $table->string('zoho_status')->nullable()->after('zoho_role');
            $table->timestamp('zoho_join_date')->nullable()->after('zoho_status');
            $table->timestamp('zoho_leave_date')->nullable()->after('zoho_join_date');
        });
    }

    public function down(): void
    {
        Schema::table('tcs_home_sections', function (Blueprint $table) {
            $table->dropColumn(['cta_text', 'cta_link']);
        });

        Schema::table('tcs_portfolio_projects', function (Blueprint $table) {
            $table->dropColumn(['published_at', 'timeline']);
        });

        Schema::table('tcs_services', function (Blueprint $table) {
            $table->dropColumn('published_at');
        });

        Schema::table('tcs_materials', function (Blueprint $table) {
            $table->dropColumn(['content', 'properties', 'sustainability']);
        });

        Schema::table('tcs_team_members', function (Blueprint $table) {
            $table->dropColumn([
                'zoho_employee_id',
                'zoho_department',
                'zoho_role',
                'zoho_status',
                'zoho_join_date',
                'zoho_leave_date',
            ]);
        });
    }
};
