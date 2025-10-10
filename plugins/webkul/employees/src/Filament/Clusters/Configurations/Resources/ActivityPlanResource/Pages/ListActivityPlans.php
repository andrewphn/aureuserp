<?php

namespace Webkul\Employee\Filament\Clusters\Configurations\Resources\ActivityPlanResource\Pages;

use Filament\Actions\CreateAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Auth;
use Webkul\Employee\Filament\Clusters\Configurations\Resources\ActivityPlanResource;
use Webkul\Support\Models\ActivityPlan;
use Webkul\TableViews\Filament\Components\PresetView;
use Webkul\TableViews\Filament\Concerns\HasTableViews;

class ListActivityPlans extends ListRecords
{
    use HasTableViews;

    protected static string $resource = ActivityPlanResource::class;

    protected static ?string $pluginName = 'employees';

    protected static function getPluginName()
    {
        return static::$pluginName;
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->icon('heroicon-o-plus-circle')
                ->label(__('employees::filament/clusters/configurations/resources/activity-plan/pages/list-activity-plan.header-actions.create.label'))
                ->mutateDataUsing(function ($data) {
                    $user = Auth::user();

                    $data['plugin'] = static::getPluginName();

                    $data['creator_id'] = $user->id;

                    $data['company_id'] ??= $user->defaultCompany?->id;

                    return $data;
                })
                ->successNotification(
                    Notification::make()
                        ->success()
                        ->title(__('employees::filament/clusters/configurations/resources/activity-plan/pages/list-activity-plan.header-actions.create.notification.title'))
                        ->body(__('employees::filament/clusters/configurations/resources/activity-plan/pages/list-activity-plan.header-actions.create.notification.body')),
                ),
        ];
    }

    public function getPresetTableViews(): array
    {
        return [
            'all' => PresetView::make(__('employees::filament/clusters/configurations/resources/activity-plan/pages/list-activity-plan.tabs.all'))
                ->icon('heroicon-s-queue-list')
                ->favorite()
                ->setAsDefault()
                ->modifyQueryUsing(fn ($query) => $query->where('plugin', static::getPluginName())),
            'archived' => PresetView::make(__('employees::filament/clusters/configurations/resources/activity-plan/pages/list-activity-plan.tabs.archived'))
                ->icon('heroicon-s-archive-box')
                ->modifyQueryUsing(fn ($query) => $query->where('plugin', static::getPluginName())->onlyTrashed()),
        ];
    }
}
