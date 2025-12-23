<?php

namespace Webkul\TcsCms\Filament\Admin\Resources;

use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Webkul\TcsCms\Filament\Admin\Resources\HomeSectionResource\Pages;
use Webkul\TcsCms\Models\HomeSection;

class HomeSectionResource extends Resource
{
    protected static ?string $model = HomeSection::class;

    protected static ?string $slug = 'cms/home-sections';

    protected static ?string $recordTitleAttribute = 'title';

    protected static ?int $navigationSort = 5;

    public static function getNavigationIcon(): string|BackedEnum|null
    {
        return 'heroicon-o-home';
    }

    public static function getNavigationLabel(): string
    {
        return 'Home Sections';
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
                        Section::make('Section Details')
                            ->schema([
                                TextInput::make('section_key')
                                    ->required()
                                    ->maxLength(50)
                                    ->unique(HomeSection::class, 'section_key', ignoreRecord: true)
                                    ->helperText('Unique identifier (e.g., hero, featured-projects)'),
                                Select::make('section_type')
                                    ->options(HomeSection::SECTION_TYPES)
                                    ->required()
                                    ->reactive(),
                                Select::make('layout_style')
                                    ->options(HomeSection::LAYOUT_STYLES)
                                    ->default('default'),
                                TextInput::make('position')
                                    ->numeric()
                                    ->default(0)
                                    ->helperText('Lower = appears first'),
                                TextInput::make('title')
                                    ->maxLength(255)
                                    ->columnSpanFull(),
                                TextInput::make('subtitle')
                                    ->maxLength(255)
                                    ->columnSpanFull(),
                                RichEditor::make('content')
                                    ->columnSpanFull(),
                            ])
                            ->columns(2),

                        Section::make('Service Items')
                            ->schema([
                                Repeater::make('service_items')
                                    ->schema([
                                        TextInput::make('title')
                                            ->required(),
                                        Textarea::make('description')
                                            ->rows(2),
                                        TextInput::make('icon')
                                            ->placeholder('heroicon-o-wrench'),
                                        TextInput::make('link'),
                                    ])
                                    ->columns(2)
                                    ->defaultItems(0)
                                    ->collapsed(),
                            ])
                            ->hidden(fn ($get) => ! in_array($get('section_type'), ['services', 'custom'])),

                        Section::make('Testimonials')
                            ->schema([
                                Repeater::make('testimonial_items')
                                    ->schema([
                                        Textarea::make('quote')
                                            ->required()
                                            ->rows(3),
                                        TextInput::make('author')
                                            ->required(),
                                        TextInput::make('role'),
                                        TextInput::make('company'),
                                        FileUpload::make('avatar')
                                            ->image()
                                            ->directory('testimonials'),
                                    ])
                                    ->columns(2)
                                    ->defaultItems(0)
                                    ->collapsed(),
                            ])
                            ->hidden(fn ($get) => $get('section_type') !== 'testimonials'),

                        Section::make('Process Steps')
                            ->schema([
                                Repeater::make('process_steps')
                                    ->schema([
                                        TextInput::make('title')
                                            ->required(),
                                        Textarea::make('description')
                                            ->rows(2),
                                        TextInput::make('icon')
                                            ->placeholder('heroicon-o-clipboard'),
                                        TextInput::make('step_number')
                                            ->numeric(),
                                    ])
                                    ->columns(2)
                                    ->defaultItems(0)
                                    ->collapsed(),
                            ])
                            ->hidden(fn ($get) => $get('section_type') !== 'process'),

                        Section::make("Owner's Note")
                            ->schema([
                                KeyValue::make('author_info')
                                    ->keyLabel('Field')
                                    ->valueLabel('Value')
                                    ->keyPlaceholder('e.g., name')
                                    ->valuePlaceholder('e.g., Bryan Patton'),
                            ])
                            ->hidden(fn ($get) => $get('section_type') !== 'owner_note'),

                        Section::make('Additional Settings')
                            ->schema([
                                KeyValue::make('settings')
                                    ->keyLabel('Setting')
                                    ->valueLabel('Value'),
                            ])
                            ->collapsed(),
                    ])
                    ->columnSpan(['lg' => 2]),

                Group::make()
                    ->schema([
                        Section::make('Media')
                            ->schema([
                                FileUpload::make('image')
                                    ->image()
                                    ->directory('home-sections'),
                                FileUpload::make('background_image')
                                    ->image()
                                    ->directory('home-sections/backgrounds'),
                                FileUpload::make('additional_images')
                                    ->image()
                                    ->multiple()
                                    ->directory('home-sections/gallery')
                                    ->reorderable(),
                            ]),

                        Section::make('Status')
                            ->schema([
                                Toggle::make('is_active')
                                    ->label('Active')
                                    ->default(true),
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
                ImageColumn::make('image')
                    ->label('Image')
                    ->circular(),
                TextColumn::make('section_key')
                    ->label('Key')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('section_type')
                    ->label('Type')
                    ->badge()
                    ->formatStateUsing(fn ($state) => HomeSection::SECTION_TYPES[$state] ?? $state)
                    ->sortable(),
                TextColumn::make('title')
                    ->searchable()
                    ->limit(40),
                TextColumn::make('layout_style')
                    ->label('Layout')
                    ->badge(),
                IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean(),
                TextColumn::make('position')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('section_type')
                    ->options(HomeSection::SECTION_TYPES),
                TernaryFilter::make('is_active')
                    ->label('Active'),
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
            'index' => Pages\ListHomeSections::route('/'),
            'create' => Pages\CreateHomeSection::route('/create'),
            'view' => Pages\ViewHomeSection::route('/{record}'),
            'edit' => Pages\EditHomeSection::route('/{record}/edit'),
        ];
    }
}
