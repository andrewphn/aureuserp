<?php

namespace Webkul\Project\Filament\Widgets;

use BezhanSalleh\FilamentShield\Traits\HasWidgetShield;
use Filament\Tables;
use Filament\Tables\Actions\Action as TableAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Webkul\Project\Models\CncProgramPart;

/**
 * CNC Queue Widget
 *
 * Shows pending and in-progress CNC parts across all projects.
 */
class CncQueueWidget extends BaseWidget
{
    use HasWidgetShield;

    protected static ?string $heading = 'CNC Queue';

    protected static ?int $sort = 5;

    protected int|string|array $columnSpan = 'full';

    protected ?string $pollingInterval = '30s';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                CncProgramPart::query()
                    ->with(['cncProgram.project', 'operator'])
                    ->whereIn('status', [CncProgramPart::STATUS_PENDING, CncProgramPart::STATUS_RUNNING])
                    ->orderByRaw("CASE WHEN status = 'running' THEN 0 ELSE 1 END")
                    ->orderBy('run_at')
                    ->orderBy('created_at')
                    ->limit(15)
            )
            ->columns([
                TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pending' => 'gray',
                        'running' => 'info',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => ucfirst($state)),

                TextColumn::make('file_name')
                    ->label('Part')
                    ->searchable()
                    ->limit(30),

                TextColumn::make('cncProgram.project.name')
                    ->label('Project')
                    ->limit(20),

                TextColumn::make('cncProgram.name')
                    ->label('Program')
                    ->limit(20),

                TextColumn::make('cncProgram.material_code')
                    ->label('Material')
                    ->badge()
                    ->color(fn (?string $state): string => match ($state) {
                        'FL' => 'amber',
                        'PreFin' => 'blue',
                        'RiftWOPly' => 'green',
                        'MDF_RiftWO' => 'purple',
                        'Medex' => 'pink',
                        default => 'gray',
                    }),

                TextColumn::make('sheet_number')
                    ->label('Sheet'),

                TextColumn::make('operator.name')
                    ->label('Operator')
                    ->placeholder('-'),

                TextColumn::make('run_duration_minutes')
                    ->label('Duration')
                    ->suffix(' min')
                    ->getStateUsing(fn (CncProgramPart $record) => $record->status === 'running' ? $record->run_duration_minutes : null)
                    ->placeholder('-'),
            ])
            ->actions([
                TableAction::make('view_program')
                    ->label('View')
                    ->icon('heroicon-o-eye')
                    ->url(fn (CncProgramPart $record) => route(
                        'filament.admin.resources.project/cnc-programs.view',
                        $record->cnc_program_id
                    )),
            ])
            ->paginated(false)
            ->emptyStateHeading('No parts in queue')
            ->emptyStateDescription('All CNC parts are complete!')
            ->emptyStateIcon('heroicon-o-check-circle');
    }
}
