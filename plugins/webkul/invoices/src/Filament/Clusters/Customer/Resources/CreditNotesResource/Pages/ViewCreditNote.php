<?php

namespace Webkul\Invoice\Filament\Clusters\Customer\Resources\CreditNotesResource\Pages;

use Webkul\Invoice\Filament\Clusters\Customer\Resources\CreditNotesResource;
use Webkul\Account\Filament\Clusters\Customer\Resources\InvoiceResource\Pages\ViewInvoice as BaseViewInvoice;

class ViewCreditNote extends BaseViewInvoice
{
    protected static string $resource = CreditNotesResource::class;
}
