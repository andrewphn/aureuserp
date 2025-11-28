<?php

namespace Webkul\Sale\Filament\Clusters\Configuration\Resources;

use Filament\Resources\Pages\Page;
use Webkul\Invoice\Filament\Clusters\Configuration\Resources\ProductCategoryResource as BaseProductCategoryResource;
use Webkul\Sale\Filament\Clusters\Configuration;
use Webkul\Sale\Filament\Clusters\Configuration\Resources\ProductCategoryResource\Pages\CreateProductCategory;
use Webkul\Sale\Filament\Clusters\Configuration\Resources\ProductCategoryResource\Pages\EditProductCategory;
use Webkul\Sale\Filament\Clusters\Configuration\Resources\ProductCategoryResource\Pages\ListProductCategories;
use Webkul\Sale\Filament\Clusters\Configuration\Resources\ProductCategoryResource\Pages\ManageProducts;
use Webkul\Sale\Filament\Clusters\Configuration\Resources\ProductCategoryResource\Pages\ViewProductCategory;
use Webkul\Sale\Models\Category;

/**
 * Product Category Resource Filament resource
 *
 * @see \Filament\Resources\Resource
 */
class ProductCategoryResource extends BaseProductCategoryResource
{
    protected static ?string $model = Category::class;

    protected static ?string $cluster = Configuration::class;

    /**
     * Get the navigation group
     *
     * @return string|null
     */
    public static function getNavigationGroup(): ?string
    {
        return __('sales::filament/clusters/configurations/resources/product-category.navigation.group');
    }

    /**
     * Get Record Sub Navigation
     *
     * @param Page $page Page number
     * @return array
     */
    public static function getRecordSubNavigation(Page $page): array
    {
        return $page->generateNavigationItems([
            ViewProductCategory::class,
            EditProductCategory::class,
            ManageProducts::class,
        ]);
    }

    /**
     * Get the pages for this resource
     *
     * @return array<string, \Filament\Resources\Pages\PageRegistration>
     */
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
