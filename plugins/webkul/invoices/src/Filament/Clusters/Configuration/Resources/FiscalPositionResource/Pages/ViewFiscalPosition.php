<?php

namespace Webkul\Invoice\Filament\Clusters\Configuration\Resources\FiscalPositionResource\Pages;

use Webkul\Invoice\Filament\Clusters\Configuration\Resources\FiscalPositionResource;
use Webkul\Account\Filament\Resources\FiscalPositionResource\Pages\ViewFiscalPosition as BaseViewFiscalPosition;

class ViewFiscalPosition extends BaseViewFiscalPosition
{
    protected static string $resource = FiscalPositionResource::class;
}
