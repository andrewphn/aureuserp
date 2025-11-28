<?php

namespace Webkul\Inventory\Filament\Clusters\Products\Resources\LotResource\Pages;

use Filament\Resources\Pages\CreateRecord;
use Webkul\Inventory\Filament\Clusters\Products\Resources\LotResource;

/**
 * Create Lot class
 *
 * @see \Filament\Resources\Resource
 */
class CreateLot extends CreateRecord
{
    protected static string $resource = LotResource::class;
}
