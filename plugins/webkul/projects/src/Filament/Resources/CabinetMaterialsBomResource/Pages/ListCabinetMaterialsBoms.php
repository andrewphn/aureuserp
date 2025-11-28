<?php

namespace Webkul\Project\Filament\Resources\CabinetMaterialsBomResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Webkul\Project\Filament\Resources\CabinetMaterialsBomResource;

/**
 * List Cabinet Materials Boms class
 *
 * @see \Filament\Resources\Resource
 */
class ListCabinetMaterialsBoms extends ListRecords
{
    protected static string $resource = CabinetMaterialsBomResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
