<?php

namespace Webkul\Sale\Filament\Clusters\Orders\Resources;

use Filament\Tables\Table;
use Webkul\Partner\Filament\Resources\PartnerResource;
use Webkul\Sale\Filament\Clusters\Orders;
use Webkul\Sale\Filament\Clusters\Orders\Resources\CustomerResource\Pages;

class CustomerResource extends PartnerResource
{
    protected static ?string $navigationIcon = 'heroicon-o-users';

    protected static bool $shouldRegisterNavigation = true;

    protected static ?string $cluster = Orders::class;

    protected static ?int $navigationSort = 3;

    public static function getModelLabel(): string
    {
        return __('sales::filament/clusters/orders/resources/customer.title');
    }

    public static function getNavigationLabel(): string
    {
        return __('sales::filament/clusters/orders/resources/customer.navigation.title');
    }

    public static function table(Table $table): Table
    {
        return PartnerResource::table($table)
            ->contentGrid([
                'sm'  => 1,
                'md'  => 2,
                'xl'  => 3,
                '2xl' => 3,
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListCustomers::route('/'),
            'create' => Pages\CreateCustomer::route('/create'),
            'edit'   => Pages\EditCustomer::route('/{record}/edit'),
            'view'   => Pages\ViewCustomer::route('/{record}'),
        ];
    }
}
