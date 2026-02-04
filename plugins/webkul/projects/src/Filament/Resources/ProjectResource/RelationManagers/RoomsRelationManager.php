<?php

namespace Webkul\Project\Filament\Resources\ProjectResource\RelationManagers;

use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Webkul\Project\Models\Room;

/**
 * Rooms Relation Manager class
 *
 * @see \Filament\Resources\Resource
 */
class RoomsRelationManager extends RelationManager
{
    protected static string $relationship = 'rooms';

    protected static ?string $recordTitleAttribute = 'name';

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

                        Section::make('Linear Feet by Tier')
                            ->description('Enter linear feet for each pricing tier')
                            ->schema([
                                Grid::make(5)
                                    ->schema([
                                        TextInput::make('total_linear_feet_tier_1')
                                            ->label('Tier 1')
                                            ->numeric()
                                            ->suffix('LF')
                                            ->placeholder('0'),
                                        TextInput::make('total_linear_feet_tier_2')
                                            ->label('Tier 2')
                                            ->numeric()
                                            ->suffix('LF')
                                            ->placeholder('0'),
                                        TextInput::make('total_linear_feet_tier_3')
                                            ->label('Tier 3')
                                            ->numeric()
                                            ->suffix('LF')
                                            ->placeholder('0'),
                                        TextInput::make('total_linear_feet_tier_4')
                                            ->label('Tier 4')
                                            ->numeric()
                                            ->suffix('LF')
                                            ->placeholder('0'),
                                        TextInput::make('total_linear_feet_tier_5')
                                            ->label('Tier 5')
                                            ->numeric()
                                            ->suffix('LF')
                                            ->placeholder('0'),
                                    ]),
                            ])
                            ->columnSpanFull()
                            ->collapsible(),

                        Section::make('Pricing & Materials')
                            ->schema([
                                Grid::make(3)
                                    ->schema([
                                        Select::make('cabinet_level')
                                            ->label('Cabinet Level')
                                            ->options([
                                                1 => 'Level 1 - Basic',
                                                2 => 'Level 2 - Standard',
                                                3 => 'Level 3 - Enhanced',
                                                4 => 'Level 4 - Premium',
                                                5 => 'Level 5 - Custom',
                                            ])
                                            ->native(false),
                                        Select::make('material_category')
                                            ->label('Material Category')
                                            ->options([
                                                'paint_grade' => 'Paint Grade',
                                                'stain_grade' => 'Stain Grade',
                                                'natural_wood' => 'Natural Wood',
                                                'laminate' => 'Laminate',
                                                'thermofoil' => 'Thermofoil',
                                            ])
                                            ->native(false),
                                        TextInput::make('estimated_cabinet_value')
                                            ->label('Estimated Value')
                                            ->numeric()
                                            ->prefix('$')
                                            ->placeholder('Auto-calculated'),
                                    ]),
                            ])
                            ->columnSpanFull()
                            ->collapsible(),

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

                Tables\Columns\TextColumn::make('total_lf')
                    ->label('Linear Feet')
                    ->getStateUsing(fn (Room $record): float =>
                        ($record->total_linear_feet_tier_1 ?? 0) +
                        ($record->total_linear_feet_tier_2 ?? 0) +
                        ($record->total_linear_feet_tier_3 ?? 0) +
                        ($record->total_linear_feet_tier_4 ?? 0) +
                        ($record->total_linear_feet_tier_5 ?? 0)
                    )
                    ->numeric(decimalPlaces: 1)
                    ->suffix(' LF')
                    ->color('primary'),

                Tables\Columns\TextColumn::make('estimated_cabinet_value')
                    ->label('Est. Value')
                    ->money('USD')
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
