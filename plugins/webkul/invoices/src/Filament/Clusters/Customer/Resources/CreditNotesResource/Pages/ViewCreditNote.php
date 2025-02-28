<?php

namespace Webkul\Invoice\Filament\Clusters\Customer\Resources\CreditNotesResource\Pages;

use Webkul\Account\Filament\Resources\InvoiceResource\Pages\ViewInvoice as BaseViewInvoice;
use Webkul\Invoice\Filament\Clusters\Customer\Resources\CreditNotesResource;

class ViewCreditNote extends BaseViewInvoice
{
    protected static string $resource = CreditNotesResource::class;

    protected function getHeaderActions(): array
    {
        $predefinedActions = parent::getHeaderActions();

        $predefinedActions = collect($predefinedActions)->filter(function ($action) {
            return !in_array($action->getName(), [
                'customers.invoice.set-as-checked',
                'customers.invoice.credit-note'
            ]);
        })->toArray();

        return $predefinedActions;
    }
}
