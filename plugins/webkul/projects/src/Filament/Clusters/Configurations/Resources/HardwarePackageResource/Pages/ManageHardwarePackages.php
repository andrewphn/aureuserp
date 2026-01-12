<?php

namespace Webkul\Project\Filament\Clusters\Configurations\Resources\HardwarePackageResource\Pages;

use Filament\Actions\CreateAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ManageRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Webkul\Project\Filament\Clusters\Configurations\Resources\HardwarePackageResource;
use Webkul\Project\Models\HardwarePackage;

class ManageHardwarePackages extends ManageRecords
{
    protected static string $resource = HardwarePackageResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('New Hardware Package')
                ->icon('heroicon-o-plus-circle')
                ->successNotification(
                    Notification::make()
                        ->success()
                        ->title('Hardware Package Created')
                        ->body('The hardware package has been created successfully.'),
                ),
        ];
    }

    public function getTabs(): array
    {
        return [
            'active' => Tab::make('Active')
                ->badge(HardwarePackage::where('is_active', true)->count())
                ->modifyQueryUsing(fn ($query) => $query->where('is_active', true)),
            'all' => Tab::make('All')
                ->badge(HardwarePackage::count()),
        ];
    }
}
