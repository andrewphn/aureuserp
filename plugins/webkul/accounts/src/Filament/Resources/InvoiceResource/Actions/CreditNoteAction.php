<?php

namespace Webkul\Account\Filament\Resources\InvoiceResource\Actions;

use Filament\Actions\Action;
use Filament\Forms;
use Filament\Forms\Form;
use Illuminate\Support\Facades\Auth;
use Webkul\Account\Enums\AutoPost;
use Webkul\Account\Enums\MoveState;
use Webkul\Account\Enums\MoveType;
use Webkul\Account\Enums\PaymentState;
use Webkul\Account\Models\Move;
use Webkul\Account\Models\MoveReversal;
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
            ->modalHeading(__('Credit Note'));

        $this->form(
            function (Form $form) {
                return $form->schema([
                    Forms\Components\Textarea::make('reason')
                        ->label(__('Reason displayed on Credit Note'))
                        ->required(),
                    Forms\Components\DatePicker::make('date')
                        ->label(__('Reason displayed on Credit Note'))
                        ->default(now())
                        ->required(),
                ]);
            }
        );

        $this->action(function (Move $record, array $data) {
            $user = Auth::user();

            $creditNote = MoveReversal::create([
                'reason'     => $data['reason'],
                'date'       => $data['date'],
                'company_id' => $record->company_id,
                'creator_id' => $user->id,
            ]);

            $creditNote->moves()->attach($record);

            $this->createMove($creditNote, $record);

            return $creditNote;
        });
    }

    private function createMove(MoveReversal $creditNote, Move $record): void
    {
        $moveData = [
            'company_id' => $record->company_id,
            'creator_id' => $creditNote->creator_id,
            'partner_id' => $record->partner_id,
            'commercial_partner_id' => $record->commercial_partner_id,
            'partner_shipping_id' => $record->partner_shipping_id,
            'currency_id' => $record->currency_id,
            'reversed_entry_id' => $record->id,
            'invoice_user_id' => $record->invoice_user_id,
            'reference' => "Reversal of: {$record->name}, {$creditNote->reason}",
            'state' => MoveState::DRAFT->value,
            'move_type' => MoveType::OUT_REFUND->value,
            'auto_post' => AutoPost::NO->value,
            'payment_state' => PaymentState::NOT_PAID->value,
            'invoice_partner_display_name' => $record->invoice_partner_display_name,
            'date' => $creditNote->date,
            'invoice_date' => $record->invoice_date,
            'invoice_date_due' => $record->invoice_date_due,
            'amount_untaxed' => $record->amount_untaxed,
            'amount_tax' => $record->amount_tax,
            'amount_total' => $record->amount_total,
            'amount_residual' => $record->amount_total,
            'amount_untaxed_signed' => -$record->amount_untaxed_signed,
            'amount_untaxed_in_currency_signed' => -$record->amount_untaxed_in_currency_signed,
            'amount_tax_signed' => -$record->amount_tax_signed,
            'amount_total_signed' => -$record->amount_total_signed,
            'amount_total_in_currency_signed' => -$record->amount_total_in_currency_signed,
            'company_currency_id' => $record->company_currency_id,
            'amount_residual_signed' => -$record->amount_total_signed,
            'checked' => $record->checked,
        ];

        $newMove = Move::create($moveData);

        $creditNote->newMoves()->attach($newMove->id);

        $this->createMoveLines($newMove, $record);
    }

    private function createMoveLines(Move $newMove, Move $record): void
    {
        $record->allLines->each(function ($line) use ($newMove) {
            $newMoveLine = $line->replicate();

            $newMoveLine->move_id = $newMove->id;
            $newMoveLine->sort = $newMove->lines->max('sort') + 1;
            $newMoveLine->debit = -$line->debit;
            $newMoveLine->credit = -$line->credit;
            $newMoveLine->balance = -$line->balance;
            $newMoveLine->amount_currency = -$line->amount_currency;

            $newMoveLine->save();
        });
    }
}
