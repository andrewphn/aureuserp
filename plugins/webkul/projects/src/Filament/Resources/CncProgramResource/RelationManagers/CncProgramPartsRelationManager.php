<?php

namespace Webkul\Project\Filament\Resources\CncProgramResource\RelationManagers;

use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;
use Webkul\Project\Models\CncProgramPart;
use Webkul\Security\Models\User;

/**
 * CNC Program Parts Relation Manager
 *
 * Manages individual NC files/parts within a CNC program
 */
class CncProgramPartsRelationManager extends RelationManager
{
    protected static string $relationship = 'parts';

    protected static ?string $recordTitleAttribute = 'file_name';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Grid::make(2)
                    ->schema([
                        TextInput::make('file_name')
                            ->label('File Name')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('e.g., 2026-01-15_RiftWO_01_1-5-Profile.NC'),

                        TextInput::make('file_path')
                            ->label('File Path')
                            ->maxLength(500),

                        TextInput::make('sheet_number')
                            ->label('Sheet Number')
                            ->numeric()
                            ->minValue(1),

                        Select::make('operation_type')
                            ->label('Operation Type')
                            ->options(CncProgramPart::getOperationTypes())
                            ->native(false),

                        TextInput::make('tool')
                            ->label('Tool')
                            ->maxLength(100)
                            ->placeholder('e.g., 1/4" Spiral'),

                        TextInput::make('file_size')
                            ->label('File Size (bytes)')
                            ->numeric(),

                        Select::make('status')
                            ->label('Status')
                            ->options(CncProgramPart::getStatusOptions())
                            ->default(CncProgramPart::STATUS_PENDING)
                            ->required()
                            ->native(false),

                        Select::make('material_status')
                            ->label('Material Status')
                            ->options(CncProgramPart::getMaterialStatusOptions())
                            ->default(CncProgramPart::MATERIAL_READY)
                            ->native(false),

                        Select::make('operator_id')
                            ->label('Operator')
                            ->relationship('operator', 'name')
                            ->searchable()
                            ->preload(),

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
            ->recordTitleAttribute('file_name')
            ->columns([
                TextColumn::make('file_name')
                    ->label('File Name')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->limit(40),

                TextColumn::make('sheet_number')
                    ->label('Sheet')
                    ->sortable()
                    ->badge()
                    ->color('info'),

                TextColumn::make('operation_type')
                    ->label('Operation')
                    ->formatStateUsing(fn (?string $state): string => $state ? ucfirst($state) : '-')
                    ->badge()
                    ->color(fn (?string $state): string => match ($state) {
                        'profile' => 'blue',
                        'drilling' => 'amber',
                        'pocket' => 'green',
                        'groove' => 'purple',
                        'shelf_pins' => 'pink',
                        'slide_holes' => 'cyan',
                        default => 'gray',
                    }),

                TextColumn::make('tool')
                    ->label('Tool')
                    ->limit(20),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pending' => 'gray',
                        'running' => 'info',
                        'complete' => 'success',
                        'error' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => ucfirst($state)),

                TextColumn::make('material_status')
                    ->label('Material')
                    ->badge()
                    ->color(fn (?string $state): string => match ($state) {
                        'ready' => 'success',
                        'pending_material' => 'danger',
                        'ordered' => 'warning',
                        'received' => 'info',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                        'pending_material' => 'Pending',
                        default => ucfirst($state ?? 'Ready'),
                    }),

                TextColumn::make('operator.name')
                    ->label('Operator')
                    ->limit(15)
                    ->placeholder('-'),

                TextColumn::make('run_duration_minutes')
                    ->label('Duration')
                    ->suffix(' min')
                    ->getStateUsing(fn (CncProgramPart $record) => $record->run_duration_minutes)
                    ->placeholder('-'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options(CncProgramPart::getStatusOptions()),

                Tables\Filters\SelectFilter::make('material_status')
                    ->options(CncProgramPart::getMaterialStatusOptions()),

                Tables\Filters\SelectFilter::make('operation_type')
                    ->options(CncProgramPart::getOperationTypes()),
            ])
            ->headerActions([
                CreateAction::make()
                    ->mutateFormDataUsing(function (array $data): array {
                        if (!isset($data['quantity'])) {
                            $data['quantity'] = 1;
                        }
                        return $data;
                    }),
            ])
            ->actions([
                Action::make('start')
                    ->label('Start')
                    ->icon('heroicon-o-play')
                    ->color('info')
                    ->requiresConfirmation()
                    ->visible(fn (CncProgramPart $record): bool => $record->status === CncProgramPart::STATUS_PENDING)
                    ->action(function (CncProgramPart $record): void {
                        $record->startRunning();
                        Notification::make()
                            ->title('Part Started')
                            ->success()
                            ->send();
                    }),

                Action::make('complete')
                    ->label('Complete')
                    ->icon('heroicon-o-check')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn (CncProgramPart $record): bool => $record->status === CncProgramPart::STATUS_RUNNING)
                    ->action(function (CncProgramPart $record): void {
                        $record->markComplete();
                        Notification::make()
                            ->title('Part Completed')
                            ->success()
                            ->send();
                    }),

                EditAction::make(),
                DeleteAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    BulkAction::make('mark_complete')
                        ->label('Mark Complete')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->requiresConfirmation()
                        ->action(function (Collection $records): void {
                            $completed = 0;
                            foreach ($records as $record) {
                                if ($record->status === CncProgramPart::STATUS_RUNNING) {
                                    $record->markComplete();
                                    $completed++;
                                }
                            }
                            Notification::make()
                                ->title("{$completed} parts marked complete")
                                ->success()
                                ->send();
                        }),

                    BulkAction::make('assign_operator')
                        ->label('Assign Operator')
                        ->icon('heroicon-o-user')
                        ->form([
                            Select::make('operator_id')
                                ->label('Operator')
                                ->options(User::pluck('name', 'id'))
                                ->searchable()
                                ->required(),
                        ])
                        ->action(function (Collection $records, array $data): void {
                            $records->each(fn ($record) => $record->update(['operator_id' => $data['operator_id']]));
                            Notification::make()
                                ->title('Operator assigned')
                                ->success()
                                ->send();
                        }),

                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('sheet_number')
            ->reorderable(false);
    }
}
