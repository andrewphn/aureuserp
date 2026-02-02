<?php

namespace Webkul\Project\Filament\Resources;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Webkul\Inventory\Models\WoodworkingMaterialCategory;
use Webkul\Project\Filament\Resources\CncMaterialProductResource\Pages\CreateCncMaterialProduct;
use Webkul\Project\Filament\Resources\CncMaterialProductResource\Pages\EditCncMaterialProduct;
use Webkul\Project\Filament\Resources\CncMaterialProductResource\Pages\ListCncMaterialProducts;
use Webkul\Project\Models\CncMaterialProduct;
use Webkul\Project\Models\CncProgram;

/**
 * CNC Material Product Resource
 *
 * Manages the mapping between CNC material codes and inventory products.
 * Enables material tracking, stock visibility, and purchase order generation.
 */
class CncMaterialProductResource extends Resource
{
    protected static ?string $model = CncMaterialProduct::class;

    protected static ?string $slug = 'project/cnc-materials';

    protected static ?string $recordTitleAttribute = 'material_code';

    public static function getNavigationLabel(): string
    {
        return 'CNC Materials';
    }

    public static function getNavigationIcon(): ?string
    {
        return 'heroicon-o-cube';
    }

    public static function getNavigationGroup(): ?string
    {
        return 'Production';
    }

    public static function getNavigationSort(): ?int
    {
        return 6;
    }

    public static function getModelLabel(): string
    {
        return 'CNC Material';
    }

