<?php

namespace Webkul\Project\Filament\Widgets;

use Carbon\Carbon;
use Filament\Widgets\Widget;
use Webkul\Project\Services\CncProductionStatsService;

/**
 * CNC Capacity Summary Widget
 *
 * Displays daily board feet capacity summary in a table format
 * matching the requested display format.
 */
class CncCapacitySummaryWidget extends Widget
{
    protected string $view = 'webkul-project::filament.widgets.cnc-capacity-summary';

    protected static ?int $sort = 2;

    protected int|string|array $columnSpan = [
        'md' => 2,
        'xl' => 3,
    ];

    public function getCapacityStats(): array
    {
        $service = app(CncProductionStatsService::class);

        return $service->getDailyCapacitySummary(
            Carbon::now()->subDays(30),
            Carbon::now()
        );
    }

    public function getMaterialBreakdown(): array
    {
        $service = app(CncProductionStatsService::class);

        return $service->getMaterialStats(
            Carbon::now()->subDays(30),
            Carbon::now()
        )->toArray();
    }

    public function getOperatorStats(): array
    {
        $service = app(CncProductionStatsService::class);

        return $service->getOperatorStats(
            Carbon::now()->subDays(30),
            Carbon::now()
        )->toArray();
    }

    public function getUtilizationStats(): array
    {
        $service = app(CncProductionStatsService::class);

        return $service->getUtilizationSummary(
            Carbon::now()->subDays(30),
            Carbon::now()
        );
    }
}
