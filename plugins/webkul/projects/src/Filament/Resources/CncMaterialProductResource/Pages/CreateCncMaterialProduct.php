<?php

namespace Webkul\Project\Filament\Resources\CncMaterialProductResource\Pages;

use Filament\Resources\Pages\CreateRecord;
use Webkul\Project\Filament\Resources\CncMaterialProductResource;

class CreateCncMaterialProduct extends CreateRecord
{
    protected static string $resource = CncMaterialProductResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
