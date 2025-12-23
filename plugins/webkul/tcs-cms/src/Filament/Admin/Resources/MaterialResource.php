<?php

namespace Webkul\TcsCms\Filament\Admin\Resources;

use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Support\Str;
use Webkul\TcsCms\Filament\Admin\Resources\MaterialResource\Pages;
use Webkul\TcsCms\Models\Material;

class MaterialResource extends Resource
{
    protected static ?string $model = Material::class;

    protected static ?string $slug = 'cms/materials';

    protected static ?string $recordTitleAttribute = 'name';

    protected static ?int $navigationSort = 40;

    public static function getNavigationIcon(): string|BackedEnum|null
    {
        return 'heroicon-o-cube';
    }

    public static function getNavigationLabel(): string
    {
        return 'Materials';
    }

    public static function getNavigationGroup(): string
    {
        return 'TCS CMS';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Group::make()
                    ->schema([
                        Section::make('Basic Information')
                            ->schema([
                                TextInput::make('name')
                                    ->required()
                                    ->live(onBlur: true)
                                    ->maxLength(255)
                                    ->afterStateUpdated(fn (string $operation, $state, Set $set) => $operation === 'create' ? $set('slug', Str::slug($state)) : null),
                                TextInput::make('slug')
                                    ->required()
                                    ->maxLength(255)
                                    ->unique(Material::class, 'slug', ignoreRecord: true),
                                TextInput::make('scientific_name')
                                    ->placeholder('e.g., Quercus alba'),
                                TextInput::make('common_names')
                                    ->placeholder('e.g., American White Oak'),
                                Select::make('type')
                                    ->options([
                                        'hardwood' => 'Hardwood',
                                        'softwood' => 'Softwood',
                                        'exotic' => 'Exotic Wood',
                                        'reclaimed' => 'Reclaimed Wood',
                                        'veneer' => 'Veneer',
                                        'plywood' => 'Plywood',
                                        'mdf' => 'MDF',
                                        'other' => 'Other',
                                    ]),
                                TextInput::make('origin')
                                    ->placeholder('e.g., North America'),
                                RichEditor::make('description')
                                    ->columnSpanFull(),
                            ])
                            ->columns(2),

                        Section::make('Physical Properties')
                            ->schema([
                                TextInput::make('hardness')
                                    ->placeholder('e.g., 1360 Janka')
                                    ->helperText('Janka hardness rating'),
                                TextInput::make('density')
                                    ->placeholder('e.g., 47 lbs/ft³'),
                                TextInput::make('grain_pattern')
                                    ->placeholder('e.g., Straight to slightly wavy'),
                                TextInput::make('color_range')
                                    ->placeholder('e.g., Light tan to medium brown'),
                            ])
                            ->columns(2),

                        Section::make('Working Characteristics')
                            ->schema([
                                Textarea::make('workability')
                                    ->rows(3)
                                    ->placeholder('How easy is it to work with?'),
                                Textarea::make('durability')
                                    ->rows(3)
                                    ->placeholder('Resistance to decay, insects, etc.'),
                                Select::make('sustainability_rating')
                                    ->options([
                                        'excellent' => 'Excellent - FSC Certified Available',
                                        'good' => 'Good - Responsibly Sourced',
                                        'moderate' => 'Moderate - Limited Availability',
                                        'concern' => 'Concern - Endangered Species',
                                    ]),
                            ])
                            ->columns(3),

                        Section::make('Applications')
                            ->schema([
                                Repeater::make('applications')
                                    ->simple(TextInput::make('application'))
                                    ->defaultItems(3),
                                Repeater::make('best_uses')
                                    ->simple(TextInput::make('use'))
                                    ->defaultItems(3),
                                Repeater::make('finish_recommendations')
                                    ->simple(TextInput::make('finish'))
                                    ->defaultItems(2),
                            ])
                            ->columns(3),

                        Section::make('SEO')
                            ->schema([
                                TextInput::make('meta_title')
                                    ->maxLength(60),
                                Textarea::make('meta_description')
                                    ->maxLength(160)
                                    ->rows(2),
                            ])
                            ->columns(2)
                            ->collapsed(),
                    ])
                    ->columnSpan(['lg' => 2]),

                Group::make()
                    ->schema([
                        Section::make('Media')
                            ->schema([
                                FileUpload::make('featured_image')
                                    ->image()
                                    ->directory('materials'),
                                FileUpload::make('gallery')
                                    ->image()
                                    ->multiple()
                                    ->directory('materials/gallery')
                                    ->reorderable(),
                            ]),

                        Section::make('Status')
                            ->schema([
                                Toggle::make('is_published')
                                    ->label('Published')
                                    ->default(false),
                                Toggle::make('featured')
                                    ->label('Featured Material')
                                    ->default(false),
                                TextInput::make('position')
                                    ->numeric()
                                    ->default(0)
                                    ->helperText('Lower = higher priority'),
                            ]),
                    ])
                    ->columnSpan(['lg' => 1]),
            ])
            ->columns(3);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('featured_image')
                    ->label('Image')
                    ->circular(),
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('type')
                    ->badge()
                    ->sortable(),
                TextColumn::make('hardness')
                    ->toggleable(),
                TextColumn::make('origin')
                    ->toggleable(),
                IconColumn::make('is_published')
                    ->label('Published')
                    ->boolean(),
                IconColumn::make('featured')
                    ->boolean(),
                TextColumn::make('position')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('type')
                    ->options([
                        'hardwood' => 'Hardwood',
                        'softwood' => 'Softwood',
                        'exotic' => 'Exotic Wood',
                        'reclaimed' => 'Reclaimed Wood',
                        'veneer' => 'Veneer',
                        'plywood' => 'Plywood',
                    ]),
                TernaryFilter::make('is_published')
                    ->label('Published'),
                TernaryFilter::make('featured'),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('position')
            ->reorderable('position');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListMaterials::route('/'),
            'create' => Pages\CreateMaterial::route('/create'),
            'view' => Pages\ViewMaterial::route('/{record}'),
            'edit' => Pages\EditMaterial::route('/{record}/edit'),
        ];
    }
}
