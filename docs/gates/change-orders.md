# Change Orders and Gate Locks

## Overview

Change Orders are the **only authorized mechanism** to modify data after gates have passed and locks are applied. They provide:

1. **Controlled modifications** to locked data
2. **Approval workflow** for oversight
3. **Audit trail** for compliance
4. **Automatic re-locking** after changes

## How Gates and Locks Relate

```
┌─────────────────────────────────────────────────────────────────────────────┐
│                           GATE PASSES                                        │
│                              ↓                                               │
│                    ┌─────────────────────┐                                   │
│                    │   LOCKS APPLIED     │                                   │
│                    │  - Design Lock      │                                   │
│                    │  - Procurement Lock │                                   │
│                    │  - Production Lock  │                                   │
│                    └─────────────────────┘                                   │
│                              ↓                                               │
│              ┌───────────────────────────────┐                               │
│              │    DATA IS NOW PROTECTED      │                               │
│              │  - Cabinet dimensions locked  │                               │
│              │  - BOM quantities locked      │                               │
│              │  - Schedule dates locked      │                               │
│              └───────────────────────────────┘                               │
│                              ↓                                               │
│                    Need to make changes?                                     │
│                              ↓                                               │
│              ┌───────────────────────────────┐                               │
│              │     CREATE CHANGE ORDER       │                               │
│              │  - Specifies what to change   │                               │
│              │  - Specifies which gate/lock  │                               │
│              │  - Requires approval          │                               │
│              └───────────────────────────────┘                               │
│                              ↓                                               │
│              ┌───────────────────────────────┐                               │
│              │    CHANGE ORDER APPROVED      │                               │
│              │    Locks temporarily lifted   │                               │
│              └───────────────────────────────┘                               │
│                              ↓                                               │
│              ┌───────────────────────────────┐                               │
│              │    CHANGES APPLIED            │                               │
│              │    Locks automatically re-    │                               │
│              │    applied after completion   │                               │
│              └───────────────────────────────┘                               │
└─────────────────────────────────────────────────────────────────────────────┘
```

## Change Order Statuses

| Status | Description |
|--------|-------------|
| `draft` | Being created, not yet submitted |
| `pending_approval` | Awaiting approval |
| `approved` | Approved, ready to apply |
| `rejected` | Denied, no changes allowed |
| `applied` | Changes completed, locks re-applied |
| `cancelled` | Abandoned change order |

## Change Order Reasons

When creating a change order, specify the reason:

| Reason | When to Use |
|--------|-------------|
| `client_request` | Customer requested a modification |
| `field_condition` | Site conditions require changes |
| `design_error` | Correcting a design mistake |
| `material_availability` | Material substitution needed |
| `cost_adjustment` | Price or budget changes |
| `other` | Other documented reason |

## Change Order Line Types

Each change order contains lines specifying what to change:

| Type | Description |
|------|-------------|
| `add` | Add new items |
| `modify` | Change existing items |
| `remove` | Remove items |

## Workflow

### 1. Create Change Order

```php
use Webkul\Project\Services\ChangeOrders\ChangeOrderService;

$service = app(ChangeOrderService::class);

$changeOrder = $service->create($project, [
    'title' => 'Update cabinet dimensions',
    'reason' => 'client_request',
    'reason_detail' => 'Customer wants larger island cabinet',
    'unlocks_gate' => 'design_lock',  // Which gate's locks to release
    'requested_by' => auth()->id(),
]);
```

### 2. Add Change Order Lines

```php
$changeOrder->lines()->create([
    'change_type' => 'modify',
    'entity_type' => 'cabinet',
    'entity_id' => $cabinet->id,
    'field_name' => 'width',
    'old_value' => '36',
    'new_value' => '42',
    'description' => 'Increase island width from 36" to 42"',
]);
```

### 3. Submit for Approval

```php
$service->submitForApproval($changeOrder);
// Status changes: draft → pending_approval
// Triggers: ChangeOrderSubmittedForApproval event
```

### 4. Approve or Reject

```php
// Approve
$service->approve($changeOrder, 'Approved per customer request');
// Status: pending_approval → approved
// Triggers: ChangeOrderApproved event

// OR Reject
$service->reject($changeOrder, 'Budget does not allow this change');
// Status: pending_approval → rejected
// Triggers: ChangeOrderRejected event
```

### 5. Apply Changes

```php
$service->apply($changeOrder);
// This:
// 1. Unlocks the specified gate's locks
// 2. Applies each line item change
// 3. Re-locks the entities
// 4. Updates status to 'applied'
// Triggers: ChangeOrderApplied event
```

### 6. Cancel (if needed)

```php
$service->cancel($changeOrder, 'Customer changed their mind');
// Status → cancelled
// Triggers: ChangeOrderCancelled event
```

## Lock Management

### How Locks Are Applied by Gates

When a gate with `applies_lock` passes:

```php
// EntityLockService.php
public function applyGateLocks(Project $project, Gate $gate): void
{
    $lockType = $gate->lock_type; // 'design', 'procurement', or 'production'

    // Lock cabinets
    foreach ($project->cabinets as $cabinet) {
        EntityLock::create([
            'project_id' => $project->id,
            'entity_type' => 'cabinet',
            'entity_id' => $cabinet->id,
            'lock_type' => $lockType,
            'locked_by_gate' => $gate->gate_key,
            'locked_at' => now(),
            'locked_by' => auth()->id(),
        ]);
    }
}
```

