<?php

namespace Webkul\Purchase\Filament\Customer\Clusters\Account\Resources\QuotationResource\Pages;

use Filament\Resources\Pages\ListRecords;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Webkul\Purchase\Enums\OrderState;
use Webkul\Purchase\Filament\Customer\Clusters\Account\Resources\QuotationResource;
use Webkul\Purchase\Models\Order;
use Webkul\TableViews\Filament\Components\PresetView;
use Webkul\TableViews\Filament\Concerns\HasTableViews;

class ListQuotations extends ListRecords
{
    use HasTableViews;

    protected static string $resource = QuotationResource::class;

    public function table(Table $table): Table
    {
        return QuotationResource::table($table)
            ->modifyQueryUsing(fn (Builder $query) => $query->where('state', OrderState::SENT));
    }

    public function getPresetTableViews(): array
    {
        return [
            'all' => PresetView::make(__('purchases::filament/customer/clusters/account/resources/quotation/pages/list-quotations.tabs.all'))
                ->icon('heroicon-s-document-text')
                ->favorite()
                ->setAsDefault()
                ->badge(Order::where('state', OrderState::SENT)->count()),
        ];
    }
}
