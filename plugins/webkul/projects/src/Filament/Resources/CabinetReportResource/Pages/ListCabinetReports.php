<?php

namespace Webkul\Project\Filament\Resources\CabinetReportResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\DB;
use Webkul\Project\Filament\Resources\CabinetReportResource;
use Webkul\Project\Models\CabinetSpecification;

class ListCabinetReports extends ListRecords
{
    protected static string $resource = CabinetReportResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('view_analytics')
                ->label('View Analytics Dashboard')
                ->icon('heroicon-o-chart-pie')
                ->color('success')
                ->modalHeading('Cabinet Size Analytics')
                ->modalWidth('6xl')
                ->modalContent(view('webkul-project::filament.cabinet-analytics'))
                ->modalSubmitAction(false)
                ->modalCancelActionLabel('Close'),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            CabinetReportResource\Widgets\CabinetStatsWidget::class,
            CabinetReportResource\Widgets\SizeDistributionWidget::class,
            CabinetReportResource\Widgets\CommonSizesWidget::class,
        ];
    }

    public function getTitle(): string
    {
        return 'Cabinet Analytics & Reports';
    }
}
