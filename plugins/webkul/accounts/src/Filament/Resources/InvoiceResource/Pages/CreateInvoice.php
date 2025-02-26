<?php

namespace Webkul\Account\Filament\Resources\InvoiceResource\Pages;

use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;
use Webkul\Account\Enums;
use Webkul\Account\Filament\Resources\InvoiceResource;
use Webkul\Account\Models\Move;
use Webkul\Account\Models\MoveLine;
use Webkul\Account\Models\Partner;

class CreateInvoice extends CreateRecord
{
    protected static string $resource = InvoiceResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->getRecord()]);
    }

    protected function getCreatedNotification(): ?Notification
    {
        return Notification::make()
            ->success()
            ->title(__('accounts::filament/resources/invoice/pages/create-invoice.notification.title'))
            ->body(__('accounts::filament/resources/invoice/pages/create-invoice.notification.body'));
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $user = Auth::user();

        $data['creator_id'] = $user->id;
        $data['state'] ??= Enums\MoveState::DRAFT->value;
        $data['move_type'] ??= Enums\MoveType::OUT_INVOICE->value;
        $data['date'] = now();
        $data['sort'] = Move::max('sort') + 1;

        if ($data['partner_id']) {
            $partner = Partner::find($data['partner_id']);

            $data['invoice_partner_display_name'] = $partner->name;
        } else {
            $data['invoice_partner_display_name'] = "#Created By: {$user->name}";
        }

        return $data;
    }

    protected function afterCreate(): void
    {
        $record = $this->getRecord();

        $this->getResource()::collectTotals($record);

        $this->createPaymentTerm($record);
    }

    protected function createPaymentTerm($record): void
    {
        if ($record->invoicePaymentTerm && $record->invoicePaymentTerm?->dueTerm?->nb_days) {
            $dateMaturity = $record->invoice_date_due->addDays($record->invoicePaymentTerm->dueTerm->nb_days);
        } else {
            $dateMaturity = $record->invoice_date_due;
        }

        MoveLine::create([
            'move_id'               => $record->id,
            'move_name'             => $record->name,
            'display_type'          => 'payment_term',
            'currency_id'           => $record->currency_id,
            'partner_id'            => $record->partner_id,
            'date_maturity'         => $dateMaturity,
            'company_id'            => $record->company_id,
            'company_currency_id'   => $record->company_currency_id,
            'commercial_partner_id' => $record->partner_id,
            'sort'                  => MoveLine::max('sort') + 1,
            'parent_state'          => $record->state,
            'date'                  => now(),
            'creator_id'            => $record->creator_id,
            'debit'                 => $record->amount_total,
            'balance'               => $record->amount_total,
            'amount_currency'       => $record->amount_total,
        ]);
    }
}
