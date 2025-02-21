<?php

namespace Webkul\Invoice\Filament\Clusters\Configuration\Resources\AccountResource\Pages;

use Webkul\Account\Filament\Clusters\Configuration\Resources\AccountResource\Pages\ListAccounts as BaseListAccounts;
use Webkul\Invoice\Filament\Clusters\Configuration\Resources\AccountResource;

class ListAccounts extends BaseListAccounts
{
    protected static string $resource = AccountResource::class;
}
