<?php

namespace Webkul\Invoice\Filament\Clusters\Customer\Resources\CreditNotesResource\Pages;

use Webkul\Invoice\Filament\Clusters\Customer\Resources\CreditNotesResource;
use Webkul\Account\Filament\Clusters\Customer\Resources\InvoiceResource\Pages\EditInvoice as BaseEditInvoice;

class EditCreditNotes extends BaseEditInvoice
{
    protected static string $resource = CreditNotesResource::class;
}
