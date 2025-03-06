<?php

namespace Webkul\Sale\Filament\Clusters\Orders\Resources\QuotationResource\Actions;

use Filament\Actions\Action;
use Filament\Support\Enums\MaxWidth;
use Webkul\Sale\Enums\InvoiceStatus;

class CreateInvoiceAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'orders.sales.create-invoice';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this
            ->modalIcon('heroicon-s-receipt-percent')
            ->modalHeading(__('sales::traits/sale-order-action.header-actions.create-invoice.modal.heading'))
            ->hidden(fn ($record) => $record->invoice_status != InvoiceStatus::TO_INVOICE->value)
            ->action(function () {})
            ->modalWidth(MaxWidth::SevenExtraLarge);
    }
}
