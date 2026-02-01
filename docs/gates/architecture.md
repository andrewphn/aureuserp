# Gates System Architecture

## System Overview

```
┌─────────────────────────────────────────────────────────────────────┐
│                         Gate System                                  │
├─────────────────────────────────────────────────────────────────────┤
│                                                                      │
│  ┌──────────────┐      ┌───────────────────┐      ┌──────────────┐ │
│  │    Gate      │ 1──* │  GateRequirement  │      │GateEvaluation│ │
│  │              │      │                   │      │              │ │
│  │ - name       │      │ - requirement_type│      │ - passed     │ │
│  │ - gate_key   │      │ - target_field    │      │ - evaluated_at│
│  │ - is_blocking│      │ - target_relation │      │ - context    │ │
│  │ - locks      │      │ - error_message   │      │ - results    │ │
│  └──────┬───────┘      └───────────────────┘      └──────────────┘ │
│         │                                                           │
│         │ belongs_to                                                │
│         ▼                                                           │
│  ┌──────────────┐      ┌───────────────────┐                       │
│  │ ProjectStage │ 1──* │     Project       │                       │
│  │              │      │                   │                       │
│  │ - stage_key  │◄─────│ - stage_id        │                       │
│  │ - name       │      │ - partner_id      │                       │
│  │ - sort       │      │ - design_approved │                       │
│  └──────────────┘      └───────────────────┘                       │
│                                                                      │
└─────────────────────────────────────────────────────────────────────┘
```

## Database Schema

### projects_gates

| Column | Type | Description |
|--------|------|-------------|
| id | bigint | Primary key |
| stage_id | bigint | FK to projects_project_stages |
| name | varchar(100) | Display name |
| gate_key | varchar(50) | Unique identifier |
| description | text | Description |
| sequence | int | Order within stage |
| is_blocking | boolean | Blocks stage advancement |
| is_active | boolean | Whether gate is evaluated |
| applies_design_lock | boolean | Locks design changes |
| applies_procurement_lock | boolean | Locks procurement changes |
| applies_production_lock | boolean | Locks production changes |
| creates_tasks_on_pass | boolean | Creates tasks when passed |
| task_templates_json | json | Task templates to create |

### projects_gate_requirements

| Column | Type | Description |
|--------|------|-------------|
| id | bigint | Primary key |
| gate_id | bigint | FK to projects_gates |
| requirement_type | enum | Type of check (see below) |
| target_model | varchar(100) | Model to check (Project, SalesOrder, etc.) |
| target_relation | varchar(100) | Relation name to check |
| target_field | varchar(100) | Field name to check |
| target_value | text | Expected value (JSON for complex) |
| comparison_operator | varchar(20) | Operator (=, !=, >=, etc.) |
| custom_check_class | varchar(255) | Class for custom checks |
| custom_check_method | varchar(100) | Method for custom checks |
| error_message | varchar(255) | User-facing error |
| help_text | varchar(500) | Guidance text |
| action_label | varchar(100) | Button label |
| action_route | varchar(255) | Route to resolve |
| sequence | int | Order within gate |
| is_active | boolean | Whether requirement is checked |

### projects_gate_evaluations

| Column | Type | Description |
|--------|------|-------------|
| id | bigint | Primary key |
| project_id | bigint | FK to projects_projects |
| gate_id | bigint | FK to projects_gates |
| passed | boolean | Whether gate passed |
| evaluated_at | timestamp | When checked |
| evaluated_by | bigint | FK to users |
| requirement_results | json | Per-requirement results |
| failure_reasons | json | Failed requirement details |
| context | json | Project snapshot at evaluation |
| evaluation_type | enum | manual, automatic, scheduled |

## Requirement Types

| Type | Description | Configuration |
|------|-------------|---------------|
| `field_not_null` | Check if field has value | target_field |
| `field_equals` | Check if field equals value | target_field, target_value |
| `field_greater_than` | Check if field > value | target_field, target_value |
| `relation_exists` | Check if relation has records | target_relation |
| `relation_count` | Check relation count | target_relation, target_value, comparison_operator |
| `all_children_pass` | All related items pass condition | target_relation, target_field, target_value |
| `document_uploaded` | Check media collection | target_value (collection name) |
| `payment_received` | Check payment milestone | target_value (deposit/final) |
| `task_completed` | Check task type completion | target_value (task_type) |
| `custom_check` | Custom PHP class/method | custom_check_class, custom_check_method |

