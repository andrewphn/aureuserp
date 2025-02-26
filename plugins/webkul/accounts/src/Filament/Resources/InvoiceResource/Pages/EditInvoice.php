<?php

namespace Webkul\Account\Filament\Resources\InvoiceResource\Pages;

use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Auth;
use Webkul\Account\Filament\Resources\InvoiceResource;
use Webkul\Account\Filament\Resources\InvoiceResource\Actions as BaseActions;
use Webkul\Account\Models\Journal;
use Webkul\Account\Models\Move;
use Webkul\Account\Models\MoveLine;
use Webkul\Account\Models\PaymentTerm;
use Webkul\Partner\Models\Partner;
use Webkul\Support\Models\Currency;

class EditInvoice extends EditRecord
{
    protected static string $resource = InvoiceResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->getRecord()]);
    }

    protected function getSavedNotification(): ?Notification
    {
        return Notification::make()
            ->success()
            ->title(__('accounts::filament/resources/invoice/pages/edit-invoice.notification.title'))
            ->body(__('accounts::filament/resources/invoice/pages/edit-invoice.notification.body'));
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
            BaseActions\PayAction::make(),
            BaseActions\ConfirmAction::make(),
            BaseActions\CancelAction::make(),
            BaseActions\ResetToDraftAction::make(),
            BaseActions\SetAsCheckedAction::make(),
            BaseActions\PreviewAction::make(),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $user = Auth::user();

        $record = $this->getRecord();

        $data['partner_id'] = $data['partner_id'] ??= $record->partner_id;

        if ($data['partner_id']) {
            $partner = Partner::find($data['partner_id']);

            $data['invoice_partner_display_name'] = $partner->name;
        } else {
            $data['invoice_partner_display_name'] = "#Created By: {$user->name}";
        }

        return $data;
    }

    protected function afterSave(): void
    {
        $record = $this->getRecord();

        $this->getResource()::collectTotals($record);

        $this->updatePaymentTerm($record);
    }

    protected function updatePaymentTerm($record): void
    {
        $paymentTermLine = MoveLine::where('move_id', $record->id)
            ->where('display_type', 'payment_term')
            ->first();

        if ($record->invoicePaymentTerm && $record->invoicePaymentTerm?->dueTerm?->nb_days) {
            $dateMaturity = $record->invoice_date_due->addDays($record->invoicePaymentTerm->dueTerm->nb_days);
        } else {
            $dateMaturity = $record->invoice_date_due;
        }

        if ($paymentTermLine) {
            $paymentTermLine->update([
                'currency_id'           => $record->currency_id,
                'partner_id'            => $record->partner_id,
                'date_maturity'         => $dateMaturity,
                'company_id'            => $record->company_id,
                'company_currency_id'   => $record->company_currency_id,
                'commercial_partner_id' => $record->partner_id,
                'parent_state'          => $record->state,
                'debit'                 => $record->amount_total,
                'balance'               => $record->amount_total,
                'amount_currency'       => $record->amount_total,
            ]);
        } else {
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
}
