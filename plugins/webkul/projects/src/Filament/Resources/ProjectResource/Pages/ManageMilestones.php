<?php

namespace Webkul\Project\Filament\Resources\ProjectResource\Pages;

use Filament\Actions\CreateAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Get;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ManageRelatedRecords;
use Filament\Schemas\Schema;
use Filament\Tables\Actions\Action;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Webkul\Project\Filament\Clusters\Configurations\Resources\MilestoneResource;
use Webkul\Project\Filament\Resources\ProjectResource;
use Webkul\Project\Models\Milestone;
use Webkul\Project\Models\MilestoneTemplate;
use Webkul\Project\Models\Task;
use Webkul\Project\Models\TaskStage;
use Webkul\Project\Services\GeminiTaskTemplateService;

/**
 * Manage Milestones class
 *
 * @see \Filament\Resources\Resource
 */
class ManageMilestones extends ManageRelatedRecords
{
    protected static string $resource = ProjectResource::class;

    protected static string $relationship = 'milestones';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-flag';

    /**
     * @param  array<string, mixed>  $parameters
     */
    public static function canAccess(array $parameters = []): bool
    {
        $canAccess = parent::canAccess($parameters);

        if (! $canAccess) {
            return false;
        }

        if (! static::$resource::getTaskSettings()->enable_milestones) {
            return false;
        }

        return $parameters['record']?->allow_milestones;
    }

    public static function getNavigationLabel(): string
    {
        return __('webkul-project::filament/resources/project/pages/manage-milestones.title');
    }

    /**
     * Define the form schema
     *
     * @param Schema $schema
     * @return Schema
     */
    public function form(Schema $schema): Schema
    {
        return MilestoneResource::form($schema);
    }

    /**
     * Define the table schema
     *
     * @param Table $table
     * @return Table
     */
    public function table(Table $table): Table
    {
        return MilestoneResource::table($table)
            ->headerActions([
                CreateAction::make()
                    ->label(__('webkul-project::filament/resources/project/pages/manage-milestones.table.header-actions.create.label'))
                    ->icon('heroicon-o-plus-circle')
                    ->mutateDataUsing(function (array $data): array {
                        $data['creator_id'] = Auth::id();

                        return $data;
                    })
                    ->successNotification(
                        Notification::make()
                            ->success()
                            ->title(__('webkul-project::filament/resources/project/pages/manage-milestones.table.header-actions.create.notification.title'))
                            ->body(__('webkul-project::filament/resources/project/pages/manage-milestones.table.header-actions.create.notification.body')),
                    ),
                $this->getImportFromTemplateAction(),
            ]);
    }

    /**
     * Get the import from template action
     */
    protected function getImportFromTemplateAction(): Action
    {
        return Action::make('importFromTemplate')
            ->label('Import from Template')
            ->icon('heroicon-o-document-duplicate')
            ->color('gray')
            ->form([
                Select::make('template_ids')
                    ->label('Select Milestone Templates')
                    ->multiple()
                    ->searchable()
                    ->preload()
                    ->options(fn () => $this->getTemplateOptions())
                    ->getSearchResultsUsing(fn (string $search) => $this->getTemplateOptions($search))
                    ->helperText('Search by name, stage, or description. Templates marked as "Already Imported" will be skipped.')
                    ->required(),
                Toggle::make('generate_tasks_with_ai')
                    ->label('Generate Tasks with AI')
                    ->helperText('Use Gemini AI to automatically generate tasks for each milestone based on woodworking best practices.')
                    ->default(true)
                    ->live(),
                Textarea::make('ai_context')
                    ->label('Additional Context for AI')
                    ->placeholder('e.g., "This is a high-end residential kitchen with custom finish requirements" or "Commercial project with tight deadlines"')
                    ->helperText('Provide any project-specific context to help the AI generate more relevant tasks.')
                    ->rows(3)
                    ->visible(fn (Get $get) => $get('generate_tasks_with_ai')),
            ])
            ->modalHeading('Import Milestones from Templates')
            ->modalDescription('Select milestone templates to import into this project. Enable AI task generation for automatic task creation.')
            ->modalSubmitActionLabel('Import Selected')
            ->action(fn (array $data) => $this->importMilestonesFromTemplates($data));
    }

