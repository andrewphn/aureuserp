<?php

namespace Webkul\Project\Filament\Resources\ProjectResource\RelationManagers;

use Filament\Forms\Components\Get;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Set;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Webkul\Project\Models\CabinetSpecification;

class CabinetsRelationManager extends RelationManager
{
    protected static string $relationship = 'cabinets';

    protected static ?string $recordTitleAttribute = 'cabinet_number';

    protected static ?string $title = 'Cabinet Specifications';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Grid::make(3)
                    ->schema([
                        Select::make('room_id')
                            ->label('Room')
                            ->relationship('room', 'name', fn ($query, RelationManager $livewire) =>
                                $query->where('project_id', $livewire->getOwnerRecord()->id)
                            )
                            ->required()
                            ->searchable()
                            ->preload()
                            ->live()
                            ->columnSpan(1),

                        Select::make('cabinet_run_id')
                            ->label('Cabinet Run')
                            ->relationship('cabinetRun', 'name', fn ($query, Get $get, RelationManager $livewire) =>
                                $query->whereHas('roomLocation', function ($q) use ($get, $livewire) {
                                    $q->whereHas('room', fn ($q2) =>
                                        $q2->where('project_id', $livewire->getOwnerRecord()->id)
                                    );

                                    if ($get('room_id')) {
                                        $q->where('room_id', $get('room_id'));
                                    }
                                })
                            )
                            ->searchable()
                            ->preload()
                            ->disabled(fn (Get $get) => !$get('room_id'))
                            ->columnSpan(2),

                        TextInput::make('cabinet_number')
                            ->label('Cabinet Number')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('e.g., B1, U2, P1')
                            ->columnSpan(1),

                        TextInput::make('position_in_run')
                            ->label('Position in Run')
                            ->numeric()
                            ->default(1)
                            ->minValue(1)
                            ->columnSpan(1),

                        TextInput::make('wall_position_start_inches')
                            ->label('Wall Position Start')
                            ->suffix('in')
                            ->numeric()
                            ->step(0.125)
                            ->columnSpan(1),

                        Section::make('Dimensions')
                            ->schema([
                                Grid::make(4)
                                    ->schema([
                                        TextInput::make('length_inches')
                                            ->label('Length')
                                            ->suffix('in')
                                            ->numeric()
                                            ->step(0.125)
                                            ->live(onBlur: true)
                                            ->afterStateUpdated(function (Set $set, $state) {
                                                if ($state) {
                                                    $set('linear_feet', round($state / 12, 2));
                                                }
                                            })
                                            ->helperText('Optional during annotation stage'),

                                        TextInput::make('width_inches')
                                            ->label('Width')
                                            ->suffix('in')
                                            ->numeric()
                                            ->step(0.125),

                                        TextInput::make('depth_inches')
                                            ->label('Depth')
                                            ->suffix('in')
                                            ->numeric()
                                            ->step(0.125),

                                        TextInput::make('height_inches')
                                            ->label('Height')
                                            ->suffix('in')
                                            ->numeric()
                                            ->step(0.125),
                                    ]),
                            ])
                            ->columnSpanFull(),

                        Section::make('Pricing')
                            ->schema([
                                Grid::make(4)
                                    ->schema([
                                        TextInput::make('linear_feet')
                                            ->label('Linear Feet')
                                            ->suffix('ft')
                                            ->numeric()
                                            ->step(0.01)
                                            ->disabled()
                                            ->dehydrated()
                                            ->helperText('Auto-calculated from length'),

                                        TextInput::make('quantity')
                                            ->label('Quantity')
                                            ->required()
                                            ->numeric()
                                            ->default(1)
                                            ->minValue(1),

                                        TextInput::make('unit_price_per_lf')
                                            ->label('Price per LF')
                                            ->prefix('$')
                                            ->numeric()
                                            ->step(0.01),

                                        TextInput::make('total_price')
                                            ->label('Total Price')
                                            ->prefix('$')
                                            ->numeric()
                                            ->step(0.01)
                                            ->helperText('Auto-calculated if left blank'),
                                    ]),
                            ])
                            ->columnSpanFull(),

                        Textarea::make('hardware_notes')
                            ->label('Hardware Notes')
                            ->rows(2)
                            ->columnSpanFull(),

                        Textarea::make('custom_modifications')
                            ->label('Custom Modifications')
                            ->rows(2)
                            ->columnSpanFull(),

                        Textarea::make('shop_notes')
                            ->label('Shop Notes')
                            ->rows(2)
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('cabinet_number')
            ->columns([
                Tables\Columns\TextColumn::make('cabinet_number')
                    ->label('Cabinet #')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('room.name')
                    ->label('Room')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('cabinetRun.name')
                    ->label('Run')
                    ->searchable()
                    ->sortable()
                    ->placeholder('—'),

                Tables\Columns\TextColumn::make('cabinetRun.run_type')
                    ->label('Type')
                    ->badge()
                    ->color(fn (?string $state): string => match ($state) {
                        'base' => 'blue',
                        'wall' => 'green',
                        'tall' => 'purple',
                        'specialty' => 'amber',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (?string $state): string => $state ? ucfirst($state) : '—'),

                Tables\Columns\TextColumn::make('material_category')
                    ->label('Material')
                    ->badge()
                    ->color('info')
                    ->formatStateUsing(fn (?string $state): ?string => match($state) {
                        'paint_grade' => 'Paint',
                        'stain_grade' => 'Stain',
                        'premium' => 'Premium',
                        'custom_exotic' => 'Custom',
                        default => null,
                    })
                    ->toggleable(),

                Tables\Columns\TextColumn::make('cabinet_level')
                    ->label('Level')
                    ->badge()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('finish_option')
                    ->label('Finish')
                    ->badge()
                    ->color('warning')
                    ->formatStateUsing(fn (?string $state): ?string => match($state) {
                        'unfinished' => 'Unfinished',
                        'natural_stain' => 'Natural',
                        'custom_stain' => 'Custom',
                        'paint_finish' => 'Paint',
                        'clear_coat' => 'Clear',
                        default => null,
                    })
                    ->toggleable(),

                Tables\Columns\TextColumn::make('length_inches')
                    ->label('Length')
                    ->suffix(' in')
                    ->numeric(decimalPlaces: 2)
                    ->sortable(),

                Tables\Columns\TextColumn::make('linear_feet')
                    ->label('Linear Feet')
                    ->suffix(' ft')
                    ->numeric(decimalPlaces: 2)
                    ->sortable(),

                Tables\Columns\TextColumn::make('quantity')
                    ->label('Qty')
                    ->badge()
                    ->color('info'),

                Tables\Columns\TextColumn::make('total_price')
                    ->label('Total Price')
                    ->prefix('$')
                    ->numeric(decimalPlaces: 2)
                    ->sortable()
                    ->summarize([
                        Tables\Columns\Summarizers\Sum::make()
                            ->money('USD'),
                    ]),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('room_id')
                    ->label('Room')
                    ->relationship('room', 'name'),

                Tables\Filters\SelectFilter::make('cabinet_run_id')
                    ->label('Cabinet Run')
                    ->relationship('cabinetRun', 'name'),
            ])
            ->headerActions([
                \Filament\Actions\CreateAction::make()
                    ->mutateFormDataUsing(function (array $data, RelationManager $livewire): array {
                        $data['creator_id'] = auth()->id();
                        $data['project_id'] = $livewire->getOwnerRecord()->id;
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
            ->defaultSort('cabinet_number')
            ->modifyQueryUsing(function ($query) use ($table) {
                $livewire = $table->getLivewire();
                $projectId = $livewire->getOwnerRecord()->id;

                // Bypass the broken direct relationship and use proper filtering
                return CabinetSpecification::query()
                    ->whereHas('room', function ($q) use ($projectId) {
                        $q->where('project_id', $projectId);
                    })
                    ->with(['room', 'cabinetRun']);
            });
    }
}
