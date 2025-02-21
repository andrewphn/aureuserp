<?php

namespace Webkul\Invoice\Filament\Clusters\Configuration\Resources;

use Webkul\Account\Filament\Clusters\Configuration\Resources\PaymentTermResource as BasePaymentTermResource;
use Webkul\Invoice\Filament\Clusters\Configuration;
use Webkul\Invoice\Filament\Clusters\Configuration\Resources\PaymentTermResource\Pages;

class PaymentTermResource extends BasePaymentTermResource
{
    protected static bool $shouldRegisterNavigation = true;

    protected static ?string $cluster = Configuration::class;

    public static function getPages(): array
    {
        return [
            'index'             => Pages\ListPaymentTerms::route('/'),
            'create'            => Pages\CreatePaymentTerm::route('/create'),
            'view'              => Pages\ViewPaymentTerm::route('/{record}'),
            'edit'              => Pages\EditPaymentTerm::route('/{record}/edit'),
            'payment-due-terms' => Pages\ManagePaymentDueTerm::route('/{record}/payment-due-terms'),
        ];
    }
}
