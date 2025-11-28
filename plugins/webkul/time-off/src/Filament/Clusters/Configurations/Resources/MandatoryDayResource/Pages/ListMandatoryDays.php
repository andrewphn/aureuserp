<?php

namespace Webkul\TimeOff\Filament\Clusters\Configurations\Resources\MandatoryDayResource\Pages;

use Filament\Actions\CreateAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Auth;
use Webkul\TimeOff\Filament\Clusters\Configurations\Resources\MandatoryDayResource;
use Webkul\TableViews\Filament\Components\PresetView;
use Webkul\TableViews\Filament\Concerns\HasTableViews;

/**
 * List Mandatory Days class
 *
 * @see \Filament\Resources\Resource
 */
class ListMandatoryDays extends ListRecords
{
    use HasTableViews;

    protected static string $resource = MandatoryDayResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label(__('time-off::filament/clusters/configurations/resources/mandatory-days/pages/list-mandatory-days.header-actions.create.title'))
                ->icon('heroicon-o-plus-circle')
                ->successNotification(
                    Notification::make()
                        ->success()
                        ->title(__('time-off::filament/clusters/configurations/resources/mandatory-days/pages/list-mandatory-days.header-actions.create.notification.created.title'))
                        ->body(__('time-off::filament/clusters/configurations/resources/mandatory-days/pages/list-mandatory-days.header-actions.create.notification.created.body'))
                )
                ->mutateDataUsing(function ($data) {
                    $user = Auth::user();

                    $data['company_id'] = $user->default_company_id;
                    $data['creator_id'] = $user->id;

                    return $data;
                }),
        ];
    }

    public function getPresetTableViews(): array
    {
        return [
            'all' => PresetView::make(__('time-off::filament/clusters/configurations/resources/mandatory-days/pages/list-mandatory-days.tabs.all'))
                ->icon('heroicon-s-queue-list')
                ->favorite()
                ->setAsDefault(),
        ];
    }
}
