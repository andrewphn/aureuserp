<?php

namespace Webkul\Invoice\Filament\Clusters\Customer\Resources\PaymentsResource\Pages;

use Webkul\Invoice\Filament\Clusters\Customer\Resources\PaymentsResource;
use Webkul\Account\Filament\Resources\PaymentsResource\Pages\ListPayments as BaseListPayments;
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
            'customer_payments' => PresetView::make(__('Customer Payments'))
                ->favorite()
                ->default()
                ->icon('heroicon-s-banknotes')
                ->modifyQueryUsing(fn(Builder $query) => $query->where('partner_type', 'customer')),
        ];
    }
}
