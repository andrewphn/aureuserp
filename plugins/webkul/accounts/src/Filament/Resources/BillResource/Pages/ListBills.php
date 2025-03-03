<?php

namespace Webkul\Account\Filament\Resources\BillResource\Pages;

use Webkul\Account\Filament\Resources\BillResource;
use Filament\Actions;
use Webkul\Account\Enums\MoveType;
use Webkul\Account\Filament\Resources\InvoiceResource\Pages\ListInvoices as BaseListBills;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Arr;
use Webkul\TableViews\Filament\Components\PresetView;
use Webkul\TableViews\Filament\Concerns\HasTableViews;

class ListBills extends BaseListBills
{
    use HasTableViews;

    protected static string $resource = BillResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->icon('heroicon-o-plus-circle'),
        ];
    }

    public function getPresetTableViews(): array
    {
        return [
            'bill' => PresetView::make(__('Bills'))
                ->favorite()
                ->default()
                ->icon('heroicon-s-receipt-percent')
                ->modifyQueryUsing(fn(Builder $query) => $query->where('move_type', MoveType::IN_INVOICE->value)),
            ...Arr::except(parent::getPresetTableViews(), 'invoice'),
        ];
    }
}
