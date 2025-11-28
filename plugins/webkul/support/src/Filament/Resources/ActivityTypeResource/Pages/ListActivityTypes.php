<?php

namespace Webkul\Support\Filament\Resources\ActivityTypeResource\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Webkul\Support\Filament\Resources\ActivityTypeResource;
use Webkul\Support\Models\ActivityType;
use Webkul\TableViews\Filament\Components\PresetView;
use Webkul\TableViews\Filament\Concerns\HasTableViews;

/**
 * List Activity Types class
 *
 * @see \Filament\Resources\Resource
 */
class ListActivityTypes extends ListRecords
{
    use HasTableViews;

    protected static string $resource = ActivityTypeResource::class;

    protected static ?string $pluginName = 'support';

    protected static function getPluginName()
    {
        return static::$pluginName;
    }

    public function getPresetTableViews(): array
    {
        return [
            'all' => PresetView::make(__('support::filament/resources/activity-type/pages/list-activity-type.tabs.all'))
                ->icon('heroicon-s-queue-list')
                ->favorite()
                ->setAsDefault()
                ->modifyQueryUsing(fn ($query) => $query->where('plugin', static::getPluginName())),
            'archived' => PresetView::make(__('support::filament/resources/activity-type/pages/list-activity-type.tabs.archived'))
                ->icon('heroicon-s-archive-box')
                ->modifyQueryUsing(fn ($query) => $query->where('plugin', static::getPluginName())->onlyTrashed()),
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label(__('support::filament/resources/activity-type/pages/list-activity-type.header-actions.create.label'))
                ->icon('heroicon-o-plus-circle'),
        ];
    }
}
