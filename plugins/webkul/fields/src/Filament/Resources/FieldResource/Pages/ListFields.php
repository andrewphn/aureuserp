<?php

namespace Webkul\Field\Filament\Resources\FieldResource\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Webkul\Field\Filament\Resources\FieldResource;
use Webkul\Field\Models\Field;
use Webkul\TableViews\Filament\Components\PresetView;
use Webkul\TableViews\Filament\Concerns\HasTableViews;

/**
 * List Fields class
 *
 * @see \Filament\Resources\Resource
 */
class ListFields extends ListRecords
{
    use HasTableViews;

    protected static string $resource = FieldResource::class;

    public function getPresetTableViews(): array
    {
        return [
            'all' => PresetView::make(__('fields::filament/resources/field/pages/list-fields.tabs.all'))
                ->icon('heroicon-s-queue-list')
                ->favorite()
                ->setAsDefault(),
            'archived' => PresetView::make(__('fields::filament/resources/field/pages/list-fields.tabs.archived'))
                ->icon('heroicon-s-archive-box')
                ->modifyQueryUsing(fn ($query) => $query->onlyTrashed()),
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label(__('fields::filament/resources/field/pages/list-fields.header-actions.create.label'))
                ->icon('heroicon-o-plus-circle'),
        ];
    }
}
