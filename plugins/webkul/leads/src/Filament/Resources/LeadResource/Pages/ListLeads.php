<?php

namespace Webkul\Lead\Filament\Resources\LeadResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Webkul\Lead\Filament\Resources\LeadResource;

class ListLeads extends ListRecords
{
    protected static string $resource = LeadResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
