<?php

namespace Webkul\Security\Filament\Resources\RoleResource\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Webkul\Security\Filament\Resources\RoleResource;
use Webkul\TableViews\Filament\Components\PresetView;
use Webkul\TableViews\Filament\Concerns\HasTableViews;

class ListRoles extends ListRecords
{
    use HasTableViews;

    protected static string $resource = RoleResource::class;

    public function getPresetTableViews(): array
    {
        return [
            'all' => PresetView::make(__('security::filament/resources/role/pages/list-roles.tabs.all'))
                ->icon('heroicon-s-queue-list')
                ->favorite()
                ->setAsDefault(),
        ];
    }

    protected function getActions(): array
    {
        return [
            CreateAction::make()->icon('heroicon-o-plus-circle'),
        ];
    }
}
