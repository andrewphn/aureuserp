<?php

namespace Webkul\Inventory\Filament\Clusters\Configurations\Resources\OperationTypeResource\Pages;

use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;
use Webkul\Inventory\Filament\Clusters\Configurations\Resources\OperationTypeResource;

/**
 * View Operation Type class
 *
 * @see \Filament\Resources\Resource
 */
class ViewOperationType extends ViewRecord
{
    protected static string $resource = OperationTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
