<?php

namespace Webkul\Project\Filament\Resources;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Table;
use Filament\Tables\Filters\QueryBuilder;
use Filament\Tables\Filters\QueryBuilder\Constraints\BooleanConstraint;
use Filament\Tables\Filters\QueryBuilder\Constraints\SelectConstraint;
use Filament\Tables\Filters\QueryBuilder\Constraints\TextConstraint;
use Webkul\Project\Filament\Resources\CabinetMaterialsBomResource\Pages\CreateCabinetMaterialsBom;
use Webkul\Project\Filament\Resources\CabinetMaterialsBomResource\Pages\EditCabinetMaterialsBom;
use Webkul\Project\Filament\Resources\CabinetMaterialsBomResource\Pages\ListCabinetMaterialsBoms;
use Webkul\Project\Models\CabinetMaterialsBom;

/**
 * Cabinet Materials Bom Resource Filament resource
 *
 * @see \Filament\Resources\Resource
 */
class CabinetMaterialsBomResource extends Resource
{
    protected static ?string $model = CabinetMaterialsBom::class;

    protected static ?string $slug = 'project/cabinet-materials-bom';

    protected static ?string $recordTitleAttribute = 'component_name';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-document-text';

    public static function getNavigationLabel(): string
    {
        return 'Materials BOM';
    }

    public static function getNavigationGroup(): string
    {
        return 'Projects';
    }

