<?php

namespace Webkul\Invoice\Filament\Clusters\Configuration\Resources\BankAccountResource\Pages;

use Webkul\Account\Filament\Resources\BankAccountResource\Pages\ListBankAccounts as BaseManageBankAccounts;
use Webkul\Invoice\Filament\Clusters\Configuration\Resources\BankAccountResource;

/**
 * List Bank Accounts class
 *
 * @see \Filament\Resources\Resource
 */
class ListBankAccounts extends BaseManageBankAccounts
{
    protected static string $resource = BankAccountResource::class;
}
