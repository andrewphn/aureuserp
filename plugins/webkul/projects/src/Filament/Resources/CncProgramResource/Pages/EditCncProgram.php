<?php

namespace Webkul\Project\Filament\Resources\CncProgramResource\Pages;

use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;
use Webkul\Chatter\Filament\Actions\ChatterAction;
use Webkul\Project\Filament\Resources\CncProgramResource;

/**
 * Edit CNC Program page
 */
class EditCncProgram extends EditRecord
{
    protected static string $resource = CncProgramResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            ChatterAction::make()
                ->setResource(static::$resource),
            DeleteAction::make(),
        ];
    }
}
