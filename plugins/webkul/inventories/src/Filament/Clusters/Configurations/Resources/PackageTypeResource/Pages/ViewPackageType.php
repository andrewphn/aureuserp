<?php

namespace Webkul\Inventory\Filament\Clusters\Configurations\Resources\PackageTypeResource\Pages;

use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;
use Webkul\Inventory\Filament\Clusters\Configurations\Resources\PackageTypeResource;

/**
 * View Package Type class
 *
 * @see \Filament\Resources\Resource
 */
class ViewPackageType extends ViewRecord
{
    protected static string $resource = PackageTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
