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
use Webkul\Project\Models\RoomLocation;

/**
 * Room Locations Relation Manager class
 *
 * @see \Filament\Resources\Resource
 */
class RoomLocationsRelationManager extends RelationManager
{
    protected static string $relationship = 'roomLocations';

    protected static ?string $recordTitleAttribute = 'name';

    protected static ?string $title = 'Room Locations';

    /**
     * Define the form schema
     *
     * @param Schema $schema
     * @return Schema
     */
    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Grid::make(2)
                    ->schema([
                        Select::make('room_id')
                            ->label('Room')
                            ->relationship('room', 'name')
                            ->required()
                            ->searchable()
                            ->preload()
                            ->createOptionForm([
                                TextInput::make('name')
                                    ->required()
                                    ->maxLength(255),
                                Select::make('room_type')
                                    ->options([
                                        'kitchen' => 'Kitchen',
                                        'pantry' => 'Pantry',
                                        'laundry' => 'Laundry',
                                        'bathroom' => 'Bathroom',
                                        'mudroom' => 'Mudroom',
                                        'office' => 'Office',
                                        'bedroom' => 'Bedroom',
                                        'closet' => 'Closet',
                                    ])
                                    ->required(),
                            ]),

                        TextInput::make('name')
                            ->label('Location Name')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('e.g., North Wall, Island, Wet Bar'),

                        Select::make('location_type')
                            ->label('Location Type')
                            ->options([
                                'wall' => 'Wall',
                                'island' => 'Island',
                                'peninsula' => 'Peninsula',
                                'corner' => 'Corner',
                                'alcove' => 'Alcove',
                            ])
                            ->native(false),

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

    /**
     * Define the table schema
     *
     * @param Table $table
     * @return Table
     */
    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                Tables\Columns\TextColumn::make('room.name')
                    ->label('Room')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('name')
                    ->label('Location Name')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('location_type')
                    ->label('Type')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => ucfirst($state)),

                Tables\Columns\TextColumn::make('cabinet_runs_count')
                    ->label('Cabinet Runs')
                    ->counts('cabinetRuns')
                    ->badge()
                    ->color('info'),

                Tables\Columns\TextColumn::make('sort_order')
                    ->label('Order')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('room_id')
                    ->label('Room')
                    ->relationship('room', 'name'),

                Tables\Filters\SelectFilter::make('location_type')
                    ->label('Location Type')
                    ->options([
                        'wall' => 'Wall',
                        'island' => 'Island',
                        'peninsula' => 'Peninsula',
                        'corner' => 'Corner',
                        'alcove' => 'Alcove',
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
            ->modifyQueryUsing(fn ($query) => $query->with('room')->withCount('cabinetRuns'));
    }
}
