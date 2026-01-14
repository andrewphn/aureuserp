<?php

namespace Webkul\Project\Filament\Resources\GateResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Webkul\Project\Filament\Resources\GateResource;

class ListGates extends ListRecords
{
    protected static string $resource = GateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
