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
use Webkul\Product\Models\Product;
use Webkul\Project\Filament\Clusters\Configurations;
use Webkul\Project\Filament\Clusters\Configurations\Resources\PulloutPresetResource\Pages\ManagePulloutPresets;
use Webkul\Project\Models\PulloutPreset;

class PulloutPresetResource extends Resource
{
    protected static ?string $model = PulloutPreset::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-arrow-right-on-rectangle';

    protected static ?int $navigationSort = 13;

    protected static ?string $cluster = Configurations::class;

    protected static string|\UnitEnum|null $navigationGroup = 'Component Presets';

    public static function getNavigationLabel(): string
    {
        return 'Pullout Presets';
    }

    public static function getModelLabel(): string
    {
        return 'Pullout Preset';
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

                Section::make('Pullout Specifications')
                    ->columnSpan(1)
                    ->schema([
                        Select::make('pullout_type')
                            ->label('Pullout Type')
                            ->options(PulloutPreset::pulloutTypeOptions()),
                        Select::make('manufacturer')
                            ->label('Manufacturer')
                            ->options(PulloutPreset::manufacturerOptions()),
                        TextInput::make('model_number')
                            ->label('Model Number')
                            ->maxLength(100),
                        Select::make('mounting_type')
                            ->label('Mounting Type')
                            ->options(PulloutPreset::mountingTypeOptions()),
                    ]),

                Section::make('Slides')
                    ->columnSpan(1)
                    ->schema([
                        Select::make('slide_type')
                            ->label('Slide Type')
                            ->options(PulloutPreset::slideTypeOptions()),
                        TextInput::make('slide_model')
                            ->label('Slide Model')
                            ->maxLength(100),
                        Checkbox::make('soft_close')
                            ->label('Soft Close')
                            ->default(true),
                    ]),

                Section::make('Inventory Link')
                    ->columnSpan(1)
                    ->schema([
                        Select::make('product_id')
                            ->label('Linked Product')
                            ->searchable()
                            ->getSearchResultsUsing(fn (string $search): array =>
                                Product::where('name', 'like', "%{$search}%")
                                    ->limit(50)
                                    ->pluck('name', 'id')
                                    ->toArray()
                            )
                            ->getOptionLabelUsing(fn ($value): ?string =>
                                Product::find($value)?->name
                            )
                            ->helperText('Optional: Link to inventory product for automated ordering'),
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
                TextColumn::make('pullout_type')
                    ->label('Type')
                    ->formatStateUsing(fn ($state) => PulloutPreset::pulloutTypeOptions()[$state] ?? $state)
                    ->sortable(),
                TextColumn::make('manufacturer')
                    ->label('Manufacturer')
                    ->formatStateUsing(fn ($state) => PulloutPreset::manufacturerOptions()[$state] ?? $state),
                TextColumn::make('model_number')
                    ->label('Model #'),
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
            ])
            ->defaultSort('sort_order')
            ->reorderable('sort_order')
            ->recordActions([
                EditAction::make()
                    ->successNotification(
                        Notification::make()
                            ->success()
                            ->title('Pullout Preset Updated')
                            ->body('The pullout preset has been updated successfully.'),
                    ),
                DeleteAction::make()
                    ->successNotification(
                        Notification::make()
                            ->success()
                            ->title('Pullout Preset Deleted')
                            ->body('The pullout preset has been deleted.'),
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
            'index' => ManagePulloutPresets::route('/'),
        ];
    }
}
