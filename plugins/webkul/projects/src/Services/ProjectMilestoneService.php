<?php

namespace Webkul\Project\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Webkul\Project\Models\Gate;
use Webkul\Project\Models\Milestone;
use Webkul\Project\Models\MilestoneRequirement;
use Webkul\Project\Models\MilestoneTemplate;
use Webkul\Project\Models\MilestoneTemplateTask;
use Webkul\Project\Models\Project;
use Webkul\Project\Models\Task;
use Webkul\Project\Models\TaskStage;

/**
 * Project Milestone Service
 *
 * Creates milestones from MilestoneTemplate when a project is created.
 * Milestones represent key checkpoints in the project workflow that
 * align with the Stage & Gate system.
 *
 * This provides a standard workflow template for all projects based on the
 * company's configured milestone templates.
 */
class ProjectMilestoneService
{
    /**
     * Create milestones for a newly created project from templates.
     *
     * @param Project $project
     * @param Carbon|null $referenceDate The date to calculate milestone deadlines from (defaults to start_date or now)
     * @param array|null $templateIds Optional array of template IDs to filter (null = all active templates)
     * @return array Summary of created milestones
     */
    public function createMilestonesFromTemplates(Project $project, ?Carbon $referenceDate = null, ?array $templateIds = null): array
    {
        $summary = [
            'milestones_created' => 0,
            'milestones' => [],
        ];

        // If empty array provided, no milestones should be created
        if ($templateIds !== null && empty($templateIds)) {
            Log::info('No milestone templates selected, skipping milestone creation', [
                'project_id' => $project->id,
            ]);
            return $summary;
        }

        // Get active milestone templates, optionally filtered by IDs
        $query = MilestoneTemplate::active()
            ->orderBy('production_stage')
            ->orderBy('sort_order');

        // Filter by specific template IDs if provided
        if ($templateIds !== null) {
            $query->whereIn('id', $templateIds);
        }

        $templates = $query->get();

        if ($templates->isEmpty()) {
            Log::info('No milestone templates found, skipping milestone creation', [
                'project_id' => $project->id,
            ]);
            return $summary;
        }

        // Use project start date, desired completion date, or today as reference
        $baseDate = $referenceDate
            ?? $project->start_date
            ?? $project->desired_completion_date?->subDays(120) // Work backwards from completion
            ?? Carbon::today();

        foreach ($templates as $template) {
            $milestone = $this->createMilestoneFromTemplate($project, $template, $baseDate);

            $summary['milestones'][] = [
                'id' => $milestone->id,
                'name' => $milestone->name,
                'stage' => $milestone->production_stage,
                'deadline' => $milestone->deadline?->toDateString(),
                'is_critical' => $milestone->is_critical,
            ];
        }

        $summary['milestones_created'] = count($summary['milestones']);

        Log::info('Created milestones for project from templates', [
            'project_id' => $project->id,
            'project_name' => $project->name,
            'milestones_created' => $summary['milestones_created'],
            'base_date' => $baseDate->toDateString(),
        ]);

        return $summary;
    }

    /**
     * Create a single milestone from a template.
     *
     * @param Project $project
     * @param MilestoneTemplate $template
     * @param Carbon $baseDate
     * @return Milestone
     */
    protected function createMilestoneFromTemplate(
        Project $project,
        MilestoneTemplate $template,
        Carbon $baseDate
    ): Milestone {
        // Calculate deadline based on relative days from base date
        $deadline = $baseDate->copy()->addDays($template->relative_days);

        $milestone = Milestone::create([
            'name' => $template->name,
            'description' => $template->description,
            'production_stage' => $template->production_stage,
            'is_critical' => $template->is_critical,
            'sort_order' => $template->sort_order,
            'deadline' => $deadline,
            'is_completed' => false,
            'project_id' => $project->id,
            'creator_id' => auth()->id() ?? $project->creator_id,
        ]);

        // Create verification requirements from template
        $this->createRequirementsFromTemplate($milestone, $template);

        // Create tasks from template
        $this->createTasksFromTemplate($project, $milestone, $template, $deadline);

        return $milestone;
    }

