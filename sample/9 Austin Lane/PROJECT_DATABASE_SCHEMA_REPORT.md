# TCS Woodwork - Project Database Schema Report
## Staging Database Analysis (2026-01-15)

---

## EXECUTIVE SUMMARY

**Total Project Tables:** 38 tables
**Total Columns in Project Tables:** ~1,400+ columns
**Schema Version:** Post-migration (includes stretchers, faceframes, false_fronts)

---

## PART 1: COMPLETE SCHEMA REPORT

### Core Hierarchy Tables

| Table | Columns | Purpose |
|-------|---------|---------|
| `projects_projects` | 74 | Master project record |
| `projects_rooms` | 43 | Room containers within projects |
| `projects_room_locations` | 66 | Wall/location containers within rooms |
| `projects_cabinet_runs` | 72 | Cabinet run groupings |
| `projects_cabinets` | 135 | Individual cabinet specifications |
| `projects_cabinet_sections` | 39 | Openings within cabinets |

### Component Tables

| Table | Columns | Purpose |
|-------|---------|---------|
| `projects_drawers` | 91 | Drawer specifications with full cut list support |
| `projects_doors` | 55 | Door specifications |
| `projects_shelves` | 72 | Shelf specifications (fixed/adjustable/rollout) |
| `projects_pullouts` | 46 | Pullout accessory specifications |
| `projects_false_fronts` | 51 | False front/tilt-out panels |
| `projects_stretchers` | 29 | Cabinet stretchers (top/drawer support) |
| `projects_faceframes` | 24 | Face frame configurations per run |
| `projects_fixed_dividers` | 19 | Fixed divider panels |

### Support Tables

| Table | Columns | Purpose |
|-------|---------|---------|
| `hardware_requirements` | 54 | Hardware tracking per component |
| `projects_bom` | 35 | Bill of materials per cabinet |
| `projects_change_orders` | 27 | Change order management |
| `projects_change_order_lines` | 14 | Individual change order items |
| `projects_entity_locks` | 14 | Design lock tracking |
| `projects_gate_evaluations` | 12 | Gate check results |
| `projects_gate_requirements` | 20 | Gate check definitions |
| `projects_gates` | 16 | Project stage gates |
| `projects_milestones` | 14 | Project milestones |
| `projects_material_reservations` | 17 | Inventory reservations |
| `projects_production_estimates` | 18 | Production time estimates |
| `projects_cnc_programs` | 15 | CNC program tracking |
| `projects_cnc_program_parts` | 14 | CNC program parts |
| `projects_stage_transitions` | 14 | Stage change history |

### Preset Tables

| Table | Columns | Purpose |
|-------|---------|---------|
| `projects_door_presets` | 18 | Door style templates |
| `projects_drawer_presets` | 15 | Drawer style templates |
| `projects_shelf_presets` | 14 | Shelf style templates |
| `projects_pullout_presets` | 17 | Pullout style templates |
| `projects_hardware_packages` | 22 | Hardware package templates |

---

## PART 2: GAPS REPORT

### A. Missing Tables (Not in Database)

The following tables referenced in JSON data map do NOT exist in database:

| Missing Table | JSON Section | Recommendation |
|---------------|--------------|----------------|
| `projects_countertops` | countertop | Store in `projects_rooms.countertop_*` fields OR create new table |
| `projects_fixtures` | sink | Store in room notes OR create new table |

### B. Fields in JSON NOT in Database

| JSON Section | Field | DB Location | Status |
|--------------|-------|-------------|--------|
| `room_locations` | `face_frame_stile_width_inches` | `projects_cabinets.face_frame_stile_width_inches` | **WRONG TABLE** - should be at cabinet level |
| `room_locations` | `face_frame_thickness_inches` | `projects_faceframes.material_thickness` | Move to faceframes table |
| `cabinets` | `overlay_type` | `projects_cabinets.door_mounting` | **RENAME** - use door_mounting |
| `drawers` | `stile_width_inches` | `projects_drawers.style_width_inches` | **TYPO** - DB uses "style" not "stile" |

### C. Nested Objects Requiring Flattening

These JSON nested objects need to be flattened for database insertion:

| JSON Path | Flatten To |
|-----------|-----------|
| `drawers.opening.*` | `projects_drawers.opening_width_inches`, `opening_height_inches`, `opening_depth_inches` |
| `drawers.drawer_box_calculated.*` | `projects_drawers.box_width_inches`, `box_depth_inches`, `box_height_inches` |
| `drawers.box_construction.*` | `projects_drawers.box_material`, `side_thickness_inches`, `joinery_method` |
| `drawers.cut_list.*` | Store in notes OR new cut_list table |
| `drawers.dado_specs.*` | `projects_drawers.dado_depth_inches`, `dado_width_inches`, `dado_height_inches` |
| `drawers.clearances_applied.*` | `projects_drawers.clearance_side_inches`, `clearance_top_inches`, `clearance_bottom_inches` |
| `cabinets.toe_kick.*` | `projects_cabinets.toe_kick_height`, `toe_kick_depth` |
| `cabinets.box_material.*` | `projects_cabinets.box_material`, `box_thickness` |

### D. Database Columns in Wrong Tables

| Column | Current Table | Should Be In | Notes |
|--------|--------------|--------------|-------|
| `toe_kick_height_inches` | `projects_room_locations` | **OK** | Default for location |
| `toe_kick_height` | `projects_cabinets` | **OK** | Override per cabinet |
| `toe_kick_depth` | `projects_cabinets` | **MISSING** from room_locations | Room_locations has no toe_kick_depth/setback |
| `face_frame_stile_width_inches` | `projects_cabinets` | **OK** | Per-cabinet setting |
| `stile_width` | `projects_faceframes` | **OK** | Run-level default |

### E. Naming Inconsistencies

| Inconsistency | Tables Affected | Recommendation |
|---------------|-----------------|----------------|
| `style_width_inches` vs `stile_width` | drawers, doors, faceframes | Standardize to `stile_width_inches` |
| `toe_kick_height` vs `toe_kick_height_inches` | cabinets, room_locations | Standardize to `_inches` suffix |
| `face_frame_stile_width` vs `stile_width` | cabinets, faceframes | Keep both (cabinet=override, faceframe=default) |

### F. Data Type Observations

| Table | Column | Current Type | Note |
|-------|--------|--------------|------|
| `projects_cabinets` | dimension fields | `decimal(10,4)` | Good - supports fractional measurements |
| `projects_drawers` | cut list fields | `decimal(8,4)` | Good - supports shop precision |
| `projects_room_locations` | `toe_kick_height_inches` | `decimal(8,3)` | Good |
| `projects_cabinets` | `toe_kick_height` | `decimal(8,3)` | Good |

---

## PART 3: SCHEMA ANALYSIS BY TABLE

### projects_cabinets (135 columns)

**Key Dimension Fields:**
- `length_inches`, `width_inches`, `depth_inches`, `height_inches` - `decimal(10,4)`
- `toe_kick_height`, `toe_kick_depth` - `decimal(8,3)`
- `box_thickness`, `back_panel_thickness` - `decimal(5,3)`

**Key Relationship Fields:**
- `cabinet_run_id` - FK to cabinet_runs
- `room_id` - FK to rooms (denormalized for convenience)
- `project_id` - FK to projects (denormalized)

**Hardware Fields:**
- `hinge_model`, `hinge_product_id`, `hinge_quantity`
- `slide_model`, `slide_product_id`, `slide_quantity`
- `pullout_model`, `pullout_product_id`

**Production Tracking:**
- `cnc_cut_at`, `face_frame_cut_at`, `door_fronts_cut_at`
- `edge_banded_at`, `hardware_installed_at`, `assembled_at`
- `qc_passed`, `qc_issues`, `qc_inspected_at`

### projects_drawers (91 columns)

**Front Dimensions:**
- `front_width_inches`, `front_height_inches`, `front_thickness_inches`
- `top_rail_width_inches`, `bottom_rail_width_inches`, `style_width_inches`

**Box Dimensions:**
- `box_width_inches`, `box_depth_inches`, `box_height_inches`
- `box_depth_shop_inches`, `box_height_shop_inches` (shop-rounded values)
- `box_outside_width_inches`, `box_inside_width_inches`

**Cut List Fields:**
- `side_cut_height_inches`, `side_cut_length_inches`
- `front_cut_height_inches`, `front_cut_width_inches`
- `back_cut_height_inches`, `back_cut_width_inches`
- `bottom_cut_width_inches`, `bottom_cut_depth_inches`

**Dado Specifications:**
- `dado_depth_inches`, `dado_width_inches`, `dado_height_inches`

**Clearances:**
- `clearance_side_inches`, `clearance_top_inches`, `clearance_bottom_inches`

**Slide Specifications:**
- `slide_type`, `slide_model`, `slide_product_id`
- `slide_length_inches`, `slide_quantity`
- `min_cabinet_depth_blum_inches`, `min_cabinet_depth_shop_inches`

### projects_room_locations (66 columns)

**Dimension Fields:**
- `overall_width_inches`, `overall_height_inches`, `overall_depth_inches`
- `soffit_height_inches`, `toe_kick_height_inches`

