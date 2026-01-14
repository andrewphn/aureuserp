# Stage- & Gate-Driven Project Lifecycle Engine

## Overview

The Stage & Gate Lifecycle Engine transforms project management from status-driven to **behavior-driven** workflows. Instead of simple status labels, the system enforces explicit checkpoints (gates) that control:

- **Data editing permissions** (design lock, procurement lock, production lock)
- **Task creation and unblocking**
- **Purchasing and production workflows**
- **Quality control checkpoints**
- **Change order enforcement**

This epic converts "status" into explicit system behavior with full audit trails.

---

## Architecture

```
┌─────────────────────────────────────────────────────────────────────────────┐
│                           PROJECT LIFECYCLE                                  │
├─────────────────────────────────────────────────────────────────────────────┤
│                                                                              │
│   ┌──────────┐    ┌──────────┐    ┌──────────┐    ┌──────────┐    ┌──────┐ │
│   │ DISCOVERY│───▶│  DESIGN  │───▶│ SOURCING │───▶│PRODUCTION│───▶│CLOSE │ │
│   └────┬─────┘    └────┬─────┘    └────┬─────┘    └────┬─────┘    └──┬───┘ │
│        │               │               │               │              │      │
│        ▼               ▼               ▼               ▼              ▼      │
│   ┌─────────┐    ┌─────────┐    ┌─────────┐    ┌─────────┐    ┌─────────┐  │
│   │  GATE   │    │  GATE   │    │  GATE   │    │  GATE   │    │  GATE   │  │
│   │Discovery│    │ Design  │    │Sourcing │    │Production│   │Delivery │  │
│   │Complete │    │  Lock   │    │Complete │    │Complete │    │Complete │  │
│   └────┬────┘    └────┬────┘    └────┬────┘    └────┬────┘    └────┬────┘  │
│        │               │               │               │              │      │
│        │          ┌────┴────┐          │          ┌───┴────┐         │      │
│        │          │ APPLIES │          │          │ APPLIES│         │      │
│        │          │ DESIGN  │          │          │PROD    │         │      │
│        │          │  LOCK   │          │          │ LOCK   │         │      │
│        │          └─────────┘          │          └────────┘         │      │
│        │                               │                              │      │
│        └───────────────────────────────┴──────────────────────────────┘      │
│                                                                              │
│                      ▼ CHANGE ORDER REQUIRED ▼                               │
│                                                                              │
└─────────────────────────────────────────────────────────────────────────────┘
```

---

## Database Schema

### Core Tables

#### `projects_gates`
Defines checkpoints within project stages.

| Column | Type | Description |
|--------|------|-------------|
| `id` | bigint | Primary key |
| `stage_id` | bigint | FK to project stages |
| `name` | string | Display name |
| `gate_key` | string | Unique identifier (e.g., `design_lock`) |
| `description` | text | Help text |
| `sequence` | int | Order within stage |
| `is_blocking` | bool | Prevents stage advancement if failed |
| `is_active` | bool | Can be disabled without deletion |
| `applies_design_lock` | bool | Locks cabinet specs on pass |
| `applies_procurement_lock` | bool | Locks BOM quantities on pass |
| `applies_production_lock` | bool | Locks dimensions on pass |
| `creates_tasks_on_pass` | bool | Auto-create tasks when passed |
| `task_templates_json` | json | Task templates to create |

#### `projects_gate_requirements`
Individual conditions for gate passage.

| Column | Type | Description |
|--------|------|-------------|
| `id` | bigint | Primary key |
| `gate_id` | bigint | FK to gates |
| `requirement_type` | enum | Type of check (see below) |
| `target_model` | string | Model to check (e.g., `Project`) |
| `target_relation` | string | Relation path (e.g., `rooms.cabinets`) |
| `target_field` | string | Field to check |
| `target_value` | string | Expected value (JSON or simple) |
| `comparison_operator` | string | Operator (`!=`, `=`, `>`, etc.) |
| `custom_check_class` | string | PHP class for custom logic |
| `custom_check_method` | string | Method name (default: `check`) |
| `error_message` | string | User-facing error |
| `help_text` | string | Guidance to resolve |
| `action_label` | string | Button text for quick action |
| `action_route` | string | Route for quick action |

**Requirement Types:**
- `field_not_null` - Field must have a value
- `field_equals` - Field must equal specific value
- `field_greater_than` - Numeric comparison
- `relation_exists` - Related records must exist
- `relation_count_min` - Minimum related record count
- `all_relation_field_set` - All related records have field set
- `custom_check` - Custom PHP class logic

#### `projects_gate_evaluations`
Audit log of all gate checks.

