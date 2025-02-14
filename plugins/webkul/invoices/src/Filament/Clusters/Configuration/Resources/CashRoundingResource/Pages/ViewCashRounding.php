<?php

namespace Webkul\Invoice\Filament\Clusters\Configuration\Resources\CashRoundingResource\Pages;

use Webkul\Invoice\Filament\Clusters\Configuration\Resources\CashRoundingResource;
use Webkul\Account\Filament\Clusters\Configuration\Resources\CashRoundingResource\Pages\ViewCashRounding as BaseViewCashRounding;

class ViewCashRounding extends BaseViewCashRounding
{
    protected static string $resource = CashRoundingResource::class;
}
