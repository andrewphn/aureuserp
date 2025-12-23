<?php

namespace Webkul\TcsCms\Filament\Admin\Resources\PortfolioProjectResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Webkul\TcsCms\Filament\Admin\Resources\PortfolioProjectResource;

class ListPortfolioProjects extends ListRecords
{
    protected static string $resource = PortfolioProjectResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