| Column | Type | Description |
|--------|------|-------------|
| `id` | bigint | Primary key |
| `project_id` | bigint | FK to projects |
| `gate_id` | bigint | FK to gates |
| `passed` | bool | Result |
| `evaluated_at` | timestamp | When checked |
| `evaluated_by` | bigint | User who triggered |
| `requirement_results` | json | Per-requirement results |
| `failure_reasons` | json | Failed requirement details |
| `context` | json | Additional context |
| `evaluation_type` | enum | `manual`, `automatic`, `scheduled` |

#### `projects_stage_transitions`
Complete history of stage changes.

| Column | Type | Description |
|--------|------|-------------|
| `id` | bigint | Primary key |
| `project_id` | bigint | FK to projects |
| `from_stage_id` | bigint | Previous stage |
| `to_stage_id` | bigint | New stage |
| `gate_id` | bigint | Gate that allowed transition |
| `transition_type` | enum | `advance`, `rollback`, `force`, `skip` |
| `transitioned_at` | timestamp | When changed |
| `transitioned_by` | bigint | User who changed |
| `reason` | text | Explanation (required for force/rollback) |
| `gate_evaluation_id` | bigint | Link to evaluation record |
| `metadata` | json | Additional data |
| `duration_minutes` | int | Time in previous stage |

#### `projects_entity_locks`
Write protection records.

| Column | Type | Description |
|--------|------|-------------|
| `id` | bigint | Primary key |
| `project_id` | bigint | FK to projects |
| `entity_type` | string | Model class name |
| `entity_id` | bigint | Record ID |
| `lock_level` | enum | `design`, `procurement`, `production` |
| `locked_by_gate` | bigint | Gate that applied lock |
| `locked_at` | timestamp | When locked |
| `locked_by` | bigint | User |
| `unlock_change_order_id` | bigint | CO that unlocked |
| `unlocked_at` | timestamp | When unlocked |
| `unlocked_by` | bigint | User who unlocked |

#### `projects_change_orders`
Change order management.

| Column | Type | Description |
|--------|------|-------------|
| `id` | bigint | Primary key |
| `project_id` | bigint | FK to projects |
| `change_order_number` | string | Auto-generated (CO-001) |
| `title` | string | Brief description |
| `description` | text | Detailed explanation |
| `reason` | enum | See reasons below |
| `status` | enum | `draft`, `pending_approval`, `approved`, `rejected`, `applied`, `cancelled` |
| `requested_at` | timestamp | When submitted |
| `requested_by` | bigint | Requester |
| `approved_at` | timestamp | Approval time |
| `approved_by` | bigint | Approver |
| `rejection_reason` | text | Why rejected |
| `applied_at` | timestamp | When changes applied |
| `applied_by` | bigint | Who applied |
| `price_delta` | decimal | Cost impact |
| `labor_hours_delta` | decimal | Labor impact |
| `bom_delta_json` | json | Material changes |
| `affected_stage` | string | Stage being modified |
| `unlocks_gate` | bigint | Gate to unlock |
| `sales_order_id` | bigint | Associated SO for billing |

**Change Order Reasons:**
- `client_request` - Customer requested change
- `field_condition` - Site conditions require change
- `design_error` - Correcting design mistake
- `material_substitution` - Material unavailability
- `scope_addition` - Adding work
- `scope_removal` - Removing work
- `other` - Other reason

#### `projects_change_order_lines`
Individual field changes within a change order.

| Column | Type | Description |
|--------|------|-------------|
| `id` | bigint | Primary key |
| `change_order_id` | bigint | FK to change orders |
| `entity_type` | string | Model class |
| `entity_id` | bigint | Record ID |
| `field_name` | string | Field being changed |
| `old_value` | text | Previous value |
| `new_value` | text | New value |
| `price_impact` | decimal | Cost of this change |
| `bom_impact_json` | json | Material impact |
| `is_applied` | bool | Has been applied |
| `applied_at` | timestamp | When applied |

---

## Service Layer

### GateEvaluator

The core service for evaluating project gates.

```php
use Webkul\Project\Services\Gates\GateEvaluator;

$evaluator = app(GateEvaluator::class);

// Evaluate a specific gate
$result = $evaluator->evaluate($project, $gate);
if ($result->passed) {
    // Gate passed, can advance
}

// Check all gates for current stage
$results = $evaluator->evaluateCurrentStageGates($project);

// Check if project can advance
if ($evaluator->canAdvance($project)) {
    // All blocking gates passed
}

// Get blockers preventing advancement
$blockers = $evaluator->getBlockers($project);
// Returns: ['gate_key' => ['name' => ..., 'blockers' => [...]]]

// Get comprehensive status
$status = $evaluator->getGateStatus($project);
```

### EntityLockService

