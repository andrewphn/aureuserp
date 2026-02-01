<?php

namespace Webkul\Project\Filament\Widgets;

use BezhanSalleh\FilamentShield\Traits\HasWidgetShield;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Webkul\Project\Models\CncProgram;
use Webkul\Project\Models\CncProgramPart;

/**
 * CNC Stats Widget
 *
 * Shows key CNC metrics: pending programs, running parts, utilization, pending material.
 */
class CncStatsWidget extends BaseWidget
{
    use HasWidgetShield;

    protected ?string $pollingInterval = '30s';

    protected function getHeading(): ?string
    {
        return 'CNC Overview';
    }

    protected function getStats(): array
    {
        $pendingPrograms = CncProgram::where('status', CncProgram::STATUS_PENDING)->count();
        $inProgressPrograms = CncProgram::where('status', CncProgram::STATUS_IN_PROGRESS)->count();

        $runningParts = CncProgramPart::where('status', CncProgramPart::STATUS_RUNNING)->count();
        $pendingParts = CncProgramPart::where('status', CncProgramPart::STATUS_PENDING)->count();

        $avgUtilization = CncProgram::whereNotNull('utilization_percentage')
            ->where('utilization_percentage', '>', 0)
            ->avg('utilization_percentage');

        $pendingMaterial = CncProgramPart::where('material_status', CncProgramPart::MATERIAL_PENDING)->count();

        $completedToday = CncProgramPart::where('status', CncProgramPart::STATUS_COMPLETE)
            ->whereDate('completed_at', today())
            ->count();

        return [
            Stat::make('Pending Programs', $pendingPrograms)
                ->description("{$inProgressPrograms} in progress")
                ->icon('heroicon-o-document-text')
                ->color($pendingPrograms > 10 ? 'warning' : 'gray'),

            Stat::make('Running Parts', $runningParts)
                ->description("{$pendingParts} in queue")
                ->icon('heroicon-o-play')
                ->color($runningParts > 0 ? 'info' : 'gray'),

            Stat::make('Avg Utilization', $avgUtilization ? number_format($avgUtilization, 1) . '%' : 'N/A')
                ->description('Sheet material usage')
                ->icon('heroicon-o-chart-pie')
                ->color(match (true) {
                    $avgUtilization === null => 'gray',
                    $avgUtilization >= 85 => 'success',
                    $avgUtilization >= 75 => 'info',
                    $avgUtilization >= 65 => 'warning',
                    default => 'danger',
                }),

            Stat::make('Pending Material', $pendingMaterial)
                ->description("{$completedToday} completed today")
                ->icon('heroicon-o-exclamation-triangle')
                ->color($pendingMaterial > 0 ? 'danger' : 'success'),
        ];
    }
}
