<?php

namespace Webkul\Purchase\Filament\Clusters\Orders\Resources;

use Filament\Pages\SubNavigationPosition;
use Webkul\Purchase\Filament\Clusters\Orders;
use Filament\Tables\Table;
use Webkul\Purchase\Filament\Clusters\Orders\Resources\PurchaseOrderResource\Pages;
use Filament\Resources\Pages\Page;
use Webkul\Purchase\Enums\OrderState;
use Illuminate\Database\Eloquent\Builder;

class PurchaseOrderResource extends OrderResource
{
    protected static ?string $navigationIcon = 'heroicon-o-document-check';

    protected static bool $shouldRegisterNavigation = true;

    protected static ?string $recordTitleAttribute = 'name';

    protected static ?int $navigationSort = 2;

    protected static ?string $cluster = Orders::class;

    protected static SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;

    public static function getNavigationLabel(): string
    {
        return __('purchases::filament/clusters/orders/resources/purchase-order.navigation.title');
    }

    public static function getRecordSubNavigation(Page $page): array
    {
        return $page->generateNavigationItems([
            Pages\ViewPurchaseOrder::class,
            Pages\EditPurchaseOrder::class,
        ]);
    }

    public static function table(Table $table): Table
    {
        return parent::table($table)
            ->modifyQueryUsing(fn (Builder $query) => $query->whereIn('state', [OrderState::PURCHASE, OrderState::DONE]));
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
