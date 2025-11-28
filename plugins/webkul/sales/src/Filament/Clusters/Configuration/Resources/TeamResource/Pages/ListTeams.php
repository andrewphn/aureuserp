<?php

namespace Webkul\Sale\Filament\Clusters\Configuration\Resources\TeamResource\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Webkul\Sale\Filament\Clusters\Configuration\Resources\TeamResource;
use Webkul\TableViews\Filament\Components\PresetView;
use Webkul\TableViews\Filament\Concerns\HasTableViews;

/**
 * List Teams class
 *
 * @see \Filament\Resources\Resource
 */
class ListTeams extends ListRecords
{
    use HasTableViews;

    protected static string $resource = TeamResource::class;

    /**
     * Get the header actions for the list page
     *
     * @return array<\Filament\Actions\Action>
     */
    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->icon('heroicon-o-plus-circle'),
        ];
    }

    /**
     * Get preset table views for filtering teams
     *
     * @return array<string, PresetView>
     */
    public function getPresetTableViews(): array
    {
        return [
            'all' => PresetView::make(__('sales::filament/clusters/configurations/resources/team/pages/list-teams.tabs.all'))
                ->icon('heroicon-s-queue-list')
                ->favorite()
                ->setAsDefault(),
            'archived' => PresetView::make(__('sales::filament/clusters/configurations/resources/team/pages/list-teams.tabs.archived'))
                ->icon('heroicon-s-archive-box')
                ->modifyQueryUsing(fn ($query) => $query->onlyTrashed()),
        ];
    }
}
