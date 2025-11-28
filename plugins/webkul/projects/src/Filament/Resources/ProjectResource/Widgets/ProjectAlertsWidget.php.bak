<?php

namespace Webkul\Project\Filament\Resources\ProjectResource\Widgets;

use Filament\Widgets\Widget;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Contracts\View\View;

class ProjectAlertsWidget extends Widget
{
    public ?Model $record = null;

    protected string $view = 'webkul-project::filament.widgets.project-alerts';

    protected int | string | array $columnSpan = 'full';

    public function getAlerts(): array
    {
        if (! $this->record) {
            return [];
        }

        $alerts = [];

        // Check for missing data
        $roomsWithoutLocations = $this->record->rooms()->doesntHave('locations')->count();
        if ($roomsWithoutLocations > 0) {
            $alerts[] = [
                'type' => 'warning',
                'icon' => 'heroicon-o-exclamation-triangle',
                'message' => "{$roomsWithoutLocations} room(s) missing location details",
                'action' => 'Add room locations to complete project data',
            ];
        }

        $cabinetsWithoutPrice = $this->record->cabinets()
            ->where(function ($query) {
                $query->whereNull('unit_price_per_lf')
                    ->orWhereNull('linear_feet')
                    ->orWhereNull('quantity');
            })
            ->count();

        if ($cabinetsWithoutPrice > 0) {
            $alerts[] = [
                'type' => 'warning',
                'icon' => 'heroicon-o-currency-dollar',
                'message' => "{$cabinetsWithoutPrice} cabinet(s) missing pricing information",
                'action' => 'Complete cabinet specifications for accurate quotes',
            ];
        }

        // Check for incomplete specifications
        $cabinetsWithoutDimensions = $this->record->cabinets()
            ->where(function ($query) {
                $query->whereNull('width_inches')
                    ->orWhereNull('height_inches')
                    ->orWhereNull('depth_inches');
            })
            ->count();

        if ($cabinetsWithoutDimensions > 0) {
            $alerts[] = [
                'type' => 'info',
                'icon' => 'heroicon-o-cube',
                'message' => "{$cabinetsWithoutDimensions} cabinet(s) missing dimensions",
                'action' => 'Add dimensions for production planning',
            ];
        }

        // Check timeline issues
        if ($this->record->desired_completion_date) {
            $daysRemaining = now()->diffInDays($this->record->desired_completion_date, false);

            if ($daysRemaining < 0) {
                $alerts[] = [
                    'type' => 'danger',
                    'icon' => 'heroicon-o-exclamation-circle',
                    'message' => 'Project is ' . abs($daysRemaining) . ' days overdue',
                    'action' => 'Update timeline or expedite production',
                ];
            } elseif ($daysRemaining < 7) {
                $alerts[] = [
                    'type' => 'warning',
                    'icon' => 'heroicon-o-clock',
                    'message' => 'Deadline in ' . $daysRemaining . ' days',
                    'action' => 'Review production schedule',
                ];
            }
        }

        // Check for missing partner/customer info
        if (! $this->record->partner_id) {
            $alerts[] = [
                'type' => 'warning',
                'icon' => 'heroicon-o-user',
                'message' => 'No customer assigned to project',
                'action' => 'Assign customer for billing and communications',
            ];
        }

        // Check for missing PDF documents
        $pdfCount = $this->record->pdfDocuments()->count();
        if ($pdfCount === 0) {
            $alerts[] = [
                'type' => 'info',
                'icon' => 'heroicon-o-document',
                'message' => 'No PDF plans uploaded',
                'action' => 'Upload architectural plans for reference',
            ];
        }

        // If no alerts, show success message
        if (empty($alerts)) {
            $alerts[] = [
                'type' => 'success',
                'icon' => 'heroicon-o-check-circle',
                'message' => 'No pending action items',
                'action' => 'Project setup is complete',
            ];
        }

        return $alerts;
    }

    public function getPriorityAlerts(): array
    {
        return collect($this->getAlerts())
            ->where('type', 'danger')
            ->values()
            ->toArray();
    }

    public function getWarningAlerts(): array
    {
        return collect($this->getAlerts())
            ->where('type', 'warning')
            ->values()
            ->toArray();
    }

    public function getInfoAlerts(): array
    {
        return collect($this->getAlerts())
            ->whereIn('type', ['info', 'success'])
            ->values()
            ->toArray();
    }
}
