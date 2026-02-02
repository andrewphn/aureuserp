<?php

namespace Webkul\Project\Filament\Clusters\Configurations\Resources;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Grouping\Group;
use Filament\Tables\Table;
use Webkul\Project\Filament\Clusters\Configurations;
use Webkul\Project\Filament\Clusters\Configurations\Resources\MilestoneTemplateResource\Pages;
use Webkul\Project\Filament\Clusters\Configurations\Resources\MilestoneTemplateResource\RelationManagers;
use Webkul\Project\Models\MilestoneTemplate;
use Webkul\Project\Models\Tag;
use Webkul\Project\Settings\TaskSettings;

/**
 * Milestone Template Resource - Manage universal milestone templates
 *
 * These templates define the default milestones that can be selected
 * when creating a new project.
 */
class MilestoneTemplateResource extends Resource
{
    protected static ?string $model = MilestoneTemplate::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static ?int $navigationSort = 4;

    protected static ?string $cluster = Configurations::class;

    public static function getNavigationLabel(): string
    {
        return 'Milestone Templates';
    }

    public static function getModelLabel(): string
    {
        return 'Milestone Template';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Milestone Templates';
    }

    /**
     * Is Discovered
     */
    public static function isDiscovered(): bool
    {
        if (app()->runningInConsole()) {
            return true;
        }

        return app(TaskSettings::class)->enable_milestones;
    }

