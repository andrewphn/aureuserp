<?php

namespace Webkul\Account\Filament\Resources;

use Webkul\Account\Filament\Resources\ProductCategoryResource\Pages;
use Webkul\Sale\Filament\Clusters\Configuration\Resources\ProductCategoryResource as BaseProductCategoryResource;

class ProductCategoryResource extends BaseProductCategoryResource
{
    protected static ?string $navigationIcon = 'heroicon-o-tag';

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListProductCategories::route('/'),
            'create' => Pages\CreateProductCategory::route('/create'),
            'view'   => Pages\ViewProductCategory::route('/{record}'),
            'edit'   => Pages\EditProductCategory::route('/{record}/edit'),
        ];
    }
}