Manages entity write protection.

```php
use Webkul\Project\Services\Locks\EntityLockService;

$lockService = app(EntityLockService::class);

// Apply locks from a gate
$lockService->applyGateLocks($project, $gate);

// Check if entity is locked
if ($lockService->isLocked($cabinet)) {
    // Cannot edit directly
}

// Check specific field
if ($lockService->isFieldLocked($cabinet, 'width_inches')) {
    // This field is locked
}

// Get lock info
$info = $lockService->getLockInfo($cabinet);
// Returns: ['is_locked' => true, 'lock_level' => 'design', 'locked_at' => ...]

// Unlock for change order
$lockService->unlockForChangeOrder($changeOrder);

// Re-lock after changes applied
$lockService->relockAfterChangeOrder($changeOrder);
```

### ChangeOrderService

Orchestrates change order lifecycle.

```php
use Webkul\Project\Services\ChangeOrders\ChangeOrderService;

$coService = app(ChangeOrderService::class);

// Create a change order
$changeOrder = $coService->create($project, [
    'title' => 'Increase cabinet width',
    'reason' => 'client_request',
    'description' => 'Client needs wider cabinet for appliance',
]);

// Add a change line
$coService->addLine($changeOrder, [
    'entity_type' => 'Cabinet',
    'entity_id' => 123,
    'field_name' => 'width_inches',
    'new_value' => '36',
]);

// Submit for approval
$coService->submitForApproval($changeOrder);

// Approve (unlocks affected entities)
$coService->approve($changeOrder);

// Apply changes (modifies entities, re-locks)
$coService->apply($changeOrder);

// Or reject
$coService->reject($changeOrder, 'Budget constraints');
```

### BoardFootCalculator

Lumber and sheet goods calculations.

```php
use Webkul\Project\Services\Calculators\BoardFootCalculator;

$calc = app(BoardFootCalculator::class);

// Calculate board feet from dimensions
$bf = $calc->calculateBoardFeet(
    thickness: 0.75,  // inches
    width: 6,         // inches
    length: 96        // inches
);

// Convert linear feet to board feet
$bf = $calc->linearFeetToBoardFeet(
    linearFeet: 10,
    widthInches: 1.5,
    thicknessInches: 0.75
);

// Calculate face frame requirements
$faceFrame = $calc->calculateFaceFrameLinearFeet(
    openingWidthInches: 36,
    openingHeightInches: 30,
    divisions: 1
);

// Calculate sheet goods
$sheets = $calc->calculateSheetGoodsSquareFeet([
    ['width_inches' => 30, 'height_inches' => 24, 'quantity' => 2],
    ['width_inches' => 36, 'height_inches' => 24, 'quantity' => 1],
]);
```

---

## Traits

### HasEntityLock

Apply to any model that can be locked:

```php
use Webkul\Project\Traits\HasEntityLock;

class Cabinet extends Model
{
    use HasEntityLock;
    
    // Automatically intercepts saves and throws LockViolationException
    // if attempting to modify locked fields
}

// Usage:
$cabinet->isLocked();                    // Check if locked
$cabinet->isFieldLocked('width_inches'); // Check specific field
$cabinet->requiresChangeOrderToEdit();   // True if locked

// Bypass lock check (use carefully!)
$cabinet->withoutLockCheck(function ($model) {
    $model->update(['notes' => 'Updated via change order']);
});
```

---

## Events

### Gate Events

```php
use Webkul\Project\Events\ProjectGateEvaluated;
use Webkul\Project\Events\ProjectGatePassed;
use Webkul\Project\Events\ProjectGateFailed;

// Listen for gate passage
Event::listen(ProjectGatePassed::class, function ($event) {
    $project = $event->project;
    $gate = $event->gate;
    
    // Create follow-up tasks
    if ($event->createsTasks()) {
        foreach ($event->getTaskTemplates() as $template) {
            // Create tasks...
        }
    }
    
    // Send notifications
    Notification::send($project->team, new GatePassedNotification($gate));
});

// Listen for gate failures
Event::listen(ProjectGateFailed::class, function ($event) {
    $blockers = $event->getBlockerSummary();
    // Notify about blockers...
});
```

### Lock Events

```php
use Webkul\Project\Events\ProjectDesignLocked;
use Webkul\Project\Events\ProjectProcurementLocked;

Event::listen(ProjectDesignLocked::class, function ($event) {
    $project = $event->project;
    $bomSnapshot = $event->bomSnapshot;
    $pricingSnapshot = $event->pricingSnapshot;
    
    // Store snapshots, notify team, etc.
});
```

### Change Order Events

