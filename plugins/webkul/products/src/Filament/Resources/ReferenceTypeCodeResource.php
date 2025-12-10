<?php

namespace Webkul\Product\Filament\Resources;

use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Enums\FontWeight;
use Filament\Support\Enums\TextSize;
use Filament\Tables;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\QueryException;
use Webkul\Product\Models\ReferenceTypeCode;

/**
 * Reference Type Code Resource for managing product type codes
 */
class ReferenceTypeCodeResource extends Resource
{
    protected static ?string $model = ReferenceTypeCode::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-tag';

    protected static ?string $navigationLabel = 'Reference Type Codes';

    protected static ?string $modelLabel = 'Reference Type Code';

    protected static ?string $pluralModelLabel = 'Reference Type Codes';

    protected static ?string $navigationGroup = 'Settings';

    protected static ?int $navigationSort = 50;

    /**
     * Define the form schema
     */
    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Group::make()
                    ->schema([
                        Section::make('Type Code Details')
                            ->schema([
                                Select::make('category_id')
                                    ->label('Category')
                                    ->relationship('category', 'full_name')
                                    ->required()
                                    ->searchable()
                                    ->preload()
                                    ->helperText('Select the product category this type code belongs to'),
                                TextInput::make('code')
                                    ->label('Code')
                                    ->required()
                                    ->maxLength(10)
                                    ->placeholder('e.g., GLUE, SAW, HINGE')
                                    ->helperText('Short uppercase code (max 10 chars)')
                                    ->extraInputAttributes(['style' => 'text-transform: uppercase;']),
                                TextInput::make('name')
                                    ->label('Display Name')
                                    ->required()
                                    ->maxLength(100)
                                    ->placeholder('e.g., Glue, Saw Blade, Hinge')
                                    ->helperText('Human-readable name for this type'),
                                Toggle::make('is_active')
                                    ->label('Active')
                                    ->default(true)
                                    ->helperText('Inactive codes will not appear in dropdowns'),
                            ]),
                    ])
                    ->columnSpan(['lg' => 2]),

                Group::make()
                    ->schema([
                        Section::make('Preview')
                            ->schema([
                                \Filament\Forms\Components\Placeholder::make('preview')
                                    ->label('Reference Format Preview')
                                    ->content(fn ($get) => self::generatePreview($get('category_id'), $get('code')))
                                    ->helperText('This is how the reference code will appear'),
                            ]),
                    ])
                    ->columnSpan(['lg' => 1]),
            ])
            ->columns(3);
    }

    /**
     * Generate a preview of the reference code format
     */
    protected static function generatePreview(?int $categoryId, ?string $code): string
    {
        if (!$categoryId || !$code) {
            return 'TCS-???-???-01';
        }

        $category = \Webkul\Product\Models\Category::find($categoryId);
        $categoryCode = $category?->code ?? '???';

        return "TCS-{$categoryCode}-" . strtoupper($code) . "-01";
    }

    /**
     * Define the table schema
     */
    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('code')
                    ->label('Code')
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color('primary'),
                TextColumn::make('name')
                    ->label('Name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('category.full_name')
                    ->label('Category')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('category.code')
                    ->label('Category Code')
                    ->badge()
                    ->color('gray'),
                IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean()
                    ->sortable(),
                TextColumn::make('products_count')
                    ->label('Products')
                    ->counts('products')
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('category_id')
            ->groups([
                Tables\Grouping\Group::make('category.full_name')
                    ->label('Category')
                    ->collapsible(),
            ])
            ->filters([
                SelectFilter::make('category_id')
                    ->label('Category')
                    ->relationship('category', 'full_name')
                    ->searchable()
                    ->preload(),
                TernaryFilter::make('is_active')
                    ->label('Active'),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
                DeleteAction::make()
                    ->action(function (ReferenceTypeCode $record) {
                        try {
                            $record->delete();
                        } catch (QueryException $e) {
                            Notification::make()
                                ->danger()
                                ->title('Cannot Delete')
                                ->body('This type code is being used by products and cannot be deleted.')
                                ->send();
                        }
                    }),
            ])
            ->toolbarActions([
                DeleteBulkAction::make()
                    ->action(function (Collection $records) {
                        try {
                            $records->each(fn (Model $record) => $record->delete());
                        } catch (QueryException $e) {
                            Notification::make()
                                ->danger()
                                ->title('Cannot Delete')
                                ->body('Some type codes are being used by products and cannot be deleted.')
                                ->send();
                        }
                    }),
            ])
            ->emptyStateActions([
                CreateAction::make()
                    ->icon('heroicon-o-plus-circle'),
            ]);
    }

    /**
     * Define the infolist schema
     */
    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                Group::make()
                    ->schema([
                        Section::make('Type Code Details')
                            ->schema([
                                TextEntry::make('code')
                                    ->label('Code')
                                    ->weight(FontWeight::Bold)
                                    ->size(TextSize::Large)
                                    ->badge()
                                    ->color('primary'),
                                TextEntry::make('name')
                                    ->label('Display Name')
                                    ->icon('heroicon-o-tag'),
                                TextEntry::make('category.full_name')
                                    ->label('Category')
                                    ->icon('heroicon-o-folder'),
                                TextEntry::make('is_active')
                                    ->label('Status')
                                    ->badge()
                                    ->formatStateUsing(fn (bool $state) => $state ? 'Active' : 'Inactive')
                                    ->color(fn (bool $state) => $state ? 'success' : 'danger'),
                            ]),
                    ])
                    ->columnSpan(['lg' => 2]),

                Group::make()
                    ->schema([
                        Section::make('Record Information')
                            ->schema([
                                TextEntry::make('creator.name')
                                    ->label('Created By')
                                    ->icon('heroicon-o-user')
                                    ->placeholder('System'),
                                TextEntry::make('created_at')
                                    ->label('Created At')
                                    ->dateTime()
                                    ->icon('heroicon-o-calendar'),
                                TextEntry::make('updated_at')
                                    ->label('Updated At')
                                    ->dateTime()
                                    ->icon('heroicon-o-clock'),
                            ])
                            ->icon('heroicon-o-information-circle')
                            ->collapsible(),
                    ])
                    ->columnSpan(['lg' => 1]),
            ])
            ->columns(3);
    }

    public static function getPages(): array
    {
        return [
            'index' => \Webkul\Product\Filament\Resources\ReferenceTypeCodeResource\Pages\ListReferenceTypeCodes::route('/'),
            'create' => \Webkul\Product\Filament\Resources\ReferenceTypeCodeResource\Pages\CreateReferenceTypeCode::route('/create'),
            'view' => \Webkul\Product\Filament\Resources\ReferenceTypeCodeResource\Pages\ViewReferenceTypeCode::route('/{record}'),
            'edit' => \Webkul\Product\Filament\Resources\ReferenceTypeCodeResource\Pages\EditReferenceTypeCode::route('/{record}/edit'),
        ];
    }
}
