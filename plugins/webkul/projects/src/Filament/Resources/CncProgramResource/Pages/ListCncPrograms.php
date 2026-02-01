<?php

namespace Webkul\Project\Filament\Resources\CncProgramResource\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Webkul\Project\Filament\Resources\CncProgramResource;
use Webkul\TableViews\Filament\Components\PresetView;
use Webkul\TableViews\Filament\Concerns\HasTableViews;

/**
 * List CNC Programs page
 */
class ListCncPrograms extends ListRecords
{
    use HasTableViews;

    protected static string $resource = CncProgramResource::class;

    public function getPresetTableViews(): array
    {
        return [
            'pending' => PresetView::make('Pending Programs')
                ->icon('heroicon-s-clock')
                ->favorite()
                ->modifyQueryUsing(fn ($query) => $query->where('status', 'pending')),

            'in_progress' => PresetView::make('In Progress')
                ->icon('heroicon-s-play')
                ->favorite()
                ->modifyQueryUsing(fn ($query) => $query->where('status', 'in_progress')),

            'complete' => PresetView::make('Completed')
                ->icon('heroicon-s-check-circle')
                ->modifyQueryUsing(fn ($query) => $query->where('status', 'complete')),

            'pending_material' => PresetView::make('Pending Material')
                ->icon('heroicon-s-exclamation-triangle')
                ->modifyQueryUsing(fn ($query) => $query->whereHas('parts', function ($q) {
                    $q->where('material_status', 'pending_material');
                })),
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('New CNC Program')
                ->icon('heroicon-o-plus-circle'),
        ];
    }
}
