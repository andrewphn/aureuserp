# SOP: Project Stage Advancement

## Purpose

This Standard Operating Procedure defines the process for advancing projects through stages using the gate system.

## Scope

Applies to all TCS Woodwork cabinet projects managed in the ERP system.

## Definitions

| Term | Definition |
|------|------------|
| **Gate** | A checkpoint that must pass before stage advancement |
| **Blocker** | A requirement that prevents gate passage |
| **Lock** | Restriction applied when a gate passes |

## Project Workflow Stages

```
Discovery → Design → Procurement → Receiving → Production → QC → Delivery → Closeout
```

## Procedures

### 1. Discovery → Design

**Gate: Discovery Complete**

**Pre-requisites:**
- [ ] Client assigned to project
- [ ] Sales order created and linked
- [ ] Deposit payment received
- [ ] At least one room/specification defined

**Steps:**

1. Open the project in the ERP
2. Navigate to Gate Status panel
3. Review "Discovery Complete" gate status
4. If blockers exist:
   - Click action button to resolve each blocker
   - Re-check gate status after resolution
5. Once gate passes (all green), click "Advance to Design"

**Responsible:** Sales Coordinator

---

### 2. Design → Procurement

**Gate: Design Lock**

**Pre-requisites:**
- [ ] All cabinets have dimensions entered
- [ ] BOM (Bill of Materials) generated
- [ ] Customer has approved design
- [ ] Final redline changes confirmed

**Steps:**

1. Verify all cabinet configurations are complete
2. Run "Generate BOM" from project actions
3. Obtain customer design approval signature
4. Record approval date in `design_approved_at`
5. If redline changes made, confirm with customer
6. Record redline approval in `redline_approved_at`
7. Advance to Procurement stage

**Important:** Passing this gate applies **DESIGN LOCK**. No changes to cabinet dimensions or configurations after this point without change order.

**Responsible:** Design Team Lead

---

### 3. Procurement → Receiving

**Gate: Procurement Complete**

**Pre-requisites:**
- [ ] All materials sourced (suppliers assigned)
- [ ] Purchase orders created and confirmed

**Steps:**

1. Review BOM for all line items
2. Assign suppliers to each material
3. Create purchase orders
4. Confirm POs with suppliers
5. Verify all POs have confirmation dates
6. Advance to Receiving stage

**Important:** Passing this gate applies **PROCUREMENT LOCK**. BOM quantities cannot change without change order.

**Responsible:** Purchasing Manager

---

### 4. Receiving → Production

**Gate: Receiving Complete**

**Pre-requisites:**
- [ ] All materials received
- [ ] Materials staged for production

**Steps:**

1. Check in all deliveries against POs
2. Mark `all_materials_received_at` when complete
3. Stage materials in production area
4. Mark `materials_staged_at` when staged
5. Advance to Production stage

**Responsible:** Warehouse Manager

---

### 5. Production → QC

**Gate: Production Complete**

**Pre-requisites:**
- [ ] All production tasks completed

**Steps:**

1. Review all production tasks for project
2. Verify each task marked as complete
3. Check no outstanding production issues
4. Advance to QC stage

**Important:** Passing this gate applies **PRODUCTION LOCK**. No production rework without defect ticket.

**Responsible:** Production Manager

---

### 6. QC → Delivery

**Gate: QC Passed**

**Pre-requisites:**
- [ ] All cabinets passed QC inspection
- [ ] No blocking defects remain open

**Steps:**

1. Perform QC inspection on each cabinet
2. Mark `qc_passed` on each cabinet record
3. If defects found:
   - Create defect ticket
   - Resolve defect
   - Re-inspect
4. Verify all cabinets show QC passed
5. Advance to Delivery stage

**Responsible:** QC Inspector

---

### 7. Delivery → Closeout

**Gate: Delivery Scheduled**

**Pre-requisites:**
- [ ] Delivery date scheduled

**Steps:**

1. Coordinate delivery date with customer
2. Schedule delivery with logistics
3. Record delivery date in project
4. Advance to Closeout stage (after delivery)

**Responsible:** Logistics Coordinator

---

### 8. Project Closeout

**Gate: Delivered & Closed**

**Pre-requisites:**
- [ ] Delivery confirmed
- [ ] Closeout package delivered
- [ ] Customer signoff received
- [ ] Final payment received

**Steps:**

1. Confirm delivery completion with customer
2. Deliver closeout package (warranties, care instructions)
3. Obtain customer signoff signature
4. Process final payment
5. Close project

**Responsible:** Project Manager

---

## Handling Blockers

### Standard Resolution

1. Identify the blocker from gate status
2. Click the action button if available
3. Complete the required action
4. Return to gate status
5. Verify blocker is resolved

### Escalation Path

If a blocker cannot be resolved through standard means:

1. **Level 1:** Contact department lead
2. **Level 2:** Contact Project Manager
3. **Level 3:** Contact Operations Director

### Override Process (Emergency Only)

In rare cases where a gate must be bypassed:

1. Document the business justification
2. Get written approval from Operations Director
3. Use "Force Advance" option (Admin only)
4. Create follow-up task to resolve bypassed requirements
5. Log override in project notes

---

## Audit Requirements

All gate evaluations are automatically logged with:
- Timestamp
- User who triggered evaluation
- Pass/fail status
- Detailed requirement results
- Project state snapshot

**Retention:** Gate evaluation records retained for 7 years.

---

## Version History

| Version | Date | Author | Changes |
|---------|------|--------|---------|
| 1.0 | 2024-01-15 | System | Initial version |
