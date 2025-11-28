<?php

namespace Webkul\Sale\Filament\Clusters\Orders\Resources\OrderResource\RelationManagers;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;

/**
 * Line Items Relation Manager class
 *
 * @see \Filament\Resources\Resource
 */
class LineItemsRelationManager extends RelationManager
{
    protected static string $relationship = 'lineItems';

    protected static ?string $recordTitleAttribute = 'description';

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
                Section::make('Project Reference')
                    ->schema([
                        Grid::make(3)->schema([
                            Select::make('room_id')
                                ->label('Room')
                                ->relationship('room', 'name')
                                ->searchable()
                                ->preload()
                                ->helperText('Link to project room'),
                            Select::make('room_location_id')
                                ->label('Room Location')
                                ->relationship('roomLocation', 'location_name')
                                ->searchable()
                                ->preload(),
                            Select::make('cabinet_run_id')
                                ->label('Cabinet Run')
                                ->relationship('cabinetRun', 'run_name')
                                ->searchable()
                                ->preload(),
                        ]),
                        Select::make('cabinet_specification_id')
                            ->label('Cabinet Specification')
                            ->relationship('cabinetSpecification', 'cabinet_code')
                            ->searchable()
                            ->preload()
                            ->helperText('Link to specific cabinet'),
                    ])
                    ->collapsible()
                    ->collapsed(),

                Section::make('Line Item Details')
                    ->schema([
                        Grid::make(2)->schema([
                            Select::make('line_item_type')
                                ->label('Type')
                                ->options([
                                    'cabinet' => 'Cabinet',
                                    'countertop' => 'Countertop',
                                    'additional' => 'Additional Item',
                                    'discount' => 'Discount',
                                ])
                                ->required()
                                ->native(false)
                                ->live()
                                ->afterStateUpdated(fn (callable $set) => $set('product_id', null)),
                            Select::make('product_id')
                                ->label('Product')
                                ->relationship('product', 'name')
                                ->searchable()
                                ->preload()
                                ->helperText('Optional: link to product catalog'),
                        ]),
                        Textarea::make('description')
                            ->label('Description')
                            ->required()
                            ->rows(2)
                            ->columnSpanFull()
                            ->placeholder('Detailed line item description'),
                    ]),

                Section::make('Cabinet Pricing (Linear Feet)')
                    ->schema([
                        Grid::make(3)->schema([
                            TextInput::make('quantity')
                                ->label('Quantity')
                                ->numeric()
                                ->default(1)
                                ->live(onBlur: true),
                            TextInput::make('linear_feet')
                                ->label('Linear Feet')
                                ->numeric()
                                ->step(0.01)
                                ->live(onBlur: true)
                                ->suffix('LF'),
                            TextInput::make('unit_price_per_lf')
                                ->label('Price per LF')
                                ->numeric()
                                ->prefix('$')
                                ->step(0.01)
                                ->live(onBlur: true),
                        ]),
                    ])
                    ->visible(fn (callable $get) => $get('line_item_type') === 'cabinet'),

                Section::make('Standard Pricing')
                    ->schema([
                        Grid::make(2)->schema([
                            TextInput::make('quantity')
                                ->label('Quantity')
                                ->numeric()
                                ->default(1)
                                ->live(onBlur: true),
                            TextInput::make('unit_price')
                                ->label('Unit Price')
                                ->numeric()
                                ->prefix('$')
                                ->step(0.01)
                                ->live(onBlur: true),
                        ]),
                    ])
                    ->visible(fn (callable $get) => $get('line_item_type') !== 'cabinet'),

                Section::make('Calculated Values')
                    ->schema([
                        Grid::make(2)->schema([
                            TextInput::make('subtotal')
                                ->label('Subtotal')
                                ->numeric()
                                ->prefix('$')
                                ->disabled()
                                ->dehydrated(false)
                                ->helperText('Auto-calculated based on qty/LF and price'),
                            TextInput::make('discount_percentage')
                                ->label('Discount %')
                                ->numeric()
                                ->suffix('%')
                                ->step(0.01)
                                ->live(onBlur: true),
                        ]),
                        Grid::make(2)->schema([
                            TextInput::make('discount_amount')
                                ->label('Discount Amount')
                                ->numeric()
                                ->prefix('$')
                                ->disabled()
                                ->dehydrated(false)
                                ->helperText('Auto-calculated from percentage'),
                            TextInput::make('line_total')
                                ->label('Line Total')
                                ->numeric()
                                ->prefix('$')
                                ->disabled()
                                ->dehydrated(false)
                                ->helperText('Subtotal - Discount'),
                        ]),
                    ]),

                Section::make('Additional Information')
                    ->schema([
                        Textarea::make('notes')
                            ->label('Notes')
                            ->rows(3)
                            ->columnSpanFull(),
                        TextInput::make('sort_order')
                            ->label('Sort Order')
                            ->numeric()
                            ->default(0)
                            ->helperText('Display order on invoice'),
                    ])
                    ->collapsible()
                    ->collapsed(),
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
            ->recordTitleAttribute('description')
            ->columns([
                Tables\Columns\TextColumn::make('sort_order')
                    ->label('#')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('line_item_type')
                    ->label('Type')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'cabinet' => 'blue',
                        'countertop' => 'green',
                        'additional' => 'amber',
                        'discount' => 'red',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => ucfirst($state))
                    ->sortable(),

                Tables\Columns\TextColumn::make('description')
                    ->label('Description')
                    ->searchable()
                    ->limit(50)
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('room.name')
                    ->label('Room')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('cabinet_run.run_name')
                    ->label('Run')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('quantity')
                    ->label('Qty')
                    ->numeric(decimalPlaces: 2)
                    ->sortable(),

                Tables\Columns\TextColumn::make('linear_feet')
                    ->label('LF')
                    ->numeric(decimalPlaces: 2)
                    ->sortable()
                    ->toggleable()
                    ->visible(fn ($record) => $record->line_item_type === 'cabinet'),

                Tables\Columns\TextColumn::make('unit_price_per_lf')
                    ->label('$/LF')
                    ->money('USD')
                    ->sortable()
                    ->toggleable()
                    ->visible(fn ($record) => $record->line_item_type === 'cabinet'),

                Tables\Columns\TextColumn::make('unit_price')
                    ->label('Unit Price')
                    ->money('USD')
                    ->sortable()
                    ->toggleable()
                    ->visible(fn ($record) => $record->line_item_type !== 'cabinet'),

                Tables\Columns\TextColumn::make('subtotal')
                    ->label('Subtotal')
                    ->money('USD')
                    ->sortable(),

                Tables\Columns\TextColumn::make('discount_percentage')
                    ->label('Disc %')
                    ->numeric(decimalPlaces: 2)
                    ->suffix('%')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('line_total')
                    ->label('Total')
                    ->money('USD')
                    ->sortable()
                    ->weight('bold'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('line_item_type')
                    ->label('Type')
                    ->options([
                        'cabinet' => 'Cabinet',
                        'countertop' => 'Countertop',
                        'additional' => 'Additional Item',
                        'discount' => 'Discount',
                    ]),
            ])
            ->reorderable('sort_order')
            ->defaultSort('sort_order', 'asc')
            ->headerActions([
                \Filament\Actions\CreateAction::make(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}
