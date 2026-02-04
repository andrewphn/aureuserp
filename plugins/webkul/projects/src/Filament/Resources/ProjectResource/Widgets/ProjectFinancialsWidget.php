<?php

namespace Webkul\Project\Filament\Resources\ProjectResource\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Database\Eloquent\Model;

/**
 * Project Financials Widget - Compact single-stat widget
 *
 * Shows quoted amount with key metrics and financial alerts.
 */
class ProjectFinancialsWidget extends BaseWidget
{
    public ?Model $record = null;

    protected int | string | array $columnSpan = 1;

    protected function getStats(): array
    {
        if (! $this->record) {
            return [];
        }

        $financials = $this->calculateFinancials();
        $alert = $this->getFinancialAlert($financials);

        // Build description: LF @ price • margin
        $descParts = [];
        $descParts[] = $financials['linear_feet'] . ' @ ' . $financials['price_per_lf'];
        $descParts[] = $financials['margin_formatted'] . ' margin';

        $description = implode(' • ', $descParts);
        $color = $financials['margin_color'];

        // Show alert if present
        if ($alert) {
            $description = '⚠ ' . $alert['message'];
            $color = $alert['color'];
        }

        return [
            Stat::make('Financials', $financials['quoted_formatted'])
                ->description($description)
                ->icon('heroicon-o-currency-dollar')
                ->color($color),
        ];
    }

    /**
     * Get financial alert if any issues exist
     */
    protected function getFinancialAlert(array $financials): ?array
    {
        // Check for missing pricing
        $cabinetsWithoutPrice = $this->record->cabinets()
            ->where(function ($q) {
                $q->whereNull('unit_price_per_lf')->orWhereNull('linear_feet');
            })
            ->count();

        if ($cabinetsWithoutPrice > 0) {
            return [
                'message' => "{$cabinetsWithoutPrice} cabinet(s) missing pricing",
                'color' => 'warning',
            ];
        }

        // Check margin health
        if ($financials['margin'] < 10 && $financials['quoted'] > 0) {
            return [
                'message' => 'Low margin (' . $financials['margin_formatted'] . ')',
                'color' => 'danger',
            ];
        }

        if ($financials['margin'] < 20 && $financials['quoted'] > 0) {
            return [
                'message' => 'Margin below 20% target',
                'color' => 'warning',
            ];
        }

        // Check if over budget
        if ($financials['actual'] > $financials['quoted'] && $financials['quoted'] > 0) {
            return [
                'message' => 'Over budget by $' . number_format($financials['actual'] - $financials['quoted'], 0),
                'color' => 'danger',
            ];
        }

        return null;
    }

    /**
     * Calculate Financials
     *
     * Data source priority:
     * 1. Cabinet-level data (linear_feet, unit_price_per_lf) - most detailed
     * 2. Room-level data (tier LF, estimated_cabinet_value) - wizard estimates
     * 3. Project-level estimates (estimated_linear_feet) - fallback
     *
     * @return array
     */
    protected function calculateFinancials(): array
    {
        // Try cabinet-level data first (detailed pricing)
        $cabinetQuoted = $this->record->cabinets()
            ->selectRaw('SUM(unit_price_per_lf * linear_feet * quantity) as total')
            ->value('total') ?? 0;

        $cabinetLinearFeet = $this->record->cabinets()
            ->selectRaw('SUM(linear_feet * quantity) as total')
            ->value('total') ?? 0;

        // Try room-level data (wizard estimates)
        $roomLinearFeet = $this->record->rooms()
            ->selectRaw('SUM(COALESCE(total_linear_feet_tier_1, 0) + COALESCE(total_linear_feet_tier_2, 0) + COALESCE(total_linear_feet_tier_3, 0) + COALESCE(total_linear_feet_tier_4, 0) + COALESCE(total_linear_feet_tier_5, 0)) as total')
            ->value('total') ?? 0;

        $roomEstimatedValue = $this->record->rooms()
            ->selectRaw('SUM(COALESCE(estimated_cabinet_value, 0)) as total')
            ->value('total') ?? 0;

        // Determine which data source to use
        if ($cabinetLinearFeet > 0) {
            // Use cabinet-level data (most detailed)
            $linearFeet = $cabinetLinearFeet;
            $quoted = $cabinetQuoted;
        } elseif ($roomLinearFeet > 0) {
            // Use room-level data (wizard estimates)
            $linearFeet = $roomLinearFeet;
            $quoted = $roomEstimatedValue;
        } else {
            // Fallback to project estimates
            $linearFeet = $this->record->estimated_linear_feet ?? 0;
            $quoted = $this->record->estimated_project_value ?? 0;
        }

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
        $costBreakdown = ($actual > 0 && $quoted > 0) ? 'Spent: ' . number_format(($actual / $quoted) * 100, 1) . '% of budget' : 'No costs recorded';

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
