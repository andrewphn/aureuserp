<?php

namespace Webkul\Sale\Filament\Clusters\Configuration\Resources;

use Webkul\Invoice\Filament\Clusters\Configuration\Resources\ProductAttributeResource as BaseProductAttributeResource;
use Webkul\Sale\Filament\Clusters\Configuration;
use Webkul\Sale\Filament\Clusters\Configuration\Resources\ProductAttributeResource\Pages\CreateProductAttribute;
use Webkul\Sale\Filament\Clusters\Configuration\Resources\ProductAttributeResource\Pages\EditProductAttribute;
use Webkul\Sale\Filament\Clusters\Configuration\Resources\ProductAttributeResource\Pages\ListProductAttributes;
use Webkul\Sale\Filament\Clusters\Configuration\Resources\ProductAttributeResource\Pages\ViewProductAttribute;
use Webkul\Sale\Models\Attribute;

/**
 * Product Attribute Resource Filament resource
 *
 * @see \Filament\Resources\Resource
 */
class ProductAttributeResource extends BaseProductAttributeResource
{
    protected static ?string $model = Attribute::class;

    protected static bool $shouldRegisterNavigation = true;

    protected static ?int $navigationSort = 9;

    protected static ?string $cluster = Configuration::class;

    protected static ?string $recordTitleAttribute = 'name';

    /**
     * Get the navigation group
     *
     * @return string
     */
    public static function getNavigationGroup(): string
    {
        return __('sales::filament/clusters/configurations/resources/product-attribute.navigation.group');
    }

    /**
     * Get the navigation label
     *
     * @return string
     */
    public static function getNavigationLabel(): string
    {
        return __('sales::filament/clusters/configurations/resources/product-attribute.navigation.title');
    }

    /**
     * Get the pages for this resource
     *
     * @return array<string, \Filament\Resources\Pages\PageRegistration>
     */
    public static function getPages(): array
    {
        return [
            'index'  => ListProductAttributes::route('/'),
            'create' => CreateProductAttribute::route('/create'),
            'view'   => ViewProductAttribute::route('/{record}'),
            'edit'   => EditProductAttribute::route('/{record}/edit'),
        ];
    }
}
