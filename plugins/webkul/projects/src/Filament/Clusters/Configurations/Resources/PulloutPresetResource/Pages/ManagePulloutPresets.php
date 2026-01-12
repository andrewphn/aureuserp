<?php

namespace Webkul\Project\Filament\Clusters\Configurations\Resources\PulloutPresetResource\Pages;

use Filament\Actions\CreateAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ManageRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Webkul\Project\Filament\Clusters\Configurations\Resources\PulloutPresetResource;
use Webkul\Project\Models\PulloutPreset;

class ManagePulloutPresets extends ManageRecords
{
    protected static string $resource = PulloutPresetResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('New Pullout Preset')
                ->icon('heroicon-o-plus-circle')
                ->successNotification(
                    Notification::make()
                        ->success()
                        ->title('Pullout Preset Created')
                        ->body('The pullout preset has been created successfully.'),
                ),
        ];
    }

    public function getTabs(): array
    {
        return [
            'active' => Tab::make('Active')
                ->badge(PulloutPreset::where('is_active', true)->count())
                ->modifyQueryUsing(fn ($query) => $query->where('is_active', true)),
            'all' => Tab::make('All')
                ->badge(PulloutPreset::count()),
        ];
    }
}
