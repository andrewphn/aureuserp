<?php

namespace Webkul\Project\Filament\Resources\ProjectResource\RelationManagers;

use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Webkul\Product\Models\AttributeOption;
use Webkul\Project\Models\CabinetRun;

/**
 * Cabinet Runs Relation Manager class
 *
 * @see \Filament\Resources\Resource
 */
class CabinetRunsRelationManager extends RelationManager
{
    protected static string $relationship = 'cabinetRuns';

    protected static ?string $recordTitleAttribute = 'name';

    protected static ?string $title = 'Cabinet Runs';

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
                        Select::make('room_location_id')
                            ->label('Room Location')
                            ->relationship('roomLocation', 'name', fn ($query, RelationManager $livewire) =>
                                $query->whereHas('room', fn ($q) =>
                                    $q->where('project_id', $livewire->getOwnerRecord()->id)
                                )->with('room')
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
                            ->helperText('Optional: Auto-calculated from cabinets if left blank'),

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

                        Select::make('cabinet_level')
                            ->label('Cabinet Level')
                            ->options(fn () => AttributeOption::where('attribute_id', 20)
                                ->orderBy('sort')
                                ->pluck('name', 'name')
                                ->toArray())
                            ->native(false)
                            ->searchable()
                            ->helperText('Default pricing level for cabinets in this run'),

                        Select::make('material_category')
                            ->label('Material Category')
                            ->options(fn () => AttributeOption::where('attribute_id', 18)
                                ->orderBy('sort')
                                ->pluck('name', 'name')
                                ->toArray())
                            ->native(false)
                            ->searchable()
                            ->helperText('Default material for cabinets in this run'),

                        Select::make('finish_option')
                            ->label('Finish Option')
                            ->options(fn () => AttributeOption::where('attribute_id', 19)
                                ->orderBy('sort')
                                ->pluck('name', 'name')
                                ->toArray())
                            ->native(false)
                            ->searchable()
                            ->helperText('Default finish for cabinets in this run'),

                        Textarea::make('notes')
                            ->label('Notes')
                            ->rows(3)
                            ->columnSpanFull(),

                        Section::make('Products & Materials')
                            ->schema([
                                Repeater::make('hardwareRequirements')
                                    ->relationship()
                                    ->label('')
                                    ->schema([
                                        Grid::make(3)
                                            ->schema([
                                                Select::make('product_id')
                                                    ->label('Product')
                                                    ->relationship('product', 'name')
                                                    ->searchable()
                                                    ->preload()
                                                    ->required()
                                                    ->placeholder('Search products...'),

                                                TextInput::make('quantity_required')
                                                    ->label('Qty')
                                                    ->numeric()
                                                    ->default(1)
                                                    ->minValue(1)
                                                    ->required(),

                                                TextInput::make('installation_notes')
                                                    ->label('Notes')
                                                    ->placeholder('Optional notes...'),
                                            ]),
                                    ])
                                    ->addActionLabel('Add Product')
                                    ->reorderable()
                                    ->itemLabel(fn (array $state): ?string =>
                                        $state['product_id']
                                            ? \Webkul\Product\Models\Product::find($state['product_id'])?->name . ' (x' . ($state['quantity_required'] ?? 1) . ')'
                                            : null
                                    )
                                    ->defaultItems(0)
                                    ->columnSpanFull(),
                            ])
                            ->columnSpanFull()
                            ->collapsible()
                            ->collapsed(),
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

                Tables\Columns\SelectColumn::make('cabinet_level')
                    ->label('Level')
                    ->options(fn () => AttributeOption::where('attribute_id', 20)
                        ->orderBy('sort')
                        ->pluck('name', 'name')
                        ->toArray())
                    ->toggleable(),

                Tables\Columns\SelectColumn::make('material_category')
                    ->label('Material')
                    ->options(fn () => AttributeOption::where('attribute_id', 18)
                        ->orderBy('sort')
                        ->pluck('name', 'name')
                        ->toArray())
                    ->toggleable(),

                Tables\Columns\SelectColumn::make('finish_option')
                    ->label('Finish')
                    ->options(fn () => AttributeOption::where('attribute_id', 19)
                        ->orderBy('sort')
                        ->pluck('name', 'name')
                        ->toArray())
                    ->toggleable(),

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
                \Filament\Actions\Action::make('manageOptions')
                    ->label('Add Option')
                    ->icon('heroicon-o-plus-circle')
                    ->color('gray')
                    ->form([
                        Select::make('attribute_type')
                            ->label('Option Type')
                            ->options([
                                '20' => 'Pricing Level',
                                '18' => 'Material Category',
                                '19' => 'Finish Option',
                            ])
                            ->required()
                            ->native(false),
                        TextInput::make('name')
                            ->label('Option Name')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('e.g., Level 6 - Ultra Premium ($250/LF)'),
                        TextInput::make('extra_price')
                            ->label('Extra Price')
                            ->numeric()
                            ->prefix('$')
                            ->default(0)
                            ->helperText('Additional cost for this option'),
                    ])
                    ->action(function (array $data): void {
                        AttributeOption::create([
                            'attribute_id' => $data['attribute_type'],
                            'name' => $data['name'],
                            'extra_price' => $data['extra_price'] ?? 0,
                            'creator_id' => auth()->id(),
                        ]);

                        Notification::make()
                            ->title('Option added successfully')
                            ->success()
                            ->send();
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
            ->modifyQueryUsing(function ($query) use ($table) {
                $livewire = $table->getLivewire();
                $projectId = $livewire->getOwnerRecord()->id;

                // Completely bypass the broken hasManyThrough relationship
                // and build a proper 3-level join query
                return CabinetRun::query()
                    ->join('projects_room_locations', 'projects_cabinet_runs.room_location_id', '=', 'projects_room_locations.id')
                    ->join('projects_rooms', 'projects_room_locations.room_id', '=', 'projects_rooms.id')
                    ->where('projects_rooms.project_id', $projectId)
                    ->select('projects_cabinet_runs.*')
                    ->with(['roomLocation.room'])
                    ->withCount('cabinets');
            });
    }
}
