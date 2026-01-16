# Drawing Analysis Pipeline → Database Mapping Plan

This document maps each drawing analysis service step to the appropriate TCS database tables and provides sample JSON structures for each extraction stage.

---

## Pipeline Overview

```
┌─────────────────────────────────────────────────────────────────────────────┐
│                        DRAWING ANALYSIS PIPELINE                              │
├─────────────────────────────────────────────────────────────────────────────┤
│  Step 1: Context         → Metadata (no direct DB table)                     │
│  Step 2: Dim References  → Metadata (informs later steps)                    │
│  Step 3: Notes           → Metadata + production notes                       │
│  Step 4: Validation GATE → Pass/Fail decision                                │
│  Step 5: Entities        → projects_projects → rooms → locations → runs     │
│  Step 6: Verification    → Validates dimensions before component creation    │
│  Step 7: Alignment       → Documents standards used                          │
│  Step 8: Constraints     → Production parameters for components              │
│  Step 9: Components      → projects_cabinets → sections → doors/drawers     │
│  Step 10: Audit          → Final verification report                         │
└─────────────────────────────────────────────────────────────────────────────┘
```

---

## Database Hierarchy (Quick Reference)

```
projects_projects (Project)
└── projects_rooms (Room)
    └── projects_room_locations (Location)
        └── projects_cabinet_runs (Run)
            └── projects_cabinets (Cabinet)
                └── projects_cabinet_sections (Section - THE OPENING)
                    ├── projects_doors (Door)
                    ├── projects_drawers (Drawer)
                    ├── projects_shelves (Shelf)
                    └── projects_pullouts (Pullout)
```

---

## Step-by-Step Mapping

### Step 1: Drawing Context Analysis (`DrawingContextAnalyzerService`)

**Database Target:** None (metadata only - used to inform later steps)

**Purpose:** Understand the drawing before extraction

**Sample Output JSON:**
```json
{
  "view_type": {
    "primary": "elevation",
    "confidence": 0.95
  },
  "orientation": {
    "primary": "front",
    "confidence": 0.9
  },
  "drawing_intent": {
    "type": "production",
    "confidence": 0.85
  },
  "unit_system": {
    "primary": "inches",
    "format": "fractional"
  },
  "scale": {
    "indicated": true,
    "value": "1/2\" = 1'-0\""
  },
  "baselines": {
    "identified": ["finished_floor", "face_frame_front"],
    "primary": "finished_floor"
  }
}
```

**Where This Data Goes:**
- Stored in session/cache during analysis
- Used by Steps 2-9 for context
- Can be logged to `projects_projects.notes` or a JSON field

---

### Step 2: Dimension Reference Analysis (`DimensionReferenceAnalyzerService`)

**Database Target:** None directly (metadata for verification)

**Purpose:** Understand WHERE dimensions are measured from

**Sample Output JSON:**
```json
{
  "dimensions": [
    {
      "id": "DIM-001",
      "value": {
        "as_written": "34-1/2\"",
        "numeric": 34.5,
        "unit": "inches"
      },
      "orientation": "vertical",
      "start_reference": {
        "type": "physical",
        "point": "finished_floor"
      },
      "end_reference": {
        "type": "physical",
        "point": "cabinet_box_top"
      },
      "flags": []
    },
    {
      "id": "DIM-002",
      "value": {
        "as_written": "36\"",
        "numeric": 36.0,
        "unit": "inches"
      },
      "orientation": "horizontal",
      "start_reference": {
        "type": "visual",
        "point": "stile_edge"
      },
      "end_reference": {
        "type": "visual",
        "point": "stile_edge"
      },
      "flags": []
    }
  ]
}
```

**Where This Data Goes:**
- Used to validate dimensions in Step 6
- Reference plane data informs `projects_cabinets` dimension columns
- Flags inform audit report in Step 10

---

### Step 3: Notes Extraction (`DrawingNotesExtractorService`)

**Database Target:** Multiple (notes apply to different hierarchy levels)

**Purpose:** Capture all textual specifications

