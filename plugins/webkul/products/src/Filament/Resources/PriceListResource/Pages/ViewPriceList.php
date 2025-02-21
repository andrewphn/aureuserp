<?php

namespace Webkul\Product\Filament\Resources\PriceListResource\Pages;

use Webkul\Product\Filament\Resources\PriceListResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewPriceList extends ViewRecord
{
    protected static string $resource = PriceListResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
