<?php

namespace Webkul\Project\Filament\Resources\ProjectResource\Widgets;

use App\Services\ProductionEstimatorService;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Database\Eloquent\Model;

/**
 * Project Timeline Widget - Compact single-stat widget
 *
 * Shows deadline with days remaining and timeline alerts.
 */
class ProjectTimelineWidget extends BaseWidget
{
    public ?Model $record = null;

    protected int | string | array $columnSpan = 1;

    protected function getStats(): array
    {
        if (! $this->record) {
            return [];
        }

        $timeline = $this->calculateTimeline();
        $alert = $this->getTimelineAlert($timeline);

        // Build description
        $descParts = [];
        $descParts[] = $timeline['days_remaining_text'];
        if ($timeline['estimated_days'] !== 'TBD') {
            $descParts[] = $timeline['estimated_days'] . ' needed';
        }

        $description = implode(' • ', $descParts);
        $color = $timeline['completion_color'];

        // Show alert if present
        if ($alert) {
            $description = '⚠ ' . $alert['message'];
            $color = $alert['color'];
        }

        return [
            Stat::make('Timeline', $timeline['completion_formatted'])
                ->description($description)
                ->icon($timeline['completion_icon'])
                ->color($color),
        ];
    }

    /**
     * Get timeline alert if any issues exist
     */
    protected function getTimelineAlert(array $timeline): ?array
    {
        // Overdue
        if ($timeline['days_remaining'] !== null && $timeline['days_remaining'] < 0) {
            $days = abs((int) round($timeline['days_remaining']));
            return [
                'message' => $days . ' day' . ($days !== 1 ? 's' : '') . ' overdue',
                'color' => 'danger',
            ];
        }

        // Production exceeds deadline
        if ($timeline['estimate_color'] === 'danger') {
            return [
                'message' => 'Production time exceeds deadline',
                'color' => 'danger',
            ];
        }

        // Due soon (within 7 days)
        if ($timeline['days_remaining'] !== null && $timeline['days_remaining'] >= 0 && $timeline['days_remaining'] < 7) {
            $days = (int) round($timeline['days_remaining']);
            if ($days === 0) {
                return ['message' => 'Due today', 'color' => 'warning'];
            }
            return [
                'message' => 'Due in ' . $days . ' day' . ($days !== 1 ? 's' : ''),
                'color' => 'warning',
            ];
        }

        // Tight schedule
        if ($timeline['estimate_color'] === 'warning') {
            return [
                'message' => 'Tight schedule',
                'color' => 'warning',
            ];
        }

        return null;
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

            if ($daysRemaining < 0) {
                $daysRemainingText = abs($daysRemaining) . ' days overdue';
                $completionColor = 'danger';
                $completionIcon = 'heroicon-o-exclamation-triangle';
            } elseif ($daysRemaining === 0) {
                $daysRemainingText = 'Due today';
                $completionColor = 'warning';
                $completionIcon = 'heroicon-o-exclamation-circle';
            } elseif ($daysRemaining < 7) {
                $daysRemainingText = $daysRemaining . ' days remaining';
                $completionColor = 'warning';
                $completionIcon = 'heroicon-o-clock';
            } elseif ($daysRemaining < 30) {
                $daysRemainingText = $daysRemaining . ' days remaining';
                $completionColor = 'info';
                $completionIcon = 'heroicon-o-calendar';
            } else {
                $daysRemainingText = $daysRemaining . ' days remaining';
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
