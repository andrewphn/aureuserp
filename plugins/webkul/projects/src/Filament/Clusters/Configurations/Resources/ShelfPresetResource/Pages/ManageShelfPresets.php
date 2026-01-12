<?php

namespace Webkul\Project\Filament\Clusters\Configurations\Resources\ShelfPresetResource\Pages;

use Filament\Actions\CreateAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ManageRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Webkul\Project\Filament\Clusters\Configurations\Resources\ShelfPresetResource;
use Webkul\Project\Models\ShelfPreset;

class ManageShelfPresets extends ManageRecords
{
    protected static string $resource = ShelfPresetResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('New Shelf Preset')
                ->icon('heroicon-o-plus-circle')
                ->successNotification(
                    Notification::make()
                        ->success()
                        ->title('Shelf Preset Created')
                        ->body('The shelf preset has been created successfully.'),
                ),
        ];
    }

    public function getTabs(): array
    {
        return [
            'active' => Tab::make('Active')
                ->badge(ShelfPreset::where('is_active', true)->count())
                ->modifyQueryUsing(fn ($query) => $query->where('is_active', true)),
            'all' => Tab::make('All')
                ->badge(ShelfPreset::count()),
        ];
    }
}
