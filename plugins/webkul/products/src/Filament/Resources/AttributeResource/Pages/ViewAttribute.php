<?php

namespace Webkul\Product\Filament\Resources\AttributeResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Webkul\Product\Filament\Resources\AttributeResource;

class ViewAttribute extends ViewRecord
{
    protected static string $resource = AttributeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
