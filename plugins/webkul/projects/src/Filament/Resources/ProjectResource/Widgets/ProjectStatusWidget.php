<?php

namespace Webkul\Project\Filament\Resources\ProjectResource\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Database\Eloquent\Model;

/**
 * Project Production Stage Widget
 *
 * Shows current production stage and gate status.
 *
 * @see \Filament\Resources\Resource
 */
class ProjectStatusWidget extends BaseWidget
{
    public ?Model $record = null;

    protected static bool $isLazy = false;

    protected int | string | array $columnSpan = 1;

    /**
     * Human-readable stage labels
     */
    protected array $stageLabels = [
        'discovery' => 'Discovery',
        'design' => 'Design',
        'sourcing' => 'Sourcing',
        'production' => 'Production',
        'delivery' => 'Delivery',
    ];

    /**
     * Stage icons
     */
    protected array $stageIcons = [
        'discovery' => 'heroicon-o-magnifying-glass',
        'design' => 'heroicon-o-pencil-square',
        'sourcing' => 'heroicon-o-shopping-cart',
        'production' => 'heroicon-o-wrench-screwdriver',
        'delivery' => 'heroicon-o-truck',
    ];

    /**
     * Stage colors
     */
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
            return [
                Stat::make('Stage', '-')
                    ->description('Loading...')
                    ->icon('heroicon-o-flag')
                    ->color('gray'),
            ];
        }

        $stageData = $this->calculateStageStatus();

        return [
            Stat::make('Stage', $stageData['label'])
                ->description($stageData['description'])
                ->icon($stageData['icon'])
                ->color($stageData['color']),
        ];
    }

    /**
     * Calculate production stage status
     */
    protected function calculateStageStatus(): array
    {
        $currentStage = $this->record->current_production_stage ?? 'discovery';
        $label = $this->stageLabels[$currentStage] ?? ucfirst($currentStage);
        $icon = $this->stageIcons[$currentStage] ?? 'heroicon-o-flag';
        $color = $this->stageColors[$currentStage] ?? 'gray';

        // Get gate status to check for blockers
        $gateStatus = $this->record->getStageGateStatus();
        $blockers = $gateStatus['blockers'] ?? [];
        $canAdvance = $gateStatus['can_advance'] ?? false;
        $nextStage = $gateStatus['next_stage'] ?? null;

        // Build description
        if (!empty($blockers)) {
            // Show the most important blocker
            $description = '⚠ ' . $blockers[0];
            $color = 'warning';
        } elseif ($canAdvance && $nextStage) {
            $nextLabel = $this->stageLabels[$nextStage] ?? ucfirst($nextStage);
            $description = '✓ Ready for ' . $nextLabel;
            $color = 'success';
        } elseif ($currentStage === 'delivery') {
            $description = 'Final stage';
        } else {
            // Show progress indicator
            $stageIndex = array_search($currentStage, array_keys($this->stageLabels));
            $totalStages = count($this->stageLabels);
            $description = 'Stage ' . ($stageIndex + 1) . ' of ' . $totalStages;
        }

        return [
            'label' => $label,
            'description' => $description,
            'icon' => $icon,
            'color' => $color,
            'can_advance' => $canAdvance,
            'blockers' => $blockers,
        ];
    }
}
