<?php

namespace Webkul\Invoice\Filament\Clusters\Vendors\Resources;

use Filament\Tables\Table;
use Webkul\Invoice\Filament\Clusters\Vendors;
use Webkul\Invoice\Filament\Clusters\Vendors\Resources\PaymentsResource\Pages;
use Webkul\Account\Filament\Clusters\Customer\Resources\PaymentsResource as BasePaymentsResource;

class PaymentsResource extends BasePaymentsResource
{
    protected static bool $shouldRegisterNavigation = true;

    protected static ?string $cluster = Vendors::class;

    public static function getNavigationGroup(): ?string
    {
        return null;
    }

    public static function table(Table $table): Table
    {
        return BasePaymentsResource::table($table);
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListPayments::route('/'),
            'create' => Pages\CreatePayments::route('/create'),
            'view'   => Pages\ViewPayments::route('/{record}'),
            'edit'   => Pages\EditPayments::route('/{record}/edit'),
        ];
    }
}
