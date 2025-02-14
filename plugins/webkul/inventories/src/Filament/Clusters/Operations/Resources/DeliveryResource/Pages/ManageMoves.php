<?php

namespace Webkul\Inventory\Filament\Clusters\Operations\Resources\DeliveryResource\Pages;

use Webkul\Inventory\Filament\Clusters\Operations\Resources\OperationResource\Pages\ManageMoves as OperationManageMoves;
use Webkul\Inventory\Filament\Clusters\Operations\Resources\DeliveryResource;

class ManageMoves extends OperationManageMoves
{
    protected static string $resource = DeliveryResource::class;
}
