<?php

namespace Webkul\Account\Filament\Resources\CashRoundingResource\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Webkul\Account\Filament\Resources\CashRoundingResource;
use Webkul\TableViews\Filament\Components\PresetView;
use Webkul\TableViews\Filament\Concerns\HasTableViews;

class ListCashRounding extends ListRecords
{
    use HasTableViews;

    protected static string $resource = CashRoundingResource::class;

    public function getPresetTableViews(): array
    {
        return [
            'all' => PresetView::make(__('accounts::filament/resources/cash-rounding/pages/list-cash-rounding.tabs.all'))
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
