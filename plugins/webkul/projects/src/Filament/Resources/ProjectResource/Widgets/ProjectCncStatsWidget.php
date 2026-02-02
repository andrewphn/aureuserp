<?php

namespace Webkul\Project\Filament\Resources\ProjectResource\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Database\Eloquent\Model;
use Webkul\Project\Models\CncProgram;
use Webkul\Project\Models\CncProgramPart;

/**
 * Project CNC Stats Widget (Compact Header Version)
 *
 * Shows a single compact stat in the header row with CNC production progress.
 */
class ProjectCncStatsWidget extends BaseWidget
{
    public ?Model $record = null;

    protected static bool $isLazy = false;

    protected int|string|array $columnSpan = 1;

    protected function getStats(): array
    {
        if (!$this->record) {
            return [
                Stat::make('CNC', '-')
                    ->description('Loading...')
                    ->icon('heroicon-o-cog-8-tooth')
                    ->color('gray'),
            ];
        }

        // Get CNC stats for this project
        $programs = $this->record->cncPrograms()->with('parts')->get();

        // If no CNC programs, show a minimal message
        if ($programs->count() === 0) {
            return [
                Stat::make('CNC', 'No Programs')
                    ->description('No CNC programs created')
                    ->icon('heroicon-o-cog-8-tooth')
                    ->color('gray'),
            ];
        }

        // Calculate stats
        $totalParts = 0;
        $completeParts = 0;
        $runningParts = 0;
        $pendingMaterial = 0;
        $errorParts = 0;

        foreach ($programs as $program) {
            $totalParts += $program->parts->count();
            $completeParts += $program->parts->where('status', CncProgramPart::STATUS_COMPLETE)->count();
            $runningParts += $program->parts->where('status', CncProgramPart::STATUS_RUNNING)->count();
            $errorParts += $program->parts->where('status', CncProgramPart::STATUS_ERROR)->count();
            $pendingMaterial += $program->parts->where('material_status', CncProgramPart::MATERIAL_PENDING)->count();
        }

        $completionPct = $totalParts > 0 ? round(($completeParts / $totalParts) * 100, 0) : 0;

        // Build description parts
        $descParts = [];
        $descParts[] = "{$completeParts}/{$totalParts} parts";

        if ($runningParts > 0) {
            $descParts[] = "{$runningParts} cutting";
        }

        // Check for alerts
        $hasAlert = false;
        $alertMessage = null;

        if ($errorParts > 0) {
            $hasAlert = true;
            $alertMessage = "⚠ {$errorParts} errors";
        } elseif ($pendingMaterial > 0) {
            $hasAlert = true;
            $alertMessage = "⚠ {$pendingMaterial} need material";
        }

        // Determine color
        $color = match (true) {
            $hasAlert && $errorParts > 0 => 'danger',
            $hasAlert => 'warning',
            $completionPct >= 100 => 'success',
            $completionPct >= 50 => 'info',
            $completionPct > 0 => 'warning',
            default => 'gray',
        };

        $description = $alertMessage ?? implode(' • ', $descParts);

        return [
            Stat::make('CNC', "{$completionPct}%")
                ->description($description)
                ->descriptionIcon($runningParts > 0 ? 'heroicon-o-play' : null)
                ->icon('heroicon-o-cog-8-tooth')
                ->color($color)
                ->url(route('filament.admin.pages.cnc-queue')),
        ];
    }
}
