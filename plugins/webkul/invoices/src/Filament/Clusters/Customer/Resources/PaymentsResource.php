<?php

namespace Webkul\Invoice\Filament\Clusters\Customer\Resources;

use Webkul\Invoice\Filament\Clusters\Customer;
use Webkul\Invoice\Filament\Clusters\Customer\Resources\PaymentsResource\Pages;
use Webkul\Account\Filament\Resources\PaymentsResource as BasePaymentsResource;

class PaymentsResource extends BasePaymentsResource
{
    protected static bool $shouldRegisterNavigation = true;

    protected static ?int $navigationSort = 4;

    protected static ?string $cluster = Customer::class;

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPayments::route('/'),
            'create' => Pages\CreatePayments::route('/create'),
            'view' => Pages\ViewPayments::route('/{record}'),
            'edit' => Pages\EditPayments::route('/{record}/edit'),
        ];
    }
}
