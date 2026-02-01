# Change Order System Overhaul Requirements

## Current State Analysis

The existing Change Order system handles basic functionality:
- Creating change orders with line items
- Approval workflow (draft → pending → approved → applied)
- Entity lock/unlock via `EntityLockService`
- Basic price and BOM delta tracking

### What's Missing

When a change order comes in, **many downstream stop actions need to happen**. The current system only handles data locks, not the broader workflow impacts.

## Stop Actions Required When Change Order Is Submitted

### 1. Production Stop Actions

When a change order affects a cabinet or component that's in production:

| Action | Trigger Condition | Responsible System |
|--------|------------------|-------------------|
| **Halt production tasks** | Change affects entity with active production tasks | Task Service |
| **Mark tasks as blocked** | Production tasks related to changed entities | Task State Machine |
| **Notify shop floor** | Change order submitted for production-stage project | Notification Service |
| **Update CNC queue** | Change affects CNC-scheduled items | CNC Queue Service |
| **Clear cut list cache** | Change affects cabinet dimensions | Cut List Service |

### 2. Procurement Stop Actions

When a change order affects BOM or materials:

| Action | Trigger Condition | Responsible System |
|--------|------------------|-------------------|
| **Hold purchase orders** | Change order affects BOM lines | PO Service |
| **Flag POs for review** | Materials may no longer be needed | PO Service |
| **Notify purchasing** | Change order affects procurement-stage project | Notification Service |
| **Recalculate BOM** | Design changes submitted | BOM Service |
| **Update material reservations** | BOM quantities change | Inventory Service |

### 3. Scheduling Stop Actions

When a change order affects timeline:

| Action | Trigger Condition | Responsible System |
|--------|------------------|-------------------|
| **Block delivery date** | Any change order submitted | Scheduling Service |
| **Update Gantt chart** | Change order impacts estimated hours | Gantt Service |
| **Notify logistics** | Delivery-stage project gets change order | Notification Service |
| **Recalculate milestones** | Major change order | Milestone Service |

### 4. Communication Stop Actions

| Action | Trigger Condition | Responsible System |
|--------|------------------|-------------------|
| **Email project manager** | Any change order submitted | Notification Service |
| **Slack notification** | High-impact change order | Integration Service |
| **Update chatter** | Change order created | Activity Log |
| **Customer communication flag** | Client-facing change | CRM Integration |

## Proposed System Architecture

### Phase 1: Change Order Event System

Create events that trigger stop actions:

```
ChangeOrderSubmitted
├── Listeners
│   ├── HaltRelatedProductionTasks
│   ├── FlagRelatedPurchaseOrders
│   ├── BlockDeliverySchedule
│   ├── NotifyStakeholders
│   └── LogToActivityStream

ChangeOrderApproved
├── Listeners
│   ├── UnlockEntities
│   ├── EnableEditMode
│   └── NotifyAssignees

ChangeOrderApplied
├── Listeners
│   ├── RecalculateBom
│   ├── UnholdPurchaseOrders (if applicable)
│   ├── UnblockTasks (if applicable)
│   ├── RelockEntities
│   ├── UpdateSchedule
│   └── NotifyCompletion
```

### Phase 2: Impact Assessment Service

Before a change order is submitted, assess impact:

```php
interface ChangeOrderImpactService
{
    // Analyze what will be affected
    public function assessImpact(ChangeOrder $changeOrder): ChangeOrderImpact;

    // Get entities that will be blocked/held
    public function getAffectedEntities(ChangeOrder $changeOrder): array;

    // Calculate schedule impact
    public function calculateScheduleImpact(ChangeOrder $changeOrder): int; // days

    // Calculate cost impact
    public function calculateCostImpact(ChangeOrder $changeOrder): float;

    // Get notifications that will be sent
    public function getStakeholders(ChangeOrder $changeOrder): array;
}
```

### Phase 3: Stop Action Registry

Configurable stop actions per project stage:

```php
// config/change-order-stop-actions.php
return [
    'design' => [
        'halt_tasks' => false,          // Design changes don't halt design tasks
        'hold_pos' => false,            // No POs yet
        'block_schedule' => true,       // Delivery date may be affected
        'notify_production' => false,   // Not in production yet
    ],
    'procurement' => [
        'halt_tasks' => false,
        'hold_pos' => true,             // POs may need modification
        'block_schedule' => true,
        'notify_production' => false,
    ],
    'production' => [
        'halt_tasks' => true,           // Must halt related tasks
        'hold_pos' => false,            // Materials already ordered
        'block_schedule' => true,
        'notify_production' => true,    // Shop floor needs to know
    ],
    'qc' => [
        'halt_tasks' => true,
        'hold_pos' => false,
        'block_schedule' => true,
        'notify_production' => true,
    ],
];
```

## Database Changes Required

### 1. Add Change Order Status Fields

