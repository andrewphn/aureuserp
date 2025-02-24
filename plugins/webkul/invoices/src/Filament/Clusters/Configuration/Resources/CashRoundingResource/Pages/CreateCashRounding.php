<?php

namespace Webkul\Invoice\Filament\Clusters\Configuration\Resources\CashRoundingResource\Pages;

use Webkul\Account\Filament\Resources\CashRoundingResource\Pages\CreateCashRounding as BaseCreateCashRounding;
use Webkul\Invoice\Filament\Clusters\Configuration\Resources\CashRoundingResource;

class CreateCashRounding extends BaseCreateCashRounding
{
    protected static string $resource = CashRoundingResource::class;
}
