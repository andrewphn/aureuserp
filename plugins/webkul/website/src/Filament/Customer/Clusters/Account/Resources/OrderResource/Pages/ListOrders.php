<?php

namespace Webkul\Website\Filament\Customer\Clusters\Account\Resources\OrderResource\Pages;

use Webkul\Website\Filament\Customer\Clusters\Account\Resources\OrderResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListOrders extends ListRecords
{
    protected static string $resource = OrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
