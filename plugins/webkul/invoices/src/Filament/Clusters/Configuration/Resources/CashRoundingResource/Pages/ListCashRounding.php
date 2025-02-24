<?php

namespace Webkul\Invoice\Filament\Clusters\Configuration\Resources\CashRoundingResource\Pages;

use Webkul\Account\Filament\Resources\CashRoundingResource\Pages\ListCashRounding as BaseListCashRounding;
use Webkul\Invoice\Filament\Clusters\Configuration\Resources\CashRoundingResource;

class ListCashRounding extends BaseListCashRounding
{
    protected static string $resource = CashRoundingResource::class;
}
