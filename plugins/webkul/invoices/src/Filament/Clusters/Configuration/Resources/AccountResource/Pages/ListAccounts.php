<?php

namespace Webkul\Invoice\Filament\Clusters\Configuration\Resources\AccountResource\Pages;

use Webkul\Invoice\Filament\Clusters\Configuration\Resources\AccountResource;
use Webkul\Account\Filament\Resources\AccountResource\Pages\ListAccounts as BaseListAccounts;

class ListAccounts extends BaseListAccounts
{
    protected static string $resource = AccountResource::class;
}
