<?php

namespace Webkul\Invoice\Filament\Clusters\Customer\Resources;

use Webkul\Invoice\Filament\Clusters\Customer;
use Webkul\Account\Filament\Clusters\Customer\Resources\InvoiceResource as BaseInvoiceResource;
use Webkul\Invoice\Filament\Clusters\Customer\Resources\InvoiceResource\Pages;

class InvoiceResource extends BaseInvoiceResource
{
    protected static bool $shouldRegisterNavigation = true;

    protected static ?string $cluster = Customer::class;

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListInvoices::route('/'),
            'create' => Pages\CreateInvoice::route('/create'),
            'view'   => Pages\ViewInvoice::route('/{record}'),
            'edit'   => Pages\EditInvoice::route('/{record}/edit'),
        ];
    }
}
