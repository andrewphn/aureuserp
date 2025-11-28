<?php

namespace Webkul\Project\Filament\Clusters\Configurations\Resources;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Grouping\Group;
use Filament\Tables\Table;
use Webkul\Project\Filament\Clusters\Configurations;
use Webkul\Project\Filament\Clusters\Configurations\Resources\MilestoneResource\Pages;
use Webkul\Project\Filament\Resources\ProjectResource\Pages\ManageMilestones;
use Webkul\Project\Filament\Resources\ProjectResource\RelationManagers\MilestonesRelationManager;
use Webkul\Project\Models\Milestone;
use Webkul\Project\Settings\TaskSettings;

class MilestoneResource extends Resource
{
    protected static ?string $model = Milestone::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-flag';

    protected static ?int $navigationSort = 3;

    protected static ?string $cluster = Configurations::class;

    public static function getNavigationLabel(): string
    {
        return __('webkul-project::filament/clusters/configurations/resources/milestone.navigation.title');
    }

    public static function isDiscovered(): bool
    {
        if (app()->runningInConsole()) {
            return true;
        }

        return app(TaskSettings::class)->enable_milestones;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label(__('webkul-project::filament/clusters/configurations/resources/milestone.form.name'))
                    ->required()
                    ->maxLength(255)
                    ->columnSpanFull(),

                DateTimePicker::make('deadline')
                    ->label(__('webkul-project::filament/clusters/configurations/resources/milestone.form.deadline'))
                    ->native(false)
                    ->required(),

                Select::make('production_stage')
                    ->label('Production Stage')
                    ->options([
                        'discovery' => 'Discovery',
                        'design' => 'Design',
                        'sourcing' => 'Sourcing',
                        'production' => 'Production',
                        'delivery' => 'Delivery',
                    ])
                    ->placeholder('Auto-assign by deadline')
                    ->native(false),

                Toggle::make('is_critical')
                    ->label('Critical Milestone')
                    ->helperText('Critical milestones appear prominently in timeline')
                    ->inline(false)
                    ->columnSpan(1),

                Toggle::make('is_completed')
                    ->label(__('webkul-project::filament/clusters/configurations/resources/milestone.form.is-completed'))
                    ->inline(false)
                    ->columnSpan(1),

                Textarea::make('description')
                    ->label('Description')
                    ->helperText('Additional context or requirements for this milestone')
                    ->rows(3)
                    ->columnSpanFull(),

                TextInput::make('sort_order')
                    ->label('Sort Order')
                    ->helperText('Manual ordering within stage (0 = auto-sort by date)')
                    ->numeric()
                    ->default(0)
                    ->minValue(0)
                    ->columnSpan(1),

                Select::make('project_id')
                    ->label(__('webkul-project::filament/clusters/configurations/resources/milestone.form.project'))
                    ->relationship('project', 'name')
                    ->hiddenOn([
                        MilestonesRelationManager::class,
                        ManageMilestones::class,
                    ])
                    ->required()
                    ->searchable()
                    ->preload()
                    ->columnSpan(1),
            ])
            ->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                IconColumn::make('is_critical')
                    ->label('')
                    ->boolean()
                    ->trueIcon('heroicon-o-exclamation-triangle')
                    ->falseIcon('')
                    ->trueColor('danger')
                    ->tooltip(fn ($state) => $state ? 'Critical Milestone' : '')
                    ->alignCenter()
                    ->grow(false),

                TextColumn::make('name')
                    ->label(__('webkul-project::filament/clusters/configurations/resources/milestone.table.columns.name'))
                    ->description(fn (Milestone $record): string => $record->description ?? '')
                    ->searchable()
                    ->sortable()
                    ->wrap(),

                TextColumn::make('production_stage')
                    ->label('Stage')
                    ->badge()
                    ->color(fn (string $state = null): string => match ($state) {
                        'discovery' => 'purple',
                        'design' => 'info',
                        'sourcing' => 'warning',
                        'production' => 'success',
                        'delivery' => 'indigo',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state = null): string => $state ? ucfirst($state) : 'Auto')
                    ->sortable(),

                TextColumn::make('deadline')
                    ->label(__('webkul-project::filament/clusters/configurations/resources/milestone.table.columns.deadline'))
                    ->dateTime()
                    ->sortable()
                    ->color(fn (Milestone $record): string =>
                        $record->is_overdue ? 'danger' : 'gray'
                    ),

                ToggleColumn::make('is_completed')
                    ->label(__('webkul-project::filament/clusters/configurations/resources/milestone.table.columns.is-completed'))
                    ->beforeStateUpdated(function ($record, $state) {
                        $record->completed_at = $state ? now() : null;
                    })
                    ->sortable(),

                TextColumn::make('completed_at')
                    ->label(__('webkul-project::filament/clusters/configurations/resources/milestone.table.columns.completed-at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('project.name')
                    ->label(__('webkul-project::filament/clusters/configurations/resources/milestone.table.columns.project'))
                    ->hiddenOn([
                        MilestonesRelationManager::class,
                        ManageMilestones::class,
                    ])
                    ->sortable()
                    ->searchable(),

                TextColumn::make('creator.name')
                    ->label(__('webkul-project::filament/clusters/configurations/resources/milestone.table.columns.creator'))
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('sort_order')
                    ->label('Order')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('created_at')
                    ->label(__('webkul-project::filament/clusters/configurations/resources/milestone.table.columns.created-at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('updated_at')
                    ->label(__('webkul-project::filament/clusters/configurations/resources/milestone.table.columns.updated-at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->groups([
                Group::make('production_stage')
                    ->label('Production Stage'),
                Group::make('project.name')
                    ->label(__('webkul-project::filament/clusters/configurations/resources/milestone.table.groups.project')),
                Group::make('is_completed')
                    ->label(__('webkul-project::filament/clusters/configurations/resources/milestone.table.groups.is-completed')),
                Group::make('created_at')
                    ->label(__('webkul-project::filament/clusters/configurations/resources/milestone.table.groups.created-at'))
                    ->date(),
            ])
            ->filters([
                TernaryFilter::make('is_critical')
                    ->label('Critical Milestones'),
                TernaryFilter::make('is_completed')
                    ->label(__('webkul-project::filament/clusters/configurations/resources/milestone.table.filters.is-completed')),
                SelectFilter::make('production_stage')
                    ->label('Production Stage')
                    ->options([
                        'discovery' => 'Discovery',
                        'design' => 'Design',
                        'sourcing' => 'Sourcing',
                        'production' => 'Production',
                        'delivery' => 'Delivery',
                    ]),
                SelectFilter::make('project_id')
                    ->label(__('webkul-project::filament/clusters/configurations/resources/milestone.table.filters.project'))
                    ->relationship('project', 'name')
                    ->hiddenOn([
                        MilestonesRelationManager::class,
                        ManageMilestones::class,
                    ])
                    ->searchable()
                    ->preload(),
                SelectFilter::make('creator_id')
                    ->label(__('webkul-project::filament/clusters/configurations/resources/milestone.table.filters.creator'))
                    ->relationship('creator', 'name')
                    ->searchable()
                    ->preload(),
            ])
            ->recordActions([
                EditAction::make()
                    ->successNotification(
                        Notification::make()
                            ->success()
                            ->title(__('webkul-project::filament/clusters/configurations/resources/milestone.table.actions.edit.notification.title'))
                            ->body(__('webkul-project::filament/clusters/configurations/resources/milestone.table.actions.edit.notification.body')),
                    ),
                DeleteAction::make()
                    ->successNotification(
                        Notification::make()
                            ->success()
                            ->title(__('webkul-project::filament/clusters/configurations/resources/milestone.table.actions.delete.notification.title'))
                            ->body(__('webkul-project::filament/clusters/configurations/resources/milestone.table.actions.delete.notification.body')),
                    ),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->successNotification(
                            Notification::make()
                                ->success()
                                ->title(__('webkul-project::filament/clusters/configurations/resources/milestone.table.bulk-actions.delete.notification.title'))
                                ->body(__('webkul-project::filament/clusters/configurations/resources/milestone.table.bulk-actions.delete.notification.body')),
                        ),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageMilestones::route('/'),
        ];
    }
}
