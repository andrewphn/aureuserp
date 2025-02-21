<?php

namespace Webkul\Invoice\Filament\Clusters\Configuration\Resources\FiscalPositionResource\Pages;

use Webkul\Account\Filament\Clusters\Configuration\Resources\FiscalPositionResource\Pages\EditFiscalPosition as BaseEditFiscalPosition;
use Webkul\Invoice\Filament\Clusters\Configuration\Resources\FiscalPositionResource;

class EditFiscalPosition extends BaseEditFiscalPosition
{
    protected static string $resource = FiscalPositionResource::class;
}
