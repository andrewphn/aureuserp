<?php

namespace Webkul\Invoice\Filament\Clusters\Configuration\Resources\CashRoundingResource\Pages;

use Webkul\Account\Filament\Clusters\Configuration\Resources\CashRoundingResource\Pages\EditCashRounding as BaseEditCashRounding;
use Webkul\Invoice\Filament\Clusters\Configuration\Resources\CashRoundingResource;

class EditCashRounding extends BaseEditCashRounding
{
    protected static string $resource = CashRoundingResource::class;
}