```sql
ALTER TABLE projects_change_orders ADD COLUMN (
    -- Stop action tracking
    tasks_halted_count INT DEFAULT 0,
    pos_held_count INT DEFAULT 0,
    schedule_blocked_at TIMESTAMP NULL,

    -- Impact assessment
    estimated_schedule_impact_days INT NULL,
    estimated_cost_impact DECIMAL(12,2) NULL,
    affected_cabinet_ids JSON NULL,
    affected_task_ids JSON NULL,
    affected_po_ids JSON NULL,

    -- Stakeholder tracking
    stakeholders_notified_at TIMESTAMP NULL,
    notifications_sent_json JSON NULL
);
```

### 2. Create Change Order Stop Actions Table

```sql
CREATE TABLE projects_change_order_stop_actions (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    change_order_id BIGINT NOT NULL,
    action_type ENUM('halt_task', 'hold_po', 'block_schedule', 'notify', 'recalculate', 'other') NOT NULL,
    entity_type VARCHAR(50) NULL,
    entity_id BIGINT NULL,
    action_status ENUM('pending', 'executed', 'reverted', 'failed') DEFAULT 'pending',
    executed_at TIMESTAMP NULL,
    reverted_at TIMESTAMP NULL,
    metadata JSON NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (change_order_id) REFERENCES projects_change_orders(id) ON DELETE CASCADE,
    INDEX idx_change_order_status (change_order_id, action_status),
    INDEX idx_entity (entity_type, entity_id)
);
```

## Implementation Phases

### Phase 1: Event Infrastructure (Foundation)

1. Create `ChangeOrderSubmitted` event
2. Create `ChangeOrderApproved` event
3. Create `ChangeOrderApplied` event
4. Create `ChangeOrderCancelled` event
5. Update `ChangeOrderService` to dispatch events

### Phase 2: Core Stop Actions

1. **Task Halting**
   - Create `HaltRelatedProductionTasks` listener
   - Add `halted_by_change_order_id` field to tasks
   - Create UI indicator for halted tasks

2. **PO Holding**
   - Create `FlagRelatedPurchaseOrders` listener
   - Add `held_by_change_order_id` field to purchase orders
   - Create PO hold/unhold workflow

3. **Schedule Blocking**
   - Create `BlockDeliverySchedule` listener
   - Add `schedule_blocked_by_change_order_id` to projects
   - Update Gantt to show change order blocks

### Phase 3: Impact Assessment

1. Create `ChangeOrderImpactService`
2. Build impact preview UI in change order wizard
3. Require impact acknowledgment before submission

### Phase 4: Notification System

1. Define notification templates for each stakeholder type
2. Integrate with email/Slack
3. Create activity log entries
4. Customer-facing change notification system

### Phase 5: Revert Actions

1. When change order is applied/cancelled, revert stop actions
2. Unblock tasks that were halted
3. Unhold POs that were flagged
4. Update schedule to reflect resolution

## User Stories for Overhaul

### US-CO-001: See Impact Before Submitting
**As a** Designer submitting a change order
**I want to** see what will be affected before I submit
**So that** I understand the downstream impact of my changes

### US-CO-002: Production Task Halting
**As a** Production Manager
**I want** production tasks to automatically halt when a change order affects them
**So that** workers don't continue on work that may need to change

### US-CO-003: PO Hold Notification
**As a** Purchasing Manager
**I want to** be notified when a change order puts a PO on hold
**So that** I can coordinate with suppliers

### US-CO-004: Shop Floor Notification
**As a** Shop Foreman
**I want to** receive immediate notification of change orders
**So that** I can stop work before wasting materials

### US-CO-005: Schedule Impact Visibility
**As a** Project Manager
**I want to** see how a change order affects the delivery schedule
**So that** I can communicate with the customer

### US-CO-006: Revert Actions on Cancel
**As a** Administrator
**I want** all stop actions to automatically revert if a change order is cancelled
**So that** work can resume without manual intervention

## Integration with Existing Gates

The overhaul must respect gate-based locks:

1. **Change Order specifies which gate to unlock** (`unlocks_gate` field)
2. **Stop actions are determined by current project stage** (via gate progression)
3. **When applied, re-evaluation of gates may be needed** (design changes may fail gates)

### Gate Re-evaluation After Change Order

After a change order is applied:

```php
// ChangeOrderService::apply()
public function apply(ChangeOrder $changeOrder): ChangeOrder
{
    // ... existing logic ...

    // NEW: Re-evaluate current stage gates
    $evaluator = app(GateEvaluator::class);
    $result = $evaluator->evaluateCurrentStageGates($project);

    if (!$result->passed) {
        // Project may no longer meet gate requirements
        // Log warning, notify PM
        event(new ChangeOrderCausedGateFailure($changeOrder, $result));
    }

    return $changeOrder;
}
```

## Summary

The Change Order system needs to evolve from a simple "unlock/modify/relock" mechanism to a comprehensive **workflow orchestrator** that:

1. **Halts downstream work** when changes are submitted
2. **Assesses impact** before changes are approved
3. **Notifies stakeholders** throughout the process
4. **Reverts stop actions** when changes complete or are cancelled
5. **Re-evaluates gates** after changes are applied

This ensures that when a change order comes in, the entire system responds appropriately to prevent wasted work and maintain data integrity.
