<?php

namespace Webkul\Project\Filament\Resources\CncMaterialProductResource\Pages;

use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Webkul\Project\Filament\Resources\CncMaterialProductResource;

class EditCncMaterialProduct extends EditRecord
{
    protected static string $resource = CncMaterialProductResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
