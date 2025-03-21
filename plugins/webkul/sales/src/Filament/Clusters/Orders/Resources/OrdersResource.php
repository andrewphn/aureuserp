<?php

namespace Webkul\Sale\Filament\Clusters\Orders\Resources;

use Filament\Forms\Form;
use Filament\Infolists\Infolist;
use Filament\Pages\SubNavigationPosition;
use Filament\Resources\Pages\Page;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use Webkul\Sale\Enums\OrderState;
use Webkul\Sale\Filament\Clusters\Orders;
use Webkul\Sale\Filament\Clusters\Orders\Resources\OrdersResource\Pages;
use Webkul\Sale\Models\Order;

class OrdersResource extends Resource
{
    protected static ?string $model = Order::class;

    protected static ?string $navigationIcon = 'heroicon-o-shopping-bag';

    protected static ?string $cluster = Orders::class;

    protected static ?int $navigationSort = 2;

    protected static SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;

    public static function getModelLabel(): string
    {
        return __('sales::filament/clusters/orders/resources/order.title');
    }

    public static function getNavigationLabel(): string
    {
        return __('sales::filament/clusters/orders/resources/order.navigation.title');
    }

    public static function form(Form $form): Form
    {
        return QuotationResource::form($form);
    }

    public static function table(Table $table): Table
    {
        return QuotationResource::table($table)
            ->modifyQueryUsing(function ($query) {
                $query->where('state', OrderState::SALE);
            });
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return QuotationResource::infolist($infolist);
    }

    public static function getRecordSubNavigation(Page $page): array
    {
        return $page->generateNavigationItems([
            Pages\ViewOrders::class,
            Pages\EditOrders::class,
            Pages\ManageInvoices::class,
        ]);
    }

    public static function getPages(): array
    {
        return [
            'index'           => Pages\ListOrders::route('/'),
            'create'          => Pages\CreateOrders::route('/create'),
            'view'            => Pages\ViewOrders::route('/{record}'),
            'edit'            => Pages\EditOrders::route('/{record}/edit'),
            'manage-invoices' => Pages\ManageInvoices::route('/{record}/manage-invoices'),
        ];
    }
}
