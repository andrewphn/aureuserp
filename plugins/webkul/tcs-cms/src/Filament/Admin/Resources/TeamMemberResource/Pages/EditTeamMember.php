<?php

namespace Webkul\TcsCms\Filament\Admin\Resources\TeamMemberResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Webkul\TcsCms\Filament\Admin\Resources\TeamMemberResource;

class EditTeamMember extends EditRecord
{
    protected static string $resource = TeamMemberResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}
