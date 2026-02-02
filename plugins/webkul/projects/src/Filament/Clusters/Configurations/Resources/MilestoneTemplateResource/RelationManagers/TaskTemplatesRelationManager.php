<?php

namespace Webkul\Project\Filament\Clusters\Configurations\Resources\MilestoneTemplateResource\RelationManagers;

use App\Services\AI\GeminiService;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\Action;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Log;
use Webkul\Project\Models\MilestoneTemplateTask;

class TaskTemplatesRelationManager extends RelationManager
{
    protected static string $relationship = 'taskTemplates';

    protected static ?string $title = 'Task Templates';

    protected static ?string $recordTitleAttribute = 'title';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Tabs::make('TaskTabs')
                    ->tabs([
                        Tabs\Tab::make('Task Details')
                            ->icon('heroicon-o-clipboard-document-list')
                            ->schema([
                                TextInput::make('title')
                                    ->label('Task Title')
                                    ->required()
                                    ->maxLength(255)
                                    ->columnSpanFull(),

                                Select::make('parent_id')
                                    ->label('Parent Task (for subtasks)')
                                    ->options(function () {
                                        return $this->getOwnerRecord()
                                            ->taskTemplates()
                                            ->whereNull('parent_id')
                                            ->pluck('title', 'id');
                                    })
                                    ->placeholder('None (root task)')
                                    ->native(false)
                                    ->helperText('Select a parent to make this a subtask'),

                                TextInput::make('allocated_hours')
                                    ->label('Allocated Hours')
                                    ->numeric()
                                    ->default(0)
                                    ->minValue(0)
                                    ->step(0.5)
                                    ->suffix('hours'),

                                TextInput::make('relative_days')
                                    ->label('Start Day')
                                    ->helperText('Days from milestone start (e.g., 0 = starts on milestone day)')
                                    ->numeric()
                                    ->default(0)
                                    ->suffix('days'),

                                Section::make('Duration')
                                    ->description('Set fixed duration or calculate based on project size')
                                    ->schema([
                                        Radio::make('duration_type')
                                            ->label('Duration Type')
                                            ->options([
                                                'fixed' => 'Fixed Duration',
                                                'formula' => 'Calculate from Project Size',
                                            ])
                                            ->default('fixed')
                                            ->inline()
                                            ->live()
                                            ->columnSpanFull(),

                                        TextInput::make('duration_days')
                                            ->label('Fixed Duration')
                                            ->helperText('How many days this task takes to complete')
                                            ->numeric()
                                            ->default(1)
                                            ->minValue(1)
                                            ->suffix('days')
                                            ->visible(fn ($get) => $get('duration_type') !== 'formula'),

                                        Select::make('duration_rate_key')
                                            ->label('Use Company Rate')
                                            ->options(MilestoneTemplateTask::COMPANY_RATE_KEYS)
                                            ->placeholder('Custom rate (specify below)')
                                            ->helperText('Select a company production rate or leave empty for custom')
                                            ->native(false)
                                            ->live()
                                            ->visible(fn ($get) => $get('duration_type') === 'formula')
                                            ->columnSpanFull(),

                                        Grid::make(2)
                                            ->schema([
                                                TextInput::make('duration_unit_size')
                                                    ->label('Custom Rate (LF per day)')
                                                    ->helperText('How many linear feet can be processed per day')
                                                    ->numeric()
                                                    ->default(15)
                                                    ->placeholder('e.g., 15'),

                                                Select::make('duration_unit_type')
                                                    ->label('Unit Type')
                                                    ->options(MilestoneTemplateTask::DURATION_UNIT_TYPES)
                                                    ->default('linear_feet')
                                                    ->native(false),
                                            ])
                                            ->visible(fn ($get) => $get('duration_type') === 'formula' && !$get('duration_rate_key')),

                                        Grid::make(2)
                                            ->schema([
                                                TextInput::make('duration_min_days')
                                                    ->label('Minimum Days')
                                                    ->helperText('Floor for calculated duration')
                                                    ->numeric()
                                                    ->minValue(1)
                                                    ->placeholder('No minimum'),

                                                TextInput::make('duration_max_days')
                                                    ->label('Maximum Days')
                                                    ->helperText('Ceiling for calculated duration')
                                                    ->numeric()
                                                    ->minValue(1)
                                                    ->placeholder('No maximum'),
                                            ])
                                            ->visible(fn ($get) => $get('duration_type') === 'formula'),
                                    ])
                                    ->columns(1)
                                    ->columnSpanFull(),

                                Grid::make(3)
                                    ->schema([
                                        TextInput::make('sort_order')
                                            ->label('Sort Order')
                                            ->numeric()
                                            ->default(0)
                                            ->minValue(0),

                                        Toggle::make('priority')
                                            ->label('High Priority')
                                            ->inline(false)
                                            ->default(false),

                                        Toggle::make('is_active')
                                            ->label('Active')
                                            ->inline(false)
                                            ->default(true),
                                    ]),

                                Textarea::make('description')
                                    ->label('Description')
                                    ->rows(3)
                                    ->columnSpanFull(),
                            ]),

                        Tabs\Tab::make('Subtasks')
                            ->icon('heroicon-o-queue-list')
                            ->badge(fn ($record) => $record?->children?->count() ?: null)
                            ->visible(fn ($record) => $record && $record->parent_id === null) // Only show for root tasks
                            ->schema([
                                Repeater::make('children')
                                    ->relationship('children')
                                    ->label('Subtasks')
                                    ->addActionLabel('Add Subtask')
                                    ->collapsible()
                                    ->collapsed()
                                    ->itemLabel(fn (array $state): ?string => $state['title'] ?? 'New Subtask')
                                    ->orderColumn('sort_order')
                                    ->reorderable()
                                    ->schema([
                                        TextInput::make('title')
                                            ->label('Subtask Title')
                                            ->required()
                                            ->maxLength(255)
                                            ->columnSpanFull(),

                                        Grid::make(3)
                                            ->schema([
                                                TextInput::make('allocated_hours')
                                                    ->label('Hours')
                                                    ->numeric()
                                                    ->default(0)
                                                    ->minValue(0)
                                                    ->step(0.5),

                                                TextInput::make('relative_days')
                                                    ->label('Start Day')
                                                    ->helperText('From parent task start')
                                                    ->numeric()
                                                    ->default(0),

                                                TextInput::make('duration_days')
                                                    ->label('Duration (days)')
                                                    ->numeric()
                                                    ->default(1)
                                                    ->minValue(1),
                                            ]),

                                        Grid::make(2)
                                            ->schema([
                                                Toggle::make('priority')
                                                    ->label('High Priority')
                                                    ->default(false),

                                                Toggle::make('is_active')
                                                    ->label('Active')
                                                    ->default(true),
                                            ]),

                                        Textarea::make('description')
                                            ->label('Description')
                                            ->rows(2)
                                            ->columnSpanFull(),
                                    ])
                                    ->mutateRelationshipDataBeforeCreateUsing(function (array $data): array {
                                        // Set milestone_template_id from parent
                                        $data['milestone_template_id'] = $this->getOwnerRecord()->id;
                                        $data['duration_type'] = 'fixed'; // Subtasks always fixed
                                        return $data;
                                    })
                                    ->columnSpanFull(),
                            ]),
                    ])
                    ->columnSpanFull(),
            ]);
    }

    public function table(Table $table): Table
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
                    ->grow(false),

                IconColumn::make('priority')
                    ->label('')
                    ->boolean()
                    ->trueIcon('heroicon-o-flag')
                    ->falseIcon('')
                    ->trueColor('danger')
                    ->tooltip(fn ($state) => $state ? 'High Priority' : '')
                    ->grow(false),

                IconColumn::make('ai_suggestion_id')
                    ->label('')
                    ->icon('heroicon-o-sparkles')
                    ->tooltip('Created from AI suggestion')
                    ->visible(fn ($state) => $state !== null)
                    ->color('primary')
                    ->grow(false),

                TextColumn::make('title')
                    ->label('Task')
                    ->description(fn (MilestoneTemplateTask $record): string => $record->description ?? '')
                    ->formatStateUsing(function (MilestoneTemplateTask $record) {
                        $prefix = $record->parent_id ? '↳ ' : '';
                        return $prefix . $record->title;
                    })
                    ->searchable()
                    ->sortable()
                    ->wrap(),

                TextColumn::make('parent.title')
                    ->label('Parent')
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('allocated_hours')
                    ->label('Hours')
                    ->suffix('h')
                    ->sortable()
                    ->alignCenter(),

                TextColumn::make('relative_days')
                    ->label('Start')
                    ->formatStateUsing(fn ($state) => "Day {$state}")
                    ->sortable()
                    ->alignCenter(),

                TextColumn::make('duration_days')
                    ->label('Duration')
                    ->formatStateUsing(function (MilestoneTemplateTask $record) {
                        if ($record->duration_type === 'formula') {
                            // Using company rate
                            if ($record->duration_rate_key) {
                                $rateLabel = MilestoneTemplateTask::COMPANY_RATE_KEYS[$record->duration_rate_key] ?? $record->duration_rate_key;
                                // Shorten the label for table display
                                $shortLabel = str_replace([' (LF/day)', '_lf_per_day'], '', $rateLabel);
                                return "Co: {$shortLabel}";
                            }
                            // Custom rate
                            if ($record->duration_unit_size) {
                                return "{$record->duration_unit_size} LF/day";
                            }
                        }
                        return $record->duration_days . ' days';
                    })
                    ->tooltip(fn (MilestoneTemplateTask $record) => $record->duration_formula_description)
                    ->sortable()
                    ->alignCenter(),

                TextColumn::make('children_count')
                    ->label('Subtasks')
                    ->counts('children')
                    ->badge()
                    ->color(fn ($state) => $state > 0 ? 'info' : 'gray')
                    ->formatStateUsing(fn ($state) => $state ?: '—')
                    ->alignCenter(),

                TextColumn::make('sort_order')
                    ->label('Order')
                    ->sortable()
                    ->alignCenter()
                    ->toggleable(isToggledHiddenByDefault: true),

                ToggleColumn::make('is_active')
                    ->label('Active'),
            ])
            ->reorderable('sort_order')
            ->defaultSort('sort_order', 'asc')
            ->headerActions([
                CreateAction::make()
                    ->label('Add Task')
                    ->slideOver()
                    ->modalWidth('xl')
                    ->successNotification(
                        Notification::make()
                            ->success()
                            ->title('Task template created'),
                    ),
            ])
            ->actions([
                Action::make('generateSubtasks')
                    ->label('AI Subtasks')
                    ->icon('heroicon-o-sparkles')
                    ->color('primary')
                    ->visible(fn (MilestoneTemplateTask $record) => $record->parent_id === null) // Only for root tasks
                    ->requiresConfirmation()
                    ->modalHeading('Generate Subtasks with AI')
                    ->modalDescription(fn (MilestoneTemplateTask $record) => "Generate subtasks for \"{$record->title}\" using AI. Existing subtasks will NOT be deleted.")
                    ->modalSubmitActionLabel('Generate')
                    ->form([
                        Textarea::make('context')
                            ->label('Additional Context (optional)')
                            ->placeholder('e.g., Include QC checkpoints, break into daily tasks...')
                            ->rows(2),
                    ])
                    ->action(function (MilestoneTemplateTask $record, array $data) {
                        $this->generateSubtasksWithAi($record, $data['context'] ?? null);
                    }),
                EditAction::make()
                    ->slideOver()
                    ->modalWidth('xl')
                    ->successNotification(
                        Notification::make()
                            ->success()
                            ->title('Task template updated'),
                    ),
                DeleteAction::make()
                    ->successNotification(
                        Notification::make()
                            ->success()
                            ->title('Task template deleted'),
                    ),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    /**
     * Generate subtasks for a task using AI.
     */
    protected function generateSubtasksWithAi(MilestoneTemplateTask $task, ?string $context = null): void
    {
        try {
            $geminiService = app(GeminiService::class);
            $milestoneTemplate = $this->getOwnerRecord();

            $prompt = $this->buildSubtaskPrompt($task, $milestoneTemplate, $context);

            Log::info('Generating subtasks with AI', [
                'task_id' => $task->id,
                'task_title' => $task->title,
                'milestone' => $milestoneTemplate->name,
            ]);

            // Use reasoning model for better subtask generation
            $reasoningModel = config('gemini.reasoning_model', 'gemini-2.5-pro');
            $response = $geminiService->generateResponseWithModel(
                $prompt,
                ['domain' => 'woodworking_production'],
                $reasoningModel,
                8192
            );

            // Parse the response
            $parsed = $this->parseSubtaskResponse($response);

            if (empty($parsed['subtasks'])) {
                Notification::make()
                    ->warning()
                    ->title('No subtasks generated')
                    ->body('The AI did not generate any subtasks. Try providing more context.')
                    ->send();
                return;
            }

            // Create the subtasks
            $created = 0;
            $nextSortOrder = $task->children()->max('sort_order') ?? 0;

            foreach ($parsed['subtasks'] as $subtaskData) {
                $nextSortOrder++;
                MilestoneTemplateTask::create([
                    'milestone_template_id' => $milestoneTemplate->id,
                    'parent_id' => $task->id,
                    'title' => $subtaskData['title'],
                    'description' => $subtaskData['description'] ?? null,
                    'allocated_hours' => $subtaskData['allocated_hours'] ?? 0,
                    'relative_days' => $subtaskData['relative_days'] ?? 0,
                    'duration_type' => 'fixed',
                    'duration_days' => $subtaskData['duration_days'] ?? 1,
                    'priority' => $subtaskData['priority'] ?? false,
                    'sort_order' => $nextSortOrder,
                    'is_active' => true,
                ]);
                $created++;
            }

            Notification::make()
                ->success()
                ->title('Subtasks Generated')
                ->body("Created {$created} subtasks for \"{$task->title}\"")
                ->send();

            Log::info('AI subtasks created', [
                'task_id' => $task->id,
                'subtask_count' => $created,
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to generate subtasks with AI', [
                'task_id' => $task->id,
                'error' => $e->getMessage(),
            ]);

            Notification::make()
                ->danger()
                ->title('Failed to Generate Subtasks')
                ->body('An error occurred: ' . $e->getMessage())
                ->send();
        }
    }

    /**
     * Build the prompt for subtask generation.
     */
    protected function buildSubtaskPrompt(MilestoneTemplateTask $task, $milestone, ?string $context): string
    {
        $existingSubtasks = $task->children->pluck('title')->toArray();
        $existingList = !empty($existingSubtasks)
            ? "\n\nExisting subtasks (do NOT duplicate):\n- " . implode("\n- ", $existingSubtasks)
            : '';

        $contextSection = $context ? "\n\nAdditional context: {$context}" : '';

        return <<<PROMPT
You are a woodworking production expert. Generate subtasks for a task in a cabinetry shop.

## Parent Task
- **Title**: {$task->title}
- **Description**: {$task->description}
- **Milestone**: {$milestone->name} ({$milestone->production_stage} phase)
- **Allocated Hours**: {$task->allocated_hours}h
- **Duration**: {$task->duration_days} days
{$existingList}
{$contextSection}

## Instructions
Generate 3-6 subtasks that break down this task into discrete, actionable steps.
Focus on woodworking-specific activities appropriate for a custom cabinetry shop.
Each subtask should be completable in 1-4 hours.

## Response Format
Return ONLY a JSON object:
```json
{
  "subtasks": [
    {
      "title": "Subtask title (action-oriented)",
      "description": "Brief description of what to do",
      "allocated_hours": 2,
      "relative_days": 0,
      "duration_days": 1,
      "priority": false
    }
  ]
}
```

Return ONLY the JSON object, no additional text.
PROMPT;
    }

    /**
     * Parse the AI response for subtasks.
     */
    protected function parseSubtaskResponse(string $response): array
    {
        $jsonStart = strpos($response, '{');
        $jsonEnd = strrpos($response, '}');

        if ($jsonStart === false || $jsonEnd === false) {
            Log::warning('No JSON found in subtask response', ['response' => substr($response, 0, 500)]);
            return ['subtasks' => []];
        }

        $jsonString = substr($response, $jsonStart, $jsonEnd - $jsonStart + 1);
        $parsed = json_decode($jsonString, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            Log::warning('Failed to parse subtask JSON', ['error' => json_last_error_msg()]);
            return ['subtasks' => []];
        }

        return $parsed;
    }
}
