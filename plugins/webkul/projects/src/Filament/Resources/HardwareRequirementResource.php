<?php

namespace Webkul\Project\Filament\Resources;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Table;
use Filament\Tables\Filters\QueryBuilder;
use Filament\Tables\Filters\QueryBuilder\Constraints\BooleanConstraint;
use Filament\Tables\Filters\QueryBuilder\Constraints\SelectConstraint;
use Filament\Tables\Filters\QueryBuilder\Constraints\TextConstraint;
use Webkul\Project\Filament\Resources\HardwareRequirementResource\Pages\CreateHardwareRequirement;
use Webkul\Project\Filament\Resources\HardwareRequirementResource\Pages\EditHardwareRequirement;
use Webkul\Project\Filament\Resources\HardwareRequirementResource\Pages\ListHardwareRequirements;
use Webkul\Project\Models\HardwareRequirement;

/**
 * Hardware Requirement Resource Filament resource
 *
 * @see \Filament\Resources\Resource
 */
class HardwareRequirementResource extends Resource
{
    protected static ?string $model = HardwareRequirement::class;

    protected static ?string $slug = 'project/hardware-requirements';

    protected static ?string $recordTitleAttribute = 'hardware_type';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-wrench-screwdriver';

    public static function getNavigationLabel(): string
    {
        return 'Hardware Requirements';
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
                                    Select::make('cabinet_specification_id')
                                        ->label('Cabinet Specification')
                                        ->relationship('cabinetSpecification', 'cabinet_code')
                                        ->searchable()
                                        ->preload()
                                        ->helperText('Individual cabinet hardware'),
                                    Select::make('cabinet_run_id')
                                        ->label('Cabinet Run')
                                        ->relationship('cabinetRun', 'run_name')
                                        ->searchable()
                                        ->preload()
                                        ->helperText('Aggregate hardware for entire run'),
                                ]),
                            ]),

                        Section::make('Hardware Product')
                            ->schema([
                                Grid::make(2)->schema([
                                    Select::make('product_id')
                                        ->label('Hardware Product')
                                        ->relationship('product', 'name')
                                        ->searchable()
                                        ->preload()
                                        ->required()
                                        ->helperText('Hardware from products/inventory'),
                                    Select::make('hardware_type')
                                        ->label('Hardware Type')
                                        ->options([
                                            'hinge' => 'Hinge',
                                            'slide' => 'Drawer Slide',
                                            'shelf_pin' => 'Shelf Pin',
                                            'pullout' => 'Pull-out/Tray',
                                            'lazy_susan' => 'Lazy Susan',
                                            'organizer' => 'Cabinet Organizer',
                                        ])
                                        ->required()
                                        ->native(false)
                                        ->live(),
                                ]),
                                Grid::make(3)->schema([
                                    TextInput::make('manufacturer')
                                        ->label('Manufacturer')
                                        ->maxLength(100)
                                        ->placeholder('e.g., Blum, Rev-a-Shelf'),
                                    TextInput::make('model_number')
                                        ->label('Model Number')
                                        ->maxLength(100)
                                        ->placeholder('e.g., 71B3550'),
                                    TextInput::make('finish')
                                        ->label('Finish')
                                        ->maxLength(50)
                                        ->placeholder('e.g., Nickel, Black'),
                                ]),
                            ]),

                        Section::make('Quantity & Application')
                            ->schema([
                                Grid::make(3)->schema([
                                    TextInput::make('quantity_required')
                                        ->label('Quantity Required')
                                        ->numeric()
                                        ->required()
                                        ->default(1),
                                    Select::make('unit_of_measure')
                                        ->label('Unit of Measure')
                                        ->options([
                                            'EA' => 'Each',
                                            'SET' => 'Set',
                                            'PAIR' => 'Pair',
                                            'PKG' => 'Package',
                                        ])
                                        ->default('EA')
                                        ->required(),
                                    Select::make('applied_to')
                                        ->label('Applied To')
                                        ->options([
                                            'door' => 'Door',
                                            'drawer' => 'Drawer',
                                            'shelf' => 'Shelf',
                                            'cabinet' => 'Cabinet',
                                        ])
                                        ->required()
                                        ->live(),
                                ]),
                                Grid::make(3)->schema([
                                    TextInput::make('door_number')
                                        ->label('Door Number')
                                        ->numeric()
                                        ->visible(fn (callable $get) => $get('applied_to') === 'door'),
                                    TextInput::make('drawer_number')
                                        ->label('Drawer Number')
                                        ->numeric()
                                        ->visible(fn (callable $get) => $get('applied_to') === 'drawer'),
                                    Select::make('mounting_location')
                                        ->label('Mounting Location')
                                        ->options([
                                            'left' => 'Left',
                                            'right' => 'Right',
                                            'center' => 'Center',
                                            'top' => 'Top',
                                            'bottom' => 'Bottom',
                                        ]),
                                ]),
                            ]),

                        Section::make('Hinge Specifications')
                            ->schema([
                                Grid::make(3)->schema([
                                    Select::make('hinge_type')
                                        ->label('Hinge Type')
                                        ->options([
                                            'clip_top' => 'Clip Top',
                                            'clip_top_blumotion' => 'Clip Top BLUMOTION',
                                            'concealed' => 'Concealed',
                                            'face_frame' => 'Face Frame',
                                        ]),
                                    TextInput::make('hinge_opening_angle')
                                        ->label('Opening Angle')
                                        ->numeric()
                                        ->suffix('°')
                                        ->placeholder('e.g., 110, 120'),
                                    TextInput::make('overlay_dimension_mm')
                                        ->label('Overlay (mm)')
                                        ->numeric()
                                        ->step(0.01)
                                        ->suffix('mm')
                                        ->helperText('Overlay dimension in mm'),
                                ]),
                            ])
                            ->visible(fn (callable $get) => $get('hardware_type') === 'hinge')
                            ->collapsible(),

                        Section::make('Slide Specifications')
                            ->schema([
                                Grid::make(3)->schema([
                                    Select::make('slide_type')
                                        ->label('Slide Type')
                                        ->options([
                                            'undermount' => 'Undermount',
                                            'side_mount' => 'Side Mount',
                                            'full_extension' => 'Full Extension',
                                            'soft_close' => 'Soft Close',
                                        ]),
                                    TextInput::make('slide_length_inches')
                                        ->label('Slide Length')
                                        ->numeric()
                                        ->step(0.1)
                                        ->suffix('"')
                                        ->placeholder('e.g., 18, 21'),
                                    TextInput::make('slide_weight_capacity_lbs')
                                        ->label('Weight Capacity')
                                        ->numeric()
                                        ->suffix('lbs')
                                        ->placeholder('e.g., 75, 100'),
                                ]),
                            ])
                            ->visible(fn (callable $get) => $get('hardware_type') === 'slide')
                            ->collapsible(),

                        Section::make('Shelf Pin Specifications')
                            ->schema([
                                Grid::make(2)->schema([
                                    Select::make('shelf_pin_type')
                                        ->label('Pin Type')
                                        ->options([
                                            'standard' => 'Standard',
                                            'locking' => 'Locking',
                                            'metal' => 'Metal',
                                            'plastic' => 'Plastic',
                                        ]),
                                    TextInput::make('shelf_pin_diameter_mm')
                                        ->label('Pin Diameter')
                                        ->numeric()
                                        ->step(0.01)
                                        ->suffix('mm')
                                        ->placeholder('e.g., 5, 6'),
                                ]),
                            ])
                            ->visible(fn (callable $get) => $get('hardware_type') === 'shelf_pin')
                            ->collapsible(),

                        Section::make('Accessory Dimensions')
                            ->schema([
                                Grid::make(3)->schema([
                                    TextInput::make('accessory_width_inches')
                                        ->label('Width')
                                        ->numeric()
                                        ->step(0.001)
                                        ->suffix('"'),
                                    TextInput::make('accessory_depth_inches')
                                        ->label('Depth')
                                        ->numeric()
                                        ->step(0.001)
                                        ->suffix('"'),
                                    TextInput::make('accessory_height_inches')
                                        ->label('Height')
                                        ->numeric()
                                        ->step(0.001)
                                        ->suffix('"'),
                                ]),
                                TextInput::make('accessory_configuration')
                                    ->label('Configuration')
                                    ->maxLength(255)
                                    ->helperText('e.g., 2-tier, 3-basket, etc.'),
                            ])
                            ->visible(fn (callable $get) => in_array($get('hardware_type'), ['pullout', 'lazy_susan', 'organizer']))
                            ->collapsible(),

                        Section::make('Cost Tracking')
                            ->schema([
                                Grid::make(2)->schema([
                                    TextInput::make('unit_cost')
                                        ->label('Unit Cost')
                                        ->numeric()
                                        ->prefix('$')
                                        ->step(0.01),
                                    TextInput::make('total_hardware_cost')
                                        ->label('Total Cost')
                                        ->numeric()
                                        ->prefix('$')
                                        ->disabled()
                                        ->dehydrated(false)
                                        ->helperText('Auto-calculated: quantity × unit_cost'),
                                ]),
                            ]),

                        Section::make('Installation Details')
                            ->schema([
                                Grid::make(3)->schema([
                                    TextInput::make('install_sequence')
                                        ->label('Install Sequence')
                                        ->numeric()
                                        ->helperText('Order in installation process'),
                                    Toggle::make('requires_jig')
                                        ->label('Requires Jig'),
                                    TextInput::make('jig_name')
                                        ->label('Jig Name')
                                        ->maxLength(100)
                                        ->visible(fn (callable $get) => $get('requires_jig')),
                                ]),
                                Textarea::make('installation_notes')
                                    ->label('Installation Notes')
                                    ->rows(3),
                            ])
                            ->collapsible(),

                        Section::make('Hardware Status')
                            ->schema([
                                Grid::make(2)->schema([
                                    Toggle::make('hardware_allocated')
                                        ->label('Allocated'),
                                    Toggle::make('hardware_issued')
                                        ->label('Issued'),
                                ]),
                                Grid::make(2)->schema([
                                    Toggle::make('hardware_kitted')
                                        ->label('Kitted'),
                                    Toggle::make('hardware_installed')
                                        ->label('Installed'),
                                ]),
                                Grid::make(2)->schema([
                                    DateTimePicker::make('hardware_installed_at')
                                        ->label('Installed At')
                                        ->visible(fn (callable $get) => $get('hardware_installed')),
                                    Select::make('installed_by_user_id')
                                        ->label('Installed By')
                                        ->relationship('installedBy', 'name')
                                        ->searchable()
                                        ->preload()
                                        ->visible(fn (callable $get) => $get('hardware_installed')),
                                ]),
                            ])
                            ->collapsible(),

                        Section::make('Issues & Substitutions')
                            ->schema([
                                Grid::make(2)->schema([
                                    Toggle::make('has_defect')
                                        ->label('Has Defect')
                                        ->live(),
                                    Toggle::make('returned_to_supplier')
                                        ->label('Returned to Supplier')
                                        ->visible(fn (callable $get) => $get('has_defect')),
                                ]),
                                Textarea::make('defect_description')
                                    ->label('Defect Description')
                                    ->rows(3)
                                    ->visible(fn (callable $get) => $get('has_defect')),
                                Select::make('substituted_product_id')
                                    ->label('Substituted Product')
                                    ->relationship('substitutedProduct', 'name')
                                    ->searchable()
                                    ->preload(),
                                Textarea::make('substitution_reason')
                                    ->label('Substitution Reason')
                                    ->rows(3)
                                    ->visible(fn (callable $get) => $get('substituted_product_id')),
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
                TextColumn::make('cabinetSpecification.cabinet_code')
                    ->label('Cabinet')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('cabinetRun.run_name')
                    ->label('Run')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('hardware_type')
                    ->label('Type')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'hinge' => 'info',
                        'slide' => 'success',
                        'shelf_pin' => 'warning',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => ucwords(str_replace('_', ' ', $state)))
                    ->sortable(),
                TextColumn::make('product.name')
                    ->label('Product')
                    ->searchable()
                    ->limit(30),
                TextColumn::make('manufacturer')
                    ->label('Manufacturer')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('model_number')
                    ->label('Model')
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('quantity_required')
                    ->label('Qty')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('total_hardware_cost')
                    ->label('Cost')
                    ->money('USD')
                    ->sortable(),
                IconColumn::make('hardware_kitted')
                    ->label('Kitted')
                    ->boolean()
                    ->toggleable(),
                IconColumn::make('hardware_installed')
                    ->label('Installed')
                    ->boolean()
                    ->toggleable(),
                IconColumn::make('has_defect')
                    ->label('Defect')
                    ->boolean()
                    ->toggleable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                QueryBuilder::make()
                    ->constraints([
                        SelectConstraint::make('hardware_type')
                            ->label('Hardware Type')
                            ->options([
                                'hinge' => 'Hinge',
                                'slide' => 'Drawer Slide',
                                'shelf_pin' => 'Shelf Pin',
                                'pullout' => 'Pull-out/Tray',
                                'lazy_susan' => 'Lazy Susan',
                                'organizer' => 'Cabinet Organizer',
                            ]),
                        TextConstraint::make('manufacturer')
                            ->label('Manufacturer'),
                        BooleanConstraint::make('hardware_kitted')
                            ->label('Kitted'),
                        BooleanConstraint::make('hardware_installed')
                            ->label('Installed'),
                        BooleanConstraint::make('has_defect')
                            ->label('Has Defect'),
                    ]),
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

    public static function getPages(): array
    {
        return [
            'index' => ListHardwareRequirements::route('/'),
            'create' => CreateHardwareRequirement::route('/create'),
            'edit' => EditHardwareRequirement::route('/{record}/edit'),
        ];
    }
}
