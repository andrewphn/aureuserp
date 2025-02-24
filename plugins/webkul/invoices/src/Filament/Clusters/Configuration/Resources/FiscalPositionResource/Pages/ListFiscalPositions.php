<?php

namespace Webkul\Invoice\Filament\Clusters\Configuration\Resources\FiscalPositionResource\Pages;

use Webkul\Account\Filament\Resources\FiscalPositionResource\Pages\ListFiscalPositions as BaseListFiscalPositions;
use Webkul\Invoice\Filament\Clusters\Configuration\Resources\FiscalPositionResource;

class ListFiscalPositions extends BaseListFiscalPositions
{
    protected static string $resource = FiscalPositionResource::class;
}
