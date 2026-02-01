# Gates API Reference

## Endpoints

### Get Project Gate Status

Returns the current gate status for a project.

```
GET /api/v1/projects/{project_id}/gate-status
```

**Authentication:** Required (Sanctum)

**Response:**

```json
{
  "data": [
    {
      "gate_key": "discovery_complete",
      "name": "Discovery Complete",
      "passed": false,
      "is_blocking": true,
      "requirements_total": 4,
      "requirements_passed": 2,
      "blockers": [
        {
          "requirement_id": 3,
          "error_message": "Deposit payment not received",
          "help_text": "Record deposit payment in sales order",
          "action_label": "Record Payment",
          "action_route": "sales.payments.create",
          "details": "Payment 'deposit' not received"
        },
        {
          "requirement_id": 4,
          "error_message": "No rooms/specifications defined",
          "help_text": "Add at least one room to the project",
          "action_label": "Add Room",
          "action_route": "projects.rooms.create",
          "details": "Relation 'rooms' is empty"
        }
      ]
    }
  ]
}
```

**Status Codes:**

| Code | Description |
|------|-------------|
| 200 | Success |
| 401 | Unauthorized |
| 404 | Project not found |

### Check Can Advance

Check if a project can advance to the next stage.

```
GET /api/v1/projects/{project_id}/can-advance
```

**Response:**

```json
{
  "can_advance": false,
  "blocking_gates": ["discovery_complete"],
  "blockers": {
    "discovery_complete": {
      "gate": {
        "id": 1,
        "name": "Discovery Complete",
        "gate_key": "discovery_complete"
      },
      "blockers": [
        {
          "requirement_id": 3,
          "error_message": "Deposit payment not received"
        }
      ]
    }
  }
}
```

### Get Gate Evaluations

Get evaluation history for a project.

```
GET /api/v1/projects/{project_id}/gate-evaluations
```

**Query Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| gate_id | int | Filter by gate |
| passed | bool | Filter by result |
| from | date | Start date |
| to | date | End date |
| limit | int | Max results (default: 50) |

**Response:**

```json
{
  "data": [
    {
      "id": 123,
      "gate_key": "discovery_complete",
      "passed": true,
      "evaluated_at": "2024-01-15T10:30:00Z",
      "evaluated_by": {
        "id": 5,
        "name": "John Smith"
      },
      "evaluation_type": "manual",
      "requirements_passed": 4,
      "requirements_failed": 0
    }
  ],
  "meta": {
    "total": 45,
    "per_page": 50
  }
}
```

### Trigger Gate Evaluation

Manually trigger a gate evaluation.

```
POST /api/v1/projects/{project_id}/gates/{gate_key}/evaluate
```

**Response:**

```json
{
  "passed": true,
  "evaluation_id": 124,
  "requirements": [
    {
      "requirement_id": 1,
      "passed": true,
      "message": "Field 'partner_id' has value"
    },
    {
      "requirement_id": 2,
      "passed": true,
      "message": "Relation 'orders' has records"
    }
  ]
}
```

## Service Usage

### Basic Usage

```php
use Webkul\Project\Services\Gates\GateEvaluator;

// Inject via dependency injection
public function __construct(
    protected GateEvaluator $evaluator
) {}

// Or resolve from container
$evaluator = app(GateEvaluator::class);
```

### Evaluate Single Gate

```php
use Webkul\Project\Models\Gate;
use Webkul\Project\Models\Project;

$project = Project::find($id);
$gate = Gate::findByKey('discovery_complete');

$result = $evaluator->evaluate($project, $gate);

if ($result->passed) {
    // Gate passed
} else {
    // Get blockers
    foreach ($result->failureReasons as $failure) {
        echo $failure['error_message'];
    }
}
```

### Evaluate All Current Stage Gates

```php
$results = $evaluator->evaluateCurrentStageGates($project);

foreach ($results as $gateKey => $result) {
    echo "{$gateKey}: " . ($result->passed ? 'PASS' : 'FAIL');
}
```

### Check If Can Advance

```php
if ($evaluator->canAdvance($project)) {
    // Safe to advance to next stage
    $project->advanceToNextStage();
} else {
    // Get blockers
    $blockers = $evaluator->getBlockers($project);
}
```

### Get Gate Status Summary

```php
$status = $evaluator->getGateStatus($project);

foreach ($status as $gateStatus) {
    echo "{$gateStatus['name']}: {$gateStatus['requirements_passed']}/{$gateStatus['requirements_total']}";

    if (!$gateStatus['passed']) {
        foreach ($gateStatus['blockers'] as $blocker) {
            echo "  - {$blocker['error_message']}";
        }
    }
}
```

### Using Evaluation Type

```php
use Webkul\Project\Models\GateEvaluation;

// Manual evaluation (default)
$result = $evaluator->evaluate($project, $gate);

// Automatic evaluation (system-triggered)
$result = $evaluator->evaluate($project, $gate, GateEvaluation::TYPE_AUTOMATIC);

// Scheduled evaluation
$result = $evaluator->evaluate($project, $gate, GateEvaluation::TYPE_SCHEDULED);
```

### Accessing Evaluation Details

```php
$result = $evaluator->evaluate($project, $gate);

// Access the evaluation record
$evaluation = $result->evaluation;

// Get context snapshot
$context = $evaluation->context;
echo "Rooms at evaluation: {$context['room_count']}";
echo "Evaluated at: {$context['snapshot_at']}";

// Get per-requirement results
foreach ($result->requirementResults as $reqId => $reqResult) {
    echo "Requirement {$reqId}: " . ($reqResult['passed'] ? 'PASS' : 'FAIL');
    echo " - {$reqResult['message']}";
}
```

## Events

The gate system emits events that can be listened to:

```php
// Event when gate is evaluated
Webkul\Project\Events\GateEvaluated::class

// Event when project advances stage (all gates passed)
Webkul\Project\Events\ProjectAdvanced::class

// Event when lock is applied
Webkul\Project\Events\ProjectLockApplied::class
```

### Listening to Events

```php
// In EventServiceProvider
protected $listen = [
    GateEvaluated::class => [
        NotifyOnGateFailure::class,
        UpdateProjectStatus::class,
    ],
];
```

## MCP Integration

The gates system is available through the TCS ERP MCP server:

```bash
# Get gate status
mcp-cli call tcs-erp/get_project_gate_status '{"project_id": 123}'

# Response
{
  "gates": [...],
  "can_advance": false,
  "blockers": [...]
}
```
