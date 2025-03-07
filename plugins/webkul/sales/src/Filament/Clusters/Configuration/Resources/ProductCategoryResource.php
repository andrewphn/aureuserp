<?php

namespace Webkul\Sale\Filament\Clusters\Configuration\Resources;

use Webkul\Invoice\Filament\Clusters\Configuration\Resources\ProductCategoryResource as BaseProductCategoryResource;
use Webkul\Sale\Filament\Clusters\Configuration;
use Webkul\Sale\Filament\Clusters\Configuration\Resources\ProductCategoryResource\Pages;
use Webkul\Sale\Models\Category;

class ProductCategoryResource extends BaseProductCategoryResource
{
    protected static ?string $model = Category::class;

    protected static bool $shouldRegisterNavigation = true;

    protected static ?string $navigationIcon = 'heroicon-o-squares-2x2';

    protected static ?string $cluster = Configuration::class;

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
