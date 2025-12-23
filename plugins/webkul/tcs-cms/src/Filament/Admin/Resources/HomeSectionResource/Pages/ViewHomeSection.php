<?php

namespace Webkul\TcsCms\Filament\Admin\Resources\HomeSectionResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Webkul\TcsCms\Filament\Admin\Resources\HomeSectionResource;

class ViewHomeSection extends ViewRecord
{
    protected static string $resource = HomeSectionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
