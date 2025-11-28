<?php

namespace Webkul\Inventory\Filament\Resources;

use Filament\Forms\Components\Grid;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Actions;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Tables\Filters\QueryBuilder;
use Filament\Tables\Filters\QueryBuilder\Constraints\TextConstraint;
use Webkul\Inventory\Filament\Resources\WoodworkingMaterialCategoryResource\Pages\CreateWoodworkingMaterialCategory;
use Webkul\Inventory\Filament\Resources\WoodworkingMaterialCategoryResource\Pages\EditWoodworkingMaterialCategory;
use Webkul\Inventory\Filament\Resources\WoodworkingMaterialCategoryResource\Pages\ListWoodworkingMaterialCategories;
use Webkul\Inventory\Models\WoodworkingMaterialCategory;

/**
 * Woodworking Material Category Resource Filament resource
 *
 * @see \Filament\Resources\Resource
 */
class WoodworkingMaterialCategoryResource extends Resource
{
    protected static ?string $model = WoodworkingMaterialCategory::class;

    protected static ?string $slug = 'inventory/woodworking-material-categories';

    protected static ?string $recordTitleAttribute = 'name';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-tag';

    public static function getNavigationLabel(): string
    {
        return 'Material Categories';
    }

    public static function getNavigationGroup(): string
    {
        return 'Inventory';
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
                Section::make('Material Category Details')
                    ->schema([
                        Grid::make(2)->schema([
                            TextInput::make('name')
                                ->label('Category Name')
                                ->required()
                                ->maxLength(100)
                                ->placeholder('e.g., Sheet Goods - Plywood'),
                            TextInput::make('code')
                                ->label('Category Code')
                                ->maxLength(50)
                                ->placeholder('e.g., SG-PLY')
                                ->helperText('Unique identifier for this category'),
                        ]),
                        RichEditor::make('description')
                            ->label('Description')
                            ->placeholder('Describe this material category'),
                        TextInput::make('sort_order')
                            ->label('Sort Order')
                            ->numeric()
                            ->default(0)
                            ->helperText('Display order (lower numbers appear first)'),
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
                TextColumn::make('name')
                    ->label('Category Name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('code')
                    ->label('Code')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('product_count')
                    ->label('Products')
                    ->state(fn (WoodworkingMaterialCategory $record) => $record->products()->count())
                    ->badge()
                    ->color('primary'),
                TextColumn::make('sort_order')
                    ->label('Sort Order')
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('sort_order', 'asc')
            ->filters([
                QueryBuilder::make()
                    ->constraints([
                        TextConstraint::make('name')
                            ->label('Category Name'),
                        TextConstraint::make('code')
                            ->label('Code'),
                    ]),
            ])
            ->actions([
                Actions\EditAction::make(),
                Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Actions\BulkActionGroup::make([
                    Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListWoodworkingMaterialCategories::route('/'),
            'create' => CreateWoodworkingMaterialCategory::route('/create'),
            'edit' => EditWoodworkingMaterialCategory::route('/{record}/edit'),
        ];
    }
}