    /**
     * Get template options for the select field
     */
    protected function getTemplateOptions(?string $search = null): array
    {
        $existingNames = Milestone::where('project_id', $this->getOwnerRecord()->id)
            ->pluck('name')
            ->toArray();

        $query = MilestoneTemplate::active()
            ->orderBy('production_stage')
            ->orderBy('sort_order');

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('production_stage', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            })->limit(50);
        }

        return $query->get()
            ->mapWithKeys(fn (MilestoneTemplate $template) => [
                $template->id => $this->buildTemplateLabel($template, $existingNames),
            ])
            ->toArray();
    }

    /**
     * Build display label for a template
     */
    protected function buildTemplateLabel(MilestoneTemplate $template, array $existingNames): string
    {
        $label = $template->name;
        $label .= ' [' . ucfirst($template->production_stage ?? 'general') . ']';

        if ($template->is_critical) {
            $label .= ' (Critical)';
        }

        if (in_array($template->name, $existingNames)) {
            $label .= ' - Already Imported';
        }

        return $label;
    }

    /**
     * Import milestones from selected templates
     */
    protected function importMilestonesFromTemplates(array $data): void
    {
        $project = $this->getOwnerRecord();
        $templateIds = $data['template_ids'] ?? [];
        $generateWithAi = $data['generate_tasks_with_ai'] ?? false;
        $aiContext = $data['ai_context'] ?? null;

        if (empty($templateIds)) {
            Notification::make()
                ->warning()
                ->title('No Templates Selected')
                ->body('Please select at least one template to import.')
                ->send();
            return;
        }

        $existingNames = Milestone::where('project_id', $project->id)
            ->pluck('name')
            ->toArray();

        $templates = MilestoneTemplate::whereIn('id', $templateIds)
            ->whereNotIn('name', $existingNames)
            ->get();

        if ($templates->isEmpty()) {
            Notification::make()
                ->info()
                ->title('No New Milestones')
                ->body('All selected templates have already been imported to this project.')
                ->send();
            return;
        }

        $milestonesCreated = 0;
        $tasksCreated = 0;
        $errors = [];

        $baseDate = $project->start_date ?? now();

        foreach ($templates as $template) {
            try {
                // Create the milestone
                $milestone = $this->createMilestoneFromTemplate($project, $template, $baseDate);
                $milestonesCreated++;

                // Generate tasks with AI if enabled
                if ($generateWithAi) {
                    $taskCount = $this->generateTasksWithAi($project, $milestone, $template, $aiContext);
                    $tasksCreated += $taskCount;
                }
            } catch (\Exception $e) {
                Log::error('Failed to import milestone template', [
                    'template_id' => $template->id,
                    'template_name' => $template->name,
                    'error' => $e->getMessage(),
                ]);
                $errors[] = $template->name;
            }
        }

        // Build notification message
        $message = "Created {$milestonesCreated} milestone(s)";
        if ($generateWithAi && $tasksCreated > 0) {
            $message .= " with {$tasksCreated} AI-generated task(s)";
        }

        if (!empty($errors)) {
            Notification::make()
                ->warning()
                ->title('Partial Import')
                ->body("{$message}. Failed: " . implode(', ', $errors))
                ->send();
        } else {
            Notification::make()
                ->success()
                ->title('Milestones Imported')
                ->body($message)
                ->send();
        }
    }

    /**
     * Create a milestone from a template
     */
    protected function createMilestoneFromTemplate($project, MilestoneTemplate $template, $baseDate): Milestone
    {
        $deadline = $baseDate->copy()->addDays($template->relative_days);

        return Milestone::create([
            'name' => $template->name,
            'description' => $template->description,
            'production_stage' => $template->production_stage,
            'is_critical' => $template->is_critical,
            'sort_order' => $template->sort_order,
            'deadline' => $deadline,
            'is_completed' => false,
            'project_id' => $project->id,
            'creator_id' => Auth::id() ?? $project->creator_id,
        ]);
    }

    /**
     * Generate tasks using AI with retry logic for parsing
     */
    protected function generateTasksWithAi($project, Milestone $milestone, MilestoneTemplate $template, ?string $additionalContext): int
    {
        try {
            $geminiService = app(GeminiTaskTemplateService::class);

            // Generate AI suggestions (includes retry logic internally)
            $suggestion = $geminiService->generateTaskSuggestions($template, $additionalContext);

            if (empty($suggestion->suggested_tasks)) {
                Log::warning('AI generated no tasks for milestone', [
                    'milestone_id' => $milestone->id,
                    'template_id' => $template->id,
                ]);
                return 0;
            }

            // Create tasks from the AI suggestions
            return $this->createTasksFromAiSuggestions(
                $project,
                $milestone,
                $suggestion->suggested_tasks
            );
        } catch (\Exception $e) {
            Log::error('AI task generation failed', [
                'milestone_id' => $milestone->id,
                'template_id' => $template->id,
                'error' => $e->getMessage(),
            ]);
            return 0;
        }
    }

    /**
     * Create tasks from AI-generated suggestions
     */
    protected function createTasksFromAiSuggestions($project, Milestone $milestone, array $suggestedTasks): int
    {
        $count = 0;
        $defaultStage = TaskStage::where('project_id', $project->id)->orderBy('sort')->first()
            ?? TaskStage::whereNull('project_id')->orderBy('sort')->first();

        foreach ($suggestedTasks as $taskData) {
            try {
                // Calculate duration based on type
                $duration = $this->calculateTaskDuration($project, $taskData);
                $taskStart = $milestone->deadline->copy()->addDays($taskData['relative_days'] ?? 0);
                $taskDeadline = $taskStart->copy()->addDays($duration);

                // Create the task
                $task = Task::create([
                    'title' => $taskData['title'],
                    'description' => $taskData['description'] ?? null,
                    'allocated_hours' => $taskData['allocated_hours'] ?? 0,
                    'priority' => $taskData['priority'] ?? false,
                    'sort' => $taskData['sort_order'] ?? $count + 1,
                    'deadline' => $taskDeadline,
                    'state' => 'pending',
                    'project_id' => $project->id,
                    'milestone_id' => $milestone->id,
                    'stage_id' => $defaultStage?->id,
                    'company_id' => $project->company_id,
                    'creator_id' => Auth::id() ?? $project->creator_id,
                    'is_active' => true,
                ]);
                $count++;

                // Create subtasks if any
                if (!empty($taskData['subtasks'])) {
                    foreach ($taskData['subtasks'] as $subtaskData) {
                        $subtaskDuration = $subtaskData['duration_days'] ?? 1;
                        $subtaskStart = $taskStart->copy()->addDays($subtaskData['relative_days'] ?? 0);
                        $subtaskDeadline = $subtaskStart->copy()->addDays($subtaskDuration);

                        Task::create([
                            'title' => $subtaskData['title'],
                            'description' => $subtaskData['description'] ?? null,
                            'allocated_hours' => $subtaskData['allocated_hours'] ?? 0,
                            'priority' => false,
                            'sort' => $count + 1,
                            'deadline' => $subtaskDeadline,
                            'state' => 'pending',
                            'project_id' => $project->id,
                            'milestone_id' => $milestone->id,
                            'parent_id' => $task->id,
                            'stage_id' => $defaultStage?->id,
                            'company_id' => $project->company_id,
                            'creator_id' => Auth::id() ?? $project->creator_id,
                            'is_active' => true,
                        ]);
                        $count++;
                    }
                }
            } catch (\Exception $e) {
                Log::error('Failed to create task from AI suggestion', [
                    'task_title' => $taskData['title'] ?? 'unknown',
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $count;
    }

    /**
     * Calculate task duration based on type (fixed or formula)
     */
    protected function calculateTaskDuration($project, array $taskData): int
    {
        $durationType = $taskData['duration_type'] ?? 'fixed';

        if ($durationType === 'fixed') {
            return max(1, (int) ($taskData['duration_days'] ?? 1));
        }

        // Formula-based duration
        $rateKey = $taskData['duration_rate_key'] ?? null;
        $unitType = $taskData['duration_unit_type'] ?? 'linear_feet';
        $minDays = $taskData['duration_min_days'] ?? 1;
        $maxDays = $taskData['duration_max_days'] ?? 365;

        // Get project metrics
        $unitValue = match ($unitType) {
            'linear_feet' => $project->estimated_linear_feet ?? $project->total_linear_feet ?? 100,
            'cabinets' => $project->cabinets()->count() ?: 10,
            'rooms' => $project->rooms()->count() ?: 1,
            'doors' => $project->cabinets()->withCount('doors')->get()->sum('doors_count') ?: 10,
            'drawers' => $project->cabinets()->withCount('drawers')->get()->sum('drawers_count') ?: 10,
            default => 100,
        };

        // Get rate from company or use default
        $rate = $this->getProductionRate($project, $rateKey);

        if ($rate <= 0) {
            return max($minDays, 1);
        }

        $calculatedDays = ceil($unitValue / $rate);

        // Apply min/max bounds
        return max($minDays, min($maxDays, $calculatedDays));
    }

    /**
     * Get production rate from company settings
     */
    protected function getProductionRate($project, ?string $rateKey): float
    {
        $defaults = [
            'shop_capacity_per_day' => 13.6,
            'design_concepts_lf_per_day' => 15,
            'design_revisions_lf_per_day' => 50,
            'shop_drawings_lf_per_day' => 100,
            'cut_list_bom_lf_per_day' => 100,
            'rough_mill_lf_per_day' => 50,
            'cabinet_assembly_lf_per_day' => 25,
            'doors_drawers_lf_per_day' => 30,
            'sanding_prep_lf_per_day' => 75,
            'finishing_lf_per_day' => 50,
            'hardware_install_lf_per_day' => 100,
            'installation_lf_per_day' => 40,
        ];

        if (!$rateKey || !isset($defaults[$rateKey])) {
            return $defaults['shop_capacity_per_day'];
        }

        $company = $project->company;
        if ($company && isset($company->$rateKey)) {
            return (float) $company->$rateKey;
        }

        return $defaults[$rateKey];
    }
}
