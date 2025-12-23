<?php

namespace Webkul\TcsCms\Filament\Admin\Resources\MaterialResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Webkul\TcsCms\Filament\Admin\Resources\MaterialResource;

class ListMaterials extends ListRecords
{
    protected static string $resource = MaterialResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
