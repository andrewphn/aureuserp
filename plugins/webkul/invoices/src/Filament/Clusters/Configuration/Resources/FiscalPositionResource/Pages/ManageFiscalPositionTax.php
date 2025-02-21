<?php

namespace Webkul\Invoice\Filament\Clusters\Configuration\Resources\FiscalPositionResource\Pages;

use Webkul\Account\Filament\Clusters\Configuration\Resources\FiscalPositionResource\Pages\ManageFiscalPositionTax as BaseManageFiscalPositionTax;
use Webkul\Invoice\Filament\Clusters\Configuration\Resources\FiscalPositionResource;

class ManageFiscalPositionTax extends BaseManageFiscalPositionTax
{
    protected static string $resource = FiscalPositionResource::class;
}