    /**
     * Create milestone requirements from template requirements.
     *
     * @param Milestone $milestone
     * @param MilestoneTemplate $template
     * @return int Number of requirements created
     */
    protected function createRequirementsFromTemplate(Milestone $milestone, MilestoneTemplate $template): int
    {
        $count = 0;

        foreach ($template->requirements as $reqTemplate) {
            MilestoneRequirement::create([
                'milestone_id' => $milestone->id,
                'template_id' => $reqTemplate->id,
                'name' => $reqTemplate->name,
                'requirement_type' => $reqTemplate->requirement_type,
                'description' => $reqTemplate->description,
                'config' => $reqTemplate->config,
                'sort_order' => $reqTemplate->sort_order,
                'is_required' => $reqTemplate->is_required,
                'is_verified' => false,
            ]);
            $count++;
        }

        return $count;
    }

    /**
     * Create tasks from milestone template task templates.
     *
     * @param Project $project
     * @param Milestone $milestone
     * @param MilestoneTemplate $template
     * @param Carbon $milestoneDeadline
     * @return int Number of tasks created
     */
    protected function createTasksFromTemplate(
        Project $project,
        Milestone $milestone,
        MilestoneTemplate $template,
        Carbon $milestoneDeadline
    ): int {
        $count = 0;

        // Get the default task stage
        $defaultStage = TaskStage::where('project_id', $project->id)->orderBy('sort')->first()
            ?? TaskStage::whereNull('project_id')->orderBy('sort')->first();

        // Get root-level task templates (no parent)
        $rootTaskTemplates = $template->taskTemplates()
            ->whereNull('parent_id')
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get();

        foreach ($rootTaskTemplates as $taskTemplate) {
            $task = $this->createTaskFromTemplate(
                $project,
                $milestone,
                $taskTemplate,
                $milestoneDeadline,
                $defaultStage,
                null // No parent for root tasks
            );

            $count++;

            // Create subtasks
            $subtaskTemplates = $taskTemplate->children()->where('is_active', true)->orderBy('sort_order')->get();
            foreach ($subtaskTemplates as $subtaskTemplate) {
                $this->createTaskFromTemplate(
                    $project,
                    $milestone,
                    $subtaskTemplate,
                    $milestoneDeadline,
                    $defaultStage,
                    $task // Parent task
                );
                $count++;
            }
        }

        Log::info('Created tasks from milestone template', [
            'project_id' => $project->id,
            'milestone_id' => $milestone->id,
            'milestone_name' => $milestone->name,
            'tasks_created' => $count,
        ]);

        return $count;
    }

    /**
     * Create a single task from a task template.
     *
     * @param Project $project
     * @param Milestone $milestone
     * @param MilestoneTemplateTask $taskTemplate
     * @param Carbon $milestoneDeadline
     * @param TaskStage|null $defaultStage
     * @param Task|null $parentTask
     * @return Task
     */
    protected function createTaskFromTemplate(
        Project $project,
        Milestone $milestone,
        MilestoneTemplateTask $taskTemplate,
        Carbon $milestoneDeadline,
        ?TaskStage $defaultStage,
        ?Task $parentTask
    ): Task {
        // Calculate task start and deadline based on relative days and duration
        // relative_days = when task starts (from milestone start)
        // duration_days = how long the task takes (or calculated from project size)
        // deadline = start + duration

        // Get project metrics for formula-based duration calculations
        $linearFeet = $project->estimated_linear_feet ?? $project->total_linear_feet ?? null;
        $cabinetCount = $project->cabinets()->count();
        $roomCount = $project->rooms()->count();
        $doorCount = $project->cabinets()->withCount('doors')->get()->sum('doors_count');
        $drawerCount = $project->cabinets()->withCount('drawers')->get()->sum('drawers_count');

        // Get company for production rates
        $company = $project->company;

        // Calculate duration using the template's formula or fixed value
        $duration = $taskTemplate->calculateDuration(
            $linearFeet,
            $cabinetCount,
            $roomCount,
            $doorCount,
            $drawerCount,
            $company
        );

        $taskStart = $milestoneDeadline->copy()->addDays($taskTemplate->relative_days);
        $taskDeadline = $taskStart->copy()->addDays($duration);

        return Task::create([
            'title' => $taskTemplate->title,
            'description' => $taskTemplate->description,
            'allocated_hours' => $taskTemplate->allocated_hours,
            'priority' => $taskTemplate->priority,
            'sort' => $taskTemplate->sort_order,
            'deadline' => $taskDeadline,
            'project_id' => $project->id,
            'milestone_id' => $milestone->id,
            'parent_id' => $parentTask?->id,
            'stage_id' => $defaultStage?->id,
            'company_id' => $project->company_id,
            'creator_id' => auth()->id() ?? $project->creator_id,
            'is_active' => true,
        ]);
    }

