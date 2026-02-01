# Stage and Gate Relationships

## Overview

TCS Woodwork ERP has **three related but distinct stage systems**:

1. **Project Stages** (`projects_project_stages`) - Visual kanban columns for project management
2. **Production Stages** (`current_production_stage` enum) - Business workflow position
3. **Task Stages** (`projects_task_stages`) - Per-project task kanban columns

**Gates** connect to **Project Stages** and control advancement through the **Production Stage** workflow.

## Relationship Diagram

```
┌─────────────────────────────────────────────────────────────────────────────────┐
│                              PROJECT                                             │
│                                                                                  │
│  ┌────────────────────────┐         ┌────────────────────────┐                  │
│  │ current_production_stage│ ◄────── │ stage_id (FK)          │                  │
│  │ (enum: discovery,       │  syncs  │ → ProjectStage         │                  │
│  │  design, sourcing,      │         │                        │                  │
│  │  production, delivery)  │         │ stage_key links them   │                  │
│  └────────────────────────┘         └────────────────────────┘                  │
│                                               │                                  │
└───────────────────────────────────────────────┼──────────────────────────────────┘
                                                │
                        ┌───────────────────────┼───────────────────────┐
                        │                       │                       │
                        ▼                       ▼                       ▼
        ┌───────────────────────┐  ┌───────────────────────┐  ┌───────────────────────┐
        │   PROJECT_STAGES      │  │        GATES          │  │    TASK_STAGES        │
        │   (Visual Kanban)     │  │  (Checkpoints)        │  │  (Per-Project Kanban) │
        ├───────────────────────┤  ├───────────────────────┤  ├───────────────────────┤
        │ - Discovery           │◄─┤ stage_id (FK)         │  │ - Backlog             │
        │ - Design              │  │ - Discovery Complete  │  │ - To Do               │
        │ - Sourcing            │  │ - Design Lock         │  │ - In Progress         │
        │ - Production          │  │ - Procurement Complete│  │ - Review              │
        │ - Delivery            │  │ - Receiving Complete  │  │ - Done                │
        │ - Complete            │  │ - Production Complete │  │ (customizable)        │
        │ (global, shared)      │  │ - QC Passed           │  │ (per-project)         │
        └───────────────────────┘  │ - Delivery Scheduled  │  └───────────────────────┘
                                   │ - Delivered & Closed  │              │
                                   └───────────────────────┘              │
                                              │                           │
                                              ▼                           │
                                   ┌───────────────────────┐              │
                                   │  GATE_REQUIREMENTS    │              │
                                   ├───────────────────────┤              │
                                   │ - field_not_null      │              │
                                   │ - relation_exists     │              │
                                   │ - custom_check        │              │
                                   │ ...                   │              │
                                   └───────────────────────┘              │
                                                                          │
                                              ┌───────────────────────────┘
                                              ▼
                                   ┌───────────────────────┐
                                   │        TASKS          │
                                   ├───────────────────────┤
                                   │ - stage_id (FK)       │──► Task Stage
                                   │ - project_id (FK)     │──► Project
                                   │ - state (enum)        │
                                   │ - task_type           │
                                   └───────────────────────┘
```

## The Three Stage Systems Explained

### 1. Project Stages (`projects_project_stages`)

**Purpose:** Global kanban columns for visual project management.

**Characteristics:**
- Shared across all projects
- Linked via `stage_key` to production stages
- Projects assigned via `project.stage_id`
- Gates are attached to these stages

**Table:** `projects_project_stages`
```sql
| id | name       | stage_key  | sort |
|----|------------|------------|------|
| 9  | Discovery  | discovery  | 1    |
| 10 | Design     | design     | 2    |
| 11 | Sourcing   | sourcing   | 3    |
| 12 | Production | production | 6    |
| 13 | Delivery   | delivery   | 7    |
```

### 2. Production Stages (`current_production_stage`)

**Purpose:** Defines the business workflow position of a project.

**Characteristics:**
- Enum field on Project model
- Controls what work can be done
- Syncs with `stage_id` via `stage_key` matching
- Sequence: discovery → design → sourcing → production → delivery

