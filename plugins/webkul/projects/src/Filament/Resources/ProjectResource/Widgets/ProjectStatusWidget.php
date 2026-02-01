<?php

namespace Webkul\Project\Filament\Resources\ProjectResource\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Database\Eloquent\Model;

/**
 * Project Production Stage Widget - Compact single-stat widget
 *
 * Shows current production stage with gate blockers.
 */
class ProjectStatusWidget extends BaseWidget
{
    public ?Model $record = null;

    protected int | string | array $columnSpan = 1;

    protected array $stageLabels = [
        'discovery' => 'Discovery',
        'design' => 'Design',
        'sourcing' => 'Sourcing',
        'production' => 'Production',
        'delivery' => 'Delivery',
    ];

    protected array $stageIcons = [
        'discovery' => 'heroicon-o-magnifying-glass',
        'design' => 'heroicon-o-pencil-square',
        'sourcing' => 'heroicon-o-shopping-cart',
        'production' => 'heroicon-o-wrench-screwdriver',
        'delivery' => 'heroicon-o-truck',
    ];

    protected array $stageColors = [
        'discovery' => 'gray',
        'design' => 'info',
        'sourcing' => 'warning',
        'production' => 'primary',
        'delivery' => 'success',
    ];

    protected function getStats(): array
    {
        if (! $this->record) {
            return [];
        }

        $stageData = $this->calculateStageStatus();

        return [
            Stat::make('Stage', $stageData['label'])
                ->description($stageData['description'])
                ->icon($stageData['icon'])
                ->color($stageData['color']),
        ];
    }

    protected function calculateStageStatus(): array
    {
        $currentStage = $this->record->current_production_stage ?? 'discovery';
        $label = $this->stageLabels[$currentStage] ?? ucfirst($currentStage);
        $icon = $this->stageIcons[$currentStage] ?? 'heroicon-o-flag';
        $color = $this->stageColors[$currentStage] ?? 'gray';

        // Get gate status for blockers
        $gateStatus = $this->record->getStageGateStatus();
        $blockers = $gateStatus['blockers'] ?? [];
        $canAdvance = $gateStatus['can_advance'] ?? false;
        $nextStage = $gateStatus['next_stage'] ?? null;

        // Build description
        if (!empty($blockers)) {
            $description = '⚠ ' . $blockers[0];
            $color = 'warning';
        } elseif ($canAdvance && $nextStage) {
            $nextLabel = $this->stageLabels[$nextStage] ?? ucfirst($nextStage);
            $description = '✓ Ready for ' . $nextLabel;
            $color = 'success';
        } elseif ($currentStage === 'delivery') {
            $description = 'Final stage';
        } else {
            $stageIndex = array_search($currentStage, array_keys($this->stageLabels));
            $totalStages = count($this->stageLabels);
            $description = 'Stage ' . ($stageIndex + 1) . ' of ' . $totalStages;
        }

        return [
            'label' => $label,
            'description' => $description,
            'icon' => $icon,
            'color' => $color,
        ];
    }
}
