<?php

namespace Webkul\Project\Filament\Resources\CncProgramResource\Pages;

use Filament\Resources\Pages\CreateRecord;
use Webkul\Project\Filament\Resources\CncProgramResource;

/**
 * Create CNC Program page
 */
class CreateCncProgram extends CreateRecord
{
    protected static string $resource = CncProgramResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['creator_id'] = auth()->id();

        return $data;
    }
}
