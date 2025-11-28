<?php

namespace Webkul\Recruitment\Filament\Clusters\Configurations\Resources\DegreeResource\Pages;

use Filament\Actions\CreateAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Webkul\Recruitment\Filament\Clusters\Configurations\Resources\DegreeResource;
use Webkul\Recruitment\Models\Degree;
use Webkul\TableViews\Filament\Components\PresetView;
use Webkul\TableViews\Filament\Concerns\HasTableViews;

/**
 * List Degrees class
 *
 * @see \Filament\Resources\Resource
 */
class ListDegrees extends ListRecords
{
    use HasTableViews;

    protected static string $resource = DegreeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->icon('heroicon-o-plus-circle')
                ->successNotification(
                    Notification::make()
                        ->success()
                        ->title(__('recruitments::filament/clusters/configurations/resources/degree/pages/list-degree.notification.title'))
                        ->body(__('recruitments::filament/clusters/configurations/resources/degree/pages/list-degree.notification.body'))
                ),
        ];
    }

    public function getPresetTableViews(): array
    {
        return [
            'all' => PresetView::make(__('recruitments::filament/clusters/configurations/resources/degree/pages/list-degree.tabs.all'))
                ->icon('heroicon-s-academic-cap')
                ->favorite()
                ->setAsDefault()
                ->badge(Degree::count()),
        ];
    }
}
