<?php

namespace Webkul\Project\Filament\Clusters\Configurations\Resources;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Checkbox;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Select;
use Filament\Schemas\Components\Textarea;
use Filament\Schemas\Components\TextInput;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Webkul\Product\Models\Product;
use Webkul\Project\Filament\Clusters\Configurations;
use Webkul\Project\Filament\Clusters\Configurations\Resources\ConstructionTemplateResource\Pages\ManageConstructionTemplates;
use Webkul\Project\Models\ConstructionTemplate;

class ConstructionTemplateResource extends Resource
{
    protected static ?string $model = ConstructionTemplate::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-cube-transparent';

    protected static ?int $navigationSort = 1;

    protected static ?string $cluster = Configurations::class;

    protected static string|\UnitEnum|null $navigationGroup = 'Construction Standards';

    public static function getNavigationLabel(): string
    {
        return 'Construction Templates';
    }

    public static function getModelLabel(): string
    {
        return 'Construction Template';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->columns(3)
            ->components([
                // Identity Section
                Section::make('Template Identity')
                    ->columnSpan(1)
                    ->schema([
                        TextInput::make('name')
                            ->label('Template Name')
                            ->required()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true),
                        Textarea::make('description')
                            ->label('Description')
                            ->rows(2),
                        Checkbox::make('is_active')
                            ->label('Active')
                            ->default(true),
                        Checkbox::make('is_default')
                            ->label('Default Template')
                            ->helperText('Only one template can be default'),
                        TextInput::make('sort_order')
                            ->label('Sort Order')
                            ->numeric()
                            ->default(0),
                    ]),

                // Cabinet Heights Section
                Section::make('Cabinet Heights (inches)')
                    ->columnSpan(1)
                    ->schema([
                        TextInput::make('base_cabinet_height')
                            ->label('Base Cabinet')
                            ->numeric()
                            ->step(0.0625)
                            ->default(34.75)
                            ->suffix('"')
                            ->helperText('TCS: 34 3/4"'),
                        Grid::make(2)->schema([
                            TextInput::make('wall_cabinet_30_height')
                                ->label('Wall 30"')
                                ->numeric()
                                ->default(30.0)
                                ->suffix('"'),
                            TextInput::make('wall_cabinet_36_height')
                                ->label('Wall 36"')
                                ->numeric()
                                ->default(36.0)
                                ->suffix('"'),
                        ]),
                        Grid::make(2)->schema([
                            TextInput::make('wall_cabinet_42_height')
                                ->label('Wall 42"')
                                ->numeric()
                                ->default(42.0)
                                ->suffix('"'),
                            TextInput::make('tall_cabinet_84_height')
                                ->label('Tall 84"')
                                ->numeric()
                                ->default(84.0)
                                ->suffix('"'),
                        ]),
                        TextInput::make('tall_cabinet_96_height')
                            ->label('Tall 96"')
                            ->numeric()
                            ->default(96.0)
                            ->suffix('"'),
                    ]),

                // Toe Kick Section
                Section::make('Toe Kick')
                    ->columnSpan(1)
                    ->schema([
                        TextInput::make('toe_kick_height')
                            ->label('Height')
                            ->numeric()
                            ->step(0.0625)
                            ->default(4.5)
                            ->suffix('"')
                            ->helperText('TCS: 4 1/2"'),
                        TextInput::make('toe_kick_recess')
                            ->label('Recess from Face')
                            ->numeric()
                            ->step(0.0625)
                            ->default(3.0)
                            ->suffix('"')
                            ->helperText('TCS: 3"'),
                    ]),

                // Stretchers Section
                Section::make('Stretchers')
                    ->columnSpan(1)
                    ->schema([
                        TextInput::make('stretcher_depth')
                            ->label('Depth')
                            ->numeric()
                            ->step(0.0625)
                            ->default(3.0)
                            ->suffix('"')
                            ->helperText('TCS: 3"'),
                        TextInput::make('stretcher_thickness')
                            ->label('Thickness')
                            ->numeric()
                            ->step(0.0625)
                            ->default(0.75)
                            ->suffix('"'),
                        Grid::make(2)->schema([
                            TextInput::make('stretcher_min_depth')
                                ->label('Min Depth')
                                ->numeric()
                                ->default(2.5)
                                ->suffix('"'),
                            TextInput::make('stretcher_max_depth')
                                ->label('Max Depth')
                                ->numeric()
                                ->default(4.0)
                                ->suffix('"'),
                        ]),
                    ]),

                // Face Frame Section
                Section::make('Face Frame')
                    ->columnSpan(1)
                    ->schema([
                        Grid::make(2)->schema([
                            TextInput::make('face_frame_stile_width')
                                ->label('Stile Width')
                                ->numeric()
                                ->step(0.0625)
                                ->default(1.5)
                                ->suffix('"')
                                ->helperText('TCS: 1 1/2"'),
                            TextInput::make('face_frame_rail_width')
                                ->label('Rail Width')
                                ->numeric()
                                ->step(0.0625)
                                ->default(1.5)
                                ->suffix('"'),
                        ]),
                        TextInput::make('face_frame_door_gap')
                            ->label('Door Gap')
                            ->numeric()
                            ->step(0.0625)
                            ->default(0.125)
                            ->suffix('"')
                            ->helperText('TCS: 1/8"'),
                        TextInput::make('face_frame_thickness')
                            ->label('Thickness')
                            ->numeric()
                            ->step(0.0625)
                            ->default(0.75)
                            ->suffix('"'),
                    ]),

                // Default Materials Section
                Section::make('Default Materials')
                    ->columnSpan(1)
                    ->schema([
                        Select::make('default_box_material_product_id')
                            ->label('Box Material (Sheet Goods)')
                            ->relationship('defaultBoxMaterialProduct', 'name')
                            ->searchable()
                            ->preload()
                            ->helperText('Thickness pulled from product'),
                        Select::make('default_back_material_product_id')
                            ->label('Back Material')
                            ->relationship('defaultBackMaterialProduct', 'name')
                            ->searchable()
                            ->preload(),
                        Select::make('default_face_frame_material_product_id')
                            ->label('Face Frame Material (Lumber)')
                            ->relationship('defaultFaceFrameMaterialProduct', 'name')
                            ->searchable()
                            ->preload(),
                        Select::make('default_edge_banding_product_id')
                            ->label('Edge Banding')
                            ->relationship('defaultEdgeBandingProduct', 'name')
                            ->searchable()
                            ->preload(),
                    ]),

                // Material Thickness Overrides Section
                Section::make('Material Thickness Overrides')
                    ->columnSpan(1)
                    ->description('Used when product has no thickness attribute')
                    ->schema([
                        TextInput::make('box_material_thickness')
                            ->label('Box Material')
                            ->numeric()
                            ->step(0.0625)
                            ->default(0.75)
                            ->suffix('"'),
                        TextInput::make('back_panel_thickness')
                            ->label('Back Panel')
                            ->numeric()
                            ->step(0.0625)
                            ->default(0.75)
                            ->suffix('"')
                            ->helperText('TCS: 3/4" full backs'),
                        TextInput::make('side_panel_thickness')
                            ->label('Side Panel')
                            ->numeric()
                            ->step(0.0625)
                            ->default(0.75)
                            ->suffix('"'),
                    ]),

                // Sink Cabinet Section
                Section::make('Sink Cabinet')
                    ->columnSpan(1)
                    ->schema([
                        TextInput::make('sink_side_extension')
                            ->label('Side Extension')
                            ->numeric()
                            ->step(0.0625)
                            ->default(0.75)
                            ->suffix('"')
                            ->helperText('Extra height for countertop support (TCS: 3/4")'),
                    ]),

                // Section Layout Ratios
                Section::make('Section Layout Ratios')
                    ->columnSpan(1)
                    ->schema([
                        TextInput::make('drawer_bank_ratio')
                            ->label('Drawer Bank')
                            ->numeric()
                            ->step(0.05)
                            ->default(0.40)
                            ->helperText('Width ratio (0.40 = 40%)'),
                        TextInput::make('door_section_ratio')
                            ->label('Door Section')
                            ->numeric()
                            ->step(0.05)
                            ->default(0.60)
                            ->helperText('Width ratio (0.60 = 60%)'),
                        TextInput::make('equal_section_ratio')
                            ->label('Equal Split')
                            ->numeric()
                            ->step(0.05)
                            ->default(0.50)
                            ->helperText('Width ratio (0.50 = 50%)'),
                    ]),

                // Countertop Section
                Section::make('Countertop')
                    ->columnSpan(1)
                    ->schema([
                        TextInput::make('countertop_thickness')
                            ->label('Thickness')
                            ->numeric()
                            ->step(0.0625)
                            ->default(1.25)
                            ->suffix('"')
                            ->helperText('TCS: 1 1/4"'),
                        TextInput::make('finished_counter_height')
                            ->label('Finished Height')
                            ->numeric()
                            ->step(0.0625)
                            ->default(36.0)
                            ->suffix('"')
                            ->helperText('Total height from floor'),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Name')
                    ->searchable()
                    ->sortable(),
                IconColumn::make('is_default')
                    ->label('Default')
                    ->boolean(),
                IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean(),
                TextColumn::make('base_cabinet_height')
                    ->label('Base Height')
                    ->suffix('"')
                    ->sortable(),
                TextColumn::make('stretcher_depth')
                    ->label('Stretcher')
                    ->suffix('"'),
                TextColumn::make('face_frame_stile_width')
                    ->label('Stile')
                    ->suffix('"'),
                TextColumn::make('toe_kick_height')
                    ->label('Toe Kick')
                    ->suffix('"'),
                TextColumn::make('projects_count')
                    ->label('Projects')
                    ->counts('projects'),
            ])
            ->filters([
                TernaryFilter::make('is_active')
                    ->label('Active'),
                TernaryFilter::make('is_default')
                    ->label('Default'),
            ])
            ->defaultSort('sort_order')
            ->reorderable('sort_order')
            ->recordActions([
                EditAction::make()
                    ->successNotification(
                        Notification::make()
                            ->success()
                            ->title('Template Updated')
                            ->body('The construction template has been updated successfully.'),
                    ),
                DeleteAction::make()
                    ->before(function (ConstructionTemplate $record) {
                        if ($record->is_default) {
                            Notification::make()
                                ->danger()
                                ->title('Cannot Delete Default')
                                ->body('Set another template as default first.')
                                ->send();

                            return false;
                        }
                    })
                    ->successNotification(
                        Notification::make()
                            ->success()
                            ->title('Template Deleted')
                            ->body('The construction template has been deleted.'),
                    ),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageConstructionTemplates::route('/'),
        ];
    }
}
