<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('projects_projects', function (Blueprint $table) {
            $table->timestamp('stage_entered_at')->nullable()->after('stage_id')
                ->comment('When the project entered the current stage');
        });

        // Backfill existing projects with updated_at as stage_entered_at
        DB::table('projects_projects')
            ->whereNull('stage_entered_at')
            ->update(['stage_entered_at' => DB::raw('updated_at')]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('projects_projects', function (Blueprint $table) {
            $table->dropColumn('stage_entered_at');
        });
    }
};
