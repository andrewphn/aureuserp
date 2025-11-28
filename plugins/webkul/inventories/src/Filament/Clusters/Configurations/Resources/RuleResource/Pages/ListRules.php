<?php

namespace Webkul\Inventory\Filament\Clusters\Configurations\Resources\RuleResource\Pages;

use Filament\Actions\CreateAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Auth;
use Webkul\Inventory\Filament\Clusters\Configurations\Resources\RuleResource;
use Webkul\Inventory\Models\Rule;
use Webkul\TableViews\Filament\Components\PresetView;
use Webkul\TableViews\Filament\Concerns\HasTableViews;

/**
 * List Rules class
 *
 * @see \Filament\Resources\Resource
 */
class ListRules extends ListRecords
{
    use HasTableViews;

    protected static string $resource = RuleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label(__('inventories::filament/clusters/configurations/resources/rule/pages/list-rules.header-actions.create.label'))
                ->icon('heroicon-o-plus-circle')
                ->mutateDataUsing(function ($data) {
                    $user = Auth::user();

                    $data['creator_id'] = $user->id;

                    $data['company_id'] = $user->default_company_id;

                    return $data;
                })
                ->successNotification(
                    Notification::make()
                        ->success()
                        ->title(__('inventories::filament/clusters/configurations/resources/rule/pages/list-rules.header-actions.create.notification.title'))
                        ->body(__('inventories::filament/clusters/configurations/resources/rule/pages/list-rules.header-actions.create.notification.body')),
                ),
        ];
    }

    public function getPresetTableViews(): array
    {
        return [
            'all' => PresetView::make(__('inventories::filament/clusters/configurations/resources/rule/pages/list-rules.tabs.all'))
                ->icon('heroicon-s-scale')
                ->favorite()
                ->setAsDefault()
                ->badge(Rule::count()),
            'archived' => PresetView::make(__('inventories::filament/clusters/configurations/resources/rule/pages/list-rules.tabs.archived'))
                ->icon('heroicon-s-archive-box')
                ->favorite()
                ->badge(Rule::onlyTrashed()->count())
                ->modifyQueryUsing(function ($query) {
                    return $query->onlyTrashed();
                }),
        ];
    }
}
