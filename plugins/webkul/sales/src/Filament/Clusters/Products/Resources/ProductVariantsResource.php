<?php

namespace Webkul\Sale\Filament\Clusters\Products\Resources;

use Filament\Tables\Table;
use Webkul\Sale\Filament\Clusters\Products;
use Webkul\Sale\Filament\Clusters\Products\Resources\ProductResource as BaseProductResource;
use Webkul\Sale\Filament\Clusters\Products\Resources\ProductVariantsResource\Pages;

class ProductVariantsResource extends BaseProductResource
{
    protected static ?string $cluster = Products::class;

    public static function getModelLabel(): string
    {
        return __('sales::filament/clusters/products/resources/product-variants.title');
    }

    public static function getNavigationLabel(): string
    {
        return __('sales::filament/clusters/products/resources/product-variants.navigation.title');
    }

    public static function table(Table $table): Table
    {
        return BaseProductResource::table($table)
            ->modifyQueryUsing(function ($query) {
                $query->whereNotNull('parent_id');
            });
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListProductVariants::route('/'),
            'create' => Pages\CreateProductVariants::route('/create'),
            'edit'   => Pages\EditProductVariants::route('/{record}/edit'),
        ];
    }
}