**Sample Output JSON:**
```json
{
  "notes": [
    {
      "id": "NOTE-001",
      "text": {
        "exact": "ALL FACE FRAMES 1-1/2\" STILES & RAILS"
      },
      "scope": "project",
      "type": "material_spec",
      "actionability": "production_required"
    },
    {
      "id": "NOTE-002",
      "text": {
        "exact": "3/4\" MAPLE PLYWOOD"
      },
      "scope": "cabinet",
      "type": "material_spec",
      "actionability": "production_required"
    },
    {
      "id": "NOTE-003",
      "text": {
        "exact": "SOFT CLOSE HINGES & SLIDES"
      },
      "scope": "project",
      "type": "hardware_spec",
      "actionability": "production_required"
    }
  ],
  "title_block": {
    "project_name": "Smith Residence - Kitchen",
    "drawing_number": "A-101",
    "revision": "2"
  }
}
```

**Database Mapping:**

| Note Scope | Target Table | Target Column(s) |
|------------|--------------|------------------|
| `project` | `projects_projects` | `description`, `notes` (JSON field if exists) |
| `room` | `projects_rooms` | Can add `drawing_notes` column |
| `location` | `projects_room_locations` | `material_type`, `wood_species`, `door_style`, etc. |
| `cabinet` | `projects_cabinets` | `box_material`, `face_frame_material`, etc. |
| `component` | `projects_drawers`, `projects_doors` | Component-specific columns |

**Example Mapping:**
```
NOTE-001 (scope: project, type: material_spec) →
  projects_room_locations.face_frame_stile_width = 1.5
  projects_room_locations.face_frame_rail_width = 1.5

NOTE-002 (scope: cabinet, type: material_spec) →
  projects_cabinets.box_material = "3/4_maple_plywood"
  projects_cabinets.box_thickness = 0.75

NOTE-003 (scope: project, type: hardware_spec) →
  projects_room_locations.soft_close_doors = true
  projects_room_locations.soft_close_drawers = true
```

---

### Step 4: Drawing Intent Validation (`DrawingIntentValidationService`)

**Database Target:** None (gate decision)

**Purpose:** Determine if we can proceed

**Sample Output JSON:**
```json
{
  "suitability": {
    "production_modeling": {
      "suitable": true,
      "confidence": 0.85,
      "missing_requirements": []
    },
    "cnc_generation": {
      "suitable": true,
      "confidence": 0.90,
      "missing_requirements": []
    },
    "material_takeoff": {
      "suitable": true,
      "confidence": 0.95
    }
  },
  "can_proceed": {
    "extraction_allowed": true,
    "allowed_purposes": ["production_modeling", "cnc_generation", "material_takeoff"]
  },
  "blockers": []
}
```

**Where This Data Goes:**
- If `extraction_allowed = false`, pipeline stops
- Blockers logged to audit report
- Can be stored in project metadata

---

### Step 5: Hierarchical Entity Extraction (`HierarchicalEntityExtractorService`)

**Database Target:** Full hierarchy creation

**Purpose:** Create database records for all entities

**Sample Output JSON:**
```json
{
  "entities": {
    "project": {
      "id": "PRJ-001",
      "name": "Smith Residence Kitchen"
    },
    "rooms": [
      {
        "id": "ROOM-001",
        "type": "kitchen",
        "name": "Kitchen"
      }
    ],
    "locations": [
      {
        "id": "LOC-001",
        "type": "wall",
        "name": "Sink Wall",
        "parent_id": "ROOM-001"
      }
    ],
    "cabinet_runs": [
      {
        "id": "RUN-001",
        "type": "base_run",
        "name": "Base Run",
        "parent_id": "LOC-001"
      }
    ],
    "cabinets": [
      {
        "id": "CAB-001",
        "type": "sink_base",
        "name": "SB36",
        "parent_id": "RUN-001",
        "bounding_geometry": {
          "width": {"value": "36\"", "source": "labeled"},
          "height": {"value": "34-1/2\"", "source": "labeled"},
          "depth": {"value": "24\"", "source": "assumed"}
        }
      }
    ],
    "sections": [
      {
        "id": "SEC-001",
        "type": "drawer_stack",
        "parent_id": "CAB-001",
        "position": "top"
      }
    ],
    "components": [
      {
        "id": "COMP-001",
        "type": "false_front",
        "parent_id": "SEC-001",
        "placeholder": true,
        "position": "top"
      },
      {
        "id": "COMP-002",
        "type": "drawer",
        "parent_id": "SEC-001",
        "placeholder": true,
        "position": "2nd"
      }
    ]
  }
}
```

**Database Mapping:**

