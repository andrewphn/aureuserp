<?php

namespace Webkul\Recruitment\Filament\Clusters\Configurations\Resources\UTMSourceResource\Pages;

use Filament\Actions\CreateAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Webkul\Recruitment\Filament\Clusters\Configurations\Resources\UTMSourceResource;
use Webkul\Recruitment\Models\UTMSource;
use Webkul\TableViews\Filament\Components\PresetView;
use Webkul\TableViews\Filament\Concerns\HasTableViews;

class ListUTMSources extends ListRecords
{
    use HasTableViews;

    protected static string $resource = UTMSourceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label(__('recruitments::filament/clusters/configurations/resources/source/pages/list-source.header-actions.create.label'))
                ->icon('heroicon-o-plus-circle')
                ->successNotification(
                    Notification::make()
                        ->success()
                        ->title(__('recruitments::filament/clusters/configurations/resources/source/pages/list-source.header-actions.create.notification.title'))
                        ->body(__('recruitments::filament/clusters/configurations/resources/source/pages/list-source.header-actions.create.notification.body'))
                ),
        ];
    }

    public function getPresetTableViews(): array
    {
        return [
            'all' => PresetView::make(__('recruitments::filament/clusters/configurations/resources/source/pages/list-source.tabs.all'))
                ->icon('heroicon-s-link')
                ->favorite()
                ->setAsDefault()
                ->badge(UTMSource::count()),
        ];
    }
}
