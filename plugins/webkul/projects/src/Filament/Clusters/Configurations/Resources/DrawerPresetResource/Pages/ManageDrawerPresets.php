<?php

namespace Webkul\Project\Filament\Clusters\Configurations\Resources\DrawerPresetResource\Pages;

use Filament\Actions\CreateAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ManageRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Webkul\Project\Filament\Clusters\Configurations\Resources\DrawerPresetResource;
use Webkul\Project\Models\DrawerPreset;

class ManageDrawerPresets extends ManageRecords
{
    protected static string $resource = DrawerPresetResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('New Drawer Preset')
                ->icon('heroicon-o-plus-circle')
                ->successNotification(
                    Notification::make()
                        ->success()
                        ->title('Drawer Preset Created')
                        ->body('The drawer preset has been created successfully.'),
                ),
        ];
    }

    public function getTabs(): array
    {
        return [
            'active' => Tab::make('Active')
                ->badge(DrawerPreset::where('is_active', true)->count())
                ->modifyQueryUsing(fn ($query) => $query->where('is_active', true)),
            'all' => Tab::make('All')
                ->badge(DrawerPreset::count()),
        ];
    }
}