## Service Classes

### GateEvaluator

Main service for evaluating gates.

```php
use Webkul\Project\Services\Gates\GateEvaluator;

$evaluator = app(GateEvaluator::class);

// Evaluate a single gate
$result = $evaluator->evaluate($project, $gate);

// Evaluate all gates for current stage
$results = $evaluator->evaluateCurrentStageGates($project);

// Check if project can advance
$canAdvance = $evaluator->canAdvance($project);

// Get all blockers
$blockers = $evaluator->getBlockers($project);

// Get gate status summary
$status = $evaluator->getGateStatus($project);
```

### GateRequirementChecker

Checks individual requirements.

```php
use Webkul\Project\Services\Gates\GateRequirementChecker;

$checker = app(GateRequirementChecker::class);
$result = $checker->check($project, $requirement);
```

### GateEvaluationResult

Result object returned from evaluations.

```php
$result->passed;              // bool
$result->gate;                // Gate model
$result->evaluation;          // GateEvaluation model
$result->requirementResults;  // array of per-requirement results
$result->failureReasons;      // array of failure details

// Helper methods
$result->getFailedCount();
$result->getPassedCount();
$result->getProgressPercentage();
$result->getBlockerMessages();
```

## Model Relationships

### Gate Model

```php
$gate->stage;           // BelongsTo ProjectStage
$gate->requirements;    // HasMany GateRequirement (active only)
$gate->allRequirements; // HasMany GateRequirement (including inactive)
$gate->evaluations;     // HasMany GateEvaluation

// Scopes
Gate::active();
Gate::blocking();
Gate::ordered();
Gate::forStage($stageId);
Gate::forStageKey('design');

// Methods
$gate->appliesAnyLock();  // bool
$gate->getLockTypes();    // ['design', 'procurement', 'production']
Gate::findByKey('design_lock');
```

### GateRequirement Model

```php
$requirement->gate;  // BelongsTo Gate

// Scopes
GateRequirement::active();
GateRequirement::ordered();

// Methods
$requirement->isCustomCheck();
$requirement->getDecodedTargetValue();
$requirement->hasAction();
$requirement->getCustomCheckIdentifier();
```

### GateEvaluation Model

```php
$evaluation->project;   // BelongsTo Project
$evaluation->gate;      // BelongsTo Gate
$evaluation->evaluator; // BelongsTo User
$evaluation->transition;// HasOne StageTransition

// Scopes
GateEvaluation::passed();
GateEvaluation::failed();
GateEvaluation::forProject($projectId);
GateEvaluation::forGate($gateId);
GateEvaluation::recent();

// Methods
$evaluation->getFailedCount();
$evaluation->getPassedCount();
$evaluation->getTotalRequirementCount();
$evaluation->getRequirementResult($requirementId);
$evaluation->ledToTransition();

// Static factory
GateEvaluation::record($project, $gate, $passed, $results, $failures, $context, $type);
```

## File Locations

```
plugins/webkul/projects/
├── src/
│   ├── Models/
│   │   ├── Gate.php
│   │   ├── GateRequirement.php
│   │   └── GateEvaluation.php
│   └── Services/Gates/
│       ├── GateEvaluator.php
│       ├── GateRequirementChecker.php
│       ├── GateEvaluationResult.php
│       └── RequirementCheckResult.php
├── database/
│   └── factories/
│       ├── GateFactory.php
│       ├── GateRequirementFactory.php
│       └── GateEvaluationFactory.php

database/migrations/
├── 2026_01_14_000001_create_projects_gates_table.php
├── 2026_01_14_000002_create_projects_gate_requirements_table.php
└── 2026_01_14_000003_create_projects_gate_evaluations_table.php

tests/
├── Unit/Models/Gates/
│   ├── GateTest.php
│   ├── GateRequirementTest.php
│   └── GateEvaluationTest.php
├── Unit/Services/Gates/
│   ├── GateEvaluatorTest.php
│   ├── GateRequirementCheckerTest.php
│   └── GateEvaluationResultTest.php
└── Feature/Gates/
    ├── GateApiTest.php
    └── GateWorkflowTest.php
```
