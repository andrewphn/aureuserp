<?php

namespace Webkul\Employee\Filament\Clusters\Configurations\Resources\CalendarResource\Pages;

use Filament\Actions\CreateAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Webkul\Employee\Filament\Clusters\Configurations\Resources\CalendarResource;
use Webkul\Employee\Models\Calendar;
use Webkul\TableViews\Filament\Components\PresetView;
use Webkul\TableViews\Filament\Concerns\HasTableViews;

/**
 * List Calendars class
 *
 * @see \Filament\Resources\Resource
 */
class ListCalendars extends ListRecords
{
    use HasTableViews;

    protected static string $resource = CalendarResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label(__('employees::filament/clusters/configurations/resources/calendar/pages/list-calendar.header-actions.create.label'))
                ->icon('heroicon-o-plus-circle')
                ->successNotification(
                    Notification::make()
                        ->success()
                        ->title(__('employees::filament/clusters/configurations/resources/calendar/pages/list-calendar.header-actions.create.notification.title'))
                        ->body(__('employees::filament/clusters/configurations/resources/calendar/pages/list-calendar.header-actions.create.notification.body')),
                ),
        ];
    }

    public function getPresetTableViews(): array
    {
        return [
            'all' => PresetView::make(__('employees::filament/clusters/configurations/resources/calendar/pages/list-calendar.tabs.all'))
                ->icon('heroicon-s-queue-list')
                ->favorite()
                ->setAsDefault(),
            'archived' => PresetView::make(__('employees::filament/clusters/configurations/resources/calendar/pages/list-calendar.tabs.archived'))
                ->icon('heroicon-s-archive-box')
                ->modifyQueryUsing(fn ($query) => $query->onlyTrashed()),
        ];
    }
}
