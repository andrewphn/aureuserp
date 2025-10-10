<?php

namespace Webkul\Purchase\Filament\Customer\Clusters\Account\Resources\PurchaseOrderResource\Pages;

use Filament\Resources\Pages\ListRecords;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Webkul\Purchase\Enums\OrderState;
use Webkul\Purchase\Filament\Customer\Clusters\Account\Resources\PurchaseOrderResource;
use Webkul\Purchase\Models\Order;
use Webkul\TableViews\Filament\Components\PresetView;
use Webkul\TableViews\Filament\Concerns\HasTableViews;

class ListPurchaseOrders extends ListRecords
{
    use HasTableViews;

    protected static string $resource = PurchaseOrderResource::class;

    public function table(Table $table): Table
    {
        return PurchaseOrderResource::table($table)
            ->modifyQueryUsing(fn (Builder $query) => $query->whereIn('state', [OrderState::PURCHASE, OrderState::DONE, OrderState::CANCELED]));
    }

    public function getPresetTableViews(): array
    {
        return [
            'all' => PresetView::make(__('purchases::filament/customer/clusters/account/resources/purchase-order/pages/list-purchase-orders.tabs.all'))
                ->icon('heroicon-s-shopping-cart')
                ->favorite()
                ->setAsDefault()
                ->badge(Order::whereIn('state', [OrderState::PURCHASE, OrderState::DONE, OrderState::CANCELED])->count()),
        ];
    }
}
