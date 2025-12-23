<?php

namespace Webkul\TcsCms\Filament\Admin\Resources\HomeSectionResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Webkul\TcsCms\Filament\Admin\Resources\HomeSectionResource;

class ListHomeSections extends ListRecords
{
    protected static string $resource = HomeSectionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
