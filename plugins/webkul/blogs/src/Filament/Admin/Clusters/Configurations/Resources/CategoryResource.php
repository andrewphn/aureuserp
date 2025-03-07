<?php

namespace Webkul\Blog\Filament\Admin\Clusters\Configurations\Resources;

use Webkul\Website\Filament\Admin\Clusters\Configurations;
use Filament\Pages\SubNavigationPosition;
use Webkul\Blog\Filament\Admin\Clusters\Configurations\Resources\CategoryResource\Pages;
use Webkul\Blog\Filament\Admin\Clusters\Configurations\Resources\CategoryResource\RelationManagers;
use Webkul\Blog\Models\Category;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Notifications\Notification;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class CategoryResource extends Resource
{
    protected static ?string $model = Category::class;

    protected static ?string $cluster = Configurations::class;

    protected static ?string $navigationIcon = 'heroicon-o-folder';

    protected static ?string $recordTitleAttribute = 'name';

    public static function getNavigationLabel(): string
    {
        return __('blogs::filament/admin/clusters/configurations/resources/category.navigation.title');
    }

    public static function getNavigationGroup(): string
    {
        return __('blogs::filament/admin/clusters/configurations/resources/category.navigation.group');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->label(__('blogs::filament/admin/clusters/configurations/resources/category.form.fields.name'))
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('sub_title')
                    ->label(__('blogs::filament/admin/clusters/configurations/resources/category.form.fields.sub-title'))
                    ->maxLength(255),
            ])
            ->columns(1);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label(__('blogs::filament/admin/clusters/configurations/resources/category.table.columns.name'))
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('blogs::filament/admin/clusters/configurations/resources/category.table.columns.created-at'))
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->hidden(fn ($record) => $record->trashed())
                    ->successNotification(
                        Notification::make()
                            ->success()
                            ->title(__('blogs::filament/admin/clusters/configurations/resources/category.table.actions.edit.notification.title'))
                            ->body(__('blogs::filament/admin/clusters/configurations/resources/category.table.actions.edit.notification.body')),
                    ),
                Tables\Actions\RestoreAction::make()
                    ->successNotification(
                        Notification::make()
                            ->success()
                            ->title(__('blogs::filament/admin/clusters/configurations/resources/category.table.actions.restore.notification.title'))
                            ->body(__('blogs::filament/admin/clusters/configurations/resources/category.table.actions.restore.notification.body')),
                    ),
                Tables\Actions\DeleteAction::make()
                    ->successNotification(
                        Notification::make()
                            ->success()
                            ->title(__('blogs::filament/admin/clusters/configurations/resources/category.table.actions.delete.notification.title'))
                            ->body(__('blogs::filament/admin/clusters/configurations/resources/category.table.actions.delete.notification.body')),
                    ),
                Tables\Actions\ForceDeleteAction::make()
                    ->successNotification(
                        Notification::make()
                            ->success()
                            ->title(__('blogs::filament/admin/clusters/configurations/resources/category.table.actions.force-delete.notification.title'))
                            ->body(__('blogs::filament/admin/clusters/configurations/resources/category.table.actions.force-delete.notification.body')),
                    ),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\RestoreBulkAction::make()
                        ->successNotification(
                            Notification::make()
                                ->success()
                                ->title(__('blogs::filament/admin/clusters/configurations/resources/category.table.bulk-actions.restore.notification.title'))
                                ->body(__('blogs::filament/admin/clusters/configurations/resources/category.table.bulk-actions.restore.notification.body')),
                        ),
                    Tables\Actions\DeleteBulkAction::make()
                        ->successNotification(
                            Notification::make()
                                ->success()
                                ->title(__('blogs::filament/admin/clusters/configurations/resources/category.table.bulk-actions.delete.notification.title'))
                                ->body(__('blogs::filament/admin/clusters/configurations/resources/category.table.bulk-actions.delete.notification.body')),
                        ),
                    Tables\Actions\ForceDeleteBulkAction::make()
                        ->successNotification(
                            Notification::make()
                                ->success()
                                ->title(__('blogs::filament/admin/clusters/configurations/resources/category.table.bulk-actions.force-delete.notification.title'))
                                ->body(__('blogs::filament/admin/clusters/configurations/resources/category.table.bulk-actions.force-delete.notification.body')),
                        ),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageCategories::route('/'),
        ];
    }
}
