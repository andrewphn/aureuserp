<?php

namespace Webkul\TcsCms\Filament\Admin\Resources\MaterialResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Webkul\TcsCms\Filament\Admin\Resources\MaterialResource;

class ViewMaterial extends ViewRecord
{
    protected static string $resource = MaterialResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
