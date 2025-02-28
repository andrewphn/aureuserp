<?php

namespace Webkul\Account\Filament\Resources\CreditNoteResource\Pages;

use Webkul\Account\Filament\Resources\InvoiceResource\Pages\ListInvoices as ListRecords;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Arr;
use Webkul\Account\Filament\Resources\CreditNoteResource;
use Webkul\TableViews\Filament\Components\PresetView;
use Webkul\TableViews\Filament\Concerns\HasTableViews;
use Webkul\Account\Enums\MoveType;

class ListCreditNotes extends ListRecords
{
    use HasTableViews;

    protected static string $resource = CreditNoteResource::class;

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
