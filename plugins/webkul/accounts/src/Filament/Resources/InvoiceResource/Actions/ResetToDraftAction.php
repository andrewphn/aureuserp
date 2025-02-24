<?php

namespace Webkul\Account\Filament\Resources\InvoiceResource\Actions;

use Filament\Actions\Action;
use Livewire\Component;
use Webkul\Account\Enums\MoveState;
use Webkul\Account\Models\Move;

class ResetToDraftAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'customers.invoice.reset-to-draft';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this
            ->label(__('accounts::filament/resources/invoice/actions/reset-to-draft-action.title'))
            ->color('gray')
            ->action(function (Move $record, Component $livewire): void {
                $record->state = MoveState::DRAFT->value;
                $record->save();

                $livewire->refreshFormData(['state']);
            })
            ->visible(function (Move $record) {
                return
                    $record->state == MoveState::CANCEL->value
                    || $record->state == MoveState::POSTED->value;
            });
    }
}
