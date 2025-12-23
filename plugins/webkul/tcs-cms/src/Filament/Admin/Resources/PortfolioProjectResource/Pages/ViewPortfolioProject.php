<?php

namespace Webkul\TcsCms\Filament\Admin\Resources\PortfolioProjectResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Webkul\TcsCms\Filament\Admin\Resources\PortfolioProjectResource;

class ViewPortfolioProject extends ViewRecord
{
    protected static string $resource = PortfolioProjectResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
