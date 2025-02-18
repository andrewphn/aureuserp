<?php

namespace Webkul\Invoice\Filament\Clusters\Vendors\Resources\PaymentsResource\Pages;

use Webkul\Invoice\Filament\Clusters\Vendors\Resources\PaymentsResource;
use Webkul\Account\Filament\Clusters\Customer\Resources\PaymentsResource\Pages\ListPayments as BaseListPayments;
use Webkul\TableViews\Filament\Components\PresetView;
use Illuminate\Database\Eloquent\Builder;

class ListPayments extends BaseListPayments
{
    protected static string $resource = PaymentsResource::class;

    public function getPresetTableViews(): array
    {
        $presets = parent::getPresetTableViews();

        return [
            ...$presets,
            'vendor_payments' => PresetView::make(__('Vendor Payments'))
                ->favorite()
                ->default()
                ->icon('heroicon-s-banknotes')
                ->modifyQueryUsing(fn(Builder $query) => $query->where('partner_type', 'supplier')),
        ];
    }
}
