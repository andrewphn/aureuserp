<?php

namespace Webkul\Account\Filament\Resources\RefundResource\Pages;

use Filament\Resources\Pages\ListRecords;
use Webkul\Account\Filament\Resources\RefundResource;
use Webkul\TableViews\Filament\Components\PresetView;
use Webkul\TableViews\Filament\Concerns\HasTableViews;

/**
 * List Refunds class
 *
 * @see \Filament\Resources\Resource
 */
class ListRefunds extends ListRecords
{
    use HasTableViews;

    protected static string $resource = RefundResource::class;

    public function getPresetTableViews(): array
    {
        return [
            'all' => PresetView::make(__('accounts::filament/resources/refund/pages/list-refunds.tabs.all'))
                ->icon('heroicon-s-queue-list')
                ->favorite()
                ->setAsDefault(),
        ];
    }
}
