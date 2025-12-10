<?php

namespace Webkul\Product\Filament\Resources\ReferenceTypeCodeResource\Pages;

use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;
use Webkul\Product\Filament\Resources\ReferenceTypeCodeResource;

class ViewReferenceTypeCode extends ViewRecord
{
    protected static string $resource = ReferenceTypeCodeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
            DeleteAction::make(),
        ];
    }
}