### How Change Orders Unlock

```php
// EntityLockService.php
public function unlockForChangeOrder(ChangeOrder $changeOrder): int
{
    $project = $changeOrder->project;
    $unlockGate = $changeOrder->unlocks_gate;

    $locks = EntityLock::active()
        ->forProject($project->id)
        ->where('locked_by_gate', $unlockGate)
        ->get();

    foreach ($locks as $lock) {
        $lock->update([
            'unlock_change_order_id' => $changeOrder->id,
            'unlocked_at' => now(),
            'unlocked_by' => auth()->id(),
        ]);
    }

    return $locks->count();
}
```

### How Locks Are Re-Applied

```php
// EntityLockService.php
public function relockAfterChangeOrder(ChangeOrder $changeOrder): int
{
    // Re-locks all entities that were unlocked by this change order
    // Creates new lock records with current timestamp
}
```

## Checking If Data Is Locked

```php
use Webkul\Project\Services\Locks\EntityLockService;

$lockService = app(EntityLockService::class);

// Check if entity is locked
if ($lockService->isLocked($cabinet, 'design')) {
    // Cannot modify - need change order
}

// Check if project has any active locks
$activeLocks = $lockService->getActiveLocks($project);

// Check specific lock type
$hasDesignLock = $lockService->hasActiveLock($project, 'design');
```

## Impact Calculation

Change orders can calculate their impact:

```php
$changeOrder->calculateImpact();

// Returns:
[
    'cost_impact' => 1250.00,      // Additional cost
    'schedule_impact_days' => 3,   // Additional days needed
    'affected_cabinets' => 2,      // Number of cabinets affected
]
```

## Database Schema

### change_orders

| Column | Type | Description |
|--------|------|-------------|
| id | bigint | Primary key |
| project_id | bigint | Associated project |
| change_order_number | string | Unique identifier (CO-YYYY-NNNN) |
| title | string | Brief description |
| description | text | Detailed description |
| reason | enum | Reason code |
| reason_detail | text | Detailed reason |
| status | enum | Current status |
| unlocks_gate | string | Gate key to unlock |
| cost_impact | decimal | Financial impact |
| schedule_impact_days | int | Schedule impact |
| requested_by | bigint | User who requested |
| approved_by | bigint | User who approved |
| approved_at | timestamp | Approval timestamp |
| applied_by | bigint | User who applied |
| applied_at | timestamp | Application timestamp |

### change_order_lines

| Column | Type | Description |
|--------|------|-------------|
| id | bigint | Primary key |
| change_order_id | bigint | Parent change order |
| change_type | enum | add/modify/remove |
| entity_type | string | Type of entity |
| entity_id | bigint | Entity being changed |
| field_name | string | Field being changed |
| old_value | text | Previous value |
| new_value | text | New value |
| description | text | Description of change |
| applied_at | timestamp | When applied |

### entity_locks

| Column | Type | Description |
|--------|------|-------------|
| id | bigint | Primary key |
| project_id | bigint | Project |
| entity_type | string | Type locked |
| entity_id | bigint | Entity locked |
| lock_type | enum | design/procurement/production |
| locked_by_gate | string | Gate that applied lock |
| locked_at | timestamp | When locked |
| locked_by | bigint | User who locked |
| unlock_change_order_id | bigint | Change order that unlocked |
| unlocked_at | timestamp | When unlocked |
| unlocked_by | bigint | User who unlocked |

## Events

The change order system dispatches these events:

| Event | When |
|-------|------|
| `ChangeOrderCreated` | New change order created |
| `ChangeOrderSubmittedForApproval` | Submitted for review |
| `ChangeOrderApproved` | Approved by approver |
| `ChangeOrderRejected` | Rejected by approver |
| `ChangeOrderApplied` | Changes applied to project |
| `ChangeOrderCancelled` | Change order cancelled |

## User Interface

### Accessing Change Orders

1. Navigate to project detail page
2. Click "Change Orders" tab
3. View list of all change orders
4. Click "New Change Order" to create

### Creating via Wizard

The change order wizard guides users through:

1. **Step 1:** Basic Information
   - Title, reason, description
   - Which gate to unlock

2. **Step 2:** Line Items
   - Add items to change
   - Specify old/new values
   - Calculate impact

3. **Step 3:** Review & Submit
   - Review all changes
   - Submit for approval

## API Endpoints

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/v1/projects/{id}/change-orders` | List change orders |
| POST | `/api/v1/projects/{id}/change-orders` | Create change order |
| GET | `/api/v1/change-orders/{id}` | Get change order |
| PUT | `/api/v1/change-orders/{id}` | Update change order |
| POST | `/api/v1/change-orders/{id}/submit` | Submit for approval |
| POST | `/api/v1/change-orders/{id}/approve` | Approve |
| POST | `/api/v1/change-orders/{id}/reject` | Reject |
| POST | `/api/v1/change-orders/{id}/apply` | Apply changes |
| POST | `/api/v1/change-orders/{id}/cancel` | Cancel |

## Best Practices

1. **Always document the reason** - Include detailed explanation
2. **Calculate impact first** - Know cost/schedule impact before approving
3. **Review all line items** - Ensure accuracy before applying
4. **Use appropriate gate** - Only unlock the minimum necessary
5. **Monitor for patterns** - Frequent change orders may indicate process issues
