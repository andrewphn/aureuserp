<?php

namespace Webkul\Sale\Filament\Clusters\Products\Resources;

use Webkul\Sale\Filament\Clusters\Products;
use Webkul\Sale\Filament\Clusters\Products\Resources\PriceListResource\Pages;
use Webkul\Product\Filament\Resources\PriceListResource as BasePriceListResource;

class PriceListResource extends BasePriceListResource
{
    protected static bool $shouldRegisterNavigation = true;

    protected static ?string $cluster = Products::class;

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPriceLists::route('/'),
            'create' => Pages\CreatePriceList::route('/create'),
            'view' => Pages\ViewPriceList::route('/{record}'),
            'edit' => Pages\EditPriceList::route('/{record}/edit'),
        ];
    }
}
