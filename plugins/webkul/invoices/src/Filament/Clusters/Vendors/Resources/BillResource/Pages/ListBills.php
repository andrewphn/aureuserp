<?php

namespace Webkul\Invoice\Filament\Clusters\Vendors\Resources\BillResource\Pages;

use Illuminate\Database\Eloquent\Builder;
use Webkul\Account\Enums\MoveType;
use Webkul\Account\Filament\Resources\BillResource\Pages\ListBills as BaseListBills;
use Webkul\Invoice\Filament\Clusters\Vendors\Resources\BillResource;
use Webkul\TableViews\Filament\Components\PresetView;

class ListBills extends BaseListBills
{
    protected static string $resource = BillResource::class;

    public function getPresetTableViews(): array
    {
        $presets = parent::getPresetTableViews();

        return array_merge(
            $presets,
            [
                'bills' => PresetView::make(__('invoices::filament/clusters/vendors/resources/bill/pages/list-bill.tabs.bills'))
                    ->icon('heroicon-s-receipt-percent')
                    ->default()
                    ->favorite()
                    ->modifyQueryUsing(fn(Builder $query) => $query->where('move_type', MoveType::IN_INVOICE->value)),
            ]
        );
    }
}
