<?php

namespace Webkul\Project\Filament\Clusters\Configurations\Resources;

use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\ColorColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Grouping\Group;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\QueryException;
use Webkul\Project\Filament\Clusters\Configurations;
use Webkul\Project\Filament\Clusters\Configurations\Resources\ProjectStageResource\Pages\ManageProjectStages;
use Webkul\Project\Models\ProjectStage;
use Webkul\Project\Settings\TaskSettings;

/**
 * Project Stage Resource Filament resource
 *
 * @see \Filament\Resources\Resource
 */
class ProjectStageResource extends Resource
{
    protected static ?string $model = ProjectStage::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-squares-2x2';

    protected static ?int $navigationSort = 1;

    protected static ?string $cluster = Configurations::class;

    public static function getNavigationLabel(): string
    {
        return __('webkul-project::filament/clusters/configurations/resources/project-stage.navigation.title');
    }

    /**
     * Is Discovered
     *
     * @return bool
     */
    public static function isDiscovered(): bool
    {
        if (app()->runningInConsole()) {
            return true;
        }

        return app(TaskSettings::class)->enable_project_stages;
    }

    /**
     * Define the form schema
     *
     * @param Schema $schema
     * @return Schema
     */
    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Basic Information')
                    ->schema([
                        TextInput::make('name')
                            ->label(__('webkul-project::filament/clusters/configurations/resources/project-stage.form.name'))
                            ->required()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true),

                        ColorPicker::make('color')
                            ->label('Stage Color')
                            ->helperText('Color coding for quick visual reference on project pages')
                            ->default('#3B82F6'),
                    ]),

                Section::make('Stage Expiry Settings')
                    ->description('Configure how long projects can stay in this stage before triggering a warning')
                    ->schema([
                        TextInput::make('max_days_in_stage')
                            ->label('Maximum Days in Stage')
                            ->numeric()
                            ->minValue(1)
                            ->maxValue(365)
                            ->placeholder('Leave empty for unlimited')
                            ->helperText('Projects exceeding this will show a warning on the Kanban board'),

                        TextInput::make('expiry_warning_days')
                            ->label('Warning Days Before Expiry')
                            ->numeric()
                            ->minValue(1)
                            ->maxValue(30)
                            ->default(3)
                            ->helperText('Show warning this many days before the limit is reached'),
                    ])
                    ->columns(2)
                    ->collapsible(),

                Section::make('Stage Notice')
                    ->description('Display a custom notice in the Kanban column header for this stage')
                    ->schema([
                        Textarea::make('notice_message')
                            ->label('Notice Message')
                            ->rows(2)
                            ->placeholder('e.g., "Requires manager approval before moving to next stage"')
                            ->helperText('This message will appear in the Kanban column header'),

                        Select::make('notice_severity')
                            ->label('Notice Severity')
                            ->options([
                                'info' => 'Info (Blue)',
                                'warning' => 'Warning (Orange)',
                                'danger' => 'Danger (Red)',
                            ])
                            ->default('info')
                            ->helperText('Controls the visual urgency of the notice'),
                    ])
                    ->collapsible(),
            ])
            ->columns(1);
    }

    /**
     * Define the table schema
     *
     * @param Table $table
     * @return Table
     */
    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label(__('webkul-project::filament/clusters/configurations/resources/project-stage.table.columns.name'))
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color(fn (ProjectStage $record): string => $record->color ?? 'gray'),

                ColorColumn::make('color')
                    ->label('Color')
                    ->sortable(),
            ])
            ->groups([
                Group::make('created_at')
                    ->label(__('webkul-project::filament/clusters/configurations/resources/project-stage.table.columns.created-at'))
                    ->date(),
            ])
            ->reorderable('sort')
            ->defaultSort('sort', 'desc')
            ->recordActions([
                EditAction::make()
                    ->hidden(fn ($record) => $record->trashed())
                    ->successNotification(
                        Notification::make()
                            ->success()
                            ->title(__('webkul-project::filament/clusters/configurations/resources/project-stage.table.actions.edit.notification.title'))
                            ->body(__('webkul-project::filament/clusters/configurations/resources/project-stage.table.actions.edit.notification.body')),
                    ),
                RestoreAction::make()
                    ->successNotification(
                        Notification::make()
                            ->success()
                            ->title(__('webkul-project::filament/clusters/configurations/resources/project-stage.table.actions.restore.notification.title'))
                            ->body(__('webkul-project::filament/clusters/configurations/resources/project-stage.table.actions.restore.notification.body')),
                    ),
                DeleteAction::make()
                    ->successNotification(
                        Notification::make()
                            ->success()
                            ->title(__('webkul-project::filament/clusters/configurations/resources/project-stage.table.actions.delete.notification.title'))
                            ->body(__('webkul-project::filament/clusters/configurations/resources/project-stage.table.actions.delete.notification.body')),
                    ),
                ForceDeleteAction::make()
                    ->action(function (ProjectStage $record) {
                        try {
                            $record->forceDelete();

                            Notification::make()
                                ->success()
                                ->title(__('webkul-project::filament/clusters/configurations/resources/project-stage.table.actions.force-delete.notification.success.title'))
                                ->body(__('webkul-project::filament/clusters/configurations/resources/project-stage.table.actions.force-delete.notification.success.body'))
                                ->send();
                        } catch (QueryException $e) {
                            Notification::make()
                                ->danger()
                                ->title(__('webkul-project::filament/clusters/configurations/resources/project-stage.table.actions.force-delete.notification.error.title'))
                                ->body(__('webkul-project::filament/clusters/configurations/resources/project-stage.table.actions.force-delete.notification.error.body'))
                                ->send();
                        }
                    }),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    RestoreBulkAction::make()
                        ->successNotification(
                            Notification::make()
                                ->success()
                                ->title(__('webkul-project::filament/clusters/configurations/resources/project-stage.table.bulk-actions.restore.notification.title'))
                                ->body(__('webkul-project::filament/clusters/configurations/resources/project-stage.table.bulk-actions.restore.notification.body')),
                        ),
                    DeleteBulkAction::make()
                        ->successNotification(
                            Notification::make()
                                ->success()
                                ->title(__('webkul-project::filament/clusters/configurations/resources/project-stage.table.bulk-actions.delete.notification.title'))
                                ->body(__('webkul-project::filament/clusters/configurations/resources/project-stage.table.bulk-actions.delete.notification.body')),
                        ),
                    ForceDeleteBulkAction::make()
                        ->action(function (Collection $records) {
                            try {
                                $records->each(fn (Model $record) => $record->forceDelete());

                                Notification::make()
                                    ->success()
                                    ->title(__('webkul-project::filament/clusters/configurations/resources/project-stage.table.actions.force-delete.notification.success.title'))
                                    ->body(__('webkul-project::filament/clusters/configurations/resources/project-stage.table.actions.force-delete.notification.success.body'))
                                    ->send();
                            } catch (QueryException $e) {
                                Notification::make()
                                    ->danger()
                                    ->title(__('webkul-project::filament/clusters/configurations/resources/project-stage.table.actions.force-delete.notification.error.title'))
                                    ->body(__('webkul-project::filament/clusters/configurations/resources/project-stage.table.actions.force-delete.notification.error.body'))
                                    ->send();
                            }
                        }),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageProjectStages::route('/'),
        ];
    }
}
