<?php

namespace Webkul\Invoice\Filament\Clusters\Configuration\Resources\AccountResource\Pages;

use Webkul\Invoice\Filament\Clusters\Configuration\Resources\AccountResource;
use Webkul\Account\Filament\Resources\AccountResource\Pages\CreateAccount as BaseCreateAccount;

class CreateAccount extends BaseCreateAccount
{
    protected static string $resource = AccountResource::class;
}
