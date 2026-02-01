# Gates User Guide

## Understanding Gate Status

When viewing a project, the gate status shows which requirements have been met and which are blocking advancement.

### Status Indicators

| Icon | Meaning |
|------|---------|
| Pass (green) | All requirements met |
| Fail (red) | One or more requirements not met |
| Warning (yellow) | Non-blocking gate failed |

### Viewing Gate Status

1. Navigate to the project detail page
2. Look for the "Gate Status" section or panel
3. Each gate shows:
   - Gate name
   - Pass/Fail status
   - Progress (e.g., "3/4 requirements passed")
   - List of blockers (if any)

## Resolving Blockers

When a gate fails, the blockers section shows:

1. **Error Message** - What's missing
2. **Help Text** - Guidance on how to fix it
3. **Action Button** - Quick link to resolve (if configured)

### Common Blockers and Solutions

#### Discovery Stage

| Blocker | Solution |
|---------|----------|
| "No client assigned to project" | Go to project settings, assign a Partner |
| "No sales order linked to project" | Create a sales order from the project |
| "Deposit payment not received" | Record deposit payment in sales order |
| "No rooms/specifications defined" | Add at least one room to the project |

#### Design Stage

| Blocker | Solution |
|---------|----------|
| "Not all cabinets have dimensions" | Enter dimensions for all cabinets |
| "BOM not generated" | Run BOM calculation for the project |
| "Design not approved by customer" | Get customer approval, mark design_approved_at |
| "Final redline changes not confirmed" | Confirm redlines, mark redline_approved_at |

#### Production Stage

| Blocker | Solution |
|---------|----------|
| "Not all production tasks completed" | Complete all production task types |
| "Not all cabinets have passed QC" | Run QC on each cabinet, mark qc_passed |
| "Blocking defects remain open" | Resolve or close all blocking defects |

## Advancing Stages

### Automatic Advancement

When all blocking gates pass, the project can be advanced to the next stage.

### Manual Override (Admin Only)

In some cases, administrators may override gate requirements:
1. Document the reason for override
2. Use the "Force Advance" option (if available)
3. Override is logged in the evaluation history

## Understanding Locks

When certain gates pass, they apply **locks** that prevent changes:

| Lock Type | What It Protects |
|-----------|-----------------|
| Design Lock | Cabinet dimensions, configurations |
| Procurement Lock | BOM quantities, material selections |
| Production Lock | Production schedules, task assignments |

### Why Locks Exist

Locks prevent costly mistakes:
- Changing dimensions after materials are ordered
- Modifying BOM after production starts
- Rescheduling after deliveries are confirmed

## User Stories

### As a Project Manager

**Goal:** I want to quickly see what's blocking a project from advancing.

1. Open the project
2. Check the Gate Status panel
3. Review any failed requirements
4. Take action to resolve blockers

### As a Sales Coordinator

**Goal:** I want to ensure all discovery requirements are met before handoff to design.

1. Open the project in Discovery stage
2. Verify:
   - Client is assigned
   - Sales order is created
   - Deposit is received
   - Rooms are defined
3. If all pass, advance to Design stage

### As a Production Manager

**Goal:** I want to verify QC is complete before scheduling delivery.

1. Open the project in QC stage
2. Check "QC Passed" gate status
3. Review any cabinets that haven't passed QC
4. Run QC on remaining cabinets
5. Once all pass, advance to Delivery stage

### As an Administrator

**Goal:** I want to see why a project was blocked historically.

1. Open the project
2. Go to Gate Evaluations history
3. Filter by date range or gate
4. Review past evaluations showing:
   - What was checked
   - What passed/failed
   - Who triggered the check
   - Snapshot of project state at that time
