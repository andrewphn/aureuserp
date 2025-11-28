<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Webkul\Project\Models\ProjectStage;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Adds stage_key column for programmatic identification and new inventory-related stages:
     * - Material Reserved: Materials locked in inventory for this project
     * - Material Issued: Materials physically pulled from inventory
     */
    public function up(): void
    {
        // Add stage_key column if it doesn't exist
        if (!Schema::hasColumn('projects_project_stages', 'stage_key')) {
            Schema::table('projects_project_stages', function (Blueprint $table) {
                $table->string('stage_key', 50)->nullable()->after('name')->unique();
            });
        }

        // Update existing stages with stage keys
        $stageKeys = [
            'Discovery' => 'discovery',
            'Design' => 'design',
            'Sourcing' => 'sourcing',
            'Production' => 'production',
            'Delivery' => 'delivery',
        ];

        foreach ($stageKeys as $name => $key) {
            ProjectStage::where('name', $name)->update(['stage_key' => $key]);
        }

        // Add new inventory-related stages
        $newStages = [
            [
                'name' => 'Material Reserved',
                'stage_key' => 'material_reserved',
                'color' => '#6366F1', // Indigo - material locked
                'sort' => 3.5, // Between Sourcing and Production
            ],
            [
                'name' => 'Material Issued',
                'stage_key' => 'material_issued',
                'color' => '#22C55E', // Lime green - ready to build
                'sort' => 3.6, // After Material Reserved
            ],
        ];

        foreach ($newStages as $stage) {
            // Only create if stage doesn't already exist
            if (!ProjectStage::where('stage_key', $stage['stage_key'])->exists()) {
                ProjectStage::create($stage);
            }
        }

        // Re-sort to have proper order (Discovery=1, Design=2, Sourcing=3, Material Reserved=4, Material Issued=5, Production=6, Delivery=7)
        $sortOrder = [
            'discovery' => 1,
            'design' => 2,
            'sourcing' => 3,
            'material_reserved' => 4,
            'material_issued' => 5,
            'production' => 6,
            'delivery' => 7,
        ];

        foreach ($sortOrder as $key => $sort) {
            ProjectStage::where('stage_key', $key)->update(['sort' => $sort]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove the new inventory stages
        ProjectStage::whereIn('stage_key', ['material_reserved', 'material_issued'])->delete();

        // Remove stage_key column
        Schema::table('projects_project_stages', function (Blueprint $table) {
            $table->dropColumn('stage_key');
        });
    }
};
