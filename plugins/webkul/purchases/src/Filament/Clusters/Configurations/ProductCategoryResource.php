<?php

namespace Webkul\Purchase\Filament\Clusters\Configurations\Resources;

use Filament\Pages\SubNavigationPosition;
use Filament\Resources\Pages\Page;
use Webkul\Product\Filament\Resources\CategoryResource;
use Webkul\Product\Models\Category;
use Webkul\Purchase\Filament\Clusters\Configurations;
use Webkul\Purchase\Filament\Clusters\Configurations\Resources\ProductCategoryResource\Pages;

class ProductCategoryResource extends CategoryResource
{
    protected static ?string $model = Category::class;

    protected static ?string $navigationIcon = 'heroicon-o-folder';

    protected static bool $shouldRegisterNavigation = true;

    protected static ?int $navigationSort = 8;

    protected static ?string $cluster = Configurations::class;

    protected static ?string $recordTitleAttribute = 'name';

    public static function getNavigationGroup(): string
    {
        return __('purcahses::filament/clusters/configurations/resources/product-category.navigation.group');
    }

    public static function getNavigationLabel(): string
    {
        return __('purcahses::filament/clusters/configurations/resources/product-category.navigation.title');
    }

    public static function getSubNavigationPosition(): SubNavigationPosition
    {
        $currentRoute = request()->route()?->getName();

        if ($currentRoute === self::getRouteBaseName().'.index') {
            return SubNavigationPosition::Start;
        }

        return SubNavigationPosition::Top;
    }

    public static function getRecordSubNavigation(Page $page): array
    {
        return $page->generateNavigationItems([
            Pages\ViewProductCategory::class,
            Pages\EditProductCategory::class,
            Pages\ManageProducts::class,
        ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index'    => Pages\ListProductCategories::route('/'),
            'create'   => Pages\CreateProductCategory::route('/create'),
            'view'     => Pages\ViewProductCategory::route('/{record}'),
            'edit'     => Pages\EditProductCategory::route('/{record}/edit'),
            'products' => Pages\ManageProducts::route('/{record}/products'),
        ];
    }
}
