<?php

namespace Webkul\Invoice\Filament\Clusters\Configuration\Resources\BankAccountResource\Pages;

use Webkul\Contact\Filament\Clusters\Configurations\Resources\BankAccountResource\Pages\ManageBankAccounts as BaseManageBankAccounts;
use Webkul\Invoice\Filament\Clusters\Configuration\Resources\BankAccountResource;

class ListBankAccounts extends BaseManageBankAccounts
{
    protected static string $resource = BankAccountResource::class;
}
