<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Seeds TCS Woodwork workflow stages with color-coded progression:
     * - Discovery (Blue): Trust, communication, beginning
     * - Design (Purple): Creativity, planning, transformation
     * - Sourcing (Orange): Energy, procurement, action
     * - Production (Green): Growth, progress, building
     * - Delivery (Teal): Success, completion, satisfaction
     */
    public function up(): void
    {
        $stages = [
            [
                'name' => 'Discovery',
                'color' => '#3B82F6', // Blue - trust, communication, beginning
                'sort_order' => 1,
            ],
            [
                'name' => 'Design',
                'color' => '#8B5CF6', // Purple - creativity, planning, transformation
                'sort_order' => 2,
            ],
            [
                'name' => 'Sourcing',
                'color' => '#F59E0B', // Orange - energy, procurement, action
                'sort_order' => 3,
            ],
            [
                'name' => 'Production',
                'color' => '#10B981', // Green - growth, progress, building
                'sort_order' => 4,
            ],
            [
                'name' => 'Delivery',
                'color' => '#14B8A6', // Teal - success, completion, satisfaction
                'sort_order' => 5,
            ],
        ];

        foreach ($stages as $stage) {
            \Webkul\Project\Models\ProjectStage::create($stage);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        \Webkul\Project\Models\ProjectStage::whereIn('name', [
            'Discovery',
            'Design',
            'Sourcing',
            'Production',
            'Delivery',
        ])->delete();
    }
};
