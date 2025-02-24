<?php

namespace Webkul\Invoice\Filament\Clusters\Configuration\Resources\CashRoundingResource\Pages;

use Webkul\Invoice\Filament\Clusters\Configuration\Resources\CashRoundingResource;
use Webkul\Account\Filament\Resources\CashRoundingResource\Pages\ListCashRounding as BaseListCashRounding;

class ListCashRounding extends BaseListCashRounding
{
    protected static string $resource = CashRoundingResource::class;
}
