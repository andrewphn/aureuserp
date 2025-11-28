<?php

namespace Webkul\Inventory\Filament\Resources\WoodworkingMaterialCategoryResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Webkul\Inventory\Filament\Resources\WoodworkingMaterialCategoryResource;

/**
 * Edit Woodworking Material Category class
 *
 * @see \Filament\Resources\Resource
 */
class EditWoodworkingMaterialCategory extends EditRecord
{
    protected static string $resource = WoodworkingMaterialCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
