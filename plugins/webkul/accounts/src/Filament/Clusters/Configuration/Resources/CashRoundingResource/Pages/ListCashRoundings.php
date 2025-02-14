<?php

namespace Webkul\Account\Filament\Clusters\Configuration\Resources\CashRoundingResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Webkul\Invoice\Filament\Clusters\Configuration\Resources\CashRoundingResource;

class ListCashRoundings extends ListRecords
{
    protected static string $resource = CashRoundingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
