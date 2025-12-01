<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Adds project_number_start column to allow companies to set their
     * starting project number sequence (e.g., TCS starts at 500, WT starts at 1)
     */
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->unsignedInteger('project_number_start')
                ->nullable()
                ->default(1)
                ->after('working_days_per_month')
                ->comment('Starting sequence number for project numbers (e.g., 500 for TCS-500-xxx)');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn('project_number_start');
        });
    }
};
