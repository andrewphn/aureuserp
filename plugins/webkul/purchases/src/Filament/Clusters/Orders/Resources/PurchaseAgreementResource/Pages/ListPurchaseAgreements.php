<?php

namespace Webkul\Purchase\Filament\Clusters\Orders\Resources\PurchaseAgreementResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Contracts\Support\Htmlable;
use Webkul\Purchase\Filament\Clusters\Orders\Resources\PurchaseAgreementResource;
use Webkul\TableViews\Filament\Concerns\HasTableViews;

class ListPurchaseAgreements extends ListRecords
{
    use HasTableViews;

    protected static string $resource = PurchaseAgreementResource::class;

    public function getTitle(): string|Htmlable
    {
        return __('purchases::filament/clusters/orders/resources/purchase-agreement/pages/list-purchase-agreements.navigation.title');
    }

    public function getPresetTableViews(): array
    {
        return [];
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label(__('purchases::filament/clusters/orders/resources/purchase-agreement/pages/list-purchase-agreements.header-actions.create.label'))
                ->icon('heroicon-o-plus-circle'),
        ];
    }
}
