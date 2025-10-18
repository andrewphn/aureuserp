<?php

namespace App\Filament\Resources\FooterTemplateResource\Pages;

use App\Filament\Resources\FooterTemplateResource;
use Filament\Resources\Pages\CreateRecord;

class CreateFooterTemplate extends CreateRecord
{
    protected static string $resource = FooterTemplateResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['created_by'] = auth()->id();

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
