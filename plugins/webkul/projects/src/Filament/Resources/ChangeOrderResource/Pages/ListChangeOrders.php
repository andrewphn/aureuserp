<?php

namespace Webkul\Project\Filament\Resources\ChangeOrderResource\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Webkul\Project\Filament\Resources\ChangeOrderResource;

class ListChangeOrders extends ListRecords
{
    protected static string $resource = ChangeOrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('New Change Order')
                ->icon('heroicon-o-plus'),
        ];
    }
}
