<?php

namespace Webkul\TcsCms\Filament\Admin\Resources\TeamMemberResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Webkul\TcsCms\Filament\Admin\Resources\TeamMemberResource;

class ListTeamMembers extends ListRecords
{
    protected static string $resource = TeamMemberResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
