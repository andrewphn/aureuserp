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
use Webkul\Project\Models\Room;

class RoomsRelationManager extends RelationManager
{
    protected static string $relationship = 'rooms';

    protected static ?string $recordTitleAttribute = 'name';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Grid::make(2)
                    ->schema([
                        TextInput::make('name')
                            ->label('Room Name')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('e.g., Master Kitchen, Guest Bathroom'),

                        Select::make('room_type')
                            ->label('Room Type')
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
                            ->required()
                            ->searchable()
                            ->native(false),

                        TextInput::make('floor_number')
                            ->label('Floor Number')
                            ->placeholder('e.g., 1, 2, Basement'),

                        TextInput::make('sort_order')
                            ->label('Sort Order')
                            ->numeric()
                            ->default(0)
                            ->helperText('Order in which rooms appear'),

                        TextInput::make('pdf_page_number')
                            ->label('PDF Page Number')
                            ->numeric()
                            ->helperText('Page number where this room appears in plans'),

                        TextInput::make('pdf_room_label')
                            ->label('PDF Room Label')
                            ->maxLength(255)
                            ->placeholder('e.g., K1, BR2'),

                        TextInput::make('pdf_detail_number')
                            ->label('PDF Detail Number')
                            ->maxLength(255),

                        Textarea::make('pdf_notes')
                            ->label('PDF Notes')
                            ->rows(2)
                            ->columnSpanFull(),

                        Textarea::make('notes')
                            ->label('Internal Notes')
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
                Tables\Columns\TextColumn::make('name')
                    ->label('Room Name')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('room_type')
                    ->label('Type')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'kitchen' => 'blue',
                        'pantry' => 'green',
                        'laundry' => 'amber',
                        'bathroom' => 'red',
                        'mudroom' => 'purple',
                        'office' => 'pink',
                        'bedroom' => 'cyan',
                        'closet' => 'lime',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => ucfirst($state))
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('floor_number')
                    ->label('Floor')
                    ->sortable(),

                Tables\Columns\TextColumn::make('locations_count')
                    ->label('Locations')
                    ->counts('locations')
                    ->badge()
                    ->color('info'),

                Tables\Columns\TextColumn::make('cabinets_count')
                    ->label('Cabinets')
                    ->counts('cabinets')
                    ->badge()
                    ->color('success'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('room_type')
                    ->label('Room Type')
                    ->options([
                        'kitchen' => 'Kitchen',
                        'pantry' => 'Pantry',
                        'laundry' => 'Laundry',
                        'bathroom' => 'Bathroom',
                        'mudroom' => 'Mudroom',
                        'office' => 'Office',
                        'bedroom' => 'Bedroom',
                        'closet' => 'Closet',
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
            ->modifyQueryUsing(fn ($query) => $query->withCount(['locations', 'cabinets']));
    }
}
