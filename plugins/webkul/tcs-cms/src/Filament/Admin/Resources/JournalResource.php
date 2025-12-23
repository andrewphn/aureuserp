<?php

namespace Webkul\TcsCms\Filament\Admin\Resources;

use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Builder;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
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
use Webkul\TcsCms\Filament\Admin\Resources\JournalResource\Pages;
use Webkul\TcsCms\Filament\Blocks\PageBlocks;
use Webkul\TcsCms\Models\Journal;

class JournalResource extends Resource
{
    protected static ?string $model = Journal::class;

    protected static ?string $slug = 'cms/journals';

    protected static ?string $recordTitleAttribute = 'title';

    protected static ?int $navigationSort = 20;

    public static function getNavigationIcon(): string|BackedEnum|null
    {
        return 'heroicon-o-document-text';
    }

    public static function getNavigationLabel(): string
    {
        return 'Journal';
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
                        Section::make('Article Details')
                            ->schema([
                                TextInput::make('title')
                                    ->required()
                                    ->live(onBlur: true)
                                    ->maxLength(255)
                                    ->afterStateUpdated(fn (string $operation, $state, Set $set) => $operation === 'create' ? $set('slug', Str::slug($state)) : null),
                                TextInput::make('slug')
                                    ->required()
                                    ->maxLength(255)
                                    ->unique(Journal::class, 'slug', ignoreRecord: true),
                                Textarea::make('excerpt')
                                    ->rows(3)
                                    ->maxLength(500)
                                    ->helperText('Brief summary for listings and SEO'),
                                RichEditor::make('content')
                                    ->columnSpanFull()
                                    ->helperText('Basic article content. Use Page Blocks below for rich content layouts.'),
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

                        Section::make('Classification')
                            ->schema([
                                Select::make('category')
                                    ->options([
                                        'craftsmanship' => 'Craftsmanship',
                                        'design' => 'Design Insights',
                                        'materials' => 'Materials & Wood',
                                        'projects' => 'Project Stories',
                                        'tips' => 'Tips & Techniques',
                                        'news' => 'Company News',
                                        'sustainability' => 'Sustainability',
                                    ]),
                                TagsInput::make('tags')
                                    ->separator(','),
                                TextInput::make('read_time')
                                    ->numeric()
                                    ->suffix('minutes')
                                    ->helperText('Auto-calculated if left blank'),
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
                                    ->directory('journal'),
                                FileUpload::make('gallery')
                                    ->image()
                                    ->multiple()
                                    ->directory('journal/gallery')
                                    ->reorderable(),
                            ]),

                        Section::make('Publishing')
                            ->schema([
                                Toggle::make('is_published')
                                    ->label('Published')
                                    ->default(false),
                                DateTimePicker::make('published_at')
                                    ->label('Publish Date')
                                    ->default(now()),
                                Select::make('status')
                                    ->options([
                                        'draft' => 'Draft',
                                        'review' => 'In Review',
                                        'scheduled' => 'Scheduled',
                                        'published' => 'Published',
                                    ])
                                    ->default('draft'),
                                Select::make('author_id')
                                    ->relationship('author', 'name')
                                    ->default(fn () => Auth::id())
                                    ->searchable()
                                    ->preload(),
                            ]),

                        Section::make('Stats')
                            ->schema([
                                TextInput::make('view_count')
                                    ->numeric()
                                    ->default(0)
                                    ->disabled(),
                            ])
                            ->hidden(fn ($record) => $record === null),
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
                TextColumn::make('category')
                    ->badge()
                    ->sortable(),
                TextColumn::make('author.name')
                    ->label('Author')
                    ->sortable(),
                IconColumn::make('is_published')
                    ->label('Published')
                    ->boolean(),
                TextColumn::make('published_at')
                    ->label('Published')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('view_count')
                    ->label('Views')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('category')
                    ->options([
                        'craftsmanship' => 'Craftsmanship',
                        'design' => 'Design Insights',
                        'materials' => 'Materials & Wood',
                        'projects' => 'Project Stories',
                        'tips' => 'Tips & Techniques',
                        'news' => 'Company News',
                    ]),
                TernaryFilter::make('is_published')
                    ->label('Published'),
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
            ->defaultSort('published_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListJournals::route('/'),
            'create' => Pages\CreateJournal::route('/create'),
            'view' => Pages\ViewJournal::route('/{record}'),
            'edit' => Pages\EditJournal::route('/{record}/edit'),
        ];
    }
}