| Extracted Entity | Target Table | Key Columns |
|------------------|--------------|-------------|
| `project` | `projects_projects` | `name`, `description` |
| `rooms[]` | `projects_rooms` | `project_id`, `name`, `room_type`, `floor_number` |
| `locations[]` | `projects_room_locations` | `room_id`, `name`, `location_type`, `sequence` |
| `cabinet_runs[]` | `projects_cabinet_runs` | `room_location_id`, `name`, `run_type` |
| `cabinets[]` | `projects_cabinets` | `cabinet_run_id`, `length_inches`, `height_inches`, `depth_inches` |
| `sections[]` | `projects_cabinet_sections` | `cabinet_specification_id`, `section_type`, `name` |
| `components[]` | (placeholder only - created in Step 9) | -- |

**Detailed Column Mapping for `projects_cabinets`:**
```
Extracted JSON                    → Database Column
─────────────────────────────────────────────────────────
bounding_geometry.width.value     → length_inches (cabinet width)
bounding_geometry.height.value    → height_inches
bounding_geometry.depth.value     → depth_inches / width_inches
type: "sink_base"                 → cabinet_type (custom field or inferred)
name: "SB36"                      → name or product reference
```

---

### Step 6: Dimension Consistency Verification (`DimensionConsistencyVerifierService`)

**Database Target:** Validation only (no direct writes)

**Purpose:** Verify math before component creation

**Sample Output JSON:**
```json
{
  "cabinet_verifications": [
    {
      "cabinet_id": "CAB-001",
      "cabinet_name": "SB36",
      "total_dimensions": {
        "height": {"value": 34.5, "includes_toe_kick": true},
        "width": {"value": 36},
        "depth": {"value": 24}
      },
      "vertical_stackup": {
        "calculation": "4 (toe) + 6 (drawer 1) + 1.5 (rail) + 21.5 (drawer 2) + 1.5 (top rail) = 34.5",
        "status": "reconciled"
      },
      "horizontal_stackup": {
        "calculation": "1.5 (L stile) + 33 (opening) + 1.5 (R stile) = 36",
        "status": "reconciled"
      },
      "implied_gaps": [
        {"location": "drawer_reveal", "calculated_value": 0.125}
      ]
    }
  ],
  "discrepancies": []
}
```

**Where This Data Goes:**
- Validates dimensions before writing to `projects_cabinets`
- Calculated gaps inform `face_frame_door_gap_inches` column
- Stack-up data can be stored in `projects_cabinets` JSON fields for traceability

**Relevant `projects_cabinets` Columns:**
```
toe_kick_height                   → From toe kick in stack-up
face_frame_stile_width            → From horizontal stack-up
face_frame_rail_width             → From vertical stack-up
face_frame_door_gap_inches        → From implied_gaps (default 0.125)
```

---

### Step 7: Standard Practice Alignment (`StandardPracticeAlignmentService`)

**Database Target:** Documents standards, may update defaults

**Purpose:** Confirm or flag non-standard practices

**Sample Output JSON:**
```json
{
  "practice_evaluations": [
    {
      "category": "drawer_spacing",
      "status": "standard",
      "details": {
        "observed_value": 0.125,
        "standard_value": 0.125
      },
      "assessment": "Standard 1/8\" reveal"
    },
    {
      "category": "face_frame_overlap",
      "status": "acceptable_variation",
      "details": {
        "observed_value": 0.375,
        "standard_value": 0.375
      }
    }
  ],
  "flags": [],
  "custom_elements": []
}
```

**Where This Data Goes:**
- Confirms standard values can be used for `projects_room_locations` hardware columns
- Custom elements documented in project notes
- Can update `projects_cabinet_runs` or location-level defaults

---

### Step 8: Production Constraint Derivation (`ProductionConstraintDerivationService`)

**Database Target:** Informs component dimensions and hardware

**Purpose:** Establish machining parameters

**Sample Output JSON:**
```json
{
  "constraints": [
    {
      "id": "GAP-0125",
      "type": "gap_standard",
      "value": 0.125,
      "unit": "inches",
      "source": "math_reconciliation",
      "scope": "project",
      "is_inferred": false
    },
    {
      "id": "MAT-001",
      "type": "material_thickness",
      "value": 0.75,
      "unit": "inches",
      "source": "explicit_note",
      "scope": "project"
    },
    {
      "id": "REF-CAB001",
      "type": "reference_surface",
      "value": "face_frame_front",
      "scope": "cabinet",
      "applies_to": ["CAB-001"]
    }
  ]
}
```

