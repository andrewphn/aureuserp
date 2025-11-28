<?php

namespace Webkul\Project\Filament\Resources\HardwareRequirementResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Webkul\Project\Filament\Resources\HardwareRequirementResource;

/**
 * Edit Hardware Requirement class
 *
 * @see \Filament\Resources\Resource
 */
class EditHardwareRequirement extends EditRecord
{
    protected static string $resource = HardwareRequirementResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
