<?php

namespace Webkul\Project\Filament\Clusters\Configurations\Resources\ConstructionTemplateResource\Pages;

use Filament\Actions\CreateAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ManageRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Webkul\Project\Filament\Clusters\Configurations\Resources\ConstructionTemplateResource;
use Webkul\Project\Models\ConstructionTemplate;

class ManageConstructionTemplates extends ManageRecords
{
    protected static string $resource = ConstructionTemplateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('New Construction Template')
                ->icon('heroicon-o-plus-circle')
                ->successNotification(
                    Notification::make()
                        ->success()
                        ->title('Construction Template Created')
                        ->body('The construction template has been created successfully.'),
                ),
        ];
    }

    public function getTabs(): array
    {
        return [
            'active' => Tab::make('Active')
                ->badge(ConstructionTemplate::where('is_active', true)->count())
                ->modifyQueryUsing(fn ($query) => $query->where('is_active', true)),
            'all' => Tab::make('All')
                ->badge(ConstructionTemplate::count()),
        ];
    }
}
