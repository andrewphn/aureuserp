<?php

namespace Webkul\TimeOff\Filament\Clusters\Configurations\Resources\AccrualPlanResource\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Webkul\TimeOff\Filament\Clusters\Configurations\Resources\AccrualPlanResource;
use Webkul\TableViews\Filament\Components\PresetView;
use Webkul\TableViews\Filament\Concerns\HasTableViews;

class ListAccrualPlans extends ListRecords
{
    use HasTableViews;

    protected static string $resource = AccrualPlanResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label(__('time-off::filament/clusters/configurations/resources/accrual-plan/pages/list-accrual-plan.header-actions.new-accrual-plan'))
                ->icon('heroicon-o-plus-circle'),
        ];
    }

    public function getPresetTableViews(): array
    {
        return [
            'all' => PresetView::make(__('time-off::filament/clusters/configurations/resources/accrual-plan/pages/list-accrual-plans.tabs.all'))
                ->icon('heroicon-s-queue-list')
                ->favorite()
                ->setAsDefault(),
        ];
    }
}
