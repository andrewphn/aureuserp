<?php

namespace Webkul\Employee\Filament\Clusters\Configurations\Resources\EmploymentTypeResource\Pages;

use Filament\Actions\CreateAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Auth;
use Webkul\Employee\Filament\Clusters\Configurations\Resources\EmploymentTypeResource;
use Webkul\TableViews\Filament\Components\PresetView;
use Webkul\TableViews\Filament\Concerns\HasTableViews;

/**
 * List Employment Types class
 *
 * @see \Filament\Resources\Resource
 */
class ListEmploymentTypes extends ListRecords
{
    use HasTableViews;

    protected static string $resource = EmploymentTypeResource::class;

    public function getPresetTableViews(): array
    {
        return [
            'all' => PresetView::make(__('employees::filament/clusters/configurations/resources/employment-type/pages/list-employment-types.tabs.all'))
                ->icon('heroicon-s-queue-list')
                ->favorite()
                ->setAsDefault(),
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->icon('heroicon-o-plus-circle')
                ->label(__('employees::filament/clusters/configurations/resources/employment-type/pages/list-employment-type.header-actions.create.label'))
                ->mutateDataUsing(function (array $data): array {
                    $data['code'] = $data['code'] ?? $data['name'];

                    $data['user_id'] = Auth::user()->id;

                    return $data;
                })
                ->successNotification(
                    Notification::make()
                        ->success()
                        ->title(__('employees::filament/clusters/configurations/resources/employment-type/pages/list-employment-type.header-actions.create.notification.title'))
                        ->body(__('employees::filament/clusters/configurations/resources/employment-type/pages/list-employment-type.header-actions.create.notification.body'))
                ),
        ];
    }
}
