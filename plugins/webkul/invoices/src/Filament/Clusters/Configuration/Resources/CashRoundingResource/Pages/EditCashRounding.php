<?php

namespace Webkul\Invoice\Filament\Clusters\Configuration\Resources\CashRoundingResource\Pages;

use Webkul\Invoice\Filament\Clusters\Configuration\Resources\CashRoundingResource;
use Webkul\Account\Filament\Resources\CashRoundingResource\Pages\EditCashRounding as BaseEditCashRounding;

class EditCashRounding extends BaseEditCashRounding
{
    protected static string $resource = CashRoundingResource::class;
}
