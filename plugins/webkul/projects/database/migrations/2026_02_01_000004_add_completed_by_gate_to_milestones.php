<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Links milestones to gates - tracks which gate completed a milestone.
     */
    public function up(): void
    {
        Schema::table('projects_milestones', function (Blueprint $table) {
            $table->string('completed_by_gate')->nullable()->after('completed_at')
                ->comment('Gate key that triggered milestone completion');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('projects_milestones', function (Blueprint $table) {
            $table->dropColumn('completed_by_gate');
        });
    }
};
