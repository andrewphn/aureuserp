<?php

namespace Webkul\Project\Filament\Resources\CncProgramResource\Widgets;

use Filament\Widgets\Widget;
use Illuminate\Database\Eloquent\Model;
use Webkul\Project\Models\CncProgram;
use Webkul\Project\Models\CncProgramPart;

/**
 * CNC Program Status Widget
 *
 * Displays alerts, progress, and status for a CNC program
 */
class CncProgramStatusWidget extends Widget
{
    protected string $view = 'webkul-project::filament.widgets.cnc-program-status';

    public ?Model $record = null;

    protected int|string|array $columnSpan = 'full';

    public function getAlerts(): array
    {
        $alerts = [];

        if (!$this->record) {
            return $alerts;
        }

        // Check for pending material
        $pendingMaterial = $this->record->parts()
            ->where('material_status', CncProgramPart::MATERIAL_PENDING)
            ->count();

        if ($pendingMaterial > 0) {
            $alerts[] = [
                'type' => 'warning',
                'icon' => 'heroicon-o-exclamation-triangle',
                'title' => 'Material Pending',
                'message' => "{$pendingMaterial} parts waiting for material",
            ];
        }

        // Check for errors
        $errorParts = $this->record->parts()
            ->where('status', CncProgramPart::STATUS_ERROR)
            ->count();

        if ($errorParts > 0) {
            $alerts[] = [
                'type' => 'danger',
                'icon' => 'heroicon-o-x-circle',
                'title' => 'Parts with Errors',
                'message' => "{$errorParts} parts have errors that need attention",
            ];
        }

        // Check for running parts
        $runningParts = $this->record->parts()
            ->where('status', CncProgramPart::STATUS_RUNNING)
            ->count();

        if ($runningParts > 0) {
            $alerts[] = [
                'type' => 'info',
                'icon' => 'heroicon-o-play',
                'title' => 'In Progress',
                'message' => "{$runningParts} parts currently running",
            ];
        }

        // Program complete alert
        if ($this->record->status === CncProgram::STATUS_COMPLETE) {
            $alerts[] = [
                'type' => 'success',
                'icon' => 'heroicon-o-check-circle',
                'title' => 'Program Complete',
                'message' => 'All parts have been completed',
            ];
        }

        return $alerts;
    }

    public function getStats(): array
    {
        if (!$this->record) {
            return [];
        }

        $total = $this->record->parts()->count();
        $complete = $this->record->parts()->where('status', CncProgramPart::STATUS_COMPLETE)->count();
        $running = $this->record->parts()->where('status', CncProgramPart::STATUS_RUNNING)->count();
        $pending = $this->record->parts()->where('status', CncProgramPart::STATUS_PENDING)->count();

        return [
            'total' => $total,
            'complete' => $complete,
            'running' => $running,
            'pending' => $pending,
            'progress' => $total > 0 ? round(($complete / $total) * 100) : 0,
        ];
    }

    public function getProgramInfo(): array
    {
        if (!$this->record) {
            return [];
        }

        return [
            'material' => $this->record->material_code,
            'sheet_size' => $this->record->sheet_size,
            'sheet_count' => $this->record->sheet_count,
            'utilization' => $this->record->utilization_percentage,
            'status' => $this->record->status,
            'project' => $this->record->project?->name,
        ];
    }
}
