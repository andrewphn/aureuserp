<?php

namespace Webkul\Account\Filament\Resources\FiscalPositionResource\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Webkul\Account\Filament\Resources\FiscalPositionResource;
use Webkul\TableViews\Filament\Components\PresetView;
use Webkul\TableViews\Filament\Concerns\HasTableViews;

class ListFiscalPositions extends ListRecords
{
    use HasTableViews;

    protected static string $resource = FiscalPositionResource::class;

    public function getPresetTableViews(): array
    {
        return [
            'all' => PresetView::make(__('accounts::filament/resources/fiscal-position/pages/list-fiscal-positions.tabs.all'))
                ->icon('heroicon-s-queue-list')
                ->favorite()
                ->setAsDefault(),
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->icon('heroicon-o-plus-circle'),
        ];
    }
}
