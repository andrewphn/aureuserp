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
        Schema::table('projects_projects', function (Blueprint $table) {
            $table->string('lead_source', 50)->nullable()->after('project_type')
                ->comment('Lead source for BI tracking: trott_nantucket, referral, walk_in, website, repeat_customer, other');
            $table->string('budget_range', 20)->nullable()->after('lead_source')
                ->comment('Budget range: 5k_15k, 15k_30k, 30k_50k, 50k_100k, 100k_plus');
            $table->unsignedTinyInteger('complexity_score')->nullable()->after('budget_range')
                ->comment('Complexity score 1-10 for production time multiplier');

            // Indexes for BI reporting queries
            $table->index('lead_source');
            $table->index('budget_range');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('projects_projects', function (Blueprint $table) {
            $table->dropIndex(['lead_source']);
            $table->dropIndex(['budget_range']);
            $table->dropColumn(['lead_source', 'budget_range', 'complexity_score']);
        });
    }
};
