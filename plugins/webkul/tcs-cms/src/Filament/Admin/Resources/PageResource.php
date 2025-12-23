<?php

namespace Webkul\TcsCms\Filament\Admin\Resources;

use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Builder;
use Filament\Forms\Components\FileUpload;
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
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Webkul\TcsCms\Filament\Admin\Resources\PageResource\Pages;
use Webkul\TcsCms\Filament\Blocks\PageBlocks;
use Webkul\TcsCms\Models\Page;

class PageResource extends Resource
{
    protected static ?string $model = Page::class;

    protected static ?string $slug = 'cms/pages';

    protected static ?string $recordTitleAttribute = 'title';

    protected static ?int $navigationSort = 10;

    public static function getNavigationIcon(): string|BackedEnum|null
    {
        return 'heroicon-o-document-duplicate';
    }

    public static function getNavigationLabel(): string
    {
        return 'Pages';
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
                        Section::make('Page Details')
                            ->schema([
                                TextInput::make('title')
                                    ->required()
                                    ->live(onBlur: true)
                                    ->maxLength(255)
                                    ->afterStateUpdated(fn (string $operation, $state, Set $set) => $operation === 'create' ? $set('slug', Str::slug($state)) : null),
                                TextInput::make('slug')
                                    ->required()
                                    ->maxLength(255)
                                    ->unique(Page::class, 'slug', ignoreRecord: true)
                                    ->helperText('URL-friendly identifier (e.g., "about-us", "services")'),
                                RichEditor::make('content')
                                    ->columnSpanFull()
                                    ->helperText('Basic content. Use Page Blocks below for rich content layouts.'),
                            ])
                            ->columns(2),

                        Section::make('Page Blocks')
                            ->description('Add rich content blocks like FAQs, galleries, videos, tutorials, and more.')
                            ->schema([
                                Builder::make('blocks')
                                    ->blocks(PageBlocks::all())
                                    ->collapsible()
                                    ->cloneable()
                                    ->reorderable()
                                    ->blockNumbers(false)
                                    ->addActionLabel('Add Content Block')
                                    ->columnSpanFull(),
                            ])
                            ->collapsed()
                            ->collapsible(),

                        Section::make('SEO')
                            ->schema([
                                TextInput::make('meta_title')
                                    ->maxLength(60)
                                    ->helperText('Recommended: 50-60 characters'),
                                Textarea::make('meta_description')
                                    ->maxLength(160)
                                    ->rows(2)
                                    ->helperText('Recommended: 150-160 characters'),
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
                                    ->directory('pages'),
                                FileUpload::make('gallery')
                                    ->image()
                                    ->multiple()
                                    ->directory('pages/gallery')
                                    ->reorderable(),
                            ]),

                        Section::make('Settings')
                            ->schema([
                                Select::make('layout')
                                    ->options([
                                        'default' => 'Default',
                                        'homepage' => 'Homepage',
                                        'landing' => 'Landing Page',
                                        'full-width' => 'Full Width',
                                        'sidebar-left' => 'Sidebar Left',
                                        'sidebar-right' => 'Sidebar Right',
                                    ])
                                    ->default('default'),
                                Select::make('template')
                                    ->options([
                                        'default' => 'Default',
                                        'about' => 'About Us',
                                        'services' => 'Services',
                                        'portfolio' => 'Portfolio',
                                        'contact' => 'Contact',
                                    ])
                                    ->nullable(),
                                TextInput::make('position')
                                    ->numeric()
                                    ->default(0)
                                    ->helperText('Order in navigation'),
                            ]),

                        Section::make('Publishing')
                            ->schema([
                                Toggle::make('is_published')
                                    ->label('Published')
                                    ->default(false),
                                Toggle::make('show_in_navigation')
                                    ->label('Show in Navigation')
                                    ->default(false),
                                Select::make('status')
                                    ->options([
                                        'draft' => 'Draft',
                                        'review' => 'In Review',
                                        'published' => 'Published',
                                    ])
                                    ->default('draft'),
                                Select::make('creator_id')
                                    ->relationship('creator', 'name')
                                    ->default(fn () => Auth::id())
                                    ->searchable()
                                    ->preload()
                                    ->label('Created By'),
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
                    ->sortable()
                    ->limit(50),
                TextColumn::make('slug')
                    ->searchable()
                    ->color('gray')
                    ->prefix('/'),
                TextColumn::make('layout')
                    ->badge()
                    ->sortable(),
                IconColumn::make('is_published')
                    ->label('Published')
                    ->boolean(),
                IconColumn::make('show_in_navigation')
                    ->label('In Nav')
                    ->boolean(),
                TextColumn::make('position')
                    ->label('Order')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->label('Updated')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('layout')
                    ->options([
                        'default' => 'Default',
                        'homepage' => 'Homepage',
                        'landing' => 'Landing Page',
                        'full-width' => 'Full Width',
                    ]),
                TernaryFilter::make('is_published')
                    ->label('Published'),
                TernaryFilter::make('show_in_navigation')
                    ->label('In Navigation'),
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
            ->defaultSort('position', 'asc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPages::route('/'),
            'create' => Pages\CreatePage::route('/create'),
            'view' => Pages\ViewPage::route('/{record}'),
            'edit' => Pages\EditPage::route('/{record}/edit'),
        ];
    }
}
