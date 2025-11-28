<?php

namespace Webkul\Project\Filament\Resources\CabinetMaterialsBomResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Webkul\Project\Filament\Resources\CabinetMaterialsBomResource;

/**
 * Edit Cabinet Materials Bom class
 *
 * @see \Filament\Resources\Resource
 */
class EditCabinetMaterialsBom extends EditRecord
{
    protected static string $resource = CabinetMaterialsBomResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
