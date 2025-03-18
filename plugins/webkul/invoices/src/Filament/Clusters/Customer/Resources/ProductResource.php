<?php

namespace Webkul\Invoice\Filament\Clusters\Customer\Resources;

use Filament\Forms\Form;
use Filament\Infolists\Infolist;
use Filament\Pages\SubNavigationPosition;
use Filament\Tables\Table;
use Webkul\Field\Filament\Traits\HasCustomFields;
use Webkul\Invoice\Filament\Clusters\Customer;
use Webkul\Invoice\Filament\Clusters\Customer\Resources\ProductResource\Pages;
use Webkul\Invoice\Filament\Clusters\Vendors\Resources\ProductResource as BaseProductResource;
use Webkul\Invoice\Models\Product;

class ProductResource extends BaseProductResource
{
    use HasCustomFields;

    protected static ?string $model = Product::class;

    protected static ?string $cluster = Customer::class;

    protected static ?int $navigationSort = 5;

    public static function getModelLabel(): string
    {
        return __('invoices::filament/clusters/customers/resources/products.title');
    }

    protected static SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;

    public static function getNavigationLabel(): string
    {
        return __('invoices::filament/clusters/customers/resources/products.navigation.title');
    }

    public static function form(Form $form): Form
    {
        return BaseProductResource::form($form);
    }

    public static function table(Table $table): Table
    {
        return BaseProductResource::table($table);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return BaseProductResource::infolist($infolist);
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
