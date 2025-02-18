<?php

namespace Webkul\Account\Filament\Clusters\Customer\Resources\InvoiceResource\Pages;

use Illuminate\Database\Eloquent\Builder;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Webkul\TableViews\Filament\Concerns\HasTableViews;
use Webkul\TableViews\Filament\Components\PresetView;
use Webkul\Account\Filament\Clusters\Customer\Resources\InvoiceResource;
use Webkul\Account\Enums\MoveState;
use Webkul\Account\Enums\MoveType;
use Webkul\Account\Enums\PaymentState;

class ListInvoices extends ListRecords
{
    use HasTableViews;

    protected static string $resource = InvoiceResource::class;

    public function getPresetTableViews(): array
    {
        return [
            'draft' => PresetView::make(__('Draft'))
                ->favorite()
                ->icon('heroicon-s-stop')
                ->modifyQueryUsing(fn(Builder $query) => $query->where('state', MoveState::DRAFT->value)),
            'posted' => PresetView::make(__('Posted'))
                ->favorite()
                ->icon('heroicon-s-play')
                ->modifyQueryUsing(fn(Builder $query) => $query->where('state', MoveState::POSTED->value)),
            'cancelled' => PresetView::make(__('Cancelled'))
                ->favorite()
                ->icon('heroicon-s-x-circle')
                ->modifyQueryUsing(fn(Builder $query) => $query->where('state', MoveState::CANCEL->value)),
            'not_secured' => PresetView::make(__('Not Secured'))
                ->favorite()
                ->icon('heroicon-s-shield-exclamation')
                ->modifyQueryUsing(fn(Builder $query) => $query->whereNotNull('inalterable_hash')),
            'in_refund' => PresetView::make(__('Refund'))
                ->icon('heroicon-s-receipt-refund')
                ->modifyQueryUsing(fn(Builder $query) => $query->where('move_type', MoveType::IN_REFUND->value)),
            'to_check' => PresetView::make(__('To Check'))
                ->icon('heroicon-s-check-badge')
                ->modifyQueryUsing(function (Builder $query) {
                    $query->whereNot('state', MoveState::DRAFT->value)
                        ->where('checked', false);
                }),
            'to_pay' => PresetView::make(__('To Pay'))
                ->icon('heroicon-s-banknotes')
                ->modifyQueryUsing(function (Builder $query) {
                    $query->whereNot('state', MoveState::POSTED->value)
                        ->whereIn('payment_state', [
                            PaymentState::NOT_PAID->value,
                            PaymentState::PARTIAL->value,
                        ]);
                }),
            'in_payment' => PresetView::make(__('In Payment'))
                ->icon('heroicon-s-banknotes')
                ->modifyQueryUsing(function (Builder $query) {
                    $query->whereNot('state', MoveState::POSTED->value)
                        ->where('payment_state', PaymentState::IN_PAYMENT->value);
                }),
            'overdue' => PresetView::make(__('Overdue'))
                ->icon('heroicon-s-banknotes')
                ->modifyQueryUsing(function (Builder $query) {
                    $query->whereNot('state', MoveState::POSTED->value)
                        ->where('payment_state', PaymentState::NOT_PAID->value)
                        ->where('invoice_date_due', '<', now());
                }),
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->icon('heroicon-o-plus-circle'),
        ];
    }
}
