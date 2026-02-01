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

    protected int | string | array $columnSpan = 1;

    protected function getStats(): array
    {
        if (!$this->record) {
            return [
                Stat::make('CNC', '-')
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
                Stat::make('CNC', 'No Programs')
                    ->description('No CNC programs created')
                    ->icon('heroicon-o-cog-8-tooth')
                    ->color('gray'),
            ];
        }

        // Build condensed description
        $descParts = [];
        $descParts[] = "{$stats['complete_parts']}/{$stats['total_parts']} parts";
        if ($stats['running_parts'] > 0) {
            $descParts[] = "{$stats['running_parts']} running";
        }

        // Check for alerts
        $alert = $this->getCncAlerts($stats);
        $description = implode(' • ', $descParts);

        if (!empty($alert['message'])) {
            $description = $alert['icon'] . ' ' . $alert['message'];
        }

        // Determine color
        $color = $alert['color'] ?? match (true) {
            $stats['completion_percentage'] >= 100 => 'success',
            $stats['completion_percentage'] >= 50 => 'info',
            $stats['completion_percentage'] > 0 => 'warning',
            default => 'gray',
        };

        return [
            Stat::make('CNC', "{$stats['completion_percentage']}%")
                ->description($description)
                ->icon('heroicon-o-cog-8-tooth')
                ->color($color),
        ];
    }

    /**
     * Get CNC-specific alerts
     */
    protected function getCncAlerts(array $stats): array
    {
        // Material issues are critical
        if ($stats['pending_material'] > 0) {
            return [
                'message' => "{$stats['pending_material']} parts waiting for material",
                'icon' => '⚠',
                'color' => 'danger',
            ];
        }

        // All complete
        if ($stats['completion_percentage'] >= 100) {
            return [
                'message' => 'All parts complete',
                'icon' => '✓',
                'color' => 'success',
            ];
        }

        return [];
    }
}
