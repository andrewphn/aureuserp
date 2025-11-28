<?php

namespace Webkul\Account\Filament\Resources\PaymentTermResource\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Webkul\Account\Filament\Resources\PaymentTermResource;
use Webkul\Account\Models\PaymentTerm;
use Webkul\TableViews\Filament\Components\PresetView;
use Webkul\TableViews\Filament\Concerns\HasTableViews;

/**
 * List Payment Terms class
 *
 * @see \Filament\Resources\Resource
 */
class ListPaymentTerms extends ListRecords
{
    use HasTableViews;

    protected static string $resource = PaymentTermResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->icon('heroicon-o-plus-circle'),
        ];
    }

    public function getPresetTableViews(): array
    {
        return [
            'all' => PresetView::make(__('accounts::filament/resources/payment-term/pages/list-payment-term.tabs.all'))
                ->icon('heroicon-s-calendar-days')
                ->favorite()
                ->setAsDefault()
                ->badge(PaymentTerm::count()),
            'archived' => PresetView::make(__('accounts::filament/resources/payment-term/pages/list-payment-term.tabs.archived'))
                ->icon('heroicon-s-archive-box')
                ->favorite()
                ->badge(PaymentTerm::onlyTrashed()->count())
                ->modifyQueryUsing(fn ($query) => $query->onlyTrashed()),
        ];
    }
}
