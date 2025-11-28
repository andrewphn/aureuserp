<?php

namespace Webkul\Inventory\Filament\Resources\WoodworkingMaterialCategoryResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Webkul\Inventory\Filament\Resources\WoodworkingMaterialCategoryResource;

/**
 * List Woodworking Material Categories class
 *
 * @see \Filament\Resources\Resource
 */
class ListWoodworkingMaterialCategories extends ListRecords
{
    protected static string $resource = WoodworkingMaterialCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
