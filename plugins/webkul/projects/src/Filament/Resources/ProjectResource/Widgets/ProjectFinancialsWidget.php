<?php

namespace Webkul\Project\Filament\Resources\ProjectResource\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Database\Eloquent\Model;

/**
 * Project Financials Widget Filament widget
 *
 * @see \Filament\Resources\Resource
 */
class ProjectFinancialsWidget extends BaseWidget
{
    public ?Model $record = null;

    protected function getStats(): array
    {
        if (! $this->record) {
            return [];
        }

        $financials = $this->calculateFinancials();

        return [
            Stat::make('Total Quoted', $financials['quoted_formatted'])
                ->description($financials['linear_feet'] . ' LF @ ' . $financials['price_per_lf'])
                ->descriptionIcon('heroicon-o-document-text')
                ->color('info'),

            Stat::make('Actual Costs', $financials['actual_formatted'])
                ->description($financials['cost_breakdown'])
                ->descriptionIcon('heroicon-o-currency-dollar')
                ->color($financials['actual_color']),

            Stat::make('Profit Margin', $financials['margin_formatted'])
                ->description($financials['margin_description'])
                ->descriptionIcon($financials['margin_icon'])
                ->color($financials['margin_color']),
        ];
    }

    /**
     * Calculate Financials
     *
     * @return array
     */
    protected function calculateFinancials(): array
    {
        // Calculate total quoted amount
        $quoted = $this->record->cabinets()
            ->selectRaw('SUM(unit_price_per_lf * linear_feet * quantity) as total')
            ->value('total') ?? 0;

        // Calculate linear feet
        $linearFeet = $this->record->cabinets()
            ->selectRaw('SUM(linear_feet * quantity) as total')
            ->value('total') ?? 0;

        // Calculate price per linear foot
        $pricePerLF = $linearFeet > 0 ? $quoted / $linearFeet : 0;

        // Calculate actual costs from orders
        $actual = $this->record->orders()->sum('amount_total') ?? 0;

        // Calculate margin
        $margin = $quoted > 0 ? (($quoted - $actual) / $quoted) * 100 : 0;
        $profit = $quoted - $actual;

        // Determine colors and icons based on margin
        if ($margin < 10) {
            $marginColor = 'danger';
            $marginIcon = 'heroicon-o-arrow-trending-down';
            $marginDescription = 'Below minimum target';
        } elseif ($margin < 20) {
            $marginColor = 'warning';
            $marginIcon = 'heroicon-o-minus';
            $marginDescription = 'Below target (20%)';
        } elseif ($margin < 30) {
            $marginColor = 'success';
            $marginIcon = 'heroicon-o-check';
            $marginDescription = 'On target';
        } else {
            $marginColor = 'success';
            $marginIcon = 'heroicon-o-arrow-trending-up';
            $marginDescription = 'Above target';
        }

        $actualColor = $actual > $quoted ? 'danger' : 'success';
        $costBreakdown = $actual > 0 ? 'Spent: ' . number_format(($actual / $quoted) * 100, 1) . '% of budget' : 'No costs recorded';

        return [
            'quoted' => $quoted,
            'quoted_formatted' => '$' . number_format($quoted, 0),
            'actual' => $actual,
            'actual_formatted' => '$' . number_format($actual, 0),
            'margin' => $margin,
            'margin_formatted' => number_format($margin, 1) . '%',
            'profit' => $profit,
            'profit_formatted' => '$' . number_format($profit, 0),
            'linear_feet' => number_format($linearFeet, 0) . ' LF',
            'price_per_lf' => '$' . number_format($pricePerLF, 0) . '/LF',
            'margin_color' => $marginColor,
            'margin_icon' => $marginIcon,
            'margin_description' => $marginDescription,
            'actual_color' => $actualColor,
            'cost_breakdown' => $costBreakdown,
        ];
    }
}
