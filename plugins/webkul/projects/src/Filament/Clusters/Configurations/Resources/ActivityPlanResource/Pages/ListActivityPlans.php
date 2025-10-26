<?php

namespace Webkul\Project\Filament\Clusters\Configurations\Resources\ActivityPlanResource\Pages;

use Filament\Actions\CreateAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Auth;
use Webkul\Project\Filament\Clusters\Configurations\Resources\ActivityPlanResource;
use Webkul\TableViews\Filament\Components\PresetView;
use Webkul\TableViews\Filament\Concerns\HasTableViews;

class ListActivityPlans extends ListRecords
{
    use HasTableViews;

    protected static string $resource = ActivityPlanResource::class;

    public function getPresetTableViews(): array
    {
        return [
            'all' => PresetView::make(__('webkul-project::filament/clusters/configurations/resources/activity-plan/pages/list-activity-plans.tabs.all'))
                ->icon('heroicon-s-queue-list')
                ->favorite()
                ->setAsDefault()
                ->modifyQueryUsing(fn ($query) => $query->where('plugin', 'projects')),
            'archived' => PresetView::make(__('webkul-project::filament/clusters/configurations/resources/activity-plan/pages/list-activity-plans.tabs.archived'))
                ->icon('heroicon-s-archive-box')
                ->modifyQueryUsing(fn ($query) => $query->where('plugin', 'projects')->onlyTrashed()),
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label(__('webkul-project::filament/clusters/configurations/resources/activity-plan/pages/list-activity-plans.header-actions.create.label'))
                ->icon('heroicon-o-plus-circle')
                ->mutateDataUsing(function ($data) {
                    $user = Auth::user();

                    $data['plugin'] = 'projects';

                    $data['creator_id'] = $user->id;

                    $data['company_id'] ??= $user->defaultCompany?->id;

                    return $data;
                })
                ->successNotification(
                    Notification::make()
                        ->success()
                        ->title(__('webkul-project::filament/clusters/configurations/resources/activity-plan/pages/list-activity-plans.header-actions.create.notification.title'))
                        ->body(__('webkul-project::filament/clusters/configurations/resources/activity-plan/pages/list-activity-plans.header-actions.create.notification.body')),
                ),
        ];
    }
}
