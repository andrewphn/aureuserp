<?php

namespace Webkul\Project\Filament\Resources\CncMaterialProductResource\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Webkul\Project\Filament\Resources\CncMaterialProductResource;

class ListCncMaterialProducts extends ListRecords
{
    protected static string $resource = CncMaterialProductResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
