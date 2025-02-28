<?php

namespace Webkul\Account\Filament\Resources\InvoiceResource\Actions;

use Filament\Actions\Action;
use Filament\Forms;
use Filament\Forms\Form;
use Illuminate\Support\Facades\Auth;
use Webkul\Account\Enums\MoveState;
use Webkul\Account\Enums\PaymentState;
use Webkul\Account\Models\Move;
use Webkul\Account\Models\Payment;
use Webkul\Account\Models\PaymentRegister;

class PayAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'customers.invoice.pay';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this
            ->label(__('accounts::filament/resources/invoice/actions/pay-action.title'))
            ->color('success')
            ->form(function (Form $form) {
                return $form->schema([
                    Forms\Components\Group::make()
                        ->schema([
                            Forms\Components\TextInput::make('amount')
                                ->label(__('accounts::filament/resources/invoice/actions/pay-action.form.fields.amount'))
                                ->prefix(fn($record) => $record->currency->symbol ?? '')
                                ->formatStateUsing(fn($record) => number_format($record->lines->sum('price_total'), 2, '.', ''))
                                ->dehydrateStateUsing(fn($state) => (float) str_replace(',', '', $state))
                                ->required(),
                            Forms\Components\Select::make('payment_method_line_id')
                                ->relationship(
                                    name: 'paymentMethodLine',
                                    titleAttribute: 'name',
                                    modifyQueryUsing: function ($query) {
                                        return $query
                                            ->whereHas('paymentMethod', fn($q) => $q->where('payment_type', 'inbound'))
                                            ->whereHas('journal', fn($q) => $q->where('type', 'bank'));
                                    }
                                )
                                ->label(__('accounts::filament/resources/invoice/actions/pay-action.form.fields.payment-method-line'))
                                ->searchable()
                                ->preload(),
                            Forms\Components\DatePicker::make('payment_date')
                                ->native(false)
                                ->label(__('accounts::filament/resources/invoice/actions/pay-action.form.fields.payment-date'))
                                ->default(now())
                                ->required(),
                            Forms\Components\Select::make('partner_bank_id')
                                ->relationship(
                                    'partnerBank',
                                    'account_number',
                                )
                                ->label(__('accounts::filament/resources/invoice/actions/pay-action.form.fields.partner-bank-account'))
                                ->default(function ($record) {
                                    return $record->partner->bankAccounts->first()->id;
                                })
                                ->searchable()
                                ->required(),
                            Forms\Components\TextInput::make('communication')
                                ->label(__('accounts::filament/resources/invoice/actions/pay-action.form.fields.communication'))
                                ->default(function ($record) {
                                    return $record->name;
                                })
                                ->required(),
                        ])->columns(2),
                ]);
            })
            ->action(function (Move $record, $data): void {
                $this->registerPayment($record, $data);

                $this->createPayment($record, $data);

                $record->update(['payment_state' => PaymentState::PAID->value]);
            })
            ->hidden(function (Move $record) {
                return
                    $record->state != MoveState::POSTED->value
                    || ! in_array($record->payment_state, [PaymentState::NOT_PAID->value, PaymentState::PARTIAL->value, PaymentState::IN_PAYMENT->value]);
            });
    }

    private function registerPayment(Move $record, array $data): void
    {
        $paymentMethodLine = $record->paymentMethodLine()->findOrFail($data['payment_method_line_id']);

        $paymentRegister = PaymentRegister::Create([
            'move_id'                => $record->id,
            'amount'                 => $data['amount'],
            'payment_method_line_id' => $data['payment_method_line_id'],
            'payment_date'           => $data['payment_date'],
            'partner_bank_id'        => $data['partner_bank_id'],
            'communication'          => $data['communication'],
            'creator_id'             => Auth::id(),
            'source_currency_id'     => $record->currency_id,
            'company_id'             => $record->company_id,
            'partner_id'             => $record->partner_id,
            'payment_type'           => $paymentMethodLine?->paymentMethod?->payment_type,
            'payment_date'           => now(),
            'source_amount'          => $data['amount'],
            'source_amount_currency' => $data['amount'],
        ]);

        $paymentRegister->registerMoveLines()->sync($record->paymentTermLine->id);
    }

    private function createPayment(Move $record, array $data): void
    {
        $paymentMethodLine = $record->paymentMethodLine()->findOrFail($data['payment_method_line_id']);

        $payment = Payment::create([
            'move_id'                => $record->id,
            'amount'                 => $data['amount'],
            'payment_method_line_id' => $data['payment_method_line_id'],
            'partner_bank_id'        => $data['partner_bank_id'],
            'communication'          => $data['communication'],
            'creator_id'             => Auth::id(),
            'source_currency_id'     => $record->currency_id,
            'company_id'             => $record->company_id,
            'partner_id'             => $record->partner_id,
            'payment_type'           => $record->paymentMethodLine()->findOrFail($data['payment_method_line_id'])->paymentMethod->payment_type,
            'source_amount'          => $data['amount'],
            'source_amount_currency' => $data['amount'],
            'name'                   => str_replace("P" . $paymentMethodLine?->journal?->code, "INV", $record->name),
            'state'                  => PaymentState::PAID->value,
            'payment_type'           => $paymentMethodLine?->paymentMethod?->payment_type,
            'partner_type'           => $record->partner->sub_type,
            'memo'                   => $data['communication'],
            'amount_company_currency_signed' => $data['amount'],
            'date'                   => now(),
        ]);

        $payment->accountMovePayment()->sync($record->id);
    }
}
