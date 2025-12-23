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
        Schema::table('projects_project_stages', function (Blueprint $table) {
            $table->unsignedInteger('wip_limit')->nullable()->after('is_collapsed')
                ->comment('Work-in-progress limit for this stage (null = unlimited)');
        });

        // Set default WIP limits for production-heavy stages
        DB::table('projects_project_stages')
            ->where('stage_key', 'production')
            ->update(['wip_limit' => 4]);

        DB::table('projects_project_stages')
            ->where('stage_key', 'sourcing')
            ->update(['wip_limit' => 3]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('projects_project_stages', function (Blueprint $table) {
            $table->dropColumn('wip_limit');
        });
    }
};
