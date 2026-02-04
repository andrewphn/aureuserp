<?php

namespace Webkul\Project\Filament\Resources\CncProgramResource\RelationManagers;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\BulkAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;
use Webkul\Project\Models\CncCutPart;

/**
 * CNC Cut Parts Relation Manager
 *
 * Manages individual cabinet parts on a CNC sheet with QA workflow.
 * Used on CncProgramPart (sheet) view pages.
 */
class CncCutPartsRelationManager extends RelationManager
{
    protected static string $relationship = 'cutParts';

    protected static ?string $recordTitleAttribute = 'part_label';

    protected static ?string $title = 'Cut Parts';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Grid::make(2)->schema([
                TextInput::make('part_label')
                    ->label('Part Label')
                    ->required()
                    ->maxLength(50)
                    ->placeholder('e.g., BS-1, DR-2'),

                Select::make('part_type')
                    ->label('Part Type')
                    ->options(CncCutPart::getPartTypeOptions())
                    ->searchable(),

                TextInput::make('description')
                    ->label('Description')
                    ->maxLength(255)
                    ->columnSpanFull(),

                TextInput::make('part_width')
                    ->label('Width (inches)')
                    ->numeric()
                    ->step(0.001),

                TextInput::make('part_height')
                    ->label('Height (inches)')
                    ->numeric()
                    ->step(0.001),

                TextInput::make('part_thickness')
                    ->label('Thickness (inches)')
                    ->numeric()
                    ->step(0.001),

                Select::make('status')
                    ->label('Status')
                    ->options(CncCutPart::getStatusOptions())
                    ->default(CncCutPart::STATUS_PENDING)
                    ->required(),

                Textarea::make('notes')
                    ->label('Notes')
                    ->rows(2)
                    ->columnSpanFull(),
            ]),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('part_label')
                    ->label('Label')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->size('lg'),

                TextColumn::make('part_type_name')
                    ->label('Type')
                    ->sortable(),

                TextColumn::make('dimensions')
                    ->label('Size')
                    ->placeholder('—'),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pending' => 'gray',
                        'cut' => 'info',
                        'passed' => 'success',
                        'failed' => 'danger',
                        'recut_needed' => 'warning',
                        'scrapped' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => CncCutPart::getStatusOptions()[$state] ?? ucfirst($state)),

                TextColumn::make('failure_reason')
                    ->label('Failure')
                    ->placeholder('—')
                    ->formatStateUsing(fn (?string $state): string => $state ? (CncCutPart::getFailureReasons()[$state] ?? $state) : '—')
                    ->color('danger')
                    ->visible(fn ($record) => $record?->status === 'failed'),

                TextColumn::make('inspector.name')
                    ->label('Inspected By')
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('inspected_at')
                    ->label('Inspected')
                    ->dateTime('M j, g:i A')
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('recut_count')
                    ->label('Recuts')
                    ->badge()
                    ->color('warning')
                    ->visible(fn ($record) => ($record?->recut_count ?? 0) > 0),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options(CncCutPart::getStatusOptions())
                    ->multiple(),

                SelectFilter::make('part_type')
                    ->options(CncCutPart::getPartTypeOptions()),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('Add Part'),
            ])
            ->recordActions([
                // QA Pass action
                Action::make('pass')
                    ->label('Pass')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn (CncCutPart $record) => in_array($record->status, ['pending', 'cut']))
                    ->requiresConfirmation()
                    ->form([
                        Textarea::make('notes')
                            ->label('Notes (optional)')
                            ->rows(2),
                    ])
                    ->action(function (CncCutPart $record, array $data) {
                        $record->passInspection(notes: $data['notes'] ?? null);
                        Notification::make()
                            ->title('Part Passed QA')
                            ->body("Part {$record->part_label} passed inspection")
                            ->success()
                            ->send();
                    }),

                // QA Fail action
                Action::make('fail')
                    ->label('Fail')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn (CncCutPart $record) => in_array($record->status, ['pending', 'cut']))
                    ->form([
                        Select::make('failure_reason')
                            ->label('Failure Reason')
                            ->options(CncCutPart::getFailureReasons())
                            ->required()
                            ->native(false),

                        Textarea::make('notes')
                            ->label('Details')
                            ->rows(2)
                            ->placeholder('Describe the issue...'),
                    ])
                    ->action(function (CncCutPart $record, array $data) {
                        $record->failInspection($data['failure_reason'], notes: $data['notes'] ?? null);
                        Notification::make()
                            ->title('Part Failed QA')
                            ->body("Part {$record->part_label} marked as failed")
                            ->warning()
                            ->send();
                    }),

                // Recut action
                Action::make('recut')
                    ->label('Recut')
                    ->icon('heroicon-o-arrow-path')
                    ->color('warning')
                    ->visible(fn (CncCutPart $record) => in_array($record->status, ['failed', 'scrapped']))
                    ->requiresConfirmation()
                    ->modalHeading('Create Recut')
                    ->modalDescription('This will create a new part to be cut and mark the original as needing recut.')
                    ->action(function (CncCutPart $record) {
                        $recut = $record->createRecut();
                        Notification::make()
                            ->title('Recut Created')
                            ->body("Recut part #{$recut->id} created for {$record->part_label}")
                            ->success()
                            ->send();
                    }),

                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                // Bulk pass
                BulkAction::make('bulk_pass')
                    ->label('Pass All')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->action(function (Collection $records) {
                        $count = 0;
                        foreach ($records as $record) {
                            if (in_array($record->status, ['pending', 'cut'])) {
                                $record->passInspection();
                                $count++;
                            }
                        }
                        Notification::make()
                            ->title('Parts Passed QA')
                            ->body("{$count} parts passed inspection")
                            ->success()
                            ->send();
                    }),

                // Bulk fail
                BulkAction::make('bulk_fail')
                    ->label('Fail All')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->form([
                        Select::make('failure_reason')
                            ->label('Failure Reason')
                            ->options(CncCutPart::getFailureReasons())
                            ->required()
                            ->native(false),

                        Textarea::make('notes')
                            ->label('Details')
                            ->rows(2),
                    ])
                    ->action(function (Collection $records, array $data) {
                        $count = 0;
                        foreach ($records as $record) {
                            if (in_array($record->status, ['pending', 'cut'])) {
                                $record->failInspection($data['failure_reason'], notes: $data['notes'] ?? null);
                                $count++;
                            }
                        }
                        Notification::make()
                            ->title('Parts Failed QA')
                            ->body("{$count} parts marked as failed")
                            ->warning()
                            ->send();
                    }),

                Tables\Actions\DeleteBulkAction::make(),
            ])
            ->defaultSort('part_label');
    }
}
