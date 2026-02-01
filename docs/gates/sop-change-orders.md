# SOP: Change Orders

## Purpose

This Standard Operating Procedure defines the process for creating, approving, and applying change orders to projects after gates have passed and locks have been applied.

## Scope

Applies to all TCS Woodwork projects where design, procurement, or production locks are active.

## Definitions

| Term | Definition |
|------|------------|
| **Change Order** | A formal request to modify locked project data |
| **Lock** | Protection applied by a gate that prevents direct edits |
| **Stop Action** | Work that must halt when a change order is submitted |
| **Impact Assessment** | Evaluation of downstream effects before approval |

## When Is a Change Order Required?

A change order is **required** when modifying data after these gates have passed:

| Gate | Lock Applied | Data Protected |
|------|--------------|----------------|
| Design Lock | Design Lock | Cabinet dimensions, configurations, openings |
| Procurement Complete | Procurement Lock | BOM quantities, material specifications |
| Production Complete | Production Lock | Schedule dates, task assignments |

### Change Order NOT Required For

- Projects still in Discovery stage
- Projects in Design stage (before Design Lock gate passes)
- Non-locked fields (notes, internal comments)
- Adding new rooms/cabinets (only if BOM not yet generated)

## Workflow Diagram

```
CLIENT REQUESTS CHANGE
        │
        ▼
┌─────────────────────┐
│  CREATE CHANGE      │
│  ORDER (Draft)      │
│  - Document reason  │
│  - Specify changes  │
│  - Select gate to   │
│    unlock           │
└─────────────────────┘
        │
        ▼
┌─────────────────────┐
│  IMPACT ASSESSMENT  │
│  - Cost delta       │
│  - Schedule impact  │
│  - Affected items   │
│  - Stop actions     │
└─────────────────────┘
        │
        ▼
┌─────────────────────┐
│  SUBMIT FOR         │
│  APPROVAL           │──────────────────┐
│  (Pending Approval) │                  │
└─────────────────────┘                  │
        │                                │
        ▼                                ▼
   ┌─────────┐                    ┌─────────────┐
   │APPROVED │                    │  REJECTED   │
   └─────────┘                    │ (with reason)│
        │                         └─────────────┘
        ▼                                │
┌─────────────────────┐                  │
│  STOP ACTIONS       │                  │
│  EXECUTED           │                  │
│  - Tasks halted     │                  │
│  - POs held         │                  │
│  - Schedule blocked │                  │
│  - Teams notified   │                  │
└─────────────────────┘                  │
        │                                │
        ▼                                │
┌─────────────────────┐                  │
│  MAKE CHANGES       │                  │
│  (Locks released)   │                  │
│  - Update cabinets  │                  │
│  - Modify BOM       │                  │
│  - Adjust schedule  │                  │
└─────────────────────┘                  │
        │                                │
        ▼                                │
┌─────────────────────┐                  │
│  APPLY CHANGE       │                  │
│  ORDER (Applied)    │                  │
│  - Recalculate BOM  │                  │
│  - Relock entities  │                  │
│  - Resume work      │                  │
│  - Re-evaluate gates│                  │
└─────────────────────┘                  │
        │                                │
        └────────────────────────────────┤
                                         │
                                         ▼
                                    ┌─────────┐
                                    │  DONE   │
                                    └─────────┘
```

## Procedures

### 1. Creating a Change Order

**Responsible:** Project Manager, Designer, or Sales Coordinator

**Steps:**

1. Navigate to the project in the ERP
2. Go to **Change Orders** tab
3. Click **New Change Order**
4. Fill in required information:
   - **Title**: Brief description (e.g., "Increase island width")
   - **Reason**: Select from dropdown:
     - Client Request
     - Field Condition
     - Design Error
     - Material Substitution
     - Scope Addition
     - Scope Removal
     - Other
   - **Reason Detail**: Detailed explanation
   - **Unlocks Gate**: Which gate's locks need to be released

5. Add **Line Items** specifying what will change:
   - Entity type (Cabinet, Section, BOM Line, etc.)
   - Field being changed
   - New value
   - Price impact (if known)

6. **Save as Draft** for later completion, or proceed to submit

### 2. Impact Assessment

**Responsible:** Project Manager

**Before submitting, review impact:**

1. Click **Calculate Impact** on the change order
2. Review the impact summary:
   - **Cost Impact**: Additional/reduced cost
   - **Schedule Impact**: Days added/removed
   - **Affected Items**: Cabinets, tasks, POs affected
   - **Stop Actions**: What work will be halted

3. If impact is acceptable, proceed to submission
4. If impact is too high, discuss with stakeholders before submitting

### 3. Submitting for Approval

**Responsible:** Project Manager or Designer

**Steps:**

