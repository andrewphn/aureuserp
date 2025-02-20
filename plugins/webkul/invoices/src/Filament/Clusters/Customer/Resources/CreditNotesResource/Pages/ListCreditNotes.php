<?php

namespace Webkul\Invoice\Filament\Clusters\Customer\Resources\CreditNotesResource\Pages;

use Webkul\Invoice\Filament\Clusters\Customer\Resources\CreditNotesResource;
use Webkul\Account\Filament\Clusters\Customer\Resources\InvoiceResource\Pages\ListInvoices as BaseListInvoices;

class ListCreditNotes extends BaseListInvoices
{
    protected static string $resource = CreditNotesResource::class;
}
