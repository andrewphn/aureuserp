<?php

namespace Webkul\Project\Filament\Resources\CabinetReportResource\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;
use Webkul\Project\Models\CabinetSpecification;

class CabinetStatsWidget extends BaseWidget
{
    protected function getStats(): array
    {
        $totalCabinets = CabinetSpecification::sum('quantity');
        $totalRevenue = CabinetSpecification::sum('total_price');
        $avgLinearFeet = CabinetSpecification::avg('linear_feet');
        $uniqueConfigs = CabinetSpecification::distinct('product_variant_id')->count();

        // Month-over-month comparison
        $thisMonth = CabinetSpecification::whereMonth('created_at', now()->month)->sum('quantity');
        $lastMonth = CabinetSpecification::whereMonth('created_at', now()->subMonth()->month)->sum('quantity');
        $monthChange = $lastMonth > 0 ? (($thisMonth - $lastMonth) / $lastMonth) * 100 : 0;

        return [
            Stat::make('Total Cabinets Built', number_format($totalCabinets))
                ->description($thisMonth . ' this month')
                ->descriptionIcon($monthChange >= 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                ->color($monthChange >= 0 ? 'success' : 'danger')
                ->chart($this->getCabinetTrendData()),

            Stat::make('Total Revenue', '$' . number_format($totalRevenue, 2))
                ->description('From cabinet specifications')
                ->descriptionIcon('heroicon-m-currency-dollar')
                ->color('success'),

            Stat::make('Avg Linear Feet', number_format($avgLinearFeet, 2) . ' LF')
                ->description('Per cabinet')
                ->descriptionIcon('heroicon-m-arrows-right-left')
                ->color('info'),

            Stat::make('Unique Configurations', $uniqueConfigs)
                ->description('Different cabinet variants')
                ->descriptionIcon('heroicon-m-squares-2x2')
                ->color('warning'),
        ];
    }

    private function getCabinetTrendData(): array
    {
        return CabinetSpecification::selectRaw('DATE(created_at) as date, SUM(quantity) as total')
            ->where('created_at', '>=', now()->subDays(7))
            ->groupBy('date')
            ->orderBy('date')
            ->pluck('total')
            ->toArray();
    }
}
