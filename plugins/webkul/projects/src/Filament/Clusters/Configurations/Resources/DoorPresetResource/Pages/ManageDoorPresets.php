<?php

namespace Webkul\Project\Filament\Clusters\Configurations\Resources\DoorPresetResource\Pages;

use Filament\Actions\CreateAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ManageRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Webkul\Project\Filament\Clusters\Configurations\Resources\DoorPresetResource;
use Webkul\Project\Models\DoorPreset;

class ManageDoorPresets extends ManageRecords
{
    protected static string $resource = DoorPresetResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('New Door Preset')
                ->icon('heroicon-o-plus-circle')
                ->successNotification(
                    Notification::make()
                        ->success()
                        ->title('Door Preset Created')
                        ->body('The door preset has been created successfully.'),
                ),
        ];
    }

    public function getTabs(): array
    {
        return [
            'active' => Tab::make('Active')
                ->badge(DoorPreset::where('is_active', true)->count())
                ->modifyQueryUsing(fn ($query) => $query->where('is_active', true)),
            'all' => Tab::make('All')
                ->badge(DoorPreset::count()),
        ];
    }
}
