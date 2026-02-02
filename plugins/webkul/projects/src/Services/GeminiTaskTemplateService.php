<?php

namespace Webkul\Project\Services;

use App\Services\AI\GeminiService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Webkul\Project\Models\AiTaskSuggestion;
use Webkul\Project\Models\MilestoneTemplate;
use Webkul\Project\Models\MilestoneTemplateTask;

/**
 * Service for generating AI-powered task template suggestions using Gemini.
 *
 * Integrates with existing GeminiService to provide context-aware task generation
 * for milestone templates in the woodworking ERP system.
 */
class GeminiTaskTemplateService
{
    public function __construct(
        protected GeminiService $geminiService
    ) {}

    /**
     * Generate task suggestions for a milestone template.
     *
     * @param MilestoneTemplate $template The milestone template to generate tasks for
     * @param string|null $additionalContext Optional user-provided context
     * @return AiTaskSuggestion The created suggestion record
     */
    public function generateTaskSuggestions(
        MilestoneTemplate $template,
        ?string $additionalContext = null
    ): AiTaskSuggestion {
        $prompt = $this->buildPrompt($template, $additionalContext);
        $context = $this->buildContext($template);

        try {
            Log::info('GeminiTaskTemplateService: Starting task generation', [
                'template_id' => $template->id,
                'template_name' => $template->name,
                'production_stage' => $template->production_stage,
                'has_description' => !empty($template->description),
                'has_additional_context' => !empty($additionalContext),
                'prompt_length' => strlen($prompt),
            ]);

            // Call Gemini API with the reasoning model for better task generation
            // Using the configured reasoning model (gemini-2.5-pro by default)
            $reasoningModel = config('gemini.reasoning_model', 'gemini-2.5-pro');
            $maxTokens = config('gemini.available_models.' . $reasoningModel . '.max_tokens', 12000);

            $response = $this->geminiService->generateResponseWithModel(
                $prompt,
                $context,
                $reasoningModel,
                min($maxTokens, 16000) // Cap at 16k for task generation output
            );

            Log::info('GeminiTaskTemplateService: Received AI response', [
                'template_id' => $template->id,
                'response_length' => strlen($response),
                'response_preview' => substr($response, 0, 500),
            ]);

            // Parse the response
            $parsedResponse = $this->parseResponse($response);

            Log::info('GeminiTaskTemplateService: Parsed response', [
                'template_id' => $template->id,
                'task_count' => count($parsedResponse['tasks'] ?? []),
                'has_reasoning' => !empty($parsedResponse['reasoning']),
            ]);

            // Validate suggestions
            $validationResult = $this->validateSuggestions($parsedResponse['tasks'] ?? []);

            Log::info('GeminiTaskTemplateService: Validated suggestions', [
                'template_id' => $template->id,
                'valid_count' => count($validationResult['valid_tasks']),
                'warning_count' => count($validationResult['warnings'] ?? []),
                'error_count' => count($validationResult['errors'] ?? []),
            ]);

            // Calculate confidence score
            $confidenceScore = $this->calculateConfidenceScore($parsedResponse, $validationResult);

            // Store the prompt used for debugging
            $promptContext = [
                'milestone_name' => $template->name,
                'milestone_description' => $template->description,
                'production_stage' => $template->production_stage,
                'additional_context' => $additionalContext,
                'prompt_preview' => substr($prompt, 0, 1000),
                'raw_response_preview' => substr($response, 0, 1000),
                'validation_warnings' => $validationResult['warnings'] ?? [],
                'validation_errors' => $validationResult['errors'] ?? [],
            ];

            // Create and return the suggestion record
            return AiTaskSuggestion::create([
                'milestone_template_id' => $template->id,
                'created_by' => Auth::id(),
                'suggested_tasks' => $validationResult['valid_tasks'],
                'status' => AiTaskSuggestion::STATUS_PENDING,
                'confidence_score' => $confidenceScore,
                'ai_reasoning' => $parsedResponse['reasoning'] ?? $this->extractReasoningFallback($response),
                'prompt_context' => json_encode($promptContext),
                'model_used' => $this->geminiService->getModel(),
            ]);
        } catch (\Exception $e) {
            Log::error('GeminiTaskTemplateService: Failed to generate suggestions', [
                'template_id' => $template->id,
                'template_name' => $template->name,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    /**
     * Try to extract any reasoning from the response if structured parsing failed.
     */
    protected function extractReasoningFallback(string $response): ?string
    {
        // If response doesn't look like JSON, it might be an error message or explanation
        if (strpos($response, '{') === false) {
            return "AI Response (unparsed): " . substr($response, 0, 500);
        }

        return null;
    }

    /**
     * Build the prompt for Gemini AI.
     */
    public function buildPrompt(MilestoneTemplate $template, ?string $additionalContext): string
    {
        $stageDescription = $this->getStageDescription($template->production_stage);
        $existingTasks = $template->taskTemplates->pluck('title')->toArray();

        // Get ALL milestones grouped by phase to show full workflow context
        $allMilestonesByPhase = $this->getAllMilestonesByPhase($template);

        // Determine the primary context for task generation
        $hasDescription = !empty($template->description);
        $hasAdditionalContext = !empty($additionalContext);

        // Build description section - if no description, use additional context as primary
        $descriptionSection = '';
        if ($hasDescription) {
            $descriptionSection = "- **Description**: {$template->description}";
        } elseif ($hasAdditionalContext) {
            $descriptionSection = "- **Description**: Based on user context - see below";
        } else {
            $descriptionSection = "- **Description**: (No description provided - use milestone name and production stage to infer appropriate tasks)";
        }

        $prompt = <<<PROMPT
You are an expert in woodworking production management and project planning for custom cabinetry shops.

Generate task templates for a SPECIFIC milestone in a woodworking project.

**CRITICAL RULES**:
1. Tasks MUST be specific to THIS milestone only
2. Do NOT include tasks that belong to other milestones (see full workflow below)
3. Each milestone has a distinct purpose - respect the boundaries
4. If a task logically belongs to another milestone, DO NOT include it here

## Target Milestone (generate tasks for THIS milestone ONLY)
- **Name**: {$template->name}
- **Production Stage**: {$template->production_stage} ({$stageDescription})
{$descriptionSection}
- **Is Critical**: {$this->formatBoolean($template->is_critical)}
- **Relative Days**: Day {$template->relative_days} (when this milestone starts relative to project start)
- **Sort Order**: {$template->sort_order} (sequence within the phase)

PROMPT;

        // Add FULL WORKFLOW context - all milestones across all phases
        $prompt .= $this->buildWorkflowContextSection($template, $allMilestonesByPhase);

        // If no description but has additional context, treat it as the PRIMARY context
        if (!$hasDescription && $hasAdditionalContext) {
            $prompt .= <<<PROMPT

## PRIMARY CONTEXT (Use this as the main guide for task generation)
{$additionalContext}

PROMPT;
        }

        if (!empty($existingTasks)) {
            $existingTasksList = implode("\n- ", $existingTasks);
            $prompt .= <<<PROMPT

## Existing Tasks in THIS Milestone (avoid duplicates)
- {$existingTasksList}

PROMPT;
        }

        // If has both description and additional context, add as supplementary
        if ($hasDescription && $hasAdditionalContext) {
            $prompt .= <<<PROMPT

## Additional Context from User
{$additionalContext}

PROMPT;
        }

        // Fetch actual company production rates
        $companyRates = $this->getCompanyProductionRates();

        $prompt .= <<<PROMPT

## Company Production Rates (ACTUAL VALUES - use these for duration calculations)

### Shop Capacity (Overall Throughput)
- `shop_capacity_per_day`: **{$companyRates['shop_capacity_per_day']} LF/day** - Total shop output delivered to customer
- This is the END-TO-END throughput - what the shop can complete and deliver per day
- Use this for overall project/milestone duration estimates

### Department-Specific Rates (use the rate_key in duration_rate_key field)
| Rate Key | Description | Value |
|----------|-------------|-------|
| `design_concepts_lf_per_day` | Initial design concepts | **{$companyRates['design_concepts_lf_per_day']} LF/day** |
| `design_revisions_lf_per_day` | Design revisions | **{$companyRates['design_revisions_lf_per_day']} LF/day** |
| `shop_drawings_lf_per_day` | Shop drawings/CAD | **{$companyRates['shop_drawings_lf_per_day']} LF/day** |
| `cut_list_bom_lf_per_day` | Cut list & BOM generation | **{$companyRates['cut_list_bom_lf_per_day']} LF/day** |
| `rough_mill_lf_per_day` | Rough milling | **{$companyRates['rough_mill_lf_per_day']} LF/day** |
| `cabinet_assembly_lf_per_day` | Cabinet assembly | **{$companyRates['cabinet_assembly_lf_per_day']} LF/day** |
| `doors_drawers_lf_per_day` | Doors & drawers production | **{$companyRates['doors_drawers_lf_per_day']} LF/day** |
| `sanding_prep_lf_per_day` | Sanding & prep | **{$companyRates['sanding_prep_lf_per_day']} LF/day** |
| `finishing_lf_per_day` | Finishing | **{$companyRates['finishing_lf_per_day']} LF/day** |
| `hardware_install_lf_per_day` | Hardware installation | **{$companyRates['hardware_install_lf_per_day']} LF/day** |
| `installation_lf_per_day` | On-site installation | **{$companyRates['installation_lf_per_day']} LF/day** |

### Rate Selection Guidelines
- **For individual tasks** (e.g., "Assemble Cabinets"): Use the department-specific rate
- **For overall production milestones**: Use `shop_capacity_per_day`
- **For design phase tasks**: Use design-related rates
- **For delivery/installation**: Use `installation_lf_per_day`

### Duration Calculation Example
If a project has 100 LF and uses `cabinet_assembly_lf_per_day` ({$companyRates['cabinet_assembly_lf_per_day']} LF/day):
- Duration = 100 LF ÷ {$companyRates['cabinet_assembly_lf_per_day']} LF/day = ~{$this->formatDuration(100 / ($companyRates['cabinet_assembly_lf_per_day'] ?: 25))} days

## Valid Duration Unit Types
PROMPT;

        foreach (MilestoneTemplateTask::DURATION_UNIT_TYPES as $key => $label) {
            $prompt .= "\n- `{$key}`: {$label}";
        }

        $prompt .= <<<PROMPT


## Response Format
Return a JSON object with this structure:
```json
{
  "reasoning": "Brief explanation of why these tasks are appropriate for this milestone",
  "tasks": [
    {
      "title": "Task title (action-oriented, concise)",
      "description": "Detailed description of what needs to be done",
      "allocated_hours": 4,
      "relative_days": 0,
      "duration_type": "formula",
      "duration_rate_key": "cabinet_assembly_lf_per_day",
      "duration_unit_type": "linear_feet",
      "duration_min_days": 2,
      "duration_max_days": 30,
      "duration_days": null,
      "priority": false,
      "sort_order": 1,
      "subtasks": [
        {
          "title": "Subtask title",
          "description": "Subtask description",
          "allocated_hours": 2,
          "relative_days": 0,
          "duration_type": "fixed",
          "duration_days": 1
        }
      ]
    },
    {
      "title": "Fixed duration task example",
      "description": "Task with a set number of days",
      "allocated_hours": 8,
      "relative_days": 5,
      "duration_type": "fixed",
      "duration_rate_key": null,
      "duration_unit_type": null,
      "duration_min_days": null,
      "duration_max_days": null,
      "duration_days": 3,
      "priority": true,
      "sort_order": 2,
      "subtasks": []
    }
  ]
}
```

## Field Definitions
- **duration_type**: "formula" (scales with project size) or "fixed" (set number of days)
- **duration_rate_key**: Company production rate to use (only for formula type)
- **duration_unit_type**: What to measure - "linear_feet", "cabinets", "rooms", "doors", or "drawers"
- **duration_min_days**: Minimum days even for small projects (optional, for formula type)
- **duration_max_days**: Maximum days cap for large projects (optional, for formula type)
- **duration_days**: Fixed number of days (only for fixed type, ignored for formula)
- **relative_days**: When task should start relative to milestone start (0 = day 1)
- **subtasks**: Break down complex tasks; subtasks always use fixed duration

## Guidelines

### Task Generation
1. Generate 3-7 tasks appropriate for this milestone type
2. Ensure tasks are specific to woodworking/cabinetry production
3. Avoid duplicating existing tasks
4. Tasks should flow logically within the production stage

### Duration Configuration
5. Use `duration_type: "formula"` with `duration_rate_key` for tasks that scale with project size (assembly, finishing, etc.)
6. Use `duration_type: "fixed"` with `duration_days` for tasks with consistent duration (meetings, approvals, etc.)
7. For formula tasks, always set `duration_unit_type` (usually "linear_feet" for woodworking)
8. Use `duration_min_days`/`duration_max_days` to set reasonable bounds (e.g., min 1 day even for small jobs)

### Timing & Priority
9. Set `relative_days` to indicate when task should start (0 = milestone start, 5 = 5 days after milestone starts)
10. Set `priority: true` only for critical-path tasks that could delay the whole project

### Subtasks
11. Include subtasks to break down complex tasks into discrete steps
12. Subtasks always use fixed duration (not formula)
13. Subtask `relative_days` is relative to the PARENT task start
14. Good candidates for subtasks: QC checkpoints, documentation, review steps, prep work

Return ONLY the JSON object, no additional text or markdown formatting.
PROMPT;

        return $prompt;
    }

    /**
     * Build context array for the prompt.
     */
    protected function buildContext(MilestoneTemplate $template): array
    {
        return [
            'domain' => 'woodworking_production',
            'application' => 'tcs_woodwork_erp',
            'valid_rate_keys' => array_keys(MilestoneTemplateTask::COMPANY_RATE_KEYS),
            'valid_unit_types' => array_keys(MilestoneTemplateTask::DURATION_UNIT_TYPES),
            'production_stages' => ['discovery', 'design', 'sourcing', 'production', 'delivery', 'general'],
        ];
    }

    /**
     * Parse the AI response into structured data.
     */
    public function parseResponse(string $response): array
    {
        // Try to extract JSON from the response
        $jsonStart = strpos($response, '{');
        $jsonEnd = strrpos($response, '}');

        if ($jsonStart === false || $jsonEnd === false) {
            Log::warning('GeminiTaskTemplateService: No JSON found in response', [
                'response' => substr($response, 0, 500),
            ]);
            return ['tasks' => [], 'reasoning' => null];
        }

        $jsonString = substr($response, $jsonStart, $jsonEnd - $jsonStart + 1);
        $parsed = json_decode($jsonString, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            Log::warning('GeminiTaskTemplateService: Failed to parse JSON', [
                'error' => json_last_error_msg(),
                'json' => substr($jsonString, 0, 500),
            ]);
            return ['tasks' => [], 'reasoning' => null];
        }

        return $parsed;
    }

    /**
     * Validate and clean suggested tasks.
     *
     * @return array ['valid_tasks' => array, 'warnings' => array, 'errors' => array]
     */
    public function validateSuggestions(array $suggestions): array
    {
        $validTasks = [];
        $warnings = [];
        $errors = [];

        foreach ($suggestions as $index => $task) {
            $taskValidation = $this->validateTask($task, $index);

            if ($taskValidation['valid']) {
                $validTasks[] = $taskValidation['cleaned_task'];
            } else {
                $errors[] = $taskValidation['error'];
            }

            $warnings = array_merge($warnings, $taskValidation['warnings'] ?? []);
        }

        return [
            'valid_tasks' => $validTasks,
            'warnings' => $warnings,
            'errors' => $errors,
        ];
    }

    /**
     * Validate a single task.
     */
    protected function validateTask(array $task, int $index): array
    {
        $warnings = [];
        $cleanedTask = [];

        // Required: title
        if (empty($task['title']) || !is_string($task['title'])) {
            return [
                'valid' => false,
                'error' => "Task {$index}: Missing or invalid title",
            ];
        }
        $cleanedTask['title'] = trim($task['title']);

        // Optional: description
        $cleanedTask['description'] = isset($task['description']) && is_string($task['description'])
            ? trim($task['description'])
            : null;

        // Validate allocated_hours
        $cleanedTask['allocated_hours'] = $this->validateNumeric($task['allocated_hours'] ?? 0, 0, 100, 0);
        if ($cleanedTask['allocated_hours'] > 40) {
            $warnings[] = "Task {$index}: Allocated hours ({$cleanedTask['allocated_hours']}) seems high";
        }

        // Validate relative_days
        $cleanedTask['relative_days'] = $this->validateNumeric($task['relative_days'] ?? 0, 0, 365, 0);

        // Validate duration configuration
        $cleanedTask['duration_type'] = in_array($task['duration_type'] ?? 'fixed', ['fixed', 'formula'])
            ? $task['duration_type']
            : 'fixed';

        if ($cleanedTask['duration_type'] === 'formula') {
            // Validate rate key
            $rateKey = $task['duration_rate_key'] ?? null;
            if ($rateKey && !array_key_exists($rateKey, MilestoneTemplateTask::COMPANY_RATE_KEYS)) {
                $warnings[] = "Task {$index}: Invalid duration_rate_key '{$rateKey}', falling back to fixed";
                $cleanedTask['duration_type'] = 'fixed';
                $cleanedTask['duration_days'] = 1;
                $cleanedTask['duration_rate_key'] = null;
            } else {
                $cleanedTask['duration_rate_key'] = $rateKey;
                $cleanedTask['duration_days'] = null; // Formula tasks don't use fixed days
            }

            // Validate unit type
            $unitType = $task['duration_unit_type'] ?? 'linear_feet';
            $cleanedTask['duration_unit_type'] = array_key_exists($unitType, MilestoneTemplateTask::DURATION_UNIT_TYPES)
                ? $unitType
                : 'linear_feet';

            // Optional min/max bounds
            $cleanedTask['duration_min_days'] = isset($task['duration_min_days'])
                ? $this->validateNumeric($task['duration_min_days'], 1, 365, null)
                : null;
            $cleanedTask['duration_max_days'] = isset($task['duration_max_days'])
                ? $this->validateNumeric($task['duration_max_days'], 1, 365, null)
                : null;

            // Optional custom rate (duration_unit_size) - only if no rate_key
            if (!$rateKey && isset($task['duration_unit_size'])) {
                $cleanedTask['duration_unit_size'] = $this->validateNumeric($task['duration_unit_size'], 0.1, 1000, null);
            } else {
                $cleanedTask['duration_unit_size'] = null;
            }
        } else {
            // Fixed duration
            $cleanedTask['duration_days'] = $this->validateNumeric($task['duration_days'] ?? 1, 1, 365, 1);
            $cleanedTask['duration_rate_key'] = null;
            $cleanedTask['duration_unit_type'] = null;
            $cleanedTask['duration_min_days'] = null;
            $cleanedTask['duration_max_days'] = null;
            $cleanedTask['duration_unit_size'] = null;
        }

        // Validate priority
        $cleanedTask['priority'] = (bool) ($task['priority'] ?? false);

        // Validate sort_order
        $cleanedTask['sort_order'] = $this->validateNumeric($task['sort_order'] ?? ($index + 1), 0, 999, $index + 1);

        // Validate subtasks
        if (!empty($task['subtasks']) && is_array($task['subtasks'])) {
            $cleanedSubtasks = [];
            foreach ($task['subtasks'] as $subIndex => $subtask) {
                $subtaskValidation = $this->validateSubtask($subtask, $index, $subIndex);
                if ($subtaskValidation['valid']) {
                    $cleanedSubtasks[] = $subtaskValidation['cleaned_task'];
                } else {
                    $warnings[] = $subtaskValidation['error'];
                }
                $warnings = array_merge($warnings, $subtaskValidation['warnings'] ?? []);
            }
            $cleanedTask['subtasks'] = $cleanedSubtasks;
        } else {
            $cleanedTask['subtasks'] = [];
        }

        return [
            'valid' => true,
            'cleaned_task' => $cleanedTask,
            'warnings' => $warnings,
        ];
    }

    /**
     * Validate a subtask.
     */
    protected function validateSubtask(array $subtask, int $parentIndex, int $subIndex): array
    {
        $warnings = [];
        $cleanedTask = [];

        // Required: title
        if (empty($subtask['title']) || !is_string($subtask['title'])) {
            return [
                'valid' => false,
                'error' => "Task {$parentIndex}.{$subIndex}: Missing or invalid subtask title",
            ];
        }
        $cleanedTask['title'] = trim($subtask['title']);

        // Optional fields with defaults
        $cleanedTask['description'] = isset($subtask['description']) && is_string($subtask['description'])
            ? trim($subtask['description'])
            : null;
        $cleanedTask['allocated_hours'] = $this->validateNumeric($subtask['allocated_hours'] ?? 0, 0, 40, 0);
        $cleanedTask['relative_days'] = $this->validateNumeric($subtask['relative_days'] ?? 0, 0, 365, 0);
        $cleanedTask['duration_type'] = 'fixed';
        $cleanedTask['duration_days'] = $this->validateNumeric($subtask['duration_days'] ?? 1, 1, 30, 1);

        return [
            'valid' => true,
            'cleaned_task' => $cleanedTask,
            'warnings' => $warnings,
        ];
    }

    /**
     * Validate a numeric value within bounds.
     */
    protected function validateNumeric($value, $min, $max, $default)
    {
        if (!is_numeric($value)) {
            return $default;
        }

        $value = (float) $value;

        if ($value < $min) {
            return $min;
        }

        if ($value > $max) {
            return $max;
        }

        return $value;
    }

    /**
     * Calculate confidence score based on response quality.
     */
    public function calculateConfidenceScore(array $parsedResponse, array $validationResult): float
    {
        $score = 100.0;

        // Deduct for missing reasoning
        if (empty($parsedResponse['reasoning'])) {
            $score -= 10;
        }

        // Deduct for no valid tasks
        $validCount = count($validationResult['valid_tasks']);
        if ($validCount === 0) {
            return 0;
        }

        // Deduct for too few tasks
        if ($validCount < 3) {
            $score -= 15;
        }

        // Deduct for validation errors
        $errorCount = count($validationResult['errors'] ?? []);
        $score -= ($errorCount * 5);

        // Deduct for warnings
        $warningCount = count($validationResult['warnings'] ?? []);
        $score -= ($warningCount * 2);

        // Ensure score is between 0 and 100
        return max(0, min(100, $score));
    }

    /**
     * Get a description for the production stage.
     */
    protected function getStageDescription(string $stage): string
    {
        return match ($stage) {
            'discovery' => 'Initial client meetings, site visits, requirements gathering. Use FIXED durations (duration_type: "fixed") for these tasks as they don\'t scale with project size.',
            'design' => 'Design concepts, revisions, shop drawings, CAD work. Use FORMULA durations with design rates: design_concepts_lf_per_day, design_revisions_lf_per_day, shop_drawings_lf_per_day.',
            'sourcing' => 'Material selection, ordering, vendor coordination. Use FORMULA with cut_list_bom_lf_per_day for BOM tasks. Use FIXED durations for ordering/procurement tasks.',
            'production' => 'CNC cutting, rough mill, assembly, finishing, hardware. Use FORMULA durations with department rates: rough_mill_lf_per_day, cabinet_assembly_lf_per_day, doors_drawers_lf_per_day, sanding_prep_lf_per_day, finishing_lf_per_day, hardware_install_lf_per_day. For overall production milestone duration, use shop_capacity_per_day.',
            'delivery' => 'Final QC, delivery, installation, punch list. Use FORMULA with installation_lf_per_day for install tasks. Use FIXED for QC and punch list tasks.',
            'general' => 'Administrative and general project tasks. Use FIXED durations as these don\'t scale with project size.',
            default => 'Project tasks',
        };
    }

    /**
     * Format boolean for prompt.
     */
    protected function formatBoolean(?bool $value): string
    {
        return $value ? 'Yes' : 'No';
    }

    /**
     * Build the workflow context section showing all milestones across all phases.
     * This helps AI understand where the current milestone fits in the overall workflow.
     */
    protected function buildWorkflowContextSection(MilestoneTemplate $currentTemplate, array $allMilestonesByPhase): string
    {
        $stageOrder = ['discovery', 'design', 'sourcing', 'production', 'delivery', 'general'];
        $stageLabels = [
            'discovery' => 'Discovery Phase (Client interaction, scoping, proposals)',
            'design' => 'Design Phase (Concepts, drawings, BOMs)',
            'sourcing' => 'Sourcing Phase (Materials, ordering, receiving)',
            'production' => 'Production Phase (Milling, assembly, finishing)',
            'delivery' => 'Delivery Phase (Installation, QC, sign-off)',
            'general' => 'General (Administrative tasks)',
        ];

        $section = "\n## COMPLETE PROJECT WORKFLOW (DO NOT put tasks from other milestones here)\n";
        $section .= "Below are ALL milestones in the system. Tasks should ONLY go in the appropriate milestone.\n";

        foreach ($stageOrder as $stage) {
            if (!isset($allMilestonesByPhase[$stage]) && $stage !== $currentTemplate->production_stage) {
                continue;
            }

            $isCurrentStage = ($stage === $currentTemplate->production_stage);
            $stageLabel = $stageLabels[$stage] ?? ucfirst($stage);
            $marker = $isCurrentStage ? ' ← CURRENT PHASE' : '';

            $section .= "\n### {$stageLabel}{$marker}\n";

            // Add current template in its position
            if ($isCurrentStage) {
                $section .= "- **>>> {$currentTemplate->name} <<<** (THIS MILESTONE - generate tasks here)\n";
            }

            // Add other milestones in this stage
            if (isset($allMilestonesByPhase[$stage])) {
                foreach ($allMilestonesByPhase[$stage] as $milestone) {
                    $taskList = '';
                    if (!empty($milestone['tasks'])) {
                        $taskList = ' → Tasks: ' . implode(', ', array_slice($milestone['tasks'], 0, 4));
                        if (count($milestone['tasks']) > 4) {
                            $taskList .= '...';
                        }
                    } else {
                        $taskList = ' → (no tasks yet)';
                    }

                    $desc = $milestone['description'] ? " - {$milestone['description']}" : '';
                    $section .= "- **{$milestone['name']}**{$desc}{$taskList}\n";
                }
            }
        }

        $section .= "\n";
        return $section;
    }

    /**
     * Get ALL other milestones grouped by phase to avoid task overlap.
     * This helps AI understand what tasks belong where across the entire workflow.
     *
     * @return array Array of phases with their milestones and tasks
     */
    protected function getAllMilestonesByPhase(MilestoneTemplate $currentTemplate): array
    {
        $allMilestones = MilestoneTemplate::where('id', '!=', $currentTemplate->id)
            ->where('is_active', true)
            ->orderBy('production_stage')
            ->orderBy('sort_order')
            ->with(['taskTemplates' => function ($query) {
                $query->whereNull('parent_id')->select('id', 'milestone_template_id', 'title');
            }])
            ->get();

        // Group by production stage
        return $allMilestones->groupBy('production_stage')->map(function ($milestones, $stage) {
            return $milestones->map(function ($milestone) {
                return [
                    'name' => $milestone->name,
                    'description' => $milestone->description,
                    'relative_days' => $milestone->relative_days,
                    'tasks' => $milestone->taskTemplates->pluck('title')->toArray(),
                ];
            })->toArray();
        })->toArray();
    }

    /**
     * Get other milestones in the same production stage only.
     * @deprecated Use getAllMilestonesByPhase instead
     */
    protected function getOtherMilestonesInPhase(MilestoneTemplate $currentTemplate): array
    {
        $otherMilestones = MilestoneTemplate::where('production_stage', $currentTemplate->production_stage)
            ->where('id', '!=', $currentTemplate->id)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->with(['taskTemplates' => function ($query) {
                $query->whereNull('parent_id')->select('id', 'milestone_template_id', 'title');
            }])
            ->get();

        return $otherMilestones->map(function ($milestone) {
            return [
                'name' => $milestone->name,
                'description' => $milestone->description,
                'relative_days' => $milestone->relative_days,
                'sort_order' => $milestone->sort_order,
                'tasks' => $milestone->taskTemplates->pluck('title')->toArray(),
            ];
        })->toArray();
    }

    /**
     * Format duration for display in prompt.
     */
    protected function formatDuration(float $days): string
    {
        return number_format(ceil($days), 0);
    }

    /**
     * Get company production rates for prompt context.
     * Fetches from the current user's company or uses defaults.
     */
    protected function getCompanyProductionRates(): array
    {
        // Try to get company from authenticated user
        $company = null;
        if (Auth::check()) {
            $user = Auth::user();
            // Try to get default company from user
            if (method_exists($user, 'defaultCompany')) {
                $company = $user->defaultCompany;
            } elseif (isset($user->company_id)) {
                $company = \Webkul\Support\Models\Company::find($user->company_id);
            }
        }

        // If no company, try to get the first active company
        if (!$company) {
            $company = \Webkul\Support\Models\Company::active()->first();
        }

        // Default values if no company found
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

        if (!$company) {
            Log::warning('GeminiTaskTemplateService: No company found, using default production rates');
            return $defaults;
        }

        // Build rates array from company, falling back to defaults
        $rates = [];
        foreach ($defaults as $key => $default) {
            $rates[$key] = $company->$key ?? $default;
        }

        Log::info('GeminiTaskTemplateService: Using company production rates', [
            'company_id' => $company->id,
            'company_name' => $company->name,
            'rates' => $rates,
        ]);

        return $rates;
    }
}
