<?php

namespace Webkul\Invoice\Filament\Clusters\Customer\Resources;

use Webkul\Invoice\Filament\Clusters\Customer;
use Webkul\Invoice\Filament\Clusters\Customer\Resources\ProductResource\Pages;
use Webkul\Sale\Filament\Clusters\Products\Resources\ProductResource as BaseProductResource;

class ProductResource extends BaseProductResource
{
    protected static ?string $cluster = Customer::class;

    protected static ?int $navigationSort = 5;

    public static function getModelLabel(): string
    {
        return __('Products');
    }

    public static function getNavigationLabel(): string
    {
        return __('Products');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('Invoices');
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
