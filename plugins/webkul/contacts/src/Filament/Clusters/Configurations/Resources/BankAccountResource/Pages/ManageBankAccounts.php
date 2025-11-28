<?php

namespace Webkul\Contact\Filament\Clusters\Configurations\Resources\BankAccountResource\Pages;

use Webkul\Contact\Filament\Clusters\Configurations\Resources\BankAccountResource;
use Webkul\Partner\Filament\Resources\BankAccountResource\Pages\ManageBankAccounts as BaseManageBankAccounts;

/**
 * Manage Bank Accounts class
 *
 * @see \Filament\Resources\Resource
 */
class ManageBankAccounts extends BaseManageBankAccounts
{
    protected static string $resource = BankAccountResource::class;
}
