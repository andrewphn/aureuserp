<?php

namespace Webkul\Invoice\Filament\Clusters\Configuration\Resources\AccountResource\Pages;

use Webkul\Account\Filament\Resources\AccountResource\Pages\EditAccount as BaseEditAccount;
use Webkul\Invoice\Filament\Clusters\Configuration\Resources\AccountResource;

class EditAccount extends BaseEditAccount
{
    protected static string $resource = AccountResource::class;
}
