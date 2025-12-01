<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Adds draft_number_start column to allow companies to set their
     * starting draft number sequence (e.g., TCS-D001-xxx for drafts/quotes)
     *
     * Draft numbers track inquiries/quotes before they become official projects.
     * This enables conversion rate tracking (drafts vs official projects).
     */
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->unsignedInteger('draft_number_start')
                ->nullable()
                ->default(1)
                ->after('project_number_start')
                ->comment('Starting sequence number for draft numbers (e.g., 1 for TCS-D001-xxx)');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn('draft_number_start');
        });
    }
};
