<?php

namespace Webkul\Employee\Filament\Clusters\Configurations\Resources\EmployeeCategoryResource\Pages;

use Filament\Actions\CreateAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Webkul\Employee\Filament\Clusters\Configurations\Resources\EmployeeCategoryResource;
use Webkul\TableViews\Filament\Components\PresetView;
use Webkul\TableViews\Filament\Concerns\HasTableViews;


class ListEmployeeCategories extends ListRecords
{
    use HasTableViews;

    protected static string $resource = EmployeeCategoryResource::class;

    public function getPresetTableViews(): array
    {
        return [
            'all' => PresetView::make(__('employees::filament/clusters/configurations/resources/employee-category/pages/list-employee-categories.tabs.all'))
                ->icon('heroicon-s-queue-list')
                ->favorite()
                ->setAsDefault(),
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->icon('heroicon-o-plus-circle')
                ->label(__('employees::filament/clusters/configurations/resources/employee-category/pages/list-employee-category.header-actions.create.label'))
                ->mutateDataUsing(function (array $data): array {
                    $data['color'] = $data['color'] ?? fake()->hexColor();

                    return $data;
                })
                ->successNotification(
                    Notification::make()
                        ->success()
                        ->title(__('employees::filament/clusters/configurations/resources/employee-category/pages/list-employee-category.header-actions.create.notification.title'))
                        ->body(__('employees::filament/clusters/configurations/resources/employee-category/pages/list-employee-category.header-actions.create.notification.body'))
                ),
        ];
    }
}
