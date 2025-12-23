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
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Support\Str;
use Webkul\TcsCms\Filament\Admin\Resources\ServiceResource\Pages;
use Webkul\TcsCms\Models\Service;

class ServiceResource extends Resource
{
    protected static ?string $model = Service::class;

    protected static ?string $slug = 'cms/services';

    protected static ?string $recordTitleAttribute = 'title';

    protected static ?int $navigationSort = 30;

    public static function getNavigationIcon(): string|BackedEnum|null
    {
        return 'heroicon-o-wrench-screwdriver';
    }

    public static function getNavigationLabel(): string
    {
        return 'Services';
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
                        Section::make('Service Details')
                            ->schema([
                                TextInput::make('title')
                                    ->required()
                                    ->live(onBlur: true)
                                    ->maxLength(255)
                                    ->afterStateUpdated(fn (string $operation, $state, Set $set) => $operation === 'create' ? $set('slug', Str::slug($state)) : null),
                                TextInput::make('slug')
                                    ->required()
                                    ->maxLength(255)
                                    ->unique(Service::class, 'slug', ignoreRecord: true),
                                TextInput::make('icon')
                                    ->placeholder('heroicon-o-wrench')
                                    ->helperText('Heroicon name for service icon'),
                                Select::make('category')
                                    ->options([
                                        'cabinets' => 'Custom Cabinets',
                                        'furniture' => 'Custom Furniture',
                                        'millwork' => 'Architectural Millwork',
                                        'commercial' => 'Commercial Projects',
                                        'residential' => 'Residential Projects',
                                        'restoration' => 'Restoration',
                                    ]),
                                Textarea::make('summary')
                                    ->rows(3)
                                    ->maxLength(500)
                                    ->columnSpanFull(),
                                RichEditor::make('description')
                                    ->columnSpanFull(),
                            ])
                            ->columns(2),

                        Section::make('Features & Process')
                            ->schema([
                                Repeater::make('features')
                                    ->schema([
                                        TextInput::make('feature')
                                            ->required(),
                                    ])
                                    ->simple(TextInput::make('feature'))
                                    ->defaultItems(3)
                                    ->columnSpanFull(),
                                Repeater::make('process_steps')
                                    ->schema([
                                        TextInput::make('title')
                                            ->required(),
                                        Textarea::make('description')
                                            ->rows(2),
                                    ])
                                    ->columns(2)
                                    ->defaultItems(0)
                                    ->columnSpanFull()
                                    ->collapsed(),
                            ]),

                        Section::make('Pricing & Timeline')
                            ->schema([
                                TextInput::make('price_range')
                                    ->placeholder('$5,000 - $25,000'),
                                TextInput::make('timeline')
                                    ->placeholder('4-8 weeks'),
                            ])
                            ->columns(2),

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
                                    ->directory('services'),
                                FileUpload::make('gallery')
                                    ->image()
                                    ->multiple()
                                    ->directory('services/gallery')
                                    ->reorderable(),
                            ]),

                        Section::make('Status')
                            ->schema([
                                Toggle::make('is_published')
                                    ->label('Published')
                                    ->default(false),
                                Toggle::make('featured')
                                    ->label('Featured Service')
                                    ->default(false),
                                Select::make('status')
                                    ->options([
                                        'draft' => 'Draft',
                                        'review' => 'In Review',
                                        'published' => 'Published',
                                    ])
                                    ->default('draft'),
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
                TextColumn::make('title')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('category')
                    ->badge()
                    ->sortable(),
                TextColumn::make('price_range')
                    ->label('Price'),
                IconColumn::make('is_published')
                    ->label('Published')
                    ->boolean(),
                IconColumn::make('featured')
                    ->boolean(),
                TextColumn::make('position')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('category')
                    ->options([
                        'cabinets' => 'Custom Cabinets',
                        'furniture' => 'Custom Furniture',
                        'millwork' => 'Architectural Millwork',
                        'commercial' => 'Commercial Projects',
                        'residential' => 'Residential Projects',
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
            'index' => Pages\ListServices::route('/'),
            'create' => Pages\CreateService::route('/create'),
            'view' => Pages\ViewService::route('/{record}'),
            'edit' => Pages\EditService::route('/{record}/edit'),
        ];
    }
}
