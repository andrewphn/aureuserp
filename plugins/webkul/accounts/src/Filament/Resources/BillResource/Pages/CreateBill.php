<?php

namespace Webkul\Account\Filament\Resources\BillResource\Pages;

use Webkul\Account\Filament\Resources\BillResource;
use Filament\Resources\Pages\CreateRecord;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Auth;
use Webkul\Account\Enums;
use Webkul\Account\Enums\DisplayType;
use Webkul\Account\Enums\PaymentState;
use Webkul\Account\Models\Move;
use Webkul\Account\Models\MoveLine;
use Webkul\Account\Models\Partner;
use Webkul\Account\Services\MoveLineCalculationService;

class CreateBill extends CreateRecord
{
    protected static string $resource = BillResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->getRecord()]);
    }

    protected function getCreatedNotification(): ?Notification
    {
        return Notification::make()
            ->success()
            ->title(__('Bill Created'))
            ->body(__('Bill created has been successfully.'));
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $user = Auth::user();

        $data['creator_id'] = $user->id;
        $data['state'] ??= Enums\MoveState::DRAFT->value;
        $data['move_type'] ??= Enums\MoveType::IN_INVOICE->value;
        $data['date'] = now();
        $data['sort'] = Move::max('sort') + 1;
        $data['payment_state'] = PaymentState::NOT_PAID->value;

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

    protected function afterCreate(): void
    {
        $record = $this->getRecord();

        $this->getResource()::collectTotals($record);

        $this->createPaymentTermLine($record);

        $this->createTaxLine($record);
    }

    private function createPaymentTermLine($record): void
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

    private function createTaxLine($record): void
    {
        $calculationService = app(MoveLineCalculationService::class);
        $lines = $record->lines->where('display_type', DisplayType::PRODUCT->value);
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
                        'sort'                  => MoveLine::max('sort') + 1,
                        'parent_state'          => $record->state,
                        'date'                  => now(),
                        'creator_id'            => $record->creator_id,
                        'debit'                 => 0.0,
                        'credit'                => $currentTaxAmount,
                        'balance'               => -$currentTaxAmount,
                        'amount_currency'       => -$currentTaxAmount,
                        'tax_base_amount'       => $currentTaxBase,
                        'tax_line_id'           => $tax->id,
                        'tax_group_id'          => $tax->tax_group_id,
                    ];
                }
            }
        }

        foreach ($newTaxEntries as $taxData) {
            MoveLine::create($taxData);
        }
    }
}
