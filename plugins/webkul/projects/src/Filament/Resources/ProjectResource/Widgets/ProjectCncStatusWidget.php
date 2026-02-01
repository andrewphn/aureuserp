<?php

namespace Webkul\Project\Filament\Resources\ProjectResource\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Database\Eloquent\Model;
use Webkul\Project\Models\CncProgram;
use Webkul\Project\Models\CncProgramPart;
use Webkul\Project\Services\CncProgramService;

/**
 * Project CNC Status Widget
 *
 * Shows CNC status summary on the project view page.
 */
class ProjectCncStatusWidget extends BaseWidget
{
    public ?Model $record = null;

    protected static bool $isLazy = false;

    protected function getStats(): array
    {
        if (!$this->record) {
            return [
                Stat::make('CNC Programs', '-')
                    ->description('Loading...')
                    ->icon('heroicon-o-cog-8-tooth')
                    ->color('gray'),
            ];
        }

        $service = app(CncProgramService::class);
        $stats = $service->getProjectCncStats($this->record);

        // If no CNC programs, show a minimal message
        if ($stats['total_programs'] === 0) {
            return [
                Stat::make('CNC Programs', '0')
                    ->description('No programs yet')
                    ->icon('heroicon-o-cog-8-tooth')
                    ->color('gray'),
            ];
        }

        return [
            Stat::make('CNC Programs', "{$stats['complete_programs']}/{$stats['total_programs']}")
                ->description($stats['in_progress_programs'] > 0 ? "{$stats['in_progress_programs']} in progress" : 'Programs complete')
                ->icon('heroicon-o-cog-8-tooth')
                ->color($stats['complete_programs'] === $stats['total_programs'] ? 'success' : 'info'),

            Stat::make('CNC Parts', "{$stats['complete_parts']}/{$stats['total_parts']}")
                ->description($stats['running_parts'] > 0 ? "{$stats['running_parts']} running now" : "{$stats['pending_parts']} pending")
                ->icon('heroicon-o-queue-list')
                ->color($stats['complete_parts'] === $stats['total_parts'] ? 'success' : 'info'),

            Stat::make('Progress', "{$stats['completion_percentage']}%")
                ->description('Parts completed')
                ->icon('heroicon-o-chart-pie')
                ->color(match (true) {
                    $stats['completion_percentage'] >= 100 => 'success',
                    $stats['completion_percentage'] >= 50 => 'info',
                    $stats['completion_percentage'] > 0 => 'warning',
                    default => 'gray',
                }),

            $stats['pending_material'] > 0
                ? Stat::make('Material Issues', $stats['pending_material'])
                    ->description('Parts waiting for material')
                    ->icon('heroicon-o-exclamation-triangle')
                    ->color('danger')
                : Stat::make('Material', 'Ready')
                    ->description('All materials available')
                    ->icon('heroicon-o-check-circle')
                    ->color('success'),
        ];
    }
}
