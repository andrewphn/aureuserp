<?php

namespace Webkul\Inventory\Filament\Clusters\Operations\Resources\DropshipResource\Pages;

use Webkul\Inventory\Filament\Clusters\Operations\Resources\OperationResource\Pages\ManageMoves as OperationManageMoves;
use Webkul\Inventory\Filament\Clusters\Operations\Resources\DropshipResource;

class ManageMoves extends OperationManageMoves
{
    protected static string $resource = DropshipResource::class;
}
