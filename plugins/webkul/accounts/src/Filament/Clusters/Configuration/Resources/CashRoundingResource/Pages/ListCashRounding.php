<?php

namespace Webkul\Account\Filament\Clusters\Configuration\Resources\CashRoundingResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Webkul\Account\Filament\Clusters\Configuration\Resources\CashRoundingResource;

class ListCashRounding extends ListRecords
{
    protected static string $resource = CashRoundingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
