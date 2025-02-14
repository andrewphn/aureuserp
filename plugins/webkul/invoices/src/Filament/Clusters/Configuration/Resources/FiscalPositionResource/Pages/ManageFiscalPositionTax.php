<?php

namespace Webkul\Invoice\Filament\Clusters\Configuration\Resources\FiscalPositionResource\Pages;

use Webkul\Invoice\Filament\Clusters\Configuration\Resources\FiscalPositionResource;
use Webkul\Account\Filament\Clusters\Configuration\Resources\FiscalPositionResource\Pages\ManageFiscalPositionTax as BaseManageFiscalPositionTax;

class ManageFiscalPositionTax extends BaseManageFiscalPositionTax
{
    protected static string $resource = FiscalPositionResource::class;
}
