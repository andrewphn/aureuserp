<?php

namespace Webkul\Blog\Filament\Admin\Resources;

use Webkul\Blog\Filament\Admin\Resources\BlogResource\Pages;
use Webkul\Blog\Filament\Admin\Resources\BlogResource\RelationManagers;
use Filament\Pages\SubNavigationPosition;
use Webkul\Blog\Models\Blog;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class BlogResource extends Resource
{
    protected static ?string $model = Blog::class;

    protected static ?string $navigationIcon = 'heroicon-o-newspaper';

    protected static SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;

    protected static ?string $recordTitleAttribute = 'name';

    public static function getNavigationLabel(): string
    {
        return __('blogs::filament/resources/blog.navigation.title');
    }

    public static function getNavigationGroup(): string
    {
        return __('blogs::filament/resources/blog.navigation.group');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('title')
                    ->required()
                    ->maxLength(255),
                Forms\Components\Textarea::make('content')
                    ->required()
                    ->columnSpanFull(),
                Forms\Components\TextInput::make('slug')
                    ->required()
                    ->maxLength(255),
                Forms\Components\FileUpload::make('image')
                    ->image(),
                Forms\Components\TextInput::make('author_name')
                    ->maxLength(255),
                Forms\Components\Toggle::make('is_active')
                    ->required(),
                Forms\Components\Toggle::make('is_published')
                    ->required(),
                Forms\Components\DateTimePicker::make('published_at'),
                Forms\Components\TextInput::make('visits')
                    ->required()
                    ->numeric()
                    ->default(0),
                Forms\Components\TextInput::make('meta_title')
                    ->maxLength(255),
                Forms\Components\TextInput::make('meta_keywords')
                    ->maxLength(255),
                Forms\Components\Textarea::make('meta_description')
                    ->columnSpanFull(),
                Forms\Components\Select::make('category_id')
                    ->relationship('category', 'name'),
                Forms\Components\Select::make('author_id')
                    ->relationship('author', 'name'),
                Forms\Components\Select::make('creator_id')
                    ->relationship('creator', 'name'),
                Forms\Components\Select::make('last_editor_id')
                    ->relationship('lastEditor', 'name'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->label(__('blogs::filament/resources/blog.table.columns.title'))
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('slug')
                    ->label(__('blogs::filament/resources/blog.table.columns.slug'))
                    ->sortable(),
                Tables\Columns\TextColumn::make('author.name')
                    ->label(__('blogs::filament/resources/blog.table.columns.author'))
                    ->sortable(),
                Tables\Columns\TextColumn::make('category.name')
                    ->label(__('blogs::filament/resources/blog.table.columns.category'))
                    ->sortable()
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('creator.name')
                    ->label(__('blogs::filament/resources/blog.table.columns.creator'))
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\IconColumn::make('is_published')
                    ->label(__('blogs::filament/resources/blog.table.columns.is-published'))
                    ->boolean()
                    ->sortable(),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label(__('blogs::filament/resources/blog.table.columns.updated-at'))
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('blogs::filament/resources/blog.table.columns.created-at'))
                    ->sortable(),
            ])
            ->groups([
                Tables\Grouping\Group::make('category.name')
                    ->label(__('blogs::filament/resources/blog.table.groups.category')),
                Tables\Grouping\Group::make('author.name')
                    ->label(__('blogs::filament/resources/blog.table.groups.author')),
                Tables\Grouping\Group::make('created_at')
                    ->label(__('blogs::filament/resources/blog.table.groups.created-at'))
                    ->date(),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\EditAction::make()
                        ->hidden(fn ($record) => $record->trashed()),
                    Tables\Actions\RestoreAction::make()
                        ->successNotification(
                            Notification::make()
                                ->success()
                                ->title(__('blogs::filament/resources/blog.table.actions.restore.notification.title'))
                                ->body(__('blogs::filament/resources/blog.table.actions.restore.notification.body')),
                        ),
                    Tables\Actions\DeleteAction::make()
                        ->successNotification(
                            Notification::make()
                                ->success()
                                ->title(__('blogs::filament/resources/blog.table.actions.delete.notification.title'))
                                ->body(__('blogs::filament/resources/blog.table.actions.delete.notification.body')),
                        ),
                    Tables\Actions\ForceDeleteAction::make()
                        ->successNotification(
                            Notification::make()
                                ->success()
                                ->title(__('blogs::filament/resources/blog.table.actions.force-delete.notification.title'))
                                ->body(__('blogs::filament/resources/blog.table.actions.force-delete.notification.body')),
                        ),
                ])
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\RestoreBulkAction::make()
                        ->successNotification(
                            Notification::make()
                                ->success()
                                ->title(__('blogs::filament/resources/blog.table.bulk-actions.restore.notification.title'))
                                ->body(__('blogs::filament/resources/blog.table.bulk-actions.restore.notification.body')),
                        ),
                    Tables\Actions\DeleteBulkAction::make()
                        ->successNotification(
                            Notification::make()
                                ->success()
                                ->title(__('blogs::filament/resources/blog.table.bulk-actions.delete.notification.title'))
                                ->body(__('blogs::filament/resources/blog.table.bulk-actions.delete.notification.body')),
                        ),
                    Tables\Actions\ForceDeleteBulkAction::make()
                        ->successNotification(
                            Notification::make()
                                ->success()
                                ->title(__('blogs::filament/resources/blog.table.bulk-actions.force-delete.notification.title'))
                                ->body(__('blogs::filament/resources/blog.table.bulk-actions.force-delete.notification.body')),
                        ),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListBlogs::route('/'),
            'create' => Pages\CreateBlog::route('/create'),
            'view'   => Pages\ViewBlog::route('/{record}'),
            'edit'   => Pages\EditBlog::route('/{record}/edit'),
        ];
    }
}
