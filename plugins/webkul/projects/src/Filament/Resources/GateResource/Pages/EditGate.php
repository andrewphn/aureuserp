<?php

namespace Webkul\Project\Filament\Resources\GateResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Webkul\Project\Filament\Resources\GateResource;

class EditGate extends EditRecord
{
    protected static string $resource = GateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
