<?php

namespace App\Filament\Resources\FooterTemplateResource\Pages;

use App\Filament\Resources\FooterTemplateResource;
use Filament\Resources\Pages\CreateRecord;

/**
 * Create Footer Template class
 *
 * @see \Filament\Resources\Resource
 */
class CreateFooterTemplate extends CreateRecord
{
    protected static string $resource = FooterTemplateResource::class;

    /**
     * Mutate Form Data Before Create
     *
     * @param array $data The data array
     * @return array
     */
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
