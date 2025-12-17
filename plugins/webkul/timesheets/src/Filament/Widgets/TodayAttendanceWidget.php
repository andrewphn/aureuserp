<?php

namespace Webkul\Timesheet\Filament\Widgets;

use App\Services\ClockingService;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;
use Webkul\Employee\Models\Employee;

/**
 * Today's Attendance Table Widget
 *
 * Shows a list of all employees and their clock status for today.
 * For owner dashboard - provides quick view of who's working.
 */
class TodayAttendanceWidget extends BaseWidget
{
    protected static ?int $sort = 2;

    protected int | string | array $columnSpan = 'full';

    protected ?string $pollingInterval = '60s';

    /**
     * Determine if the widget can be viewed.
     */
    public static function canView(): bool
    {
        return true;
    }

    public function getTableHeading(): ?string
    {
        return 'Employee Status';
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Employee::query()
                    ->where('is_active', true)
                    ->whereNotNull('user_id')
            )
            ->defaultSort('name')
            ->columns([
                TextColumn::make('name')
                    ->label('Employee')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('clock_status')
                    ->label('Status')
                    ->state(function (Employee $record): string {
                        $clockingService = app(ClockingService::class);
                        $status = $clockingService->getStatus($record->user_id);
                        return $status['is_clocked_in'] ? 'Clocked In' : 'Not In';
                    })
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Clocked In' => 'success',
                        default => 'gray',
                    }),

                TextColumn::make('clock_in_time')
                    ->label('In')
                    ->state(function (Employee $record): ?string {
                        $clockingService = app(ClockingService::class);
                        $status = $clockingService->getStatus($record->user_id);
                        return $status['clock_in_time'];
                    }),

                TextColumn::make('running_hours')
                    ->label('Running')
                    ->state(function (Employee $record): string {
                        $clockingService = app(ClockingService::class);
                        $status = $clockingService->getStatus($record->user_id);
                        if (!$status['is_clocked_in']) {
                            return '-';
                        }
                        return $this->formatHours($status['running_hours']);
                    }),

                TextColumn::make('today_hours')
                    ->label('Today')
                    ->state(function (Employee $record): string {
                        $clockingService = app(ClockingService::class);
                        $status = $clockingService->getStatus($record->user_id);
                        return $this->formatHours($status['today_hours']);
                    }),

                TextColumn::make('weekly_hours')
                    ->label('Week')
                    ->state(function (Employee $record): string {
                        $clockingService = app(ClockingService::class);
                        $status = $clockingService->getStatus($record->user_id);
                        return $this->formatHours($status['weekly_hours']) . ' / 32h';
                    }),

                TextColumn::make('workLocation.name')
                    ->label('Location')
                    ->placeholder('Not set'),
            ])
            ->paginated(false);
    }

    /**
     * Format hours for display
     */
    protected function formatHours(float $hours): string
    {
        if ($hours === 0.0) {
            return '0h';
        }

        $wholeHours = floor($hours);
        $minutes = round(($hours - $wholeHours) * 60);

        if ($minutes > 0) {
            return "{$wholeHours}h {$minutes}m";
        }

        return "{$wholeHours}h";
    }
}
