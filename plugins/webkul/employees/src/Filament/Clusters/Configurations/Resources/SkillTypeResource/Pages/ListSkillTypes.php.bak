<?php

namespace Webkul\Employee\Filament\Clusters\Configurations\Resources\SkillTypeResource\Pages;

use Filament\Actions\CreateAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Webkul\Employee\Filament\Clusters\Configurations\Resources\SkillTypeResource;
use Webkul\Employee\Models\SkillType;
use Webkul\TableViews\Filament\Components\PresetView;
use Webkul\TableViews\Filament\Concerns\HasTableViews;

class ListSkillTypes extends ListRecords
{
    use HasTableViews;

    protected static string $resource = SkillTypeResource::class;

    public function getPresetTableViews(): array
    {
        return [
            'all' => PresetView::make(__('employees::filament/clusters/configurations/resources/skill-type/pages/list-skill-type.tabs.all'))
                ->icon('heroicon-s-queue-list')
                ->favorite()
                ->setAsDefault(),
            'archived' => PresetView::make(__('employees::filament/clusters/configurations/resources/skill-type/pages/list-skill-type.tabs.archived'))
                ->icon('heroicon-s-archive-box')
                ->modifyQueryUsing(fn ($query) => $query->onlyTrashed()),
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->icon('heroicon-o-plus-circle')
                ->label(__('employees::filament/clusters/configurations/resources/skill-type/pages/list-skill-type.header-actions.create.label'))
                ->createAnother(false)
                ->after(function ($record) {
                    return redirect(
                        static::$resource::getUrl('edit', ['record' => $record]),
                    );
                })
                ->successNotification(
                    Notification::make()
                        ->success()
                        ->title(__('employees::filament/clusters/configurations/resources/skill-type/pages/list-skill-type.header-actions.create.notification.title'))
                        ->body(__('employees::filament/clusters/configurations/resources/skill-type/pages/list-skill-type.header-actions.create.notification.body')),
                ),
        ];
    }
}
