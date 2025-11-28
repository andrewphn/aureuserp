<?php

namespace Webkul\Sale\Filament\Clusters\Configuration\Resources\TagResource\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Auth;
use Webkul\Sale\Filament\Clusters\Configuration\Resources\TagResource;
use Webkul\TableViews\Filament\Components\PresetView;
use Webkul\TableViews\Filament\Concerns\HasTableViews;

/**
 * List Tags class
 *
 * @see \Filament\Resources\Resource
 */
class ListTags extends ListRecords
{
    use HasTableViews;

    protected static string $resource = TagResource::class;

    /**
     * Get the header actions for the list page
     *
     * @return array<\Filament\Actions\Action>
     */
    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->icon('heroicon-o-plus-circle')
                ->mutateDataUsing(function (array $data) {
                    $data['creator_id'] = Auth::id();

                    return $data;
                }),
        ];
    }

    /**
     * Get preset table views for filtering tags
     *
     * @return array<string, PresetView>
     */
    public function getPresetTableViews(): array
    {
        return [
            'all' => PresetView::make(__('sales::filament/clusters/configurations/resources/tag/pages/list-tags.tabs.all'))
                ->icon('heroicon-s-queue-list')
                ->favorite()
                ->setAsDefault(),
        ];
    }
}
