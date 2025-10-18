<?php

namespace Webkul\Project\Filament\Resources\ProjectResource\RelationManagers;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Webkul\Project\Models\CabinetRun;

class CabinetRunsRelationManager extends RelationManager
{
    protected static string $relationship = 'cabinetRuns';

    protected static ?string $recordTitleAttribute = 'name';

    protected static ?string $title = 'Cabinet Runs';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Grid::make(2)
                    ->schema([
                        Select::make('room_location_id')
                            ->label('Room Location')
                            ->relationship('roomLocation', 'name', fn ($query, RelationManager $livewire) =>
                                $query->where('project_id', $livewire->getOwnerRecord()->id)
                                    ->with('room')
                            )
                            ->required()
                            ->searchable()
                            ->preload()
                            ->getOptionLabelFromRecordUsing(fn ($record) =>
                                $record->room->name . ' - ' . $record->name
                            ),

                        TextInput::make('name')
                            ->label('Run Name')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('e.g., Base Run 1, Upper Run 2'),

                        Select::make('run_type')
                            ->label('Run Type')
                            ->options([
                                'base' => 'Base Cabinets',
                                'wall' => 'Wall Cabinets',
                                'tall' => 'Tall Cabinets',
                                'specialty' => 'Specialty',
                            ])
                            ->required()
                            ->native(false),

                        TextInput::make('total_linear_feet')
                            ->label('Total Linear Feet')
                            ->suffix('ft')
                            ->numeric()
                            ->step(0.01)
                            ->helperText('Auto-calculated from cabinets if left blank'),

                        TextInput::make('start_wall_measurement')
                            ->label('Start Wall Measurement')
                            ->suffix('in')
                            ->numeric()
                            ->step(0.125)
                            ->helperText('Measurement from reference point on wall'),

                        TextInput::make('end_wall_measurement')
                            ->label('End Wall Measurement')
                            ->suffix('in')
                            ->numeric()
                            ->step(0.125)
                            ->helperText('Measurement at end of cabinet run'),

                        TextInput::make('sort_order')
                            ->label('Sort Order')
                            ->numeric()
                            ->default(0),

                        Textarea::make('notes')
                            ->label('Notes')
                            ->rows(3)
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                Tables\Columns\TextColumn::make('roomLocation.room.name')
                    ->label('Room')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('roomLocation.name')
                    ->label('Location')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('name')
                    ->label('Run Name')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('run_type')
                    ->label('Type')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'base' => 'blue',
                        'wall' => 'green',
                        'tall' => 'purple',
                        'specialty' => 'amber',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => ucfirst($state)),

                Tables\Columns\TextColumn::make('total_linear_feet')
                    ->label('Total LF')
                    ->suffix(' ft')
                    ->numeric(decimalPlaces: 2)
                    ->sortable(),

                Tables\Columns\TextColumn::make('cabinets_count')
                    ->label('Cabinets')
                    ->counts('cabinets')
                    ->badge()
                    ->color('success'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('run_type')
                    ->label('Run Type')
                    ->options([
                        'base' => 'Base Cabinets',
                        'wall' => 'Wall Cabinets',
                        'tall' => 'Tall Cabinets',
                        'specialty' => 'Specialty',
                    ]),
            ])
            ->headerActions([
                \Filament\Actions\CreateAction::make()
                    ->mutateFormDataUsing(function (array $data): array {
                        $data['creator_id'] = auth()->id();
                        return $data;
                    }),
            ])
            ->actions([
                \Filament\Actions\EditAction::make(),
                \Filament\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                \Filament\Actions\BulkActionGroup::make([
                    \Filament\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('sort_order')
            ->reorderable('sort_order')
            ->modifyQueryUsing(fn ($query) => $query->with(['roomLocation.room'])->withCount('cabinets'));
    }
}
