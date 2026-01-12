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
use Webkul\Project\Filament\Clusters\Configurations\Resources\DrawerPresetResource\Pages\ManageDrawerPresets;
use Webkul\Project\Models\DrawerPreset;

class DrawerPresetResource extends Resource
{
    protected static ?string $model = DrawerPreset::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-inbox-stack';

    protected static ?int $navigationSort = 11;

    protected static ?string $cluster = Configurations::class;

    protected static string|\UnitEnum|null $navigationGroup = 'Component Presets';

    public static function getNavigationLabel(): string
    {
        return 'Drawer Presets';
    }

    public static function getModelLabel(): string
    {
        return 'Drawer Preset';
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

                Section::make('Drawer Specifications')
                    ->columnSpan(1)
                    ->schema([
                        Select::make('profile_type')
                            ->label('Profile Type')
                            ->options(DrawerPreset::profileTypeOptions()),
                        Select::make('box_material')
                            ->label('Box Material')
                            ->options(DrawerPreset::boxMaterialOptions()),
                        Select::make('joinery_method')
                            ->label('Joinery Method')
                            ->options(DrawerPreset::joineryMethodOptions()),
                    ]),

                Section::make('Slides')
                    ->columnSpan(1)
                    ->schema([
                        Select::make('slide_type')
                            ->label('Slide Type')
                            ->options(DrawerPreset::slideTypeOptions()),
                        TextInput::make('slide_model')
                            ->label('Slide Model')
                            ->maxLength(100),
                        Checkbox::make('soft_close')
                            ->label('Soft Close')
                            ->default(true),
                    ]),

                Section::make('Complexity')
                    ->columnSpan(1)
                    ->schema([
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
                    ->formatStateUsing(fn ($state) => DrawerPreset::profileTypeOptions()[$state] ?? $state),
                TextColumn::make('joinery_method')
                    ->label('Joinery')
                    ->formatStateUsing(fn ($state) => DrawerPreset::joineryMethodOptions()[$state] ?? $state),
                TextColumn::make('slide_type')
                    ->label('Slides')
                    ->formatStateUsing(fn ($state) => DrawerPreset::slideTypeOptions()[$state] ?? $state),
                IconColumn::make('soft_close')
                    ->label('Soft Close')
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
                TernaryFilter::make('soft_close')
                    ->label('Soft Close'),
            ])
            ->defaultSort('sort_order')
            ->reorderable('sort_order')
            ->recordActions([
                EditAction::make()
                    ->successNotification(
                        Notification::make()
                            ->success()
                            ->title('Drawer Preset Updated')
                            ->body('The drawer preset has been updated successfully.'),
                    ),
                DeleteAction::make()
                    ->successNotification(
                        Notification::make()
                            ->success()
                            ->title('Drawer Preset Deleted')
                            ->body('The drawer preset has been deleted.'),
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
            'index' => ManageDrawerPresets::route('/'),
        ];
    }
}
