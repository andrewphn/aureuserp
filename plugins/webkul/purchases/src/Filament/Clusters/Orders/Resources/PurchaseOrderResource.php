<?php

namespace Webkul\Purchase\Filament\Clusters\Orders\Resources;

use Filament\Pages\SubNavigationPosition;
use Webkul\Purchase\Filament\Clusters\Orders;
use Webkul\Purchase\Filament\Clusters\Orders\Resources\PurchaseOrderResource\Pages;

class PurchaseOrderResource extends OrderResource
{
    protected static ?string $navigationIcon = 'heroicon-o-truck';

    protected static bool $shouldRegisterNavigation = true;

    protected static ?string $recordTitleAttribute = 'name';

    protected static ?int $navigationSort = 2;

    protected static ?string $cluster = Orders::class;

    protected static SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;

    public static function getNavigationLabel(): string
    {
        return __('purchases::filament/clusters/orders/resources/purchase-order.navigation.title');
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListPurchaseOrders::route('/'),
            'create' => Pages\CreatePurchaseOrder::route('/create'),
            'view'   => Pages\ViewPurchaseOrder::route('/{record}'),
            'edit'   => Pages\EditPurchaseOrder::route('/{record}/edit'),
        ];
    }
}