    /**
     * Recalculate milestone deadlines based on a new reference date.
     *
     * Useful when project start date changes.
     *
     * @param Project $project
     * @param Carbon $newReferenceDate
     * @return int Number of milestones updated
     */
    public function recalculateMilestoneDeadlines(Project $project, Carbon $newReferenceDate): int
    {
        $templates = MilestoneTemplate::active()
            ->orderBy('sort_order')
            ->get()
            ->keyBy('name');

        $updated = 0;

        $milestones = Milestone::where('project_id', $project->id)
            ->where('is_completed', false)
            ->get();

        foreach ($milestones as $milestone) {
            $template = $templates->get($milestone->name);

            if ($template) {
                $newDeadline = $newReferenceDate->copy()->addDays($template->relative_days);
                $milestone->update(['deadline' => $newDeadline]);
                $updated++;
            }
        }

        Log::info('Recalculated milestone deadlines', [
            'project_id' => $project->id,
            'new_reference_date' => $newReferenceDate->toDateString(),
            'milestones_updated' => $updated,
        ]);

        return $updated;
    }

    /**
     * Get milestone progress summary for a project.
     *
     * @param Project $project
     * @return array
     */
    public function getMilestoneProgress(Project $project): array
    {
        $milestones = Milestone::where('project_id', $project->id)->get();

        $total = $milestones->count();
        $completed = $milestones->where('is_completed', true)->count();
        $overdue = $milestones->where('is_completed', false)
            ->filter(fn($m) => $m->deadline && $m->deadline->isPast())
            ->count();
        $upcoming = $milestones->where('is_completed', false)
            ->filter(fn($m) => $m->deadline && $m->deadline->isFuture())
            ->count();

        $byStage = $milestones->groupBy('production_stage')->map(function ($stageMilestones) {
            return [
                'total' => $stageMilestones->count(),
                'completed' => $stageMilestones->where('is_completed', true)->count(),
                'critical_completed' => $stageMilestones->where('is_completed', true)
                    ->where('is_critical', true)->count(),
                'critical_total' => $stageMilestones->where('is_critical', true)->count(),
            ];
        });

        return [
            'total' => $total,
            'completed' => $completed,
            'overdue' => $overdue,
            'upcoming' => $upcoming,
            'completion_percentage' => $total > 0 ? round(($completed / $total) * 100, 1) : 0,
            'by_stage' => $byStage->toArray(),
        ];
    }

    /**
     * Complete a milestone and optionally trigger gate evaluation.
     *
     * @param Milestone $milestone
     * @return Milestone
     */
    public function completeMilestone(Milestone $milestone): Milestone
    {
        if ($milestone->is_completed) {
            return $milestone;
        }

        $milestone->update([
            'is_completed' => true,
            'completed_at' => now(),
        ]);

        Log::info('Milestone completed', [
            'milestone_id' => $milestone->id,
            'milestone_name' => $milestone->name,
            'project_id' => $milestone->project_id,
            'production_stage' => $milestone->production_stage,
        ]);

        return $milestone;
    }

    /**
     * Check if all critical milestones for a stage are complete.
     *
     * @param Project $project
     * @param string $productionStage
     * @return bool
     */
    public function areAllCriticalMilestonesComplete(Project $project, string $productionStage): bool
    {
        $incompleteCritical = Milestone::where('project_id', $project->id)
            ->where('production_stage', $productionStage)
            ->where('is_critical', true)
            ->where('is_completed', false)
            ->count();

        return $incompleteCritical === 0;
    }