    /**
     * Define the form schema
     *
     * @param Schema $schema
     * @return Schema
     */
    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Group::make()
                    ->schema([
                        Section::make('Reference')
                            ->schema([
                                Grid::make(2)->schema([
                                    Select::make('cabinet_id')
                                        ->label('Cabinet')
                                        ->relationship('cabinet', 'cabinet_code')
                                        ->searchable()
                                        ->preload()
                                        ->helperText('Individual cabinet material requirement'),
                                    Select::make('cabinet_run_id')
                                        ->label('Cabinet Run')
                                        ->relationship('cabinetRun', 'run_name')
                                        ->searchable()
                                        ->preload()
                                        ->helperText('Aggregate materials for entire run'),
                                ]),
                            ]),

                        Section::make('Material Details')
                            ->schema([
                                Grid::make(2)->schema([
                                    Select::make('product_id')
                                        ->label('Material Product')
                                        ->relationship('product', 'name')
                                        ->searchable()
                                        ->preload()
                                        ->required()
                                        ->helperText('Material from products/inventory'),
                                    TextInput::make('component_name')
                                        ->label('Component Name')
                                        ->maxLength(100)
                                        ->placeholder('e.g., box_sides, face_frame, doors')
                                        ->helperText('What this material is for'),
                                ]),
                            ]),

                        Section::make('Quantity Requirements')
                            ->schema([
                                Grid::make(3)->schema([
                                    TextInput::make('quantity_required')
                                        ->label('Quantity Required')
                                        ->numeric()
                                        ->required()
                                        ->live(onBlur: true)
                                        ->helperText('Quantity needed in material UOM'),
                                    Select::make('unit_of_measure')
                                        ->label('Unit of Measure')
                                        ->options([
                                            'BF' => 'Board Feet',
                                            'SQFT' => 'Square Feet',
                                            'EA' => 'Each',
                                            'LF' => 'Linear Feet',
                                        ])
                                        ->default('EA')
                                        ->required(),
                                    TextInput::make('waste_factor_percentage')
                                        ->label('Waste Factor %')
                                        ->numeric()
                                        ->default(10.00)
                                        ->suffix('%')
                                        ->helperText('Typically 10-15%'),
                                ]),
                                TextInput::make('quantity_with_waste')
                                    ->label('Quantity with Waste')
                                    ->numeric()
                                    ->disabled()
                                    ->dehydrated(false)
                                    ->helperText('Auto-calculated: quantity × (1 + waste_factor)'),
                            ]),

                        Section::make('Component Dimensions (For Sheet Goods)')
                            ->schema([
                                Grid::make(3)->schema([
                                    TextInput::make('component_width_inches')
                                        ->label('Width (inches)')
                                        ->numeric()
                                        ->step(0.001)
                                        ->suffix('"'),
                                    TextInput::make('component_height_inches')
                                        ->label('Height (inches)')
                                        ->numeric()
                                        ->step(0.001)
                                        ->suffix('"'),
                                    TextInput::make('quantity_of_components')
                                        ->label('Number of Pieces')
                                        ->numeric()
                                        ->default(1),
                                ]),
                                Grid::make(2)->schema([
                                    TextInput::make('sqft_per_component')
                                        ->label('SQFT per Component')
                                        ->numeric()
                                        ->disabled()
                                        ->dehydrated(false),
                                    TextInput::make('total_sqft_required')
                                        ->label('Total SQFT Required')
                                        ->numeric()
                                        ->disabled()
                                        ->dehydrated(false)
                                        ->helperText('Includes waste factor'),
                                ]),
                            ])
                            ->collapsible(),

                        Section::make('Linear Feet Calculation (For Solid Wood)')
                            ->schema([
                                Grid::make(3)->schema([
                                    TextInput::make('linear_feet_per_component')
                                        ->label('LF per Component')
                                        ->numeric()
                                        ->step(0.01),
                                    TextInput::make('total_linear_feet')
                                        ->label('Total LF')
                                        ->numeric()
                                        ->step(0.01),
                                    TextInput::make('board_feet_required')
                                        ->label('Board Feet Required')
                                        ->numeric()
                                        ->step(0.01)
                                        ->helperText('Calculated from LF × thickness × width'),
                                ]),
                            ])
                            ->collapsible(),

                        Section::make('Cost Tracking')
                            ->schema([
                                Grid::make(2)->schema([
                                    TextInput::make('unit_cost')
                                        ->label('Unit Cost')
                                        ->numeric()
                                        ->prefix('$')
                                        ->step(0.01)
                                        ->helperText('Cost per UOM from product'),
                                    TextInput::make('total_material_cost')
                                        ->label('Total Material Cost')
                                        ->numeric()
                                        ->prefix('$')
                                        ->disabled()
                                        ->dehydrated(false)
                                        ->helperText('Auto-calculated: quantity_with_waste × unit_cost'),
                                ]),
                            ]),

                        Section::make('Material Specifications')
                            ->schema([
                                Grid::make(2)->schema([
                                    Select::make('grain_direction')
                                        ->label('Grain Direction')
                                        ->options([
                                            'horizontal' => 'Horizontal',
                                            'vertical' => 'Vertical',
                                            'none' => 'None',
                                        ])
                                        ->helperText('Affects sheet layout'),
                                    Toggle::make('requires_edge_banding')
                                        ->label('Requires Edge Banding')
                                        ->helperText('Exposed edges need banding'),
                                ]),
                                Grid::make(2)->schema([
                                    Select::make('edge_banding_sides')
                                        ->label('Edge Banding Sides')
                                        ->options([
                                            'all' => 'All',
                                            'front_only' => 'Front Only',
                                            'front_back' => 'Front & Back',
                                            'custom' => 'Custom',
                                        ])
                                        ->visible(fn (callable $get) => $get('requires_edge_banding')),
                                    TextInput::make('edge_banding_lf')
                                        ->label('Edge Banding LF')
                                        ->numeric()
                                        ->step(0.01)
                                        ->suffix('LF')
                                        ->visible(fn (callable $get) => $get('requires_edge_banding')),
                                ]),
                            ])
                            ->collapsible(),

                        Section::make('CNC/Machining Notes')
                            ->schema([
                                Textarea::make('cnc_notes')
                                    ->label('CNC Notes')
                                    ->rows(3)
                                    ->helperText('CNC machining requirements for this component'),
                                Textarea::make('machining_operations')
                                    ->label('Machining Operations')
                                    ->rows(3)
                                    ->helperText('Required operations: dado, groove, mortise, etc.'),
                            ])
                            ->collapsible(),

                        Section::make('Material Status')
                            ->schema([
                                Grid::make(2)->schema([
                                    Toggle::make('material_allocated')
                                        ->label('Material Allocated')
                                        ->helperText('Material reserved from inventory'),
                                    Toggle::make('material_issued')
                                        ->label('Material Issued')
                                        ->helperText('Material physically issued to production'),
                                ]),
                            ])
                            ->collapsible(),

                        Section::make('Substitutions')
                            ->schema([
                                Select::make('substituted_product_id')
                                    ->label('Substituted Product')
                                    ->relationship('substitutedProduct', 'name')
                                    ->searchable()
                                    ->preload()
                                    ->helperText('Alternative material if primary not available'),
                                Textarea::make('substitution_notes')
                                    ->label('Substitution Notes')
                                    ->rows(3)
                                    ->helperText('Why substitution was made'),
                            ])
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
    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('cabinet.cabinet_code')
                    ->label('Cabinet')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('cabinetRun.run_name')
                    ->label('Run')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('product.name')
                    ->label('Material')
                    ->searchable()
                    ->sortable()
                    ->limit(30),
                TextColumn::make('component_name')
                    ->label('Component')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('quantity_required')
                    ->label('Qty Required')
                    ->numeric(decimalPlaces: 2)
                    ->sortable(),
                TextColumn::make('unit_of_measure')
                    ->label('UOM')
                    ->badge(),
                TextColumn::make('quantity_with_waste')
                    ->label('Qty w/ Waste')
                    ->numeric(decimalPlaces: 2)
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('total_material_cost')
                    ->label('Cost')
                    ->money('USD')
                    ->sortable(),
                IconColumn::make('material_allocated')
                    ->label('Allocated')
                    ->boolean()
                    ->toggleable(),
                IconColumn::make('material_issued')
                    ->label('Issued')
                    ->boolean()
                    ->toggleable(),
                TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                QueryBuilder::make()
                    ->constraints([
                        TextConstraint::make('component_name')
                            ->label('Component'),
                        SelectConstraint::make('unit_of_measure')
                            ->label('UOM')
                            ->options([
                                'BF' => 'Board Feet',
                                'SQFT' => 'Square Feet',
                                'EA' => 'Each',
                                'LF' => 'Linear Feet',
                            ]),
                        BooleanConstraint::make('material_allocated')
                            ->label('Allocated'),
                        BooleanConstraint::make('material_issued')
                            ->label('Issued'),
                    ]),
            ])
            ->actions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCabinetMaterialsBoms::route('/'),
            'create' => CreateCabinetMaterialsBom::route('/create'),
            'edit' => EditCabinetMaterialsBom::route('/{record}/edit'),
        ];
    }
}
