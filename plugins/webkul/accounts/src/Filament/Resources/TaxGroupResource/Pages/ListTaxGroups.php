<?php

namespace Webkul\Account\Filament\Resources\TaxGroupResource\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Webkul\Account\Filament\Resources\TaxGroupResource;
use Webkul\TableViews\Filament\Components\PresetView;
use Webkul\TableViews\Filament\Concerns\HasTableViews;

class ListTaxGroups extends ListRecords
{
    use HasTableViews;

    protected static string $resource = TaxGroupResource::class;

    public function getPresetTableViews(): array
    {
        return [
            'all' => PresetView::make(__('accounts::filament/resources/tax-group/pages/list-tax-groups.tabs.all'))
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
