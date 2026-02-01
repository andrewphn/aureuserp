<?php

namespace Webkul\Project\Filament\Resources\CncProgramResource\Pages;

use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;
use Webkul\Project\Filament\Resources\CncProgramResource;

/**
 * View CNC Program page
 */
class ViewCncProgram extends ViewRecord
{
    protected static string $resource = CncProgramResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
            DeleteAction::make(),
        ];
    }
}