```php
use Webkul\Project\Events\ChangeOrderCreated;
use Webkul\Project\Events\ChangeOrderApproved;
use Webkul\Project\Events\ChangeOrderApplied;

Event::listen(ChangeOrderApproved::class, function ($event) {
    $co = $event->changeOrder;
    $approver = $event->getApprover();
    
    // Notify requester of approval
    // Create billing record if price_delta > 0
});

Event::listen(ChangeOrderApplied::class, function ($event) {
    $co = $event->changeOrder;
    $priceDelta = $event->getPriceDelta();
    
    // Update project totals
    // Regenerate BOM
    // Update production schedule
});
```

---

## UI Components

### StageGatePanel

Livewire component showing gate status:

```blade
<livewire:stage-gate-panel :project="$project" />
```

Features:
- Current stage indicator with color
- Progress bar showing overall completion
- Lock status badges (Design, Procurement, Production)
- Per-gate requirement checklist
- Quick blocker preview
- "Check Gates" refresh button
- "Advance Stage" button (enabled when all gates pass)
- Blockers modal with actionable items

### ChangeOrderWizard

Multi-step wizard for creating change orders:

```blade
<livewire:change-order-wizard :project="$project" />
```

Steps:
1. **Basic Information** - Title, reason, description
2. **Specify Changes** - Add entity/field changes
3. **Review Impact** - Price and BOM delta summary
4. **Submit** - Final confirmation

---

## Admin Configuration

### GateResource

Filament admin panel for configuring gates:

**URL:** `/admin/project/gates`

Features:
- List all gates by stage
- Create/edit gates without code changes
- Configure requirements via repeater
- Toggle active/blocking status
- Set lock behaviors
- Define task templates

---

## Default Gates

The system seeds with 8 default gates:

| Gate | Stage | Requirements | Locks |
|------|-------|--------------|-------|
| Discovery Complete | Discovery | Client assigned, budget set, deposit received, drawings uploaded | None |
| Design Lock | Design | All cabinets dimensioned, BOM generated, customer approved | Design |
| Sourcing Complete | Sourcing | All BOM lines covered by POs | Procurement |
| Receiving Complete | Sourcing | All POs received, materials staged | None |
| Production Complete | Production | All production tasks complete | Production |
| QC Passed | Production | No blocking defects | None |
| Delivery Complete | Delivery | Delivery date set, all items delivered | None |
| Project Closeout | Closeout | Final payment received, all docs complete | None |

---

## Migration Guide

### Running Migrations

```bash
php artisan migrate
```

### Seeding Default Gates

```bash
php artisan db:seed --class=ProjectGatesSeeder
```

### Applying HasEntityLock to Models

Add the trait to any model that should respect locks:

```php
use Webkul\Project\Traits\HasEntityLock;

class YourModel extends Model
{
    use HasEntityLock;
    
    // Define which project this entity belongs to
    public function getProjectForLock(): ?Project
    {
        return $this->cabinet?->cabinetRun?->project;
    }
}
```

---

## Best Practices

### 1. Gate Design

- Keep gates focused on one concern
- Use blocking gates sparingly (only for critical checkpoints)
- Provide helpful error messages and action buttons
- Consider the user journey when ordering requirements

### 2. Lock Strategy

- Design lock: After customer approval, before purchasing
- Procurement lock: After POs issued, before production
- Production lock: During active production

### 3. Change Orders

- Require change orders for any locked field modification
- Track price and BOM impact for billing
- Maintain full audit trail
- Link to sales orders for invoicing

### 4. Custom Checks

- Create dedicated check classes for complex logic
- Keep checks stateless and idempotent
- Return clear, actionable error messages
- Consider caching for expensive checks

---

## Troubleshooting

### Gate Not Passing

1. Run `$evaluator->evaluate($project, $gate)` to get detailed results
2. Check `requirement_results` in the GateEvaluation record
3. Verify custom check classes exist and are correctly namespaced

### Lock Violation Errors

1. Check if entity is locked: `$entity->isLocked()`
2. Identify lock level: `$lockService->getLockInfo($entity)`
3. Create change order to modify locked entities

### Change Order Not Applying

1. Verify status is `approved`
2. Check that all lines have valid entity references
3. Review `ChangeOrderService::apply()` for specific errors

---

## API Reference

See individual service class documentation:

- [`GateEvaluator`](../plugins/webkul/projects/src/Services/Gates/GateEvaluator.php)
- [`EntityLockService`](../plugins/webkul/projects/src/Services/Locks/EntityLockService.php)
- [`ChangeOrderService`](../plugins/webkul/projects/src/Services/ChangeOrders/ChangeOrderService.php)
- [`BoardFootCalculator`](../plugins/webkul/projects/src/Services/Calculators/BoardFootCalculator.php)
