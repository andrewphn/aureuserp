<?php

namespace Webkul\Project\Filament\Widgets;

use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Webkul\Project\Models\CncMaterialUsage;
use Webkul\Project\Services\CncMaterialService;

/**
 * CNC Material Needs Widget
 *
 * Shows materials that need to be ordered for pending CNC programs.
 * Displays shortage quantities and links to create purchase orders.
 */
class CncMaterialNeedsWidget extends TableWidget
{
    protected static ?string $heading = 'Material Needs (CNC)';

    protected static ?int $sort = 15;

    protected int | string | array $columnSpan = 'full';

    protected static ?string $pollingInterval = '60s';

    public function table(Table $table): Table
    {
        $service = app(CncMaterialService::class);
        $needs = $service->getMaterialsToOrder();

        return $table
            ->query(
                CncMaterialUsage::query()
                    ->pending()
                    ->with(['product', 'cncProgram.project'])
                    ->select('projects_cnc_material_usage.*')
                    ->selectRaw('SUM(sheets_required) as total_sheets')
                    ->groupBy('product_id')
                    ->having('total_sheets', '>', 0)
            )
            ->columns([
                TextColumn::make('product.name')
                    ->label('Material')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->limit(40),

                TextColumn::make('total_sheets')
                    ->label('Sheets Needed')
                    ->suffix(' sheets')
                    ->sortable()
                    ->color('warning'),

                TextColumn::make('product.on_hand_quantity')
                    ->label('In Stock')
                    ->getStateUsing(fn ($record) => $record->product?->on_hand_quantity ?? 0)
                    ->suffix(' sheets')
                    ->color(fn ($state) => $state > 0 ? 'success' : 'danger'),

                TextColumn::make('shortage')
                    ->label('Shortage')
                    ->getStateUsing(fn ($record) => max(0, ($record->total_sheets ?? 0) - ($record->product?->on_hand_quantity ?? 0)))
                    ->suffix(' sheets')
                    ->color('danger')
                    ->weight('bold'),

                TextColumn::make('estimated_cost')
                    ->label('Est. Cost')
                    ->money('USD')
                    ->summarize([
                        \Filament\Tables\Columns\Summarizers\Sum::make()
                            ->money('USD'),
                    ]),

                TextColumn::make('programs_count')
                    ->label('Programs')
                    ->getStateUsing(fn ($record) => CncMaterialUsage::where('product_id', $record->product_id)->pending()->count())
                    ->badge()
                    ->color('secondary'),
            ])
            ->defaultSort('total_sheets', 'desc')
            ->emptyStateHeading('No Materials Needed')
            ->emptyStateDescription('All CNC programs have their materials in stock.')
            ->emptyStateIcon('heroicon-o-check-circle');
    }

    public static function canView(): bool
    {
        // Only show if there are pending material needs
        return CncMaterialUsage::pending()->exists();
    }
}
