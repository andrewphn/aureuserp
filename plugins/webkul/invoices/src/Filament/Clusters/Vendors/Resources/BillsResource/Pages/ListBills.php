<?php

namespace Webkul\Invoice\Filament\Clusters\Vendors\Resources\BillsResource\Pages;

use Webkul\Invoice\Filament\Clusters\Vendors\Resources\BillsResource;
use Webkul\Account\Filament\Clusters\Customer\Resources\InvoiceResource\Pages\ListInvoices as BaseListInvoices;
use Webkul\TableViews\Filament\Components\PresetView;
use Illuminate\Database\Eloquent\Builder;
use Webkul\Account\Enums\MoveType;

class ListBills extends BaseListInvoices
{
    protected static string $resource = BillsResource::class;

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