    /**
     * Define the form schema
     */
    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Milestone Details')
                    ->schema([
                        TextInput::make('name')
                            ->label('Template Name')
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull(),

                        Select::make('production_stage')
                            ->label('Production Stage')
                            ->options(self::getProductionStageOptions())
                            ->required()
                            ->native(false),

                        TextInput::make('relative_days')
                            ->label('Relative Days')
                            ->helperText('Days from project start date when this milestone is due')
                            ->numeric()
                            ->required()
                            ->default(0)
                            ->minValue(0),

                        TextInput::make('sort_order')
                            ->label('Sort Order')
                            ->helperText('Order within the production stage (lower = first)')
                            ->numeric()
                            ->default(1)
                            ->minValue(1),

                        Toggle::make('is_critical')
                            ->label('Critical Milestone')
                            ->helperText('Critical milestones are highlighted and required for project completion')
                            ->inline(false)
                            ->default(false),

                        Toggle::make('is_active')
                            ->label('Active')
                            ->helperText('Inactive templates will not appear in the project creation form')
                            ->inline(false)
                            ->default(true),

                        Textarea::make('description')
                            ->label('Description')
                            ->helperText('Describe what needs to be completed for this milestone')
                            ->rows(3)
                            ->columnSpanFull(),

                        Select::make('tags')
                            ->label('Tags')
                            ->relationship('tags', 'name')
                            ->multiple()
                            ->preload()
                            ->searchable()
                            ->createOptionForm([
                                TextInput::make('name')
                                    ->required()
                                    ->maxLength(255),
                                Select::make('type')
                                    ->label('Tag Type')
                                    ->options([
                                        'milestone' => 'Milestone',
                                        'category' => 'Category',
                                        'workflow' => 'Workflow',
                                        'custom' => 'Custom',
                                    ])
                                    ->default('milestone'),
                                Select::make('color')
                                    ->label('Color')
                                    ->options([
                                        'gray' => 'Gray',
                                        'red' => 'Red',
                                        'orange' => 'Orange',
                                        'amber' => 'Amber',
                                        'yellow' => 'Yellow',
                                        'lime' => 'Lime',
                                        'green' => 'Green',
                                        'emerald' => 'Emerald',
                                        'teal' => 'Teal',
                                        'cyan' => 'Cyan',
                                        'sky' => 'Sky',
                                        'blue' => 'Blue',
                                        'indigo' => 'Indigo',
                                        'violet' => 'Violet',
                                        'purple' => 'Purple',
                                        'fuchsia' => 'Fuchsia',
                                        'pink' => 'Pink',
                                        'rose' => 'Rose',
                                    ])
                                    ->default('gray'),
                            ])
                            ->helperText('Add tags to categorize and search milestones')
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
            ]);
    }

    /**
     * Define the table schema
     */
    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                IconColumn::make('is_active')
                    ->label('')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger')
                    ->tooltip(fn ($state) => $state ? 'Active' : 'Inactive')
                    ->alignCenter()
                    ->grow(false),

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
                    ->label('Name')
                    ->description(fn (MilestoneTemplate $record): string => $record->description ?? '')
                    ->searchable()
                    ->sortable()
                    ->wrap(),

                TextColumn::make('production_stage')
                    ->label('Stage')
                    ->badge()
                    ->color(fn (string $state = null): string => self::getStageColor($state))
                    ->formatStateUsing(fn (string $state = null): string => self::getProductionStageOptions()[$state] ?? 'Unknown')
                    ->sortable(query: function ($query, string $direction) {
                        // Custom sort to maintain stage order: Discovery, Design, Sourcing, Production, Delivery, General
                        return $query->orderByRaw("FIELD(production_stage, 'discovery', 'design', 'sourcing', 'production', 'delivery', 'general') {$direction}");
                    }),

                TextColumn::make('relative_days')
                    ->label('Days')
                    ->suffix(' days')
                    ->sortable()
                    ->alignCenter(),

                TextColumn::make('sort_order')
                    ->label('Order')
                    ->sortable()
                    ->alignCenter(),

                TextColumn::make('tags.name')
                    ->label('Tags')
                    ->badge()
                    ->color(fn ($state, $record) => $record->tags->firstWhere('name', $state)?->color ?? 'gray')
                    ->separator(',')
                    ->searchable()
                    ->toggleable(),

                ToggleColumn::make('is_active')
                    ->label('Active')
                    ->sortable(),

                TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('updated_at')
                    ->label('Updated')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->modifyQueryUsing(fn ($query) => $query
                ->orderByRaw("FIELD(production_stage, 'discovery', 'design', 'sourcing', 'production', 'delivery', 'general')")
                ->orderBy('sort_order', 'asc'))
            ->reorderable('sort_order')
            ->groups([
                Group::make('production_stage')
                    ->label('Production Stage')
                    ->titlePrefixedWithLabel(false)
                    ->getTitleFromRecordUsing(fn ($record) => self::getProductionStageOptions()[$record->production_stage] ?? 'Unknown')
                    ->orderQueryUsing(fn ($query, string $direction) => $query->orderByRaw("FIELD(production_stage, 'discovery', 'design', 'sourcing', 'production', 'delivery', 'general') {$direction}")),
                Group::make('is_critical')
                    ->label('Critical Status'),
                Group::make('is_active')
                    ->label('Active Status'),
            ])
            ->filters([
                SelectFilter::make('production_stage')
                    ->label('Production Stage')
                    ->options(self::getProductionStageOptions()),
                SelectFilter::make('tags')
                    ->label('Tags')
                    ->relationship('tags', 'name')
                    ->multiple()
                    ->preload()
                    ->searchable(),
                TernaryFilter::make('is_critical')
                    ->label('Critical Milestones'),
                TernaryFilter::make('is_active')
                    ->label('Active Templates'),
            ])
            ->recordActions([
                EditAction::make()
                    ->url(fn (MilestoneTemplate $record): string => static::getUrl('edit', ['record' => $record])),
                DeleteAction::make()
                    ->successNotification(
                        Notification::make()
                            ->success()
                            ->title('Template deleted')
                            ->body('The milestone template has been deleted.'),
                    ),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->successNotification(
                            Notification::make()
                                ->success()
                                ->title('Templates deleted')
                                ->body('The selected milestone templates have been deleted.'),
                        ),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListMilestoneTemplates::route('/'),
            'create' => Pages\CreateMilestoneTemplate::route('/create'),
            'edit' => Pages\EditMilestoneTemplate::route('/{record}/edit'),
            'review-ai' => Pages\ReviewAiSuggestions::route('/{record}/review-ai'),
        ];
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\TaskTemplatesRelationManager::class,
        ];
    }

    /**
     * Get production stage options in correct order
     */
    public static function getProductionStageOptions(): array
    {
        return [
            'discovery' => 'Discovery',
            'design' => 'Design',
            'sourcing' => 'Sourcing',
            'production' => 'Production',
            'delivery' => 'Delivery',
            'general' => 'General',
        ];
    }

    /**
     * Get color for production stage badge
     */
    public static function getStageColor(?string $state): string
    {
        return match ($state) {
            'discovery' => 'purple',
            'design' => 'info',
            'sourcing' => 'warning',
            'production' => 'success',
            'delivery' => 'indigo',
            'general' => 'gray',
            default => 'gray',
        };
    }
}
