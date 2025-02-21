<?php

namespace Webkul\Invoice\Filament\Clusters\Configuration\Resources\AccountResource\Pages;

use Webkul\Invoice\Filament\Clusters\Configuration\Resources\AccountResource;
use Webkul\Account\Filament\Resources\AccountResource\Pages\EditAccount as BaseEditAccount;

class EditAccount extends BaseEditAccount
{
    protected static string $resource = AccountResource::class;
}
