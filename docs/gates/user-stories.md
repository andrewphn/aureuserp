# Gates System User Stories

## Project Manager Stories

### US-PM-001: View Gate Status
**As a** Project Manager
**I want to** see the current gate status for my project
**So that** I know what's blocking advancement and can take action

**Acceptance Criteria:**
- [ ] Gate status is visible on project detail page
- [ ] Each gate shows pass/fail status
- [ ] Failed gates show specific blockers
- [ ] Each blocker shows error message and help text
- [ ] Action buttons link to resolution screens

**Priority:** High

---

### US-PM-002: Resolve Blockers
**As a** Project Manager
**I want to** quickly resolve gate blockers
**So that** I can advance the project without delays

**Acceptance Criteria:**
- [ ] Each blocker has an action button (if applicable)
- [ ] Action button navigates to the correct screen
- [ ] After resolving, gate status refreshes automatically
- [ ] Resolved blockers disappear from the list

**Priority:** High

---

### US-PM-003: Advance Project Stage
**As a** Project Manager
**I want to** advance a project to the next stage
**So that** work can proceed according to the workflow

**Acceptance Criteria:**
- [ ] "Advance Stage" button visible when all blocking gates pass
- [ ] Button is disabled when blockers exist
- [ ] Clicking advance updates project stage
- [ ] Locks are applied based on gate configuration
- [ ] Stage transition is recorded in history

**Priority:** High

---

### US-PM-004: View Gate History
**As a** Project Manager
**I want to** see the history of gate evaluations
**So that** I can understand why a project was blocked historically

**Acceptance Criteria:**
- [ ] Evaluation history accessible from project
- [ ] Shows date, user, pass/fail, and details
- [ ] Can filter by date range
- [ ] Can filter by gate
- [ ] Shows project state snapshot at evaluation time

**Priority:** Medium

---

## Sales Coordinator Stories

### US-SC-001: Verify Discovery Readiness
**As a** Sales Coordinator
**I want to** verify all discovery requirements are met
**So that** I can hand off to the design team

**Acceptance Criteria:**
- [ ] Discovery Complete gate status visible
- [ ] Shows status of: client, sales order, deposit, rooms
- [ ] Can see what's missing at a glance
- [ ] Can take action on each missing item

**Priority:** High

---

### US-SC-002: Track Deposit Payment
**As a** Sales Coordinator
**I want to** know if deposit is blocking project advancement
**So that** I can follow up with the customer

**Acceptance Criteria:**
- [ ] Deposit status visible in gate blockers
- [ ] Shows clear message when deposit not received
- [ ] Action button links to payment screen
- [ ] Gate passes automatically when deposit recorded

**Priority:** High

---

## Design Team Stories

### US-DT-001: Verify Design Completion
**As a** Design Team Lead
**I want to** verify all design work is complete
**So that** I can lock the design and proceed to procurement

**Acceptance Criteria:**
- [ ] Design Lock gate shows all requirements
- [ ] Shows which cabinets are missing dimensions
- [ ] Shows BOM generation status
- [ ] Shows customer approval status
- [ ] Shows redline approval status

**Priority:** High

---

### US-DT-002: Understand Design Lock
**As a** Designer
**I want to** understand what happens when design is locked
**So that** I complete all changes before the lock

**Acceptance Criteria:**
- [ ] Design Lock gate clearly indicates it applies lock
- [ ] Warning shown before advancing
- [ ] Lock prevents dimension/configuration changes
- [ ] Message explains how to request changes post-lock

**Priority:** Medium

---

## Production Manager Stories

### US-PR-001: Verify Production Readiness
**As a** Production Manager
**I want to** verify materials are received and staged
**So that** I can begin production

**Acceptance Criteria:**
- [ ] Receiving Complete gate visible
- [ ] Shows materials received status
- [ ] Shows materials staged status
- [ ] Can see what's blocking production start

**Priority:** High

---

### US-PR-002: Mark Production Complete
**As a** Production Manager
**I want to** verify all production tasks are complete
**So that** the project can move to QC

**Acceptance Criteria:**
- [ ] Production Complete gate shows task status
- [ ] Can see which tasks are incomplete
- [ ] Gate passes when all tasks marked done
- [ ] Production lock is applied

