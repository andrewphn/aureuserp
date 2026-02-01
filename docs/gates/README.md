# Project Gates System

## Overview

The Project Gates system is a configurable checkpoint framework that controls when projects can advance between stages. Gates ensure all required conditions are met before a project moves forward, providing quality control, compliance tracking, and audit capabilities.

## Quick Links

### Gates Documentation
- [User Guide](./user-guide.md) - How to use gates in daily operations
- [Stage Relationships](./stage-relationships.md) - How gates connect to project/task stages
- [Technical Architecture](./architecture.md) - System design and database schema
- [Configuration Guide](./configuration.md) - Setting up gates and requirements
- [API Reference](./api-reference.md) - Endpoints and integration
- [User Stories](./user-stories.md) - Requirements organized by role
- [SOP: Stage Advancement](./sop-stage-advancement.md) - Standard Operating Procedure

### Change Orders & Locks
- [Change Orders](./change-orders.md) - How change orders work with gates and locks
- [SOP: Change Orders](./sop-change-orders.md) - Standard Operating Procedure for change orders
- [Change Order Overhaul](./change-order-overhaul.md) - Planned improvements and stop actions

## What Are Gates?

Gates are checkpoints attached to project stages. Before a project can advance to the next stage, all **blocking gates** for the current stage must pass. Each gate has one or more **requirements** that are evaluated.

```
Project Stage: Discovery
├── Gate: "Discovery Complete" (blocking)
│   ├── Requirement: Client assigned (partner_id not null)
│   ├── Requirement: Sales order linked (relation exists)
│   ├── Requirement: Deposit received (custom check)
│   └── Requirement: Rooms defined (relation exists)
```

## Key Concepts

| Concept | Description |
|---------|-------------|
| **Gate** | A named checkpoint attached to a stage |
| **Requirement** | A specific condition that must be met |
| **Blocking Gate** | Prevents stage advancement if not passed |
| **Non-Blocking Gate** | Advisory only, doesn't prevent advancement |
| **Evaluation** | A recorded check of gate status |
| **Lock** | Restriction applied when a gate passes (design, procurement, production) |

## TCS Woodwork Default Gates

| Stage | Gate | Key Requirements |
|-------|------|------------------|
| Discovery | Discovery Complete | Client assigned, Sales order, Deposit, Rooms defined |
| Design | Design Lock | Cabinet dimensions, BOM generated, Design approved, Redline confirmed |
| Procurement | Procurement Complete | Materials sourced, POs confirmed |
| Receiving | Receiving Complete | Materials received, Materials staged |
| Production | Production Complete | All production tasks done |
| QC | QC Passed | All cabinets QC'd, No blocking defects |
| Delivery | Delivery Scheduled | Delivery date set |
| Closeout | Delivered & Closed | Delivered, Closeout package, Customer signoff, Final payment |

## Benefits

1. **Quality Control** - Ensures all required steps are completed
2. **Audit Trail** - Every gate check is recorded with timestamp and user
3. **Visibility** - Clear status of what's blocking a project
4. **Flexibility** - Gates and requirements are configurable without code changes
5. **Lock Management** - Automatically applies design/procurement/production locks
