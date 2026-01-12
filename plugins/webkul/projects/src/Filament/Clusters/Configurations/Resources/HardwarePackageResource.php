<?php

namespace Webkul\Project\Filament\Clusters\Configurations\Resources;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Schemas\Components\Checkbox;
use Filament\Schemas\Components\ColorPicker;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Select;
use Filament\Schemas\Components\Textarea;
use Filament\Schemas\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\ColorColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Webkul\Project\Filament\Clusters\Configurations;
use Webkul\Project\Filament\Clusters\Configurations\Resources\HardwarePackageResource\Pages\ManageHardwarePackages;
use Webkul\Project\Models\HardwarePackage;

class HardwarePackageResource extends Resource
{
    protected static ?string $model = HardwarePackage::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-wrench-screwdriver';

    protected static ?int $navigationSort = 14;

    protected static ?string $cluster = Configurations::class;

    protected static string|\UnitEnum|null $navigationGroup = 'Hardware';

    public static function getNavigationLabel(): string
    {
        return 'Hardware Packages';
    }

    public static function getModelLabel(): string
    {
        return 'Hardware Package';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->components([
                Section::make('Basic Info')
                    ->columnSpan(1)
                    ->schema([
                        TextInput::make('name')
                            ->label('Package Name')
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
                            ->label('Default Package')
                            ->helperText('Only one package can be default'),
                        TextInput::make('sort_order')
                            ->label('Sort Order')
                            ->numeric()
                            ->default(0),
                    ]),

                Section::make('Pricing')
                    ->columnSpan(1)
                    ->schema([
                        Select::make('pricing_tier')
                            ->label('Pricing Tier')
                            ->options(HardwarePackage::pricingTierOptions()),
                        TextInput::make('price_multiplier')
                            ->label('Price Multiplier')
                            ->numeric()
                            ->step(0.01)
                            ->default(1.00)
                            ->helperText('1.00 = no change, 1.25 = 25% markup'),
                        ColorPicker::make('color')
                            ->label('UI Color')
                            ->hexColor()
                            ->helperText('Color for badges and UI elements'),
                    ]),

                Section::make('Hinge Defaults')
                    ->columnSpan(1)
                    ->schema([
                        Select::make('default_hinge_type')
                            ->label('Default Hinge Type')
                            ->options(HardwarePackage::hingeTypeOptions()),
                        TextInput::make('default_hinge_model')
                            ->label('Default Hinge Model')
                            ->maxLength(100),
                        TextInput::make('hinges_per_door')
                            ->label('Hinges Per Door')
                            ->numeric()
                            ->default(2)
                            ->minValue(1)
                            ->maxValue(6),
                    ]),

                Section::make('Slide Defaults')
                    ->columnSpan(1)
                    ->schema([
                        Select::make('default_slide_type')
                            ->label('Default Slide Type')
                            ->options(HardwarePackage::slideTypeOptions()),
                        TextInput::make('default_slide_model')
                            ->label('Default Slide Model')
                            ->maxLength(100),
                        Checkbox::make('default_soft_close')
                            ->label('Soft Close by Default')
                            ->default(true),
                    ]),

                Section::make('Construction Defaults')
                    ->columnSpan(2)
                    ->columns(2)
                    ->schema([
                        Select::make('default_joinery_method')
                            ->label('Default Joinery Method')
                            ->options(HardwarePackage::joineryMethodOptions()),
                        Select::make('default_box_material')
                            ->label('Default Box Material')
                            ->options(HardwarePackage::boxMaterialOptions()),
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
                TextColumn::make('pricing_tier')
                    ->label('Tier')
                    ->formatStateUsing(fn ($state) => HardwarePackage::pricingTierOptions()[$state] ?? $state)
                    ->badge()
                    ->color(fn ($state) => match ($state) {
                        'budget' => 'gray',
                        'standard' => 'primary',
                        'premium' => 'warning',
                        'luxury' => 'success',
                        default => 'gray',
                    }),
                TextColumn::make('price_multiplier')
                    ->label('Multiplier')
                    ->formatStateUsing(fn ($state) => number_format($state, 2) . 'x'),
                TextColumn::make('default_slide_type')
                    ->label('Slides')
                    ->formatStateUsing(fn ($state) => HardwarePackage::slideTypeOptions()[$state] ?? $state),
                IconColumn::make('default_soft_close')
                    ->label('Soft Close')
                    ->boolean(),
                IconColumn::make('is_default')
                    ->label('Default')
                    ->boolean(),
                IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean(),
                ColorColumn::make('color')
                    ->label('Color'),
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
                            ->title('Hardware Package Updated')
                            ->body('The hardware package has been updated successfully.'),
                    ),
                DeleteAction::make()
                    ->successNotification(
                        Notification::make()
                            ->success()
                            ->title('Hardware Package Deleted')
                            ->body('The hardware package has been deleted.'),
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
            'index' => ManageHardwarePackages::route('/'),
        ];
    }
}
