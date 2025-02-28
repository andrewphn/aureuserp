<?php

namespace Webkul\Invoice\Filament\Clusters\Customer\Resources\CreditNotesResource\Pages;

use Webkul\Account\Filament\Resources\InvoiceResource\Pages\ListInvoices as BaseListInvoices;
use Webkul\Invoice\Filament\Clusters\Customer\Resources\CreditNotesResource;
use Webkul\TableViews\Filament\Components\PresetView;
use Webkul\TableViews\Filament\Concerns\HasTableViews;
use Webkul\Account\Enums\MoveType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Arr;

class ListCreditNotes extends BaseListInvoices
{
    use HasTableViews;

    protected static string $resource = CreditNotesResource::class;

    public function getPresetTableViews(): array
    {
        $predefinedViews = parent::getPresetTableViews();

        return [
            'out_refund' => PresetView::make(__('Credit Notes'))
                ->favorite()
                ->default()
                ->icon('heroicon-s-receipt-percent')
                ->modifyQueryUsing(fn(Builder $query) => $query->where('move_type', MoveType::OUT_REFUND->value)),
            ...Arr::except($predefinedViews, ['invoice']),
        ];
    }
}