1. Open the change order (in Draft status)
2. Review all line items for accuracy
3. Click **Submit for Approval**
4. Change order status changes to **Pending Approval**
5. Approver is notified via email/notification

### 4. Approving or Rejecting

**Responsible:** Operations Manager or designated approver

**To Approve:**

1. Review the change order details
2. Review the impact assessment
3. Click **Approve**
4. Optionally add approval notes
5. System automatically:
   - Releases locks on affected entities
   - Executes stop actions
   - Notifies relevant teams

**To Reject:**

1. Review the change order
2. Click **Reject**
3. **Required**: Enter rejection reason
4. Change order status becomes **Rejected**
5. Requester is notified with reason

### 5. Making Changes

**Responsible:** Designer (design changes) or appropriate role

**After approval, locks are released:**

1. Edit the affected entities:
   - Cabinets
   - Sections
   - Doors/Drawers
   - BOM lines (if procurement lock was released)

2. Verify all intended changes are made
3. Do NOT make changes beyond what's in the change order

### 6. Applying the Change Order

**Responsible:** Project Manager or Designer

**Once changes are complete:**

1. Open the change order (in Approved status)
2. Review all line items
3. Verify actual changes match documented changes
4. Click **Apply Changes**
5. System automatically:
   - Re-applies locks
   - Recalculates BOM (if design changed)
   - Resumes halted tasks
   - Unholds flagged POs
   - Re-evaluates current stage gates

### 7. Cancelling a Change Order

**Responsible:** Project Manager or Administrator

**If change order is no longer needed:**

1. Open the change order (Draft or Pending status)
2. Click **Cancel**
3. Enter cancellation reason
4. If already approved (locks released):
   - System re-applies locks
   - Reverts all stop actions
   - Resumes normal workflow

## Stop Actions by Stage

When a change order is submitted, different stop actions occur based on project stage:

### Design Stage
- [ ] Schedule delivery date blocked (pending)
- [ ] Notify project manager

### Procurement Stage
- [ ] Schedule delivery date blocked
- [ ] Hold related purchase orders
- [ ] Notify purchasing manager
- [ ] Notify project manager

### Production Stage
- [ ] Halt related production tasks
- [ ] Remove from CNC queue (if applicable)
- [ ] Schedule delivery date blocked
- [ ] Notify shop floor supervisor
- [ ] Notify production manager
- [ ] Notify project manager

### QC/Delivery Stage
- [ ] Halt QC tasks
- [ ] Block delivery schedule
- [ ] Notify logistics
- [ ] Notify project manager
- [ ] Notify customer (if requested)

## Common Scenarios

### Scenario 1: Customer Wants Larger Cabinet

**Situation:** Customer requests island cabinet width increased from 36" to 42" after design approval.

**Procedure:**
1. Create change order with reason "Client Request"
2. Add line item: Cabinet entity, width field, 36 → 42
3. Calculate impact (likely affects BOM, may affect schedule)
4. Submit for approval
5. Once approved, modify cabinet in system
6. Regenerate BOM
7. Apply change order

### Scenario 2: Material Unavailable

**Situation:** Specified plywood is backordered, need to substitute.

**Procedure:**
1. Create change order with reason "Material Substitution"
2. Unlock procurement lock
3. Add line items for BOM changes
4. Document alternative material specs
5. Submit for approval
6. Once approved, update BOM
7. Create new PO for substitute material
8. Apply change order

### Scenario 3: Design Error Discovered in Production

**Situation:** Production team notices drawer box height won't fit in opening.

**Procedure:**
1. **Immediately:** Notify PM and halt work on that cabinet
2. Create change order with reason "Design Error"
3. Mark as high priority
4. Unlock design lock
5. Submit for expedited approval
6. Once approved, correct dimensions
7. Apply change order
8. Resume production

## Audit Requirements

All change order activities are logged:

| Event | Logged Data |
|-------|-------------|
| Created | User, timestamp, initial values |
| Submitted | User, timestamp |
| Approved | User, timestamp, notes |
| Rejected | User, timestamp, reason |
| Applied | User, timestamp, final values |
| Cancelled | User, timestamp, reason |

**Retention:** Change order records retained for 7 years.

## Emergency Override

In rare cases where normal approval process is too slow:

1. Contact Operations Director
2. Document emergency justification
3. Use **Emergency Override** (Admin only)
4. Override is logged with justification
5. Create follow-up review task
6. Post-incident review required

## Reports

### Change Order History
- All change orders for a project
- Status, dates, impact summary

### Impact Summary
- Total cost impact across all COs
- Total schedule impact
- Trend analysis

### Stop Action Log
- All stop actions executed
- Revert status
- Duration of work stoppage

## Version History

| Version | Date | Author | Changes |
|---------|------|--------|---------|
| 1.0 | 2024-01-15 | System | Initial version |
