<?php

namespace Webkul\Sale\Filament\Clusters\Configuration\Resources;

use BezhanSalleh\FilamentShield\Contracts\HasShieldPermissions;
use Filament\Resources\Pages\Page;
use Webkul\Invoice\Filament\Clusters\Configuration\Resources\ProductCategoryResource as BaseProductCategoryResource;
use Webkul\Sale\Filament\Clusters\Configuration;
use Webkul\Sale\Filament\Clusters\Configuration\Resources\ProductCategoryResource\Pages\CreateProductCategory;
use Webkul\Sale\Filament\Clusters\Configuration\Resources\ProductCategoryResource\Pages\EditProductCategory;
use Webkul\Sale\Filament\Clusters\Configuration\Resources\ProductCategoryResource\Pages\ListProductCategories;
use Webkul\Sale\Filament\Clusters\Configuration\Resources\ProductCategoryResource\Pages\ManageProducts;
use Webkul\Sale\Filament\Clusters\Configuration\Resources\ProductCategoryResource\Pages\ViewProductCategory;
use Webkul\Sale\Models\Category;

class ProductCategoryResource extends BaseProductCategoryResource implements HasShieldPermissions
{
    protected static ?string $model = Category::class;

    protected static ?string $cluster = Configuration::class;

    public static function getNavigationGroup(): ?string
    {
        return __('sales::filament/clusters/configurations/resources/product-category.navigation.group');
    }

    public static function getPermissionPrefixes(): array
    {
        return [
            'view::sale',
            'view_any::sale',
            'create::sale',
            'update::sale',
            'restore::sale',
            'restore_any::sale',
            'replicate::sale',
            'reorder::sale',
            'delete::sale',
            'delete_any::sale',
            'force_delete::sale',
            'force_delete_any::sale',
        ];
    }

    public static function getRecordSubNavigation(Page $page): array
    {
        return $page->generateNavigationItems([
            ViewProductCategory::class,
            EditProductCategory::class,
            ManageProducts::class,
        ]);
    }

    public static function getPages(): array
    {
        return [
            'index'    => ListProductCategories::route('/'),
            'create'   => CreateProductCategory::route('/create'),
            'view'     => ViewProductCategory::route('/{record}'),
            'edit'     => EditProductCategory::route('/{record}/edit'),
            'products' => ManageProducts::route('/{record}/products'),
        ];
    }
}
