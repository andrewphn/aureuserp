<?php

namespace Webkul\Project\Filament\Clusters\Configurations\Resources\CabinetCalculationAuditResource\Pages;

use Filament\Resources\Pages\ListRecords;
use Webkul\Project\Filament\Clusters\Configurations\Resources\CabinetCalculationAuditResource;
use Webkul\TableViews\Filament\Components\PresetView;
use Webkul\TableViews\Filament\Concerns\HasTableViews;

class ListCabinetCalculationAudits extends ListRecords
{
    use HasTableViews;

    protected static string $resource = CabinetCalculationAuditResource::class;

    public function getPresetTableViews(): array
    {
        return [
            'all' => PresetView::make('All Audits')
                ->icon('heroicon-s-queue-list')
                ->favorite()
                ->setAsDefault(),
            'needs_attention' => PresetView::make('Needs Attention')
                ->icon('heroicon-s-exclamation-triangle')
                ->modifyQueryUsing(fn ($query) => $query->whereIn('audit_status', ['failed', 'warning'])->where('is_overridden', false)),
            'failed' => PresetView::make('Failed')
                ->icon('heroicon-s-x-circle')
                ->modifyQueryUsing(fn ($query) => $query->where('audit_status', 'failed')),
            'warnings' => PresetView::make('Warnings')
                ->icon('heroicon-s-exclamation-circle')
                ->modifyQueryUsing(fn ($query) => $query->where('audit_status', 'warning')),
            'passed' => PresetView::make('Passed')
                ->icon('heroicon-s-check-circle')
                ->modifyQueryUsing(fn ($query) => $query->where('audit_status', 'passed')),
        ];
    }

    protected function getHeaderActions(): array
    {
        return [];
    }
}
