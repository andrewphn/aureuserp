<?php

namespace Webkul\Account\Filament\Resources\BillResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Auth;
use Webkul\Account\Filament\Resources\BillResource;
use Webkul\Account\Enums\DisplayType;
use Webkul\Account\Filament\Resources\BillResource\Actions\CreditNoteAction;
use Webkul\Account\Filament\Resources\InvoiceResource\Actions as BaseActions;
use Webkul\Account\Models\MoveLine;
use Webkul\Chatter\Filament\Actions as ChatterActions;
use Webkul\Partner\Models\Partner;
use Webkul\Support\Services\MoveLineCalculationService;

class EditBill extends EditRecord
{
    protected static string $resource = BillResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->getRecord()]);
    }

    protected function getSavedNotification(): ?Notification
    {
        return Notification::make()
            ->success()
            ->title(__('Bill updated'))
            ->body(__('Bill updated has been updated successfully.'));
    }

    protected function getHeaderActions(): array
    {
        return [
            ChatterActions\ChatterAction::make()
                ->setResource($this->getResource()),
            Actions\ViewAction::make(),
            BaseActions\PayAction::make(),
            BaseActions\ConfirmAction::make(),
            BaseActions\CancelAction::make(),
            BaseActions\ResetToDraftAction::make(),
            BaseActions\SetAsCheckedAction::make(),
            BaseActions\PrintAndSendAction::make(),
            CreditNoteAction::make(),
            Actions\DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $user = Auth::user();

        $record = $this->getRecord();

        $data['partner_id'] ??= $record->partner_id;
        $data['invoice_date'] ??= $record->invoice_date;
        $data['name'] ??= $record->name;
        $data['auto_post'] ??= $record->auto_post;
        $data['invoice_currency_rate'] ??= 1.0;

        if ($data['partner_id']) {
            $partner = Partner::find($data['partner_id']);

            $data['commercial_partner_id'] = $partner->id;
            $data['partner_shipping_id'] = $partner->addresses->where('type', 'present')->first()?->id;
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

        $this->updateOrCreatePaymentTermLine($record);

        $this->updateOrCreateTaxLine($record);
    }

    private function updateOrCreatePaymentTermLine($record): void
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
                'currency_id'              => $record->currency_id,
                'partner_id'               => $record->partner_id,
                'date_maturity'            => $dateMaturity,
                'company_id'               => $record->company_id,
                'company_currency_id'      => $record->company_currency_id,
                'commercial_partner_id'    => $record->partner_id,
                'parent_state'             => $record->state,
                'debit'                    => 0.00,
                'credit'                   => $record->amount_total,
                'balance'                  => -$record->amount_total,
                'amount_currency'          => -$record->amount_total,
                'amount_residual'          => -$record->amount_total,
                'amount_residual_currency' => -$record->amount_total,
            ]);
        } else {
            MoveLine::create([
                'move_id'                  => $record->id,
                'move_name'                => $record->name,
                'display_type'             => 'payment_term',
                'currency_id'              => $record->currency_id,
                'partner_id'               => $record->partner_id,
                'date_maturity'            => $dateMaturity,
                'company_id'               => $record->company_id,
                'company_currency_id'      => $record->company_currency_id,
                'commercial_partner_id'    => $record->partner_id,
                'sort'                     => MoveLine::max('sort') + 1,
                'parent_state'             => $record->state,
                'date'                     => now(),
                'creator_id'               => $record->creator_id,
                'debit'                    => 0.00,
                'credit'                   => $record->amount_total,
                'balance'                  => -$record->amount_total,
                'amount_currency'          => -$record->amount_total,
                'amount_residual'          => -$record->amount_total,
                'amount_residual_currency' => -$record->amount_total,
            ]);
        }
    }

    private function updateOrCreateTaxLine($record): void
    {
        $calculationService = app(MoveLineCalculationService::class);
        $lines = $record->lines->where('display_type', DisplayType::PRODUCT->value);
        $existingTaxLines = MoveLine::where('move_id', $record->id)->where('display_type', 'tax')->get()->keyBy('tax_line_id');
        $newTaxEntries = [];

        foreach ($lines as $line) {
            if ($line->taxes->isEmpty()) {
                continue;
            }

            $lineData = [
                'product_id' => $line->product_id,
                'price_unit' => $line->price_unit,
                'quantity'   => $line->quantity,
                'taxes'      => $line->taxes->pluck('id')->toArray(),
                'discount'   => $line->discount ?? 0,
            ];

            $calculatedLine = $calculationService->calculateLineTotals($lineData);
            $taxes = $line->taxes()->orderBy('sort')->get();
            $baseAmount = $calculatedLine['price_subtotal'];

            $taxCalculationResult = $calculationService->calculateTaxes(
                $lineData['taxes'],
                $baseAmount,
                $lineData['quantity'],
                $lineData['price_unit']
            );

            $taxesComputed = $taxCalculationResult['taxesComputed'];

            foreach ($taxes as $tax) {
                $computedTax = collect($taxesComputed)->firstWhere('tax_id', $tax->id);

                if (! $computedTax) {
                    continue;
                }

                $currentTaxAmount = $computedTax['tax_amount'];

                $currentTaxBase = $baseAmount;
                if ($tax->is_base_affected) {
                    foreach ($taxesComputed as $prevTax) {
                        if ($prevTax['include_base_amount'] && $prevTax['tax_id'] !== $tax->id) {
                            $currentTaxBase += $prevTax['tax_amount'];
                        }
                    }
                }

                if (isset($newTaxEntries[$tax->id])) {
                    $newTaxEntries[$tax->id]['credit'] += $currentTaxAmount;
                    $newTaxEntries[$tax->id]['balance'] -= $currentTaxAmount;
                    $newTaxEntries[$tax->id]['amount_currency'] -= $currentTaxAmount;
                } else {
                    $newTaxEntries[$tax->id] = [
                        'name'                  => $tax->name,
                        'move_id'               => $record->id,
                        'move_name'             => $record->name,
                        'display_type'          => 'tax',
                        'currency_id'           => $record->currency_id,
                        'partner_id'            => $record->partner_id,
                        'company_id'            => $record->company_id,
                        'company_currency_id'   => $record->company_currency_id,
                        'commercial_partner_id' => $record->partner_id,
                        'parent_state'          => $record->state,
                        'date'                  => now(),
                        'creator_id'            => $record->creator_id,
                        'debit'                 => $currentTaxAmount,
                        'credit'                => 0,
                        'balance'               => $currentTaxAmount,
                        'amount_currency'       => $currentTaxAmount,
                        'tax_base_amount'       => $currentTaxBase,
                        'tax_line_id'           => $tax->id,
                        'tax_group_id'          => $tax->tax_group_id,
                    ];
                }
            }
        }

        foreach ($newTaxEntries as $taxId => $taxData) {
            if (isset($existingTaxLines[$taxId])) {
                $existingTaxLines[$taxId]->update($taxData);
                unset($existingTaxLines[$taxId]);
            } else {
                $taxData['sort'] = MoveLine::max('sort') + 1;
                MoveLine::create($taxData);
            }
        }

        foreach ($existingTaxLines as $oldTaxLine) {
            $oldTaxLine->delete();
        }
    }
}