    public static function getPluralModelLabel(): string
    {
        return 'CNC Materials';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Material Configuration')
                    ->description('Link CNC material codes to inventory products')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                Select::make('material_code')
                                    ->label('CNC Material Code')
                                    ->options(CncProgram::getMaterialCodes())
                                    ->required()
                                    ->searchable()
                                    ->native(false)
                                    ->helperText('The material code used in CNC programs'),

                                Select::make('product_id')
                                    ->label('Inventory Product')
                                    ->relationship(
                                        'product',
                                        'name',
                                        fn ($query, $get) => $query
                                            ->when($get('filter_by_category'), fn ($q) => $q->whereNotNull('material_category_id'))
                                    )
                                    ->getOptionLabelFromRecordUsing(fn ($record) => $record->name . ($record->materialCategory ? " ({$record->materialCategory->code})" : ''))
                                    ->searchable()
                                    ->preload()
                                    ->required()
                                    ->createOptionForm([
                                        TextInput::make('name')
                                            ->label('Product Name')
                                            ->required(),
                                        TextInput::make('reference')
                                            ->label('SKU/Reference'),
                                        Select::make('material_category_id')
                                            ->label('Material Category')
                                            ->options(fn () => WoodworkingMaterialCategory::query()->pluck('name', 'id'))
                                            ->searchable()
                                            ->preload(),
                                    ])
                                    ->helperText('The product in inventory to link to'),

                                Select::make('material_type')
                                    ->label('Material Type')
                                    ->options(CncMaterialProduct::getMaterialTypes())
                                    ->default(CncMaterialProduct::TYPE_SHEET_GOODS)
                                    ->required()
                                    ->native(false),

                                Select::make('sheet_size')
                                    ->label('Standard Sheet Size')
                                    ->options(CncProgram::getSheetSizes())
                                    ->default('48x96')
                                    ->native(false),

                                TextInput::make('thickness_inches')
                                    ->label('Thickness (inches)')
                                    ->numeric()
                                    ->step(0.001)
                                    ->placeholder('0.75'),

                                TextInput::make('sqft_per_sheet')
                                    ->label('Sq.Ft. per Sheet')
                                    ->numeric()
                                    ->default(32)
                                    ->helperText('4x8 sheet = 32 sqft'),
                            ]),
                    ]),

                Section::make('Cost & Vendor')
                    ->description('Pricing and supplier information')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextInput::make('cost_per_sheet')
                                    ->label('Cost per Sheet')
                                    ->numeric()
                                    ->prefix('$')
                                    ->step(0.01),

                                TextInput::make('cost_per_sqft')
                                    ->label('Cost per Sq.Ft.')
                                    ->numeric()
                                    ->prefix('$')
                                    ->step(0.0001),

                                Select::make('preferred_vendor_id')
                                    ->label('Preferred Vendor')
                                    ->relationship('preferredVendor', 'name')
                                    ->searchable()
                                    ->preload()
                                    ->placeholder('Select vendor...'),

                                TextInput::make('vendor_sku')
                                    ->label('Vendor Part Number')
                                    ->maxLength(100),

                                TextInput::make('lead_time_days')
                                    ->label('Lead Time (Days)')
                                    ->numeric()
                                    ->minValue(0),
                            ]),
                    ])
                    ->collapsible(),

                Section::make('Stock Management')
                    ->description('Inventory thresholds for reordering')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextInput::make('min_stock_sheets')
                                    ->label('Minimum Stock (Sheets)')
                                    ->numeric()
                                    ->default(0)
                                    ->minValue(0)
                                    ->helperText('Alert when stock falls below this'),

                                TextInput::make('reorder_qty_sheets')
                                    ->label('Reorder Quantity (Sheets)')
                                    ->numeric()
                                    ->default(0)
                                    ->minValue(0)
                                    ->helperText('Suggested quantity to order'),

                                Checkbox::make('is_default')
                                    ->label('Default Product')
                                    ->helperText('Use this product by default for this material code'),

                                Checkbox::make('is_active')
                                    ->label('Active')
                                    ->default(true)
                                    ->helperText('Inactive materials will not be used'),
                            ]),
                    ])
                    ->collapsible(),

                Section::make('Notes')
                    ->schema([
                        Textarea::make('notes')
                            ->label('Notes')
                            ->rows(3)
                            ->columnSpanFull(),
                    ])
                    ->collapsed(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('material_code')
                    ->label('Material Code')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'FL' => 'amber',
                        'PreFin' => 'blue',
                        'RiftWOPly' => 'green',
                        'MDF_RiftWO' => 'purple',
                        'Medex' => 'pink',
                        'Melamine' => 'cyan',
                        default => 'gray',
                    })
                    ->sortable()
                    ->searchable(),

                TextColumn::make('product.name')
                    ->label('Product')
                    ->searchable()
                    ->sortable()
                    ->limit(30)
                    ->tooltip(fn ($record) => $record->product?->name),

                TextColumn::make('product.materialCategory.name')
                    ->label('Category')
                    ->badge()
                    ->color('info')
                    ->placeholder('Uncategorized')
                    ->toggleable(),

                TextColumn::make('material_type')
                    ->label('Type')
                    ->badge()
                    ->formatStateUsing(fn ($state) => ucwords(str_replace('_', ' ', $state)))
                    ->color('secondary'),

                TextColumn::make('sheet_size')
                    ->label('Size')
                    ->placeholder('-'),

                TextColumn::make('cost_per_sheet')
                    ->label('$/Sheet')
                    ->money('USD')
                    ->sortable(),

                TextColumn::make('current_stock_sheets')
                    ->label('In Stock')
                    ->suffix(' sheets')
                    ->color(fn ($record) => $record->is_low_stock ? 'danger' : 'success')
                    ->sortable(query: function ($query, $direction) {
                        // This requires a subquery to sort by computed value
                        return $query;
                    }),

                TextColumn::make('min_stock_sheets')
                    ->label('Min Stock')
                    ->suffix(' sheets')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('preferredVendor.name')
                    ->label('Vendor')
                    ->limit(20)
                    ->toggleable(isToggledHiddenByDefault: true),

                IconColumn::make('is_default')
                    ->label('Default')
                    ->boolean()
                    ->trueIcon('heroicon-o-star')
                    ->falseIcon('heroicon-o-minus'),

                IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean(),
            ])
            ->filters([
                SelectFilter::make('material_code')
                    ->label('Material Code')
                    ->options(CncProgram::getMaterialCodes()),

                SelectFilter::make('material_type')
                    ->label('Material Type')
                    ->options(CncMaterialProduct::getMaterialTypes()),

                SelectFilter::make('material_category')
                    ->label('Material Category')
                    ->options(fn () => WoodworkingMaterialCategory::query()->pluck('name', 'id'))
                    ->query(fn ($query, $data) => $query->when(
                        $data['value'],
                        fn ($q) => $q->whereHas('product', fn ($pq) => $pq->where('material_category_id', $data['value']))
                    )),

                TernaryFilter::make('is_active')
                    ->label('Active')
                    ->default(true),

                TernaryFilter::make('is_default')
                    ->label('Default Only'),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('material_code');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCncMaterialProducts::route('/'),
            'create' => CreateCncMaterialProduct::route('/create'),
            'edit' => EditCncMaterialProduct::route('/{record}/edit'),
        ];
    }
}
