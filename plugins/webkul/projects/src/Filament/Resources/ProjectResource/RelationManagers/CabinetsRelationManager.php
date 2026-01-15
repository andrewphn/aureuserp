<?php

namespace Webkul\Project\Filament\Resources\ProjectResource\RelationManagers;

use Filament\Forms\Components\Repeater;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Webkul\Product\Models\AttributeOption;
use Webkul\Project\Models\Cabinet;

/**
 * Cabinets Relation Manager class
 *
 * @see \Filament\Resources\Resource
 */
class CabinetsRelationManager extends RelationManager
{
    protected static string $relationship = 'cabinets';

    protected static ?string $recordTitleAttribute = 'cabinet_number';

    protected static ?string $title = 'Cabinets';

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

                        Section::make('Material & Pricing')
                            ->schema([
                                Grid::make(3)
                                    ->schema([
                                        Select::make('cabinet_level')
                                            ->label('Cabinet Level')
                                            ->options(fn () => AttributeOption::where('attribute_id', 20)
                                                ->orderBy('sort')
                                                ->pluck('name', 'name')
                                                ->toArray())
                                            ->native(false)
                                            ->searchable(),

                                        Select::make('material_category')
                                            ->label('Material Category')
                                            ->options(fn () => AttributeOption::where('attribute_id', 18)
                                                ->orderBy('sort')
                                                ->pluck('name', 'name')
                                                ->toArray())
                                            ->native(false)
                                            ->searchable(),

                                        Select::make('finish_option')
                                            ->label('Finish Option')
                                            ->options(fn () => AttributeOption::where('attribute_id', 19)
                                                ->orderBy('sort')
                                                ->pluck('name', 'name')
                                                ->toArray())
                                            ->native(false)
                                            ->searchable(),
                                    ]),
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

    /**
     * Define the table schema
     *
     * @param Table $table
     * @return Table
     */
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
                \Filament\Actions\Action::make('configure')
                    ->label('Configure')
                    ->icon('heroicon-o-cog-6-tooth')
                    ->color('primary')
                    ->modalWidth('7xl')
                    ->modalHeading(fn (Cabinet $record): string => "Configure {$record->cabinet_number}")
                    ->modalContent(fn (Cabinet $record) => view('webkul-project::livewire.cabinet-configurator-modal', ['cabinetId' => $record->id]))
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Close'),
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
                return Cabinet::query()
                    ->whereHas('room', function ($q) use ($projectId) {
                        $q->where('project_id', $projectId);
                    })
                    ->with(['room', 'cabinetRun']);
            });
    }
}
