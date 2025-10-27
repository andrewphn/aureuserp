<?php

namespace Webkul\Project\Filament\Resources\HardwareRequirementResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Webkul\Project\Filament\Resources\HardwareRequirementResource;

class ListHardwareRequirements extends ListRecords
{
    protected static string $resource = HardwareRequirementResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
