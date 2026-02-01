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
        $alerts = $this->getTimelineAlerts($timeline);

        // Build description with production estimate
        $descParts = [];
        if ($timeline['estimated_days'] !== 'TBD') {
            $descParts[] = $timeline['estimated_days'] . ' production needed';
        }
        $descParts[] = $timeline['days_remaining_text'];

        // Use alert message if present, otherwise show normal description
        $description = implode(' • ', $descParts);
        if (!empty($alerts['message'])) {
            $description = $alerts['icon'] . ' ' . $alerts['message'];
        }

        // Determine color - alerts take priority
        $color = $alerts['color'] ?? $timeline['completion_color'];

        return [
            Stat::make('Timeline', $timeline['completion_formatted'])
                ->description($description)
                ->icon($timeline['completion_icon'])
                ->color($color),
        ];
    }

    /**
     * Get timeline-specific alerts
     */
    protected function getTimelineAlerts(array $timeline): array
    {
        // Check if overdue
        if ($timeline['days_remaining'] !== null && $timeline['days_remaining'] < 0) {
            $daysOverdue = abs((int) round($timeline['days_remaining']));
            return [
                'message' => $daysOverdue . ' day' . ($daysOverdue !== 1 ? 's' : '') . ' overdue!',
                'icon' => '⚠',
                'color' => 'danger',
            ];
        }

        // Check if production estimate exceeds deadline
        if ($timeline['estimate_color'] === 'danger') {
            return [
                'message' => 'Production time exceeds deadline',
                'icon' => '⚠',
                'color' => 'danger',
            ];
        }

        // Check if deadline is approaching (within 7 days)
        if ($timeline['days_remaining'] !== null && $timeline['days_remaining'] >= 0 && $timeline['days_remaining'] < 7) {
            $daysLeft = (int) round($timeline['days_remaining']);
            if ($daysLeft === 0) {
                return [
                    'message' => 'Due today!',
                    'icon' => '⚠',
                    'color' => 'warning',
                ];
            }
            return [
                'message' => 'Deadline in ' . $daysLeft . ' day' . ($daysLeft !== 1 ? 's' : ''),
                'icon' => '⚠',
                'color' => 'warning',
            ];
        }

        // Check if schedule is tight
        if ($timeline['estimate_color'] === 'warning') {
            return [
                'message' => 'Tight schedule - limited buffer',
                'icon' => '⚠',
                'color' => 'warning',
            ];
        }

        return [];
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
