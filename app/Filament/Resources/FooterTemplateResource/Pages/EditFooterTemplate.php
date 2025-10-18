<?php

namespace App\Filament\Resources\FooterTemplateResource\Pages;

use App\Filament\Resources\FooterTemplateResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditFooterTemplate extends EditRecord
{
    protected static string $resource = FooterTemplateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()
                ->hidden(fn ($record) => $record->is_system),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
