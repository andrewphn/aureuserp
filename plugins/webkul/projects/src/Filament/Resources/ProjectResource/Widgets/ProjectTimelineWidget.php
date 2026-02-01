<?php

namespace Webkul\Project\Filament\Resources\ProjectResource\Widgets;

use App\Services\ProductionEstimatorService;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Database\Eloquent\Model;

/**
 * Project Timeline Widget Filament widget
 *
 * @see \Filament\Resources\Resource
 */
class ProjectTimelineWidget extends BaseWidget
{
    public ?Model $record = null;

    protected static bool $isLazy = false;

    protected int | string | array $columnSpan = 1;

    protected function getStats(): array
    {
        if (! $this->record) {
            return [
                Stat::make('Timeline', '-')
                    ->description('Loading...')
                    ->icon('heroicon-o-calendar')
                    ->color('gray'),
            ];
        }

        $timeline = $this->calculateTimeline();

        // Build condensed description
        $descParts = [];
        $descParts[] = $timeline['days_remaining_text'];
        if ($timeline['estimated_days'] !== 'TBD') {
            $scheduleNote = '';
            if ($timeline['estimate_color'] === 'danger') {
                $scheduleNote = ' (exceeds deadline)';
            } elseif ($timeline['estimate_color'] === 'warning') {
                $scheduleNote = ' (tight)';
            }
            $descParts[] = $timeline['estimated_days'] . ' production' . $scheduleNote;
        }

        // Determine overall color - use the more severe color
        $color = $timeline['completion_color'];
        if ($timeline['estimate_color'] === 'danger') {
            $color = 'danger';
        } elseif ($timeline['estimate_color'] === 'warning' && $color !== 'danger') {
            $color = 'warning';
        }

        return [
            Stat::make('Timeline', $timeline['completion_formatted'])
                ->description(implode(' • ', $descParts))
                ->icon('heroicon-o-calendar')
                ->color($color),
        ];
    }

    /**
     * Calculate Timeline
     *
     * @return array
     */
    protected function calculateTimeline(): array
    {
        $startDate = $this->record->start_date;
        $completionDate = $this->record->desired_completion_date;

        // Format start date
        $startFormatted = $startDate ? $startDate->format('M d, Y') : 'Not set';
        $startDescription = $startDate ? $startDate->diffForHumans() : 'No start date';

        // Calculate days remaining
        $daysRemaining = null;
        $completionFormatted = 'Not set';
        $completionColor = 'gray';
        $completionIcon = 'heroicon-o-calendar';

        if ($completionDate) {
            $completionFormatted = $completionDate->format('M d, Y');
            $daysRemaining = now()->diffInDays($completionDate, false);
            $daysRemainingRounded = (int) round($daysRemaining);

            if ($daysRemaining < 0) {
                $daysRemainingText = abs($daysRemainingRounded) . ' day' . (abs($daysRemainingRounded) !== 1 ? 's' : '') . ' overdue';
                $completionColor = 'danger';
                $completionIcon = 'heroicon-o-exclamation-triangle';
            } elseif ($daysRemaining < 1) {
                $daysRemainingText = 'Due today';
                $completionColor = 'warning';
                $completionIcon = 'heroicon-o-exclamation-circle';
            } elseif ($daysRemaining < 7) {
                $daysRemainingText = $daysRemainingRounded . ' day' . ($daysRemainingRounded !== 1 ? 's' : '') . ' remaining';
                $completionColor = 'warning';
                $completionIcon = 'heroicon-o-clock';
            } elseif ($daysRemaining < 30) {
                $daysRemainingText = $daysRemainingRounded . ' day' . ($daysRemainingRounded !== 1 ? 's' : '') . ' remaining';
                $completionColor = 'info';
                $completionIcon = 'heroicon-o-calendar';
            } else {
                $daysRemainingText = $daysRemainingRounded . ' day' . ($daysRemainingRounded !== 1 ? 's' : '') . ' remaining';
                $completionColor = 'success';
                $completionIcon = 'heroicon-o-check-circle';
            }
        } else {
            $daysRemainingText = 'No target date set';
        }

        // Calculate production estimate
        $linearFeet = $this->record->cabinets()
            ->selectRaw('SUM(linear_feet * quantity) as total')
            ->value('total') ?? $this->record->estimated_linear_feet ?? 0;

        $estimatedDays = 'TBD';
        $estimateDescription = 'Enter linear feet';
        $estimateColor = 'gray';

        if ($linearFeet > 0 && $this->record->company_id) {
            try {
                $estimate = ProductionEstimatorService::calculate($linearFeet, $this->record->company_id);

                if ($estimate && isset($estimate['days'])) {
                    $estimatedDays = number_format($estimate['days'], 1) . ' days';
                    $estimateDescription = number_format($estimate['hours'], 0) . ' production hours';

                    // Compare estimate to deadline
                    if ($daysRemaining !== null && $estimate['days'] > $daysRemaining) {
                        $estimateColor = 'danger';
                        $estimateDescription .= ' (exceeds deadline!)';
                    } elseif ($daysRemaining !== null && $estimate['days'] > ($daysRemaining * 0.8)) {
                        $estimateColor = 'warning';
                        $estimateDescription .= ' (tight schedule)';
                    } else {
                        $estimateColor = 'success';
                    }
                }
            } catch (\Exception $e) {
                $estimateDescription = 'Calculation error';
            }
        }

        return [
            'start_formatted' => $startFormatted,
            'start_description' => $startDescription,
            'completion_formatted' => $completionFormatted,
            'days_remaining' => $daysRemaining,
            'days_remaining_text' => $daysRemainingText,
            'completion_color' => $completionColor,
            'completion_icon' => $completionIcon,
            'estimated_days' => $estimatedDays,
            'estimate_description' => $estimateDescription,
            'estimate_color' => $estimateColor,
        ];
    }
}
