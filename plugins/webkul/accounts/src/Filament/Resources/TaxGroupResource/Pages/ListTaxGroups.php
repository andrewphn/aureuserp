<?php

namespace Webkul\Account\Filament\Resources\TaxGroupResource\Pages;

use Webkul\Account\Filament\Resources\TaxGroupResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListTaxGroups extends ListRecords
{
    protected static string $resource = TaxGroupResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->icon('heroicon-o-plus-circle'),
        ];
    }
}
