<?php

namespace Webkul\Invoice\Filament\Clusters\Configuration\Resources\CashRoundingResource\Pages;

use Webkul\Invoice\Filament\Clusters\Configuration\Resources\CashRoundingResource;
use Webkul\Account\Filament\Clusters\Configuration\Resources\CashRoundingResource\Pages\ListCashRoundings as BaseListCashRoundings;

class ListCashRoundings extends BaseListCashRoundings
{
    protected static string $resource = CashRoundingResource::class;
}
