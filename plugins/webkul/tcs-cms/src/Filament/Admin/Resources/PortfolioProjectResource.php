<?php

namespace Webkul\TcsCms\Filament\Admin\Resources;

use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
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
use Illuminate\Support\Str;
use Webkul\TcsCms\Filament\Admin\Resources\PortfolioProjectResource\Pages;
use Webkul\TcsCms\Models\PortfolioProject;

class PortfolioProjectResource extends Resource
{
    protected static ?string $model = PortfolioProject::class;

    protected static ?string $slug = 'cms/portfolio-projects';

    protected static ?string $recordTitleAttribute = 'title';

    protected static ?int $navigationSort = 10;

    public static function getNavigationIcon(): string|BackedEnum|null
    {
        return 'heroicon-o-photo';
    }

    public static function getNavigationLabel(): string
    {
        return 'Portfolio Projects';
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
                        Section::make('Project Details')
                            ->schema([
                                TextInput::make('title')
                                    ->required()
                                    ->live(onBlur: true)
                                    ->maxLength(255)
                                    ->afterStateUpdated(fn (string $operation, $state, Set $set) => $operation === 'create' ? $set('slug', Str::slug($state)) : null),
                                TextInput::make('slug')
                                    ->required()
                                    ->maxLength(255)
                                    ->unique(PortfolioProject::class, 'slug', ignoreRecord: true),
                                Textarea::make('summary')
                                    ->rows(3)
                                    ->maxLength(500),
                                RichEditor::make('description')
                                    ->columnSpanFull(),
                            ])
                            ->columns(2),

                        Section::make('Classification')
                            ->schema([
                                Select::make('category')
                                    ->options([
                                        'cabinets' => 'Cabinets',
                                        'furniture' => 'Furniture',
                                        'millwork' => 'Millwork',
                                        'commercial' => 'Commercial',
                                        'residential' => 'Residential',
                                    ]),
                                TextInput::make('client_name'),
                                TextInput::make('location'),
                            ])
                            ->columns(3),
                    ])
                    ->columnSpan(['lg' => 2]),

                Group::make()
                    ->schema([
                        Section::make('Media')
                            ->schema([
                                FileUpload::make('featured_image')
                                    ->image()
                                    ->directory('portfolio'),
                            ]),

                        Section::make('Status')
                            ->schema([
                                Toggle::make('is_published')
                                    ->label('Published')
                                    ->default(false),
                                Toggle::make('featured')
                                    ->label('Featured Project')
                                    ->default(false),
                                Select::make('status')
                                    ->options([
                                        'draft' => 'Draft',
                                        'review' => 'In Review',
                                        'published' => 'Published',
                                    ])
                                    ->default('draft'),
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
                TextColumn::make('client_name')
                    ->label('Client')
                    ->searchable(),
                IconColumn::make('is_published')
                    ->label('Published')
                    ->boolean(),
                IconColumn::make('featured')
                    ->boolean(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('category')
                    ->options([
                        'cabinets' => 'Cabinets',
                        'furniture' => 'Furniture',
                        'millwork' => 'Millwork',
                        'commercial' => 'Commercial',
                        'residential' => 'Residential',
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
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPortfolioProjects::route('/'),
            'create' => Pages\CreatePortfolioProject::route('/create'),
            'view' => Pages\ViewPortfolioProject::route('/{record}'),
            'edit' => Pages\EditPortfolioProject::route('/{record}/edit'),
        ];
    }
}
