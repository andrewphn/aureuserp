<?php

namespace Webkul\Project\Filament\Resources\ProjectResource\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Database\Eloquent\Model;

class ProjectStatusWidget extends BaseWidget
{
    public ?Model $record = null;

    protected function getStats(): array
    {
        if (! $this->record) {
            return [];
        }

        $status = $this->calculateProjectHealth();

        return [
            Stat::make('Project Health', $status['label'])
                ->description($status['description'])
                ->descriptionIcon($status['icon'])
                ->color($status['color'])
                ->extraAttributes([
                    'class' => 'relative',
                ]),
        ];
    }

    protected function calculateProjectHealth(): array
    {
        $warnings = [];
        $criticals = [];

        // Check financial health
        $margin = $this->calculateMargin();
        if ($margin !== null) {
            if ($margin < 10) {
                $criticals[] = 'Low margin (' . number_format($margin, 1) . '%)';
            } elseif ($margin < 20) {
                $warnings[] = 'Margin below target';
            }
        }

        // Check timeline
        if ($this->record->desired_completion_date) {
            $daysRemaining = now()->diffInDays($this->record->desired_completion_date, false);
            if ($daysRemaining < 0) {
                $criticals[] = 'Past due date';
            } elseif ($daysRemaining < 7) {
                $warnings[] = 'Deadline approaching';
            }
        }

        // Check for incomplete data
        $roomsWithoutLocations = $this->record->rooms()->doesntHave('locations')->count();
        if ($roomsWithoutLocations > 0) {
            $warnings[] = "{$roomsWithoutLocations} room(s) without locations";
        }

        $cabinetsWithoutPrice = $this->record->cabinets()
            ->whereNull('unit_price_per_lf')
            ->orWhereNull('linear_feet')
            ->count();
        if ($cabinetsWithoutPrice > 0) {
            $warnings[] = "{$cabinetsWithoutPrice} cabinet(s) missing pricing";
        }

        // Determine status
        if (count($criticals) > 0) {
            return [
                'label' => 'Needs Attention',
                'description' => implode(', ', array_merge($criticals, $warnings)),
                'icon' => 'heroicon-o-exclamation-triangle',
                'color' => 'danger',
            ];
        }

        if (count($warnings) > 2) {
            return [
                'label' => 'Review Needed',
                'description' => implode(', ', $warnings),
                'icon' => 'heroicon-o-exclamation-circle',
                'color' => 'warning',
            ];
        }

        if (count($warnings) > 0) {
            return [
                'label' => 'On Track',
                'description' => implode(', ', $warnings),
                'icon' => 'heroicon-o-information-circle',
                'color' => 'info',
            ];
        }

        return [
            'label' => 'Healthy',
            'description' => 'All systems go',
            'icon' => 'heroicon-o-check-circle',
            'color' => 'success',
        ];
    }

    protected function calculateMargin(): ?float
    {
        $quoted = $this->record->cabinets()
            ->selectRaw('SUM(unit_price_per_lf * linear_feet * quantity) as total')
            ->value('total');

        if (! $quoted) {
            return null;
        }

        $actual = $this->record->orders()->sum('amount_total');

        if ($actual === 0) {
            return null;
        }

        return (($quoted - $actual) / $quoted) * 100;
    }
}
