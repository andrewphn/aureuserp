<?php

namespace Webkul\Invoice\Filament\Clusters\Vendors\Resources;

use Filament\Tables\Table;
use Webkul\Invoice\Filament\Clusters\Vendors;
use Webkul\Invoice\Filament\Clusters\Vendors\Resources\ProductResource\Pages;
use Webkul\Sale\Filament\Clusters\Products\Resources\ProductResource as BaseProductResource;

class ProductResource extends BaseProductResource
{
    protected static ?string $cluster = Vendors::class;

    protected static ?int $navigationSort = 4;

    public static function table(Table $table): Table
    {
        return BaseProductResource::table($table)
            ->modifyQueryUsing(function ($query) {
                return $query->where('purchase_ok', true);
            });
    }

    public static function getPages(): array
    {
        return [
            'index'      => Pages\ListProducts::route('/'),
            'create'     => Pages\CreateProduct::route('/create'),
            'view'       => Pages\ViewProduct::route('/{record}'),
            'edit'       => Pages\EditProduct::route('/{record}/edit'),
            'attributes' => Pages\ManageAttributes::route('/{record}/attributes'),
            'variants'   => Pages\ManageVariants::route('/{record}/variants'),
        ];
    }
}
