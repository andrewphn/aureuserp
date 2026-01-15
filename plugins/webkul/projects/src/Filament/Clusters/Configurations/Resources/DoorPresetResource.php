<?php

namespace Webkul\Project\Filament\Clusters\Configurations\Resources;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Schemas\Components\Checkbox;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Select;
use Filament\Schemas\Components\Textarea;
use Filament\Schemas\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Webkul\Project\Filament\Clusters\Configurations;
use Webkul\Project\Filament\Clusters\Configurations\Resources\DoorPresetResource\Pages\ManageDoorPresets;
use Webkul\Project\Models\DoorPreset;

class DoorPresetResource extends Resource
{
    protected static ?string $model = DoorPreset::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-rectangle-group';

    protected static ?int $navigationSort = 10;

    protected static ?string $cluster = Configurations::class;

    protected static string|\UnitEnum|null $navigationGroup = 'Component Presets';

    public static function getNavigationLabel(): string
    {
        return 'Door Presets';
    }

    public static function getModelLabel(): string
    {
        return 'Door Preset';
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
                            ->label('Preset Name')
                            ->required()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true),
                        Textarea::make('description')
                            ->label('Description')
                            ->rows(2),
                        Checkbox::make('is_active')
                            ->label('Active')
                            ->default(true),
                        TextInput::make('sort_order')
                            ->label('Sort Order')
                            ->numeric()
                            ->default(0),
                    ]),

                Section::make('Door Profile')
                    ->columnSpan(1)
                    ->schema([
                        Select::make('profile_type')
                            ->label('Profile Type')
                            ->options(DoorPreset::profileTypeOptions()),
                        Select::make('fabrication_method')
                            ->label('Fabrication Method')
                            ->options(DoorPreset::fabricationMethodOptions()),
                        Select::make('hinge_type')
                            ->label('Hinge Type')
                            ->options(DoorPreset::hingeTypeOptions()),
                        TextInput::make('default_hinge_quantity')
                            ->label('Default Hinge Quantity')
                            ->numeric()
                            ->default(2)
                            ->minValue(1)
                            ->maxValue(6),
                    ]),

                Section::make('Glass Options')
                    ->columnSpan(1)
                    ->schema([
                        Checkbox::make('has_glass')
                            ->label('Has Glass')
                            ->reactive(),
                        Select::make('glass_type')
                            ->label('Glass Type')
                            ->options(DoorPreset::glassTypeOptions())
                            ->visible(fn ($get) => $get('has_glass')),
                        Checkbox::make('has_check_rail')
                            ->label('Has Check Rail'),
                    ]),

                Section::make('Default Dimensions')
                    ->columnSpan(1)
                    ->schema([
                        TextInput::make('default_rail_width_inches')
                            ->label('Default Rail Width (inches)')
                            ->numeric()
                            ->step(0.125),
                        TextInput::make('default_stile_width_inches')
                            ->label('Default Stile Width (inches)')
                            ->numeric()
                            ->step(0.125),
                        TextInput::make('estimated_complexity_score')
                            ->label('Estimated Complexity Score')
                            ->numeric()
                            ->disabled()
                            ->helperText('Auto-calculated on save'),
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
                TextColumn::make('profile_type')
                    ->label('Profile')
                    ->formatStateUsing(fn ($state) => DoorPreset::profileTypeOptions()[$state] ?? $state)
                    ->sortable(),
                TextColumn::make('hinge_type')
                    ->label('Hinge')
                    ->formatStateUsing(fn ($state) => DoorPreset::hingeTypeOptions()[$state] ?? $state),
                IconColumn::make('has_glass')
                    ->label('Glass')
                    ->boolean(),
                IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean(),
                TextColumn::make('estimated_complexity_score')
                    ->label('Complexity')
                    ->numeric(1)
                    ->sortable(),
            ])
            ->filters([
                TernaryFilter::make('is_active')
                    ->label('Active'),
                TernaryFilter::make('has_glass')
                    ->label('Has Glass'),
            ])
            ->defaultSort('sort_order')
            ->reorderable('sort_order')
            ->recordActions([
                EditAction::make()
                    ->successNotification(
                        Notification::make()
                            ->success()
                            ->title('Door Preset Updated')
                            ->body('The door preset has been updated successfully.'),
                    ),
                DeleteAction::make()
                    ->successNotification(
                        Notification::make()
                            ->success()
                            ->title('Door Preset Deleted')
                            ->body('The door preset has been deleted.'),
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
            'index' => ManageDoorPresets::route('/'),
        ];
    }
}
