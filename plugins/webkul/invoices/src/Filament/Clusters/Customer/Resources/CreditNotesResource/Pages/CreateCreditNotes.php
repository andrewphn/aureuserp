<?php

namespace Webkul\Invoice\Filament\Clusters\Customer\Resources\CreditNotesResource\Pages;

use Webkul\Invoice\Filament\Clusters\Customer\Resources\CreditNotesResource;
use Webkul\Account\Filament\Clusters\Customer\Resources\InvoiceResource\Pages\CreateInvoice as BaseCreateInvoice;

class CreateCreditNotes extends BaseCreateInvoice
{
    protected static string $resource = CreditNotesResource::class;
}
