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
        Schema::table('projects_milestones', function (Blueprint $table) {
            $table->enum('production_stage', [
                'discovery',
                'design',
                'sourcing',
                'production',
                'delivery',
            ])->nullable()->after('deadline')
            ->comment('Optional association with production stage');

            $table->boolean('is_critical')->default(false)->after('production_stage')
                ->comment('Critical milestones shown prominently in timeline');

            $table->text('description')->nullable()->after('is_critical')
                ->comment('Additional context or requirements for this milestone');

            $table->integer('sort_order')->default(0)->after('description')
                ->comment('Manual ordering within stage (0 = auto-sort by date)');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('projects_milestones', function (Blueprint $table) {
            $table->dropColumn(['production_stage', 'is_critical', 'description', 'sort_order']);
        });
    }
};
