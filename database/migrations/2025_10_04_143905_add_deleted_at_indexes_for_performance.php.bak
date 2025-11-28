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
        // Add composite indexes for deleted_at queries (FilamentPHP v4 optimization)
        // These indexes dramatically improve WHERE deleted_at IS NULL query performance

        if (Schema::hasTable('companies')) {
            Schema::table('companies', function (Blueprint $table) {
                $table->index(['deleted_at', 'id'], 'companies_deleted_at_id_index');
            });
        }

        if (Schema::hasTable('partners_partners')) {
            Schema::table('partners_partners', function (Blueprint $table) {
                $table->index(['deleted_at', 'id'], 'partners_deleted_at_id_index');
            });
        }

        if (Schema::hasTable('projects_tags')) {
            Schema::table('projects_tags', function (Blueprint $table) {
                $table->index(['deleted_at', 'id'], 'projects_tags_deleted_at_id_index');
            });
        }

        if (Schema::hasTable('users')) {
            Schema::table('users', function (Blueprint $table) {
                $table->index(['deleted_at', 'id'], 'users_deleted_at_id_index');
            });
        }

        if (Schema::hasTable('projects_projects')) {
            Schema::table('projects_projects', function (Blueprint $table) {
                $table->index(['deleted_at', 'id'], 'projects_deleted_at_id_index');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('companies')) {
            Schema::table('companies', function (Blueprint $table) {
                $table->dropIndex('companies_deleted_at_id_index');
            });
        }

        if (Schema::hasTable('partners_partners')) {
            Schema::table('partners_partners', function (Blueprint $table) {
                $table->dropIndex('partners_deleted_at_id_index');
            });
        }

        if (Schema::hasTable('projects_tags')) {
            Schema::table('projects_tags', function (Blueprint $table) {
                $table->dropIndex('projects_tags_deleted_at_id_index');
            });
        }

        if (Schema::hasTable('users')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropIndex('users_deleted_at_id_index');
            });
        }

        if (Schema::hasTable('projects_projects')) {
            Schema::table('projects_projects', function (Blueprint $table) {
                $table->dropIndex('projects_deleted_at_id_index');
            });
        }
    }
};