**Priority:** High

---

## QC Inspector Stories

### US-QC-001: Track QC Progress
**As a** QC Inspector
**I want to** see which cabinets need QC inspection
**So that** I can complete the QC process

**Acceptance Criteria:**
- [ ] QC Passed gate shows cabinet-level status
- [ ] Shows count of passed vs total cabinets
- [ ] Lists specific cabinets needing inspection
- [ ] Can link to individual cabinet QC screen

**Priority:** High

---

### US-QC-002: Handle Defects
**As a** QC Inspector
**I want to** track blocking defects
**So that** the project doesn't advance with unresolved issues

**Acceptance Criteria:**
- [ ] Defects blocker visible in gate status
- [ ] Shows count of open blocking defects
- [ ] Can link to defect management screen
- [ ] Gate passes when defects resolved/closed

**Priority:** High

---

## Administrator Stories

### US-AD-001: Configure Gates
**As an** Administrator
**I want to** configure gate requirements
**So that** the workflow matches business needs

**Acceptance Criteria:**
- [ ] Can create new gates for any stage
- [ ] Can add/edit/remove requirements
- [ ] Can set blocking vs non-blocking
- [ ] Can configure lock application
- [ ] Changes take effect immediately

**Priority:** Medium

---

### US-AD-002: Audit Gate History
**As an** Administrator
**I want to** audit gate evaluations across projects
**So that** I can ensure compliance and identify patterns

**Acceptance Criteria:**
- [ ] Can view evaluations across all projects
- [ ] Can filter by gate, result, date, user
- [ ] Can export evaluation data
- [ ] Context snapshots preserved for audit

**Priority:** Medium

---

### US-AD-003: Override Gates
**As an** Administrator
**I want to** override a gate in emergency situations
**So that** business operations aren't completely blocked

**Acceptance Criteria:**
- [ ] Override option available (admin only)
- [ ] Requires documented reason
- [ ] Override is logged with justification
- [ ] Creates follow-up task for resolution

**Priority:** Low

---

## System Stories

### US-SY-001: Automatic Gate Checking
**As the** System
**I want to** automatically check gates when data changes
**So that** gate status is always current

**Acceptance Criteria:**
- [ ] Gates re-evaluated when relevant data changes
- [ ] Evaluation type marked as "automatic"
- [ ] No duplicate evaluations for same state
- [ ] Performance doesn't impact user experience

**Priority:** Medium

---

### US-SY-002: Apply Locks
**As the** System
**I want to** apply locks when gates pass
**So that** data integrity is maintained

**Acceptance Criteria:**
- [ ] Design lock prevents dimension changes
- [ ] Procurement lock prevents BOM changes
- [ ] Production lock prevents schedule changes
- [ ] Lock status visible to users
- [ ] Appropriate error messages when lock prevents action

**Priority:** High

---

### US-SY-003: Create Follow-up Tasks
**As the** System
**I want to** create tasks when certain gates pass
**So that** next steps are automatically assigned

**Acceptance Criteria:**
- [ ] Tasks created based on gate configuration
- [ ] Task templates support variable substitution
- [ ] Tasks assigned to appropriate roles
- [ ] Tasks linked to project

**Priority:** Low

---

## Story Map

```
Discovery          Design           Procurement       Production        QC               Delivery
─────────────────────────────────────────────────────────────────────────────────────────────────────
US-SC-001         US-DT-001        US-PM-001         US-PR-001        US-QC-001        US-PM-003
US-SC-002         US-DT-002        US-PM-002         US-PR-002        US-QC-002
US-PM-001         US-PM-003        US-PM-003         US-PM-003        US-PM-003
US-PM-002
US-PM-003

Admin: US-AD-001, US-AD-002, US-AD-003
System: US-SY-001, US-SY-002, US-SY-003
```

## Priority Matrix

| Priority | Stories |
|----------|---------|
| **High** | US-PM-001, US-PM-002, US-PM-003, US-SC-001, US-SC-002, US-DT-001, US-PR-001, US-PR-002, US-QC-001, US-QC-002, US-SY-002 |
| **Medium** | US-PM-004, US-DT-002, US-AD-001, US-AD-002, US-SY-001 |
| **Low** | US-AD-003, US-SY-003 |
