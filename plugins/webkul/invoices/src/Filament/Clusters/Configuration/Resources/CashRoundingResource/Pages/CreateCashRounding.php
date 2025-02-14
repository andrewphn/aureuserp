<?php

namespace Webkul\Invoice\Filament\Clusters\Configuration\Resources\CashRoundingResource\Pages;

use Webkul\Invoice\Filament\Clusters\Configuration\Resources\CashRoundingResource;
use Webkul\Account\Filament\Clusters\Configuration\Resources\CashRoundingResource\Pages\CreateCashRounding as BaseCreateCashRounding;

class CreateCashRounding extends BaseCreateCashRounding
{
    protected static string $resource = CashRoundingResource::class;
}