**Database Mapping:**

| Constraint Type | Target Table | Target Column(s) |
|-----------------|--------------|------------------|
| `gap_standard` | `projects_cabinets` | `face_frame_door_gap_inches` |
| `material_thickness` | `projects_cabinets` | `box_thickness`, `shelf_thickness` |
| `material_thickness` | `projects_drawers` | `front_thickness_inches`, `box_thickness` |
| `reference_surface` | Used for calculations | Not stored directly |

---

### Step 9: Component Extraction (`ComponentExtractionService`)

**Database Target:** Component tables

**Purpose:** Create final component records with full dimensions

**Sample Output JSON:**
```json
{
  "components": [
    {
      "id": "COMP-001",
      "type": "false_front",
      "parent_id": "CAB-001",
      "dimensions": {
        "width": {
          "value": 33.5,
          "derivation_method": "calculated",
          "derivation_detail": "Opening (33\") + 2x overlay (0.25\") = 33.5\""
        },
        "height": {
          "value": 5.75,
          "derivation_method": "explicit_dimension"
        }
      },
      "governing_constraints": ["GAP-0125"]
    },
    {
      "id": "COMP-002",
      "type": "drawer",
      "parent_id": "SEC-001",
      "dimensions": {
        "width": {
          "value": 33.5,
          "derivation_method": "calculated",
          "derivation_detail": "Opening (33\") + overlay"
        },
        "height": {
          "value": 6.75,
          "derivation_method": "calculated"
        }
      },
      "box_dimensions": {
        "width": {"value": 30.25, "derivation_detail": "Opening - 2x slide clearance (0.5\" each) - 2x box material (0.5\" each)"},
        "depth": {"value": 21},
        "height": {"value": 5.5}
      },
      "governing_constraints": ["GAP-0125", "MAT-001"]
    },
    {
      "id": "STR-001",
      "type": "stretcher",
      "parent_id": "CAB-001",
      "stretcher_details": {
        "purpose": "drawer_support",
        "vertical_reference": "above_drawer",
        "vertical_position": {"value": 8.5, "from": "cabinet_box_bottom"},
        "width_type": "full_width"
      },
      "dimensions": {
        "width": {"value": 34.5},
        "height": {"value": 0.75},
        "depth": {"value": 3.5}
      }
    }
  ]
}
```

**Database Mapping - Drawers:**

| Extracted JSON Path | Target Table | Target Column |
|---------------------|--------------|---------------|
| `type: "drawer"` | `projects_drawers` | (table selection) |
| `parent_id` | `projects_drawers` | `section_id` (via SEC-001) |
| `dimensions.width.value` | `projects_drawers` | `front_width_inches` |
| `dimensions.height.value` | `projects_drawers` | `front_height_inches` |
| `box_dimensions.width.value` | `projects_drawers` | `box_width_inches` |
| `box_dimensions.depth.value` | `projects_drawers` | `box_depth_inches` |
| `box_dimensions.height.value` | `projects_drawers` | `box_height_inches` |
| `position` | `projects_drawers` | `drawer_position` |
| (from constraints) | `projects_drawers` | `box_material`, `slide_type`, `soft_close` |

**Database Mapping - Doors:**

| Extracted JSON Path | Target Table | Target Column |
|---------------------|--------------|---------------|
| `type: "door"` | `projects_doors` | (table selection) |
| `dimensions.width.value` | `projects_doors` | `width_inches` |
| `dimensions.height.value` | `projects_doors` | `height_inches` |
| `hinge_side` | `projects_doors` | `hinge_side` |
| (from constraints) | `projects_doors` | `profile_type`, `hinge_type` |

**Database Mapping - Shelves:**

| Extracted JSON Path | Target Table | Target Column |
|---------------------|--------------|---------------|
| `type: "shelf"` | `projects_shelves` | (table selection) |
| `dimensions.width.value` | `projects_shelves` | `width_inches` |
| `dimensions.depth.value` | `projects_shelves` | `depth_inches` |
| `shelf_type` | `projects_shelves` | `shelf_type` |

**Database Mapping - Stretchers:**

