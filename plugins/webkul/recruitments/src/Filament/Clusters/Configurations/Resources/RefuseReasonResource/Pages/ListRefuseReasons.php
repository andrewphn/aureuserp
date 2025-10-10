<?php

namespace Webkul\Recruitment\Filament\Clusters\Configurations\Resources\RefuseReasonResource\Pages;

use Filament\Actions\CreateAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Webkul\Recruitment\Filament\Clusters\Configurations\Resources\RefuseReasonResource;
use Webkul\Recruitment\Models\RefuseReason;
use Webkul\TableViews\Filament\Components\PresetView;
use Webkul\TableViews\Filament\Concerns\HasTableViews;

class ListRefuseReasons extends ListRecords
{
    use HasTableViews;

    protected static string $resource = RefuseReasonResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->icon('heroicon-o-plus-circle')
                ->successNotification(
                    Notification::make()
                        ->success()
                        ->title(__('recruitments::filament/clusters/configurations/resources/refuse-reason/pages/list-refuse-reasons.notification.title'))
                        ->body(__('recruitments::filament/clusters/configurations/resources/refuse-reason/pages/list-refuse-reasons.notification.body'))
                ),
        ];
    }

    public function getPresetTableViews(): array
    {
        return [
            'all' => PresetView::make(__('recruitments::filament/clusters/configurations/resources/refuse-reason/pages/list-refuse-reasons.tabs.all'))
                ->icon('heroicon-s-x-circle')
                ->favorite()
                ->setAsDefault()
                ->badge(RefuseReason::count()),
        ];
    }
}
