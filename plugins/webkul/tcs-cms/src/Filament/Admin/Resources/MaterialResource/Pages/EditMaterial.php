<?php

namespace Webkul\TcsCms\Filament\Admin\Resources\MaterialResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Webkul\TcsCms\Filament\Admin\Resources\MaterialResource;

class EditMaterial extends EditRecord
{
    protected static string $resource = MaterialResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}
