<?php

namespace Webkul\Inventory\Filament\Clusters\Operations\Resources\DeliveryResource\Pages;

use Webkul\Inventory\Filament\Clusters\Operations\Resources\DeliveryResource;
use Webkul\Inventory\Filament\Clusters\Operations\Resources\OperationResource\Pages\ManageMoves as OperationManageMoves;

/**
 * Manage Moves class
 *
 * @see \Filament\Resources\Resource
 */
class ManageMoves extends OperationManageMoves
{
    protected static string $resource = DeliveryResource::class;
}