**Code:**
```php
// Project.php
public const PRODUCTION_STAGES = [
    'discovery',
    'design',
    'sourcing',
    'production',
    'delivery',
];
```

### 3. Task Stages (`projects_task_stages`)

**Purpose:** Per-project kanban columns for task management.

**Characteristics:**
- Each project has its own set of task stages
- Tasks within a project are assigned to these stages
- Completely independent from project stages
- Default stages: Backlog, To Do, In Progress, Review, Done

**Table:** `projects_task_stages`
```sql
| id  | name        | project_id | sort |
|-----|-------------|------------|------|
| 101 | Backlog     | 441        | 1    |
| 102 | To Do       | 441        | 2    |
| 103 | In Progress | 441        | 3    |
| 104 | Done        | 441        | 4    |
```

## How Gates Connect

### Gates → Project Stages

Gates are attached to Project Stages via `stage_id`:

```sql
SELECT ps.name as stage, g.name as gate, g.is_blocking
FROM projects_gates g
JOIN projects_project_stages ps ON g.stage_id = ps.id;

-- Result:
-- | Discovery  | Discovery Complete    | 1 |
-- | Design     | Design Lock           | 1 |
-- | Sourcing   | Procurement Complete  | 1 |
-- | Production | Receiving Complete    | 1 |
-- | Production | Production Complete   | 1 |
-- | Production | QC Passed             | 1 |
-- | Delivery   | Delivery Scheduled    | 1 |
-- | Delivery   | Delivered & Closed    | 1 |
```

### Gates Control Stage Advancement

When a project tries to advance:

1. System checks `project.stage_id` → finds current Project Stage
2. Loads all active, blocking gates for that stage
3. Evaluates each gate's requirements against the project
4. If all pass → project can advance to next stage
5. If any fail → project is blocked, blockers shown to user

```php
// GateEvaluator.php
public function canAdvance(Project $project): bool
{
    // Get gates for current stage
    $gates = $project->getCurrentStageGates()->where('is_blocking', true);

    foreach ($gates as $gate) {
        $result = $this->evaluate($project, $gate);
        if (!$result->passed) {
            return false;
        }
    }

    return true;
}
```

### Stage Synchronization

When advancing stages, both systems update:

```php
// Project.php
public function advanceToNextStage(bool $force = false): bool
{
    // Update production stage enum
    $this->current_production_stage = $nextStage;

    // Sync stage_id to matching ProjectStage
    $matchingStage = ProjectStage::where('stage_key', $nextStage)->first();
    if ($matchingStage) {
        $this->stage_id = $matchingStage->id;
    }
}
```

## Gates Do NOT Connect To Task Stages

**Important:** Gates have no direct relationship to Task Stages.

Task Stages are per-project task organization tools. They don't control project workflow.

However, gates CAN check if tasks are completed:

```php
// Gate requirement can check task completion
GateRequirement::create([
    'requirement_type' => 'task_completed',
    'target_value' => 'design_review',  // task_type
    'error_message' => 'Design review task not completed',
]);
```

This checks if a task with `task_type = 'design_review'` has `state = 'done'`, but doesn't interact with Task Stages directly.

## Summary Table

| System | Scope | Purpose | Gates Connection |
|--------|-------|---------|------------------|
| Project Stages | Global (shared) | Visual kanban for projects | **Direct** - gates attached via `stage_id` |
| Production Stages | Per project (enum) | Business workflow position | **Indirect** - syncs via `stage_key` |
| Task Stages | Per project | Task organization kanban | **None** - independent system |

## Querying the Relationships

### Find gates for a project's current stage

```php
$project = Project::find($id);
$gates = $project->getCurrentStageGates();
```

### Find which stage a gate belongs to

```php
$gate = Gate::findByKey('design_lock');
$stage = $gate->stage; // ProjectStage model
echo $stage->stage_key; // 'design'
```

### Check if project can advance based on gates

```php
$evaluator = app(GateEvaluator::class);
$canAdvance = $evaluator->canAdvance($project);
$blockers = $evaluator->getBlockers($project);
```

### Get task stages for a project (unrelated to gates)

```php
$project = Project::find($id);
$taskStages = $project->taskStages; // Independent of gates
```
