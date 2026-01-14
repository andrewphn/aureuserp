<?php

namespace Webkul\Project\Filament\Resources\GateResource\Pages;

use Filament\Resources\Pages\CreateRecord;
use Webkul\Project\Filament\Resources\GateResource;

class CreateGate extends CreateRecord
{
    protected static string $resource = GateResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
