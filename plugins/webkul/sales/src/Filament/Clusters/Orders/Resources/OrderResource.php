<?php

namespace Webkul\Sale\Filament\Clusters\Orders\Resources;

use Filament\Pages\Enums\SubNavigationPosition;
use Filament\Resources\Pages\Page;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Webkul\Sale\Enums\OrderState;
use Webkul\Sale\Filament\Clusters\Orders;
use Webkul\Sale\Filament\Clusters\Orders\Resources\OrderResource\Pages\CreateOrder;
use Webkul\Sale\Filament\Clusters\Orders\Resources\OrderResource\Pages\EditOrder;
use Webkul\Sale\Filament\Clusters\Orders\Resources\OrderResource\Pages\ListOrders;
use Webkul\Sale\Filament\Clusters\Orders\Resources\OrderResource\Pages\ManageDeliveries;
use Webkul\Sale\Filament\Clusters\Orders\Resources\OrderResource\Pages\ManageInvoices;
use Webkul\Sale\Filament\Clusters\Orders\Resources\OrderResource\Pages\ViewOrder;
use Webkul\Sale\Filament\Clusters\Orders\Resources\OrderResource\RelationManagers\LineItemsRelationManager;
use Webkul\Sale\Models\Order;

/**
 * Order Resource Filament resource
 *
 * @see \Filament\Resources\Resource
 */
class OrderResource extends Resource
{
    protected static ?string $model = Order::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-shopping-bag';

    protected static ?string $cluster = Orders::class;

    protected static ?int $navigationSort = 2;

    protected static ?SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;

    /**
     * Get the model label
     *
     * @return string
     */
    public static function getModelLabel(): string
    {
        return __('sales::filament/clusters/orders/resources/order.title');
    }

    /**
     * Get the navigation label
     *
     * @return string
     */
    public static function getNavigationLabel(): string
    {
        return __('sales::filament/clusters/orders/resources/order.navigation.title');
    }

    /**
     * Define the form schema
     *
     * @param Schema $schema
     * @return Schema
     */
    public static function form(Schema $schema): Schema
    {
        return QuotationResource::form($schema);
    }

    /**
     * Define the table schema
     *
     * @param Table $table
     * @return Table
     */
    public static function table(Table $table): Table
    {
        return QuotationResource::table($table)
            ->modifyQueryUsing(function ($query) {
                $query->where('state', OrderState::SALE);
            });
    }

    /**
     * Define the infolist schema
     *
     * @param Schema $schema
     * @return Schema
     */
    public static function infolist(Schema $schema): Schema
    {
        return QuotationResource::infolist($schema);
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
            ViewOrder::class,
            EditOrder::class,
            ManageInvoices::class,
            ManageDeliveries::class,
        ]);
    }

    /**
     * Get the relation managers for this resource
     *
     * @return array<class-string>
     */
    public static function getRelations(): array
    {
        return [
            LineItemsRelationManager::class,
        ];
    }

    /**
     * Get the pages for this resource
     *
     * @return array<string, \Filament\Resources\Pages\PageRegistration>
     */
    public static function getPages(): array
    {
        return [
            'index'      => ListOrders::route('/'),
            'create'     => CreateOrder::route('/create'),
            'view'       => ViewOrder::route('/{record}'),
            'edit'       => EditOrder::route('/{record}/edit'),
            'invoices'   => ManageInvoices::route('/{record}/invoices'),
            'deliveries' => ManageDeliveries::route('/{record}/deliveries'),
        ];
    }
}