**Material Defaults:**
- `material_type`, `wood_species`, `finish_type`
- `paint_color`, `stain_color`

**Hardware Defaults:**
- `hinge_type`, `slide_type`
- `soft_close_doors`, `soft_close_drawers`

**Missing:**
- `toe_kick_depth_inches` - NOT present (only height is here)
- `face_frame_thickness_inches` - NOT present

### projects_stretchers (29 columns) - NEW TABLE

**Position:**
- `position` - enum: 'front', 'back', 'drawer_support'
- `position_from_front_inches`, `position_from_top_inches`

**Dimensions:**
- `width_inches`, `depth_inches`, `thickness_inches`
- `cut_width_inches`, `cut_depth_inches` (shop dimensions)

**Relationship:**
- `cabinet_id`, `section_id`, `drawer_id` (if supporting drawer)

### projects_faceframes (24 columns) - NEW TABLE

**Dimensions:**
- `stile_width`, `rail_width`, `material_thickness`
- `face_frame_linear_feet`

**Configuration:**
- `overlay_type`, `joinery_type`
- `beaded_face_frame`, `flush_with_carcass`
- `overhang_left`, `overhang_right`, `overhang_top`, `overhang_bottom`

---

## PART 4: RECOMMENDATIONS

### Immediate Actions

1. **Fix toe_kick inconsistency:**
   - `projects_room_locations` has `toe_kick_height_inches` but NO depth
   - Add `toe_kick_depth_inches` to room_locations as default
   - Keep cabinet-level `toe_kick_depth` as override

2. **Standardize naming:**
   - Update JSON to use `style_width_inches` (not `stile_width_inches`)
   - Or update DB to use consistent `stile_` prefix

3. **Handle countertop/sink data:**
   - Option A: Create `projects_countertops` and `projects_fixtures` tables
   - Option B: Store in JSON fields on room or use notes

### JSON Data Map Updates Needed

```json
// CHANGE: overlay_type -> door_mounting
"cabinets.overlay_type" → "cabinets.door_mounting"

// CHANGE: stile_width_inches -> style_width_inches
"drawers.stile_width_inches" → "drawers.style_width_inches"

// FLATTEN: toe_kick nested object
"cabinets.toe_kick.height_inches" → "cabinets.toe_kick_height"
"cabinets.toe_kick.setback_inches" → "cabinets.toe_kick_depth"

// FLATTEN: drawer box dimensions
"drawers.drawer_box_calculated.outside_width_inches" → "drawers.box_width_inches"
"drawers.drawer_box_calculated.depth_inches" → "drawers.box_depth_inches"
"drawers.drawer_box_calculated.height_shop_inches" → "drawers.box_height_shop_inches"
```

### Data Entry Priority

1. `projects_room_locations` - Create room location records first
2. `projects_cabinet_runs` - Create cabinet run records
3. `projects_cabinets` - Create cabinet specifications
4. `projects_cabinet_sections` - Create openings (if applicable)
5. `projects_drawers` - Create drawer specifications
6. `hardware_requirements` - Link hardware to components

---

## APPENDIX: Column Counts by Table

| Table | Column Count |
|-------|--------------|
| projects_cabinets | 135 |
| projects_drawers | 91 |
| projects_projects | 74 |
| projects_cabinet_runs | 72 |
| projects_shelves | 72 |
| projects_room_locations | 66 |
| projects_doors | 55 |
| hardware_requirements | 54 |
| projects_false_fronts | 51 |
| projects_pullouts | 46 |
| projects_rooms | 43 |
| projects_cabinet_sections | 39 |
| projects_bom | 35 |
| projects_stretchers | 29 |
| projects_change_orders | 27 |
| projects_faceframes | 24 |
| projects_hardware_packages | 22 |
| projects_gate_requirements | 20 |
| projects_fixed_dividers | 19 |
| projects_door_presets | 18 |
| projects_production_estimates | 18 |
| projects_material_reservations | 17 |
| projects_pullout_presets | 17 |
| projects_gates | 16 |
| projects_cnc_programs | 15 |
| projects_drawer_presets | 15 |
| projects_shelf_presets | 14 |
| projects_cnc_program_parts | 14 |
| projects_entity_locks | 14 |
| projects_milestones | 14 |
| projects_change_order_lines | 14 |
| projects_stage_transitions | 14 |
| projects_gate_evaluations | 12 |

---

**Report Generated:** 2026-01-15
**Database:** staging.tcswoodwork.com (ibimfste_staging_erp)
**Schema Extraction Method:** MySQL SHOW COLUMNS
