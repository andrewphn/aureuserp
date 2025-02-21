<?php

namespace Webkul\Invoice\Filament\Clusters\Configuration\Resources\FiscalPositionResource\Pages;

use Webkul\Account\Filament\Clusters\Configuration\Resources\FiscalPositionResource\Pages\ViewFiscalPosition as BaseViewFiscalPosition;
use Webkul\Invoice\Filament\Clusters\Configuration\Resources\FiscalPositionResource;

class ViewFiscalPosition extends BaseViewFiscalPosition
{
    protected static string $resource = FiscalPositionResource::class;
}