    /**
     * Get the next upcoming critical milestone for a project.
     *
     * @param Project $project
     * @return Milestone|null
     */
    public function getNextCriticalMilestone(Project $project): ?Milestone
    {
        return Milestone::where('project_id', $project->id)
            ->where('is_critical', true)
            ->where('is_completed', false)
            ->orderBy('deadline')
            ->first();
    }

    /**
     * Sync milestones with templates (add any missing ones).
     *
     * @param Project $project
     * @return array Summary of changes
     */
    public function syncMilestonesWithTemplates(Project $project): array
    {
        $summary = [
            'added' => 0,
            'already_exist' => 0,
        ];

        $templates = MilestoneTemplate::active()->get();
        $existingNames = Milestone::where('project_id', $project->id)
            ->pluck('name')
            ->toArray();

        $baseDate = $project->start_date ?? Carbon::today();

        foreach ($templates as $template) {
            if (in_array($template->name, $existingNames)) {
                $summary['already_exist']++;
                continue;
            }

            $this->createMilestoneFromTemplate($project, $template, $baseDate);
            $summary['added']++;
        }

        return $summary;
    }

    /**
     * Complete all milestones for a production stage when a gate passes.
     *
     * This links the Gate system to the Milestone system:
     * - When a gate passes (allowing stage advancement), all milestones
     *   for that stage are automatically marked as complete.
     *
     * @param Project $project
     * @param string $productionStage The stage whose milestones should be completed
     * @param Gate|null $gate The gate that passed (optional, for logging)
     * @return array Summary of completed milestones
     */
    public function completeMilestonesForStage(Project $project, string $productionStage, ?Gate $gate = null): array
    {
        $summary = [
            'stage' => $productionStage,
            'gate_key' => $gate?->gate_key,
            'milestones_completed' => 0,
            'milestones_already_complete' => 0,
            'milestones' => [],
        ];

        // Get all incomplete milestones for this stage
        $milestones = Milestone::where('project_id', $project->id)
            ->where('production_stage', $productionStage)
            ->get();

        foreach ($milestones as $milestone) {
            if ($milestone->is_completed) {
                $summary['milestones_already_complete']++;
                continue;
            }

            // Complete the milestone
            $milestone->update([
                'is_completed' => true,
                'completed_at' => now(),
                'completed_by_gate' => $gate?->gate_key,
            ]);

            $summary['milestones'][] = [
                'id' => $milestone->id,
                'name' => $milestone->name,
                'is_critical' => $milestone->is_critical,
            ];
            $summary['milestones_completed']++;
        }

        Log::info('Milestones completed for stage via gate pass', [
            'project_id' => $project->id,
            'production_stage' => $productionStage,
            'gate_key' => $gate?->gate_key,
            'milestones_completed' => $summary['milestones_completed'],
            'milestones_already_complete' => $summary['milestones_already_complete'],
        ]);

        return $summary;
    }

    /**
     * Handle gate pass event - complete related milestones.
     *
     * Maps gate_key to production_stage and completes milestones.
     * Gate-to-stage mapping:
     * - discovery_complete → discovery
     * - design_lock → design
     * - procurement_locked → sourcing
     * - receiving_complete, production_complete, qc_passed → production
     * - delivery_scheduled, delivered_closed → delivery
     *
     * @param Project $project
     * @param Gate $gate
     * @return array Summary
     */
    public function onGatePass(Project $project, Gate $gate): array
    {
        // Get the production stage from the gate's associated stage
        $productionStage = $gate->stage?->stage_key;

        if (!$productionStage) {
            Log::warning('Gate passed but no production stage found', [
                'project_id' => $project->id,
                'gate_key' => $gate->gate_key,
            ]);
            return ['error' => 'No production stage found for gate'];
        }

        return $this->completeMilestonesForStage($project, $productionStage, $gate);
    }
}
