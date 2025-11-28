<?php

namespace App\Filament\Resources\FooterTemplateResource\Pages;

use App\Filament\Resources\FooterTemplateResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

/**
 * List Footer Templates class
 *
 * @see \Filament\Resources\Resource
 */
class ListFooterTemplates extends ListRecords
{
    protected static string $resource = FooterTemplateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
