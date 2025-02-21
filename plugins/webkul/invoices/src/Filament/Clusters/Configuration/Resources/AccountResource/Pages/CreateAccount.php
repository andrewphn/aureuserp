<?php

namespace Webkul\Invoice\Filament\Clusters\Configuration\Resources\AccountResource\Pages;

use Webkul\Account\Filament\Clusters\Configuration\Resources\AccountResource\Pages\CreateAccount as BaseCreateAccount;
use Webkul\Invoice\Filament\Clusters\Configuration\Resources\AccountResource;

class CreateAccount extends BaseCreateAccount
{
    protected static string $resource = AccountResource::class;
}
