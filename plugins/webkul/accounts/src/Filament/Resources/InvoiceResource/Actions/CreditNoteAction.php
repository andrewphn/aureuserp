<?php

namespace Webkul\Account\Filament\Resources\InvoiceResource\Actions;

use Filament\Actions\Action;
use Webkul\Account\Enums\MoveState;
use Webkul\Account\Models\Move;
use Webkul\Support\Traits\PDFHandler;

class CreditNoteAction extends Action
{
    use PDFHandler;

    public static function getDefaultName(): ?string
    {
        return 'customers.invoice.credit-note';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this
            ->label(__('Credit Note'))
            ->color('gray')
            ->visible(fn(Move $record) => $record->state == MoveState::POSTED->value)
            ->icon('heroicon-o-receipt-refund')
            ->modalHeading(__('Credit Note'))
            ->modalSubmitAction(false);
    }
}
