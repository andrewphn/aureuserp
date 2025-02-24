<?php

namespace Webkul\Invoice\Filament\Clusters\Configuration\Resources\AccountResource\Pages;

use Webkul\Invoice\Filament\Clusters\Configuration\Resources\AccountResource;
use Webkul\Account\Filament\Resources\AccountResource\Pages\ViewAccount as BaseViewAccount;

class ViewAccount extends BaseViewAccount
{
    protected static string $resource = AccountResource::class;
}
