<?php

namespace Webkul\Account\Filament\Resources\BankAccountResource\Pages;

use Webkul\Account\Filament\Resources\BankAccountResource;
use Webkul\Partner\Filament\Resources\BankAccountResource\Pages\ManageBankAccounts as BaseManageBankAccounts;
use Webkul\TableViews\Filament\Components\PresetView;
use Webkul\TableViews\Filament\Concerns\HasTableViews;

/**
 * List Bank Accounts class
 *
 * @see \Filament\Resources\Resource
 */
class ListBankAccounts extends BaseManageBankAccounts
{
    use HasTableViews;

    protected static string $resource = BankAccountResource::class;

    public function getPresetTableViews(): array
    {
        return [
            'all' => PresetView::make(__('accounts::filament/resources/bank-account/pages/list-bank-accounts.tabs.all'))
                ->icon('heroicon-s-queue-list')
                ->favorite()
                ->setAsDefault(),
            'archived' => PresetView::make(__('accounts::filament/resources/bank-account/pages/list-bank-accounts.tabs.archived'))
                ->icon('heroicon-s-archive-box')
                ->modifyQueryUsing(fn ($query) => $query->onlyTrashed()),
        ];
    }
}