| Extracted JSON Path | Target Table | Target Column |
|---------------------|--------------|---------------|
| `type: "stretcher"` | `projects_cabinets` | (stored in cabinet JSON or dedicated table) |
| `stretcher_details.vertical_position.value` | -- | `stretcher_position_inches` (if column exists) |
| `dimensions.height.value` | `projects_cabinets` | `stretcher_height_inches` |

---

### Step 10: Verification Audit (`VerificationAuditService`)

**Database Target:** Audit log / project metadata

**Purpose:** Final verification status

**Sample Output JSON:**
```json
{
  "verification_level": {
    "level": "VERIFIED_WITH_ASSUMPTIONS",
    "reason": "3 documented assumptions"
  },
  "readiness": {
    "cnc_ready": {"ready": true, "confidence": 0.85},
    "production_ready": {"ready": true, "confidence": 0.90},
    "material_takeoff_ready": {"ready": true, "confidence": 0.95}
  },
  "assumptions": [
    {
      "id": "ASM-001",
      "category": "material_thickness",
      "assumed_value": 0.75,
      "source_step": "Production Constraint Derivation"
    }
  ],
  "unresolved_conflicts": [],
  "summary": {
    "verification_status": "VERIFIED_WITH_ASSUMPTIONS",
    "total_assumptions": 3,
    "total_conflicts": 0,
    "headline": "⚠ VERIFIED WITH 3 ASSUMPTION(S) - Review before production"
  }
}
```

**Where This Data Goes:**
- Can create an `audit_log` table or store in `projects_projects` JSON field
- `verification_level` can update project status
- Assumptions list stored for production team review

---

## Complete Flow Example

```
Drawing: Kitchen Cabinet Elevation (SB36 Sink Base)

STEP 1: Context
├── View: elevation (front)
├── Units: inches (fractional)
└── Baseline: finished_floor

STEP 2: Dimension References
├── 34-1/2" height (FFL → cabinet top)
├── 36" width (stile edge to stile edge)
└── No depth shown (will assume 24")

STEP 3: Notes
├── "3/4 MAPLE PLY" → box_material
├── "1-1/2 FACE FRAME" → stile/rail widths
└── "SOFT CLOSE" → hardware flags

STEP 4: Validation GATE ✓ PASSED

STEP 5: Entity Extraction
├── Project: Smith Kitchen
│   └── Room: Kitchen
│       └── Location: Sink Wall
│           └── Run: Base Run
│               └── Cabinet: SB36
│                   └── Section: Drawer Stack
│                       ├── [placeholder] False Front
│                       └── [placeholder] Drawer

STEP 6: Verification
├── Vertical: 4 + 6 + 1.5 + 21.5 + 1.5 = 34.5 ✓
├── Horizontal: 1.5 + 33 + 1.5 = 36 ✓
└── Gap: 0.125" reveal identified

STEP 7: Alignment
├── Reveal: STANDARD (1/8")
├── Stile width: STANDARD (1.5")
└── No bottom rail: STANDARD (sink base)

STEP 8: Constraints
├── GAP-0125: 1/8" reveal (from math)
├── MAT-001: 3/4" material (from note)
└── REF-001: face_frame_front (standard)

STEP 9: Component Extraction
├── False Front: 33.5" × 5.75"
├── Drawer Front: 33.5" × 6.75"
├── Drawer Box: 30.25" × 21" × 5.5"
└── Stretcher: 34.5" × 3" × 3.5" @ 8.5" from bottom

STEP 10: Audit
└── VERIFIED_WITH_ASSUMPTIONS
    └── Assumption: 24" depth assumed (not shown on drawing)
```

---

## Database Write Order

When persisting extracted data, follow this order to maintain referential integrity:

```
1. projects_projects          (create/find project)
2. projects_rooms             (create room)
3. projects_room_locations    (create location, apply material notes)
4. projects_cabinet_runs      (create run)
5. projects_cabinets          (create cabinet with dimensions)
6. projects_cabinet_sections  (create sections for openings)
7. projects_drawers           (create drawers from components)
8. projects_doors             (create doors from components)
9. projects_shelves           (create shelves from components)
```

---

## Next Steps

1. **Create DrawingAnalysisOrchestrator service** - Chains all 10 steps together
2. **Create DatabasePersistenceService** - Maps JSON output to Eloquent models
3. **Add validation at each step** - Ensure data matches DB constraints before write
4. **Add rollback capability** - If any step fails, clean up partial records
