<?php

namespace Webkul\Timesheet\Filament\Resources\TimesheetResource\Pages;

use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ManageRecords;
use Illuminate\Support\Facades\Auth;
use Webkul\Project\Models\Timesheet;
use Webkul\TableViews\Filament\Components\PresetView;
use Webkul\TableViews\Filament\Concerns\HasTableViews;
use Webkul\Timesheet\Filament\Resources\TimesheetResource;
use App\Services\ClockingService;

/**
 * Manage Timesheets class
 *
 * @see \Filament\Resources\Resource
 */
class ManageTimesheets extends ManageRecords
{
    use HasTableViews;

    protected static string $resource = TimesheetResource::class;

    protected function getHeaderActions(): array
    {
        $clockingService = app(ClockingService::class);
        $userId = Auth::id();
        $isClockedIn = Timesheet::isUserClockedIn($userId);
        $currentEntry = Timesheet::getCurrentClockEntry($userId);

        return [
            // Clock In/Out Actions
            Action::make('clockIn')
                ->label('Clock In')
                ->icon('heroicon-o-play-circle')
                ->color('success')
                ->visible(fn () => !$isClockedIn)
                ->requiresConfirmation()
                ->modalHeading('Clock In')
                ->modalDescription('Are you sure you want to clock in?')
                ->action(function () use ($clockingService, $userId) {
                    $result = $clockingService->clockIn($userId);

                    if ($result['success']) {
                        Notification::make()
                            ->success()
                            ->title('Clocked In')
                            ->body('You have successfully clocked in at ' . $result['clocked_in_at'])
                            ->send();
                    } else {
                        Notification::make()
                            ->danger()
                            ->title('Clock In Failed')
                            ->body($result['message'] ?? 'Unable to clock in')
                            ->send();
                    }

                    $this->dispatch('$refresh');
                }),

            Action::make('clockOut')
                ->label('Clock Out')
                ->icon('heroicon-o-stop-circle')
                ->color('danger')
                ->visible(fn () => $isClockedIn)
                ->requiresConfirmation()
                ->modalHeading('Clock Out')
                ->modalDescription('Are you sure you want to clock out?')
                ->form([
                    \Filament\Forms\Components\TextInput::make('break_duration')
                        ->label('Lunch Duration (minutes)')
                        ->numeric()
                        ->default(60)
                        ->minValue(0)
                        ->maxValue(480)
                        ->helperText('Enter lunch duration in minutes (e.g., 60 for 1 hour)'),
                    \Filament\Forms\Components\Select::make('project_id')
                        ->label('Project')
                        ->relationship('project', 'name', fn ($query) => $query->where('status', 'active'))
                        ->searchable()
                        ->preload()
                        ->required(),
                ])
                ->fillForm(function () use ($currentEntry) {
                    return [
                        'break_duration' => $currentEntry?->break_duration_minutes ?? 60,
                        'project_id' => $currentEntry?->project_id ?? null,
                    ];
                })
                ->action(function (array $data) use ($clockingService, $userId) {
                    $result = $clockingService->clockOut(
                        $userId,
                        $data['break_duration'] ?? 60,
                        $data['project_id'] ?? null
                    );

                    if ($result['success']) {
                        Notification::make()
                            ->success()
                            ->title('Clocked Out')
                            ->body('You have successfully clocked out. Total hours: ' . $result['hours_worked'] ?? 'N/A')
                            ->send();
                    } else {
                        Notification::make()
                            ->danger()
                            ->title('Clock Out Failed')
                            ->body($result['message'] ?? 'Unable to clock out')
                            ->send();
                    }

                    $this->dispatch('$refresh');
                }),

            CreateAction::make()
                ->label(__('timesheets::filament/resources/timesheet/manage-timesheets.header-actions.create.label'))
                ->icon('heroicon-o-plus-circle')
                ->mutateDataUsing(function (array $data): array {
                    $data['creator_id'] = Auth::id();

                    return $data;
                })
                ->successNotification(
                    Notification::make()
                        ->success()
                        ->title(__('timesheets::filament/resources/timesheet/manage-timesheets.header-actions.create.notification.title'))
                        ->body(__('timesheets::filament/resources/timesheet/manage-timesheets.header-actions.create.notification.body')),
                ),
        ];
    }

    public function getPresetTableViews(): array
    {
        return [
            'my_timesheets' => PresetView::make(__('timesheets::filament/resources/timesheet/manage-timesheets.tabs.my-timesheets'))
                ->badge(fn (): int => Timesheet::where('user_id', Auth::id())->count())
                ->icon('heroicon-o-clock')
                ->modifyQueryUsing(function ($query) {
                    return $query->where('user_id', Auth::id());
                })
                ->favorite(),
        ];
    }
}
