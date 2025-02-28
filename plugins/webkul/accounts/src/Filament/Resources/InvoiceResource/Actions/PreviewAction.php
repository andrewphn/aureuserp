<?php

namespace Webkul\Account\Filament\Resources\InvoiceResource\Actions;

use Filament\Actions\Action;
use Webkul\Account\Enums\MoveState;
use Webkul\Account\Models\Move;
use Webkul\Support\Traits\PDFHandler;

class PreviewAction extends Action
{
    use PDFHandler;

    public static function getDefaultName(): ?string
    {
        return 'customers.invoice.preview';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this
            ->label(__('Preview'))
            ->color('gray')
            ->visible(fn(Move $record) => $record->state == MoveState::POSTED->value)
            ->icon('heroicon-o-viewfinder-circle')
            ->modalHeading(__('Preview Invoice'))
            ->modalSubmitAction(false)
            ->modalContent(function ($record) {
                return view('accounts::invoice/actions/preview.index', ['record' => $record]);
            });
    }
}
