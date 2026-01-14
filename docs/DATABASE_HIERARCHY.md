# Cabinet Specification Database Hierarchy

This document describes the complete database table hierarchy for the TCS cabinet specification system, from project down to individual components.

---

## TCS Pricing Structure (January 2025)

> **Reference:** [Full Pricing System Plan](./pricing/tcs-pricing-system-plan.md)

### Cabinet Pricing (Per Linear Foot)

**Base Levels** (5 Complexity Tiers):

| Level | Price/LF | Description |
|-------|----------|-------------|
| **Level 1** | $138 | Paint grade, open boxes only, no doors/drawers |
| **Level 2** | $168 | Paint grade, semi-European, flat/shaker doors |
| **Level 3** | $192 | Stain grade, semi-complicated paint grade |
| **Level 4** | $210 | Beaded frames, specialty doors, moldings |
| **Level 5** | $225 | Unique custom work, paneling, reeded, rattan |

**Material Category Upgrades** (Added to Base):

| Category | Upgrade/LF | Wood Species |
|----------|------------|--------------|
| Paint Grade | +$138 | Hard Maple, Poplar |
| Stain Grade | +$156 | Oak, Maple |
| Premium | +$185 | Rifted White Oak, Black Walnut |
| Custom/Exotic | TBD | Rare/specialty woods |

**Calculation Example:**
```
Level 2 ($168) + Stain Grade ($156) = $324/LF
100 LF Kitchen = $32,400 base estimate
```

### Closet Systems

| Type | Labor/LF | Materials/LF | Total/LF |
|------|----------|--------------|----------|
| Paint Grade | $92 | $75.44 | **$167.44** |
| Stain Grade | $92 | $96.38 | **$188.38** |
| Shelf & Rod | - | - | **$28** (paint grade, wood only) |

*Note: Hardware/materials not included in labor rate*

### Floating Shelves

**Standard:** 1.75" thick × 10" deep × up to 120" long

| Grade | Price/LF | Materials |
|-------|----------|-----------|
| Paint Grade | $18 | Hard Maple, Poplar |
| Premium | $24 | White Oak, Walnut |

**Customizations:**
- Custom depths: +$3/LF
- Over 120" length: +$2/LF

*Note: Wood only - mounting brackets not included*

### Quick Reference Formulas

```
Cabinet Price = (Linear Feet × Base Level) + (Linear Feet × Material Upgrade)
Closet Price  = Linear Feet × (Labor Rate + Material Rate)
Shelf Price   = Linear Feet × Grade Rate + Customizations
```

### Pricing Validity
- **Effective Date:** January 2025
- **Valid For:** 30 days
- **Subject To:** Material market conditions

---

## Visual Hierarchy

```
┌─────────────────────────────────────────────────────────────────────────┐
│                        projects_projects                                 │
│                    (Top-level project container)                         │
└─────────────────────────────────────────────────────────────────────────┘
                                    │
                                    │ 1:N (project_id)
                                    ▼
┌─────────────────────────────────────────────────────────────────────────┐
│                          projects_rooms                                  │
│            (Kitchen, Master Bathroom, Laundry Room, etc.)               │
└─────────────────────────────────────────────────────────────────────────┘
                                    │
                                    │ 1:N (room_id)
                                    ▼
┌─────────────────────────────────────────────────────────────────────────┐
│                      projects_room_locations                             │
│             (North Wall, Island, Peninsula, Pantry Wall, etc.)          │
└─────────────────────────────────────────────────────────────────────────┘
                                    │
                                    │ 1:N (room_location_id)
                                    ▼
┌─────────────────────────────────────────────────────────────────────────┐
│                       projects_cabinet_runs                              │
│            (Base Run 1, Upper Cabinets, Tall Pantry Section)            │
└─────────────────────────────────────────────────────────────────────────┘
                                    │
                                    │ 1:N (cabinet_run_id)
                                    ▼
┌─────────────────────────────────────────────────────────────────────────┐
│                         projects_cabinets                                │
│                (B36, W3030, T2484 - Individual cabinet units)           │
│              (formerly: projects_cabinet_specifications)                 │
└─────────────────────────────────────────────────────────────────────────┘
                                    │
                                    │ 1:N (cabinet_specification_id)
                                    ▼
┌─────────────────────────────────────────────────────────────────────────┐
│                    projects_cabinet_sections                             │
│                        (THE OPENING)                                     │
│       (Top Drawer Stack, Door Opening, Open Shelving Area, etc.)        │
└─────────────────────────────────────────────────────────────────────────┘
                                    │
            ┌───────────────────────┼───────────────────────┐
            │ 1:N                   │ 1:N                   │ 1:N
            ▼                       ▼                       ▼
    ┌───────────────┐       ┌───────────────┐       ┌───────────────┐
    │ projects_doors│       │projects_drawers│      │projects_shelves│
    │   (D1, D2)    │       │ (DR1, DR2)     │      │  (S1, S2)      │
    └───────────────┘       └───────────────┘       └───────────────┘
            │                       │                       │
            │                       ▼                       │
            │      ┌────────────────────────────────┐       │
            │      │    DRAWER COMPONENT SPECS      │       │
            │      │  (stored in projects_drawers)  │       │
            │      ├────────────────────────────────┤       │
            │      │  • Drawer Front specs          │       │
            │      │    - width, height, thickness  │       │
            │      │    - profile, rails, stiles    │       │
            │      ├────────────────────────────────┤       │
            │      │  • Drawer Box specs            │       │
            │      │    - width, depth, height      │       │
            │      │    - material, joinery         │       │
            │      ├────────────────────────────────┤       │
            │      │  • Drawer Slides specs         │       │
            │      │    - type, model, length       │       │
            │      │    - soft close, product_id    │       │
            │      └────────────────────────────────┘       │
            │                                               │
            └───────────────────┬───────────────────────────┘
                                │
                                ▼
                    ┌───────────────────────┐
                    │  projects_pullouts    │
                    │    (P1, P2)           │
                    │ (Rev-a-Shelf, etc.)   │
                    └───────────────────────┘
```

**Component Hierarchy Example:**
```
Project: 25 Friendship Lane
└── Room: Kitchen
    └── Location: Sink Wall
        └── Run: Base Run 1
            └── Cabinet: B36 (Base 36")
                └── Section: Drawer Stack Opening (top portion)
                    ├── Drawer DR1 (top)
                    │   ├── Front: 33.5"W × 5"H, Shaker profile
                    │   ├── Box: 30"W × 21"D × 4"H, Maple dovetail
                    │   └── Slides: Blum Tandem 21", soft-close
                    ├── Drawer DR2 (middle)
                    └── Drawer DR3 (bottom)
```

---

## Table Details

### 1. `projects_projects` (Top Level)

The main project container representing a client job.

| Column | Type | Description |
|--------|------|-------------|
| `id` | bigint | Primary key |
| `name` | string | Project name |
| `description` | text | Project description |
| `partner_id` | FK | Client/customer reference |
| `company_id` | FK | Company reference |
| `user_id` | FK | Project manager |
| `start_date` | date | Project start date |
| `end_date` | date | Target completion |
| `stage_id` | FK | Current project stage |
| `is_active` | boolean | Active/archived status |

---

### 2. `projects_rooms` (Room Level)

Rooms within a project (Kitchen, Bathroom, Laundry, etc.)

| Column | Type | Description |
|--------|------|-------------|
| `id` | bigint | Primary key |
| `project_id` | FK | Parent project |
| `name` | string | Room name (e.g., "Kitchen", "Master Bath") |
| `room_type` | string | Type: kitchen, bathroom, laundry, office |
| `floor_number` | string | Floor location (1, 2, basement) |
| `pdf_page_number` | int | PDF page reference |
| `pdf_room_label` | string | Label on architectural PDF |
| `pdf_detail_number` | string | Architect callout (e.g., "A-3.1") |
| `sort_order` | int | Display order |

**Pricing Columns (aggregated):**
| Column | Type | Description |
|--------|------|-------------|
| `total_linear_feet_tier_1` | decimal | LF at $138/LF (open boxes) |
| `total_linear_feet_tier_2` | decimal | LF at $168/LF (paint grade, shaker) |
| `total_linear_feet_tier_3` | decimal | LF at $192/LF (stain grade) |
| `total_linear_feet_tier_4` | decimal | LF at $210/LF (beaded frames) |
| `total_linear_feet_tier_5` | decimal | LF at $225/LF (custom, reeded) |
| `estimated_project_value` | decimal | Total estimate for room |
| `quoted_price` | decimal | Actual quoted price |

---

### 3. `projects_room_locations` (Location Level)

Specific areas within a room (walls, islands, etc.)

| Column | Type | Description |
|--------|------|-------------|
| `id` | bigint | Primary key |
| `room_id` | FK | Parent room |
| `name` | string | Location name (e.g., "North Wall", "Island") |
| `location_type` | string | Type: wall, island, peninsula, standalone, corner |
| `sequence` | int | Left-to-right order |
| `elevation_reference` | string | Architectural reference |

**Material & Construction:**
| Column | Type | Description |
|--------|------|-------------|
| `material_type` | string | paint_grade, stain_grade, premium |
| `wood_species` | string | hard_maple, poplar, white_oak, walnut |
| `door_style` | string | flat_panel, shaker, beaded, reeded |
| `finish_type` | string | prime_only, painted, stained, clear_coat |
| `paint_color` | string | Paint color (e.g., "BM Simply White") |
| `stain_color` | string | Stain color (e.g., "Natural") |

**Dimensions:**
| Column | Type | Description |
|--------|------|-------------|
| `overall_width_inches` | decimal | Total width |
| `overall_height_inches` | decimal | Total height |
| `overall_depth_inches` | decimal | Total depth |
| `soffit_height_inches` | decimal | Ceiling height |
| `toe_kick_height_inches` | decimal | Toe kick height |

**Hardware Standards:**
| Column | Type | Description |
|--------|------|-------------|
| `hinge_type` | string | Standard hinge type |
| `slide_type` | string | Drawer slide type |
| `soft_close_doors` | boolean | Soft close hinges |
| `soft_close_drawers` | boolean | Soft close slides |

---

### 4. `projects_cabinet_runs` (Run Level)

A continuous series of cabinets (base run, upper run, etc.)

| Column | Type | Description |
|--------|------|-------------|
| `id` | bigint | Primary key |
| `room_location_id` | FK | Parent location |
| `name` | string | Run name (e.g., "Base Run 1") |
| `run_type` | string | Type: base, wall, tall, specialty |
| `total_linear_feet` | decimal | Sum of cabinet widths |
| `cabinet_count` | int | Number of cabinets |

**Material Specifications:**
| Column | Type | Description |
|--------|------|-------------|
| `material_type` | string | Inherited material type |
| `wood_species` | string | Wood species |
| `finish_type` | string | Finish type |
| `sheet_goods_required_sqft` | decimal | Plywood needed |
| `solid_wood_required_bf` | decimal | Board feet needed |

**Production Tracking:**
| Column | Type | Description |
|--------|------|-------------|
| `production_status` | string | pending, material_ordered, cnc_cut, assembly, finishing, complete |
| `cnc_program_file` | string | CNC program filename |
| `cnc_started_at` | timestamp | CNC start time |
| `cnc_completed_at` | timestamp | CNC completion |
| `assembly_started_at` | timestamp | Assembly start |
| `assembly_completed_at` | timestamp | Assembly completion |
| `finishing_started_at` | timestamp | Finishing start |
| `finishing_completed_at` | timestamp | Finishing completion |
| `ready_for_delivery` | boolean | Ready to ship |

**Hardware Kitting:**
| Column | Type | Description |
|--------|------|-------------|
| `blum_hinges_total` | int | Total hinges needed |
| `blum_slides_total` | int | Total slides needed |
| `shelf_pins_total` | int | Total shelf pins |
| `hardware_kitted` | boolean | Hardware prepared |

---

### 5. `projects_cabinets` (Cabinet Level)

Individual cabinet specifications (formerly `projects_cabinet_specifications`)

| Column | Type | Description |
|--------|------|-------------|
| `id` | bigint | Primary key |
| `cabinet_run_id` | FK | Parent run |
| `project_id` | FK | Direct project link |
| `product_variant_id` | FK | Product catalog reference |

**Dimensions:**
| Column | Type | Description |
|--------|------|-------------|
| `length_inches` | decimal(10,4) | Cabinet width |
| `width_inches` | decimal(10,4) | Cabinet depth |
| `height_inches` | decimal(10,4) | Cabinet height |
| `depth_inches` | decimal(10,4) | Depth |
| `linear_feet` | decimal | Width in linear feet |
| `toe_kick_height` | decimal | Toe kick height |
| `toe_kick_depth` | decimal | Toe kick setback |

**Box Construction:**
| Column | Type | Description |
|--------|------|-------------|
| `box_material` | string | 3/4 birch, 3/4 maple, etc. |
| `box_thickness` | decimal | Material thickness |
| `joinery_method` | string | dado, dowel, pocket_screw |
| `has_back_panel` | boolean | Full back panel |

**Face Frame:**
| Column | Type | Description |
|--------|------|-------------|
| `has_face_frame` | boolean | Face frame construction |
| `face_frame_stile_width` | decimal | Vertical stile width |
| `face_frame_rail_width` | decimal | Horizontal rail width |
| `face_frame_material` | string | Face frame wood species |
| `beaded_face_frame` | boolean | Beaded detail |

**Doors:**
| Column | Type | Description |
|--------|------|-------------|
| `door_count` | int | Number of doors |
| `door_style` | string | flat_panel, shaker, beaded, etc. |
| `door_mounting` | string | inset, full_overlay, partial_overlay |
| `door_sizes_json` | JSON | Door dimensions array |
| `has_glass_doors` | boolean | Glass panels |
| `glass_type` | string | clear, seeded, frosted |

**Drawers:**
| Column | Type | Description |
|--------|------|-------------|
| `drawer_count` | int | Number of drawers |
| `drawer_sizes_json` | JSON | Drawer dimensions array |
| `dovetail_drawers` | boolean | Dovetail construction |
| `drawer_box_material` | string | maple, birch, etc. |
| `drawer_soft_close` | boolean | Soft close slides |

**Shelving:**
| Column | Type | Description |
|--------|------|-------------|
| `adjustable_shelf_count` | int | Adjustable shelves |
| `fixed_shelf_count` | int | Fixed shelves |
| `shelf_thickness` | decimal | Shelf thickness |
| `shelf_material` | string | plywood, solid edge |

**Hardware:**
| Column | Type | Description |
|--------|------|-------------|
| `hinge_model` | string | Blum model number |
| `hinge_quantity` | int | Number of hinges |
| `slide_model` | string | Drawer slide model |
| `slide_quantity` | int | Number of slide pairs |
| `specialty_hardware_json` | JSON | Rev-a-Shelf, etc. |

**Accessories:**
| Column | Type | Description |
|--------|------|-------------|
| `has_pullout` | boolean | Pullout shelf |
| `has_lazy_susan` | boolean | Lazy susan |
| `has_tray_dividers` | boolean | Tray dividers |
| `has_spice_rack` | boolean | Spice rack |
| `has_trash_pullout` | boolean | Trash pullout |
| `interior_accessories_json` | JSON | Additional accessories |

**Pricing:**
| Column | Type | Description |
|--------|------|-------------|
| `complexity_tier` | int | 1-5 pricing tier |
| `unit_price_per_lf` | decimal | Price per linear foot |
| `total_price` | decimal | Calculated total |
| `base_price_per_lf` | decimal | Base rate |
| `material_upgrade_per_lf` | decimal | Material upgrade |

**Production:**
| Column | Type | Description |
|--------|------|-------------|
| `cnc_cut_at` | timestamp | CNC completion |
| `assembled_at` | timestamp | Assembly completion |
| `sanded_at` | timestamp | Sanding completion |
| `finished_at` | timestamp | Finishing completion |
| `qc_passed` | boolean | QC inspection |

---

### 6. `projects_cabinet_sections` (Section Level - Optional)

Subdivisions within a cabinet (drawer stacks, door openings, etc.)

| Column | Type | Description |
|--------|------|-------------|
| `id` | bigint | Primary key |
| `cabinet_specification_id` | FK | Parent cabinet |
| `section_number` | int | Section order (1, 2, 3...) |
| `name` | string | "Top Drawer Stack", "Door Opening" |
| `section_type` | string | drawer_stack, door_opening, open_shelving, pullout_area, appliance |
| `width_inches` | decimal | Section width |
| `height_inches` | decimal | Section height |
| `position_from_left_inches` | decimal | Horizontal position |
| `position_from_bottom_inches` | decimal | Vertical position |
| `component_count` | int | Number of components |
| `opening_width_inches` | decimal | Face frame opening width |
| `opening_height_inches` | decimal | Face frame opening height |

---

### 7. `projects_doors` (Component Level)

Individual door components.

| Column | Type | Description |
|--------|------|-------------|
| `id` | bigint | Primary key |
| `cabinet_id` | FK | Parent cabinet |
| `section_id` | FK (nullable) | Parent section |
| `door_number` | int | Door position |
| `door_name` | string | D1, D2, etc. |

**Dimensions:**
| Column | Type | Description |
|--------|------|-------------|
| `width_inches` | decimal | Door width |
| `height_inches` | decimal | Door height |
| `thickness_inches` | decimal | Door thickness |

**Construction:**
| Column | Type | Description |
|--------|------|-------------|
| `rail_width_inches` | decimal | Rail width |
| `style_width_inches` | decimal | Stile width |
| `has_check_rail` | boolean | Center rail |
| `profile_type` | string | shaker, flat_panel, beaded |
| `fabrication_method` | string | cnc, five_piece_manual, slab |

**Hardware:**
| Column | Type | Description |
|--------|------|-------------|
| `hinge_type` | string | Hinge mounting type |
| `hinge_model` | string | Blum model |
| `hinge_quantity` | int | Number of hinges |
| `hinge_side` | string | left, right |
| `has_glass` | boolean | Glass panel |
| `glass_type` | string | clear, seeded, frosted |

**Production:**
| Column | Type | Description |
|--------|------|-------------|
| `cnc_cut_at` | timestamp | CNC cut |
| `edge_banded_at` | timestamp | Edge banding |
| `assembled_at` | timestamp | Assembly |
| `sanded_at` | timestamp | Sanding |
| `finished_at` | timestamp | Finishing |
| `hardware_installed_at` | timestamp | Hinge install |
| `installed_in_cabinet_at` | timestamp | Final install |
| `qc_passed` | boolean | QC status |

---

### 8. `projects_drawers` (Component Level)

Individual drawer components. Each drawer record contains specifications for all three component parts:
- **Drawer Front** - the visible face (dimensions, profile, finish)
- **Drawer Box** - the internal box (dimensions, material, joinery)
- **Drawer Slides** - the hardware (type, model, length)

| Column | Type | Description |
|--------|------|-------------|
| `id` | bigint | Primary key |
| `cabinet_id` | FK | Parent cabinet |
| `section_id` | FK (nullable) | Parent section (the opening) |
| `product_id` | FK (nullable) | Link to drawer product |
| `drawer_number` | int | Drawer position (1, 2, 3...) |
| `drawer_name` | string | DR1, DR2, etc. |
| `full_code` | string | Hierarchical code (e.g., TCS-0554-K1-SW-B1-A-DRW1) |
| `drawer_position` | string | top, middle, bottom |

**Component 1: Drawer Front Dimensions**
| Column | Type | Description |
|--------|------|-------------|
| `front_width_inches` | decimal | Front width |
| `front_height_inches` | decimal | Front height |
| `front_thickness_inches` | decimal | Front thickness |

**Component 1: Drawer Front Construction**
| Column | Type | Description |
|--------|------|-------------|
| `top_rail_width_inches` | decimal | Top rail width |
| `bottom_rail_width_inches` | decimal | Bottom rail width |
| `style_width_inches` | decimal | Vertical stile width |
| `profile_type` | string | shaker, flat_panel, beaded, raised_panel |
| `fabrication_method` | string | cnc, five_piece_manual, slab |

**Component 2: Drawer Box Dimensions**
| Column | Type | Description |
|--------|------|-------------|
| `box_width_inches` | decimal | Box internal width |
| `box_depth_inches` | decimal | Box depth |
| `box_height_inches` | decimal | Box height |

**Component 2: Drawer Box Construction**
| Column | Type | Description |
|--------|------|-------------|
| `box_material` | string | maple, birch, baltic_birch |
| `box_thickness` | decimal | Side thickness |
| `joinery_method` | string | dovetail, pocket_screw, dado, finger |

**Component 3: Drawer Slides (Hardware)**
| Column | Type | Description |
|--------|------|-------------|
| `slide_type` | string | blum_tandem, undermount, full_extension, side_mount |
| `slide_model` | string | Model number |
| `slide_length_inches` | decimal | 18", 21", 24" |
| `slide_quantity` | int | Number of slide pairs |
| `soft_close` | boolean | Soft close feature |
| `slide_product_id` | FK (nullable) | Link to inventory product |

**Finish & Appearance:**
| Column | Type | Description |
|--------|------|-------------|
| `finish_type` | string | painted, stained, clear_coat |
| `paint_color` | string | Paint color if different from cabinet |
| `stain_color` | string | Stain color if different from cabinet |
| `has_decorative_hardware` | boolean | Decorative handle/knob |
| `decorative_hardware_model` | string | Handle/knob model |
| `decorative_hardware_product_id` | FK (nullable) | Link to hardware product |

**Production Tracking:**
| Column | Type | Description |
|--------|------|-------------|
| `cnc_cut_at` | timestamp | CNC cut drawer front |
| `manually_cut_at` | timestamp | Manual cut |
| `edge_banded_at` | timestamp | Edge banding completed |
| `box_assembled_at` | timestamp | Box assembly |
| `front_attached_at` | timestamp | Front attached to box |
| `sanded_at` | timestamp | Sanding completed |
| `finished_at` | timestamp | Finishing |
| `slides_installed_at` | timestamp | Slides installed |
| `installed_in_cabinet_at` | timestamp | Final install |
| `qc_passed` | boolean | QC inspection passed |
| `qc_notes` | text | QC findings |
| `qc_inspected_at` | timestamp | QC inspection date |
| `qc_inspector_id` | FK | QC inspector |

---

### 9. `projects_shelves` (Component Level)

Individual shelf components.

| Column | Type | Description |
|--------|------|-------------|
| `id` | bigint | Primary key |
| `cabinet_id` | FK | Parent cabinet |
| `section_id` | FK (nullable) | Parent section |
| `shelf_number` | int | Shelf position |
| `shelf_name` | string | S1, S2, etc. |

**Dimensions:**
| Column | Type | Description |
|--------|------|-------------|
| `width_inches` | decimal | Shelf width |
| `depth_inches` | decimal | Shelf depth |
| `thickness_inches` | decimal | Material thickness |

**Configuration:**
| Column | Type | Description |
|--------|------|-------------|
| `shelf_type` | string | adjustable, fixed, pullout |
| `material` | string | plywood, solid_edge, melamine |
| `edge_treatment` | string | edge_banded, solid_edge, exposed |

**Adjustable Specific:**
| Column | Type | Description |
|--------|------|-------------|
| `pin_hole_spacing` | decimal | 1.25" or 32mm |
| `number_of_positions` | int | Adjustment positions |

**Pullout Specific:**
| Column | Type | Description |
|--------|------|-------------|
| `slide_type` | string | Slide type |
| `slide_model` | string | Model number |
| `slide_length_inches` | decimal | Slide length |
| `soft_close` | boolean | Soft close |
| `weight_capacity_lbs` | int | Weight rating |

---

### 10. `projects_pullouts` (Component Level)

Specialty pullout components (Rev-A-Shelf, etc.)

| Column | Type | Description |
|--------|------|-------------|
| `id` | bigint | Primary key |
| `cabinet_id` | FK | Parent cabinet |
| `section_id` | FK (nullable) | Parent section |
| `pullout_number` | int | Pullout position |
| `pullout_name` | string | P1, P2, etc. |

**Type & Details:**
| Column | Type | Description |
|--------|------|-------------|
| `pullout_type` | string | trash, spice_rack, tray_divider, lazy_susan, hamper, wine_rack |
| `manufacturer` | string | Rev-a-Shelf, Lemans, etc. |
| `model_number` | string | Part number |
| `description` | text | Detailed description |

**Dimensions:**
| Column | Type | Description |
|--------|------|-------------|
| `width_inches` | decimal | Width |
| `height_inches` | decimal | Height |
| `depth_inches` | decimal | Depth |

**Mounting:**
| Column | Type | Description |
|--------|------|-------------|
| `mounting_type` | string | bottom_mount, side_mount, door_mount |
| `slide_type` | string | If slide-mounted |
| `weight_capacity_lbs` | int | Weight rating |

**Procurement:**
| Column | Type | Description |
|--------|------|-------------|
| `unit_cost` | decimal | Cost per unit |
| `quantity` | int | Number of units |
| `ordered_at` | timestamp | Order date |
| `received_at` | timestamp | Receipt date |

---

## Relationship Summary

```
projects_projects (1)
    └── projects_rooms (N)
            └── projects_room_locations (N)
                    └── projects_cabinet_runs (N)
                            └── projects_cabinets (N)
                                    ├── projects_cabinet_sections (N) [optional grouping]
                                    │       ├── projects_doors (N)
                                    │       ├── projects_drawers (N)
                                    │       ├── projects_shelves (N)
                                    │       └── projects_pullouts (N)
                                    │
                                    └── [direct children - no section]
                                            ├── projects_doors (N)
                                            ├── projects_drawers (N)
                                            ├── projects_shelves (N)
                                            └── projects_pullouts (N)
```

---

## Foreign Key References

| Child Table | Foreign Key | Parent Table |
|-------------|-------------|--------------|
| `projects_rooms` | `project_id` | `projects_projects` |
| `projects_room_locations` | `room_id` | `projects_rooms` |
| `projects_cabinet_runs` | `room_location_id` | `projects_room_locations` |
| `projects_cabinets` | `cabinet_run_id` | `projects_cabinet_runs` |
| `projects_cabinet_sections` | `cabinet_specification_id` | `projects_cabinets` |
| `projects_doors` | `cabinet_id` | `projects_cabinets` |
| `projects_doors` | `section_id` | `projects_cabinet_sections` |
| `projects_drawers` | `cabinet_id` | `projects_cabinets` |
| `projects_drawers` | `section_id` | `projects_cabinet_sections` |
| `projects_shelves` | `cabinet_id` | `projects_cabinets` |
| `projects_shelves` | `section_id` | `projects_cabinet_sections` |
| `projects_pullouts` | `cabinet_id` | `projects_cabinets` |
| `projects_pullouts` | `section_id` | `projects_cabinet_sections` |

---

## Component Type Registry

The `CabinetComponentRegistry` service provides centralized mapping between component types and their database tables. All component models implement the `CabinetComponentInterface` for type-safe handling.

### Architecture Overview

```
┌──────────────────────────────────────────────────────────────────────────────┐
│                      CabinetComponentInterface                                │
│                (Webkul\Project\Contracts\CabinetComponentInterface)          │
├──────────────────────────────────────────────────────────────────────────────┤
│  Required Methods:                                                            │
│  - getComponentCode(): string     (e.g., "DOOR1", "DRW2", "SHELF1")          │
│  - getComponentName(): ?string    (e.g., "Main Door", "Top Drawer")          │
│  - getComponentNumber(): ?int     (e.g., 1, 2, 3)                            │
│  - getComponentType(): string     (e.g., "door", "drawer", "shelf")          │
│  - cabinet(): BelongsTo           (parent cabinet relationship)              │
│  - section(): BelongsTo           (parent section relationship)              │
│  - product(): BelongsTo           (associated product)                        │
│  - hardwareRequirements(): HasMany (hardware links)                           │
│  - scopeOrdered($query)           (order by sort_order)                       │
└──────────────────────────────────────────────────────────────────────────────┘
                                    │
                    ┌───────────────┼───────────────┐
                    │               │               │
                    ▼               ▼               ▼
              ┌─────────┐    ┌─────────┐    ┌─────────────┐
              │  Door   │    │ Drawer  │    │ Shelf/Pullout│
              │ Model   │    │ Model   │    │   Models     │
              └─────────┘    └─────────┘    └─────────────┘
```

### Shared Traits (Already Exist)

All component models use these traits from `Webkul\Support\Traits`:

| Trait | Purpose |
|-------|---------|
| `HasFullCode` | Auto-generates hierarchical codes (requires `getComponentCode()`) |
| `HasComplexityScore` | Complexity calculations and display |
| `HasFormattedDimensions` | Dimension formatting with measurement settings |

### Registry Mapping

```
┌──────────────────────────────────────────────────────────────────────────────┐
│                       CabinetComponentRegistry                                │
│                   (Webkul\Project\Services\CabinetComponentRegistry)         │
├──────────────────────────────────────────────────────────────────────────────┤
│  Component Type  │  Model Class   │  Table Name        │  Relationship       │
├──────────────────┼────────────────┼────────────────────┼─────────────────────┤
│  'door'          │  Door::class   │  projects_doors    │  doors()            │
│  'drawer'        │  Drawer::class │  projects_drawers  │  drawers()          │
│  'shelf'         │  Shelf::class  │  projects_shelves  │  shelves()          │
│  'pullout'       │  Pullout::class│  projects_pullouts │  pullouts()         │
└──────────────────────────────────────────────────────────────────────────────┘
```

### Usage Examples

```php
use Webkul\Project\Contracts\CabinetComponentInterface;
use Webkul\Project\Services\CabinetComponentRegistry;

// Get model class for a type
$modelClass = CabinetComponentRegistry::getModelClass('drawer');
// Returns: Webkul\Project\Models\Drawer::class

// Get table name
$table = CabinetComponentRegistry::getTable('door');
// Returns: 'projects_doors'

// Create component from spec builder JSON (returns CabinetComponentInterface)
$drawer = CabinetComponentRegistry::createFromSpec('drawer', $section, $specData);

// Sync all components in a section
$stats = CabinetComponentRegistry::syncSectionContents($section, $contents);
// Returns: ['created' => 2, 'updated' => 1, 'deleted' => 0]

// Get component type from a model instance
$type = CabinetComponentRegistry::getTypeFromModel($drawerInstance);
// Returns: 'drawer'

// Type-safe component handling
function processComponent(CabinetComponentInterface $component): void {
    $type = $component::getComponentType();     // 'drawer'
    $code = $component->getComponentCode();     // 'DRW1'
    $name = $component->getComponentName();     // 'Top Drawer'
    $section = $component->section;             // CabinetSection model
}

// Check if a model is a component
if (CabinetComponentRegistry::isComponent($model)) {
    // Safe to cast/use as CabinetComponentInterface
}
```

### Section Types and Allowed Components

| Section Type | Label | Allowed Components |
|--------------|-------|-------------------|
| `door` | Door Section | door, shelf |
| `drawer_bank` | Drawer Bank | drawer |
| `open_shelf` | Open Shelf | shelf |
| `appliance` | Appliance Opening | (none) |
| `pullout` | Pullout Section | pullout, shelf |
| `mixed` | Mixed (Doors & Drawers) | door, drawer, shelf, pullout |

---

## Component Preset Tables

Templates for common component configurations used to quickly populate new components.

### 11. `projects_door_presets`

Door configuration templates.

| Column | Type | Description |
|--------|------|-------------|
| `id` | bigint | Primary key |
| `name` | string | Preset name (unique) |
| `description` | text | Description |
| `is_active` | boolean | Available for use |
| `sort_order` | int | Display order |
| `profile_type` | string | shaker, flat_panel, beaded, raised_panel, slab |
| `fabrication_method` | string | cnc, five_piece_manual, slab |
| `hinge_type` | string | blind_inset, half_overlay, euro_concealed |
| `default_hinge_quantity` | int | Default hinge count (usually 2) |
| `has_glass` | boolean | Glass panel |
| `glass_type` | string | clear, seeded, frosted, mullioned, leaded |
| `has_check_rail` | boolean | Center horizontal rail |
| `default_rail_width_inches` | decimal | Default rail width |
| `default_style_width_inches` | decimal | Default stile width |
| `estimated_complexity_score` | decimal | Complexity score |

---

### 12. `projects_drawer_presets`

Drawer configuration templates.

| Column | Type | Description |
|--------|------|-------------|
| `id` | bigint | Primary key |
| `name` | string | Preset name (unique) |
| `description` | text | Description |
| `is_active` | boolean | Available for use |
| `sort_order` | int | Display order |
| `profile_type` | string | shaker, flat_panel, slab |
| `box_material` | string | maple, birch, baltic_birch, plywood |
| `joinery_method` | string | dovetail, pocket_screw, dado, finger |
| `slide_type` | string | blum_tandem, undermount, full_extension, side_mount |
| `slide_model` | string | Default slide model |
| `soft_close` | boolean | Default soft close (usually true) |
| `estimated_complexity_score` | decimal | Complexity score |

---

### 13. `projects_shelf_presets`

Shelf configuration templates.

| Column | Type | Description |
|--------|------|-------------|
| `id` | bigint | Primary key |
| `name` | string | Preset name (unique) |
| `description` | text | Description |
| `is_active` | boolean | Available for use |
| `sort_order` | int | Display order |
| `shelf_type` | string | fixed, adjustable, roll_out, pull_down, corner, floating |
| `material` | string | plywood, melamine, solid_wood |
| `edge_treatment` | string | edge_banded, solid_edge, veneer |
| `slide_type` | string | For roll-out/pull-down |
| `slide_model` | string | Slide model |
| `soft_close` | boolean | Soft close |
| `estimated_complexity_score` | decimal | Complexity score |

---

### 14. `projects_pullout_presets`

Pullout/accessory configuration templates.

| Column | Type | Description |
|--------|------|-------------|
| `id` | bigint | Primary key |
| `name` | string | Preset name (unique) |
| `description` | text | Description |
| `is_active` | boolean | Available for use |
| `sort_order` | int | Display order |
| `pullout_type` | string | trash, spice_rack, lazy_susan, mixer_lift, blind_corner, pantry |
| `manufacturer` | string | Rev-a-Shelf, Hafele, etc. |
| `model_number` | string | Default model number |
| `mounting_type` | string | bottom_mount, side_mount, door_mount |
| `slide_type` | string | Slide type |
| `slide_model` | string | Slide model |
| `soft_close` | boolean | Soft close |
| `product_id` | FK (nullable) | Link to inventory product |
| `estimated_complexity_score` | decimal | Complexity score |

---

## Related Tables (Cross-Reference Tables)

These tables link to multiple levels of the main hierarchy and support operations like material tracking, task assignment, annotations, and sales.

---

### 15. `projects_bom` (Bill of Materials)

Links cabinet specifications to required materials with quantities for ordering and cost tracking.

| Column | Type | Description |
|--------|------|-------------|
| `id` | bigint | Primary key |
| `cabinet_id` | FK (nullable) | Individual cabinet material requirement |
| `cabinet_run_id` | FK (nullable) | Aggregate materials for entire run |
| `product_id` | FK | Material from products/inventory |
| `component_name` | string | What this material is for: box_sides, face_frame, doors |

**Quantity Requirements:**
| Column | Type | Description |
|--------|------|-------------|
| `quantity_required` | decimal | Quantity needed in material UOM |
| `unit_of_measure` | string | BF, SQFT, EA, LF, etc. |
| `waste_factor_percentage` | decimal | Waste/scrap factor % (typically 10-15%) |
| `quantity_with_waste` | decimal | Calculated: quantity × (1 + waste_factor) |

**Dimensioned Calculation (Sheet Goods):**
| Column | Type | Description |
|--------|------|-------------|
| `component_width_inches` | decimal | Width of component |
| `component_height_inches` | decimal | Height/length of component |
| `quantity_of_components` | int | Number of identical pieces |
| `sqft_per_component` | decimal | Calculated square footage per piece |
| `total_sqft_required` | decimal | Total SQFT needed |

**Linear Feet Calculation (Solid Wood):**
| Column | Type | Description |
|--------|------|-------------|
| `linear_feet_per_component` | decimal | Linear feet per piece |
| `total_linear_feet` | decimal | Total LF needed |
| `board_feet_required` | decimal | Calculated BF from LF × thickness × width |

**Cost Tracking:**
| Column | Type | Description |
|--------|------|-------------|
| `unit_cost` | decimal | Cost per UOM from product |
| `total_material_cost` | decimal | Calculated: quantity_with_waste × unit_cost |

**Material Specifications:**
| Column | Type | Description |
|--------|------|-------------|
| `grain_direction` | string | horizontal, vertical, none (affects sheet layout) |
| `requires_edge_banding` | boolean | Exposed edges need banding |
| `edge_banding_sides` | string | all, front_only, front_back |
| `edge_banding_lf` | decimal | Linear feet of edge banding needed |
| `cnc_notes` | text | CNC machining requirements |
| `machining_operations` | text | Required operations: dado, groove, mortise |

**Material Status:**
| Column | Type | Description |
|--------|------|-------------|
| `material_allocated` | boolean | Reserved from inventory |
| `material_allocated_at` | timestamp | When allocated |
| `material_issued` | boolean | Physically issued to production |
| `material_issued_at` | timestamp | When issued |
| `substituted_product_id` | FK (nullable) | Alternative material if primary unavailable |
| `substitution_notes` | text | Why substitution was made |

---

### 16. `hardware_requirements` (Hardware Tracking)

Tracks Blum hinges, drawer slides, shelf pins, Rev-a-Shelf accessories at ANY level of the hierarchy.

| Column | Type | Description |
|--------|------|-------------|
| `id` | bigint | Primary key |
| `product_id` | FK | Hardware product from inventory |

**Hierarchy Links (one or more can be filled):**
| Column | Type | Description |
|--------|------|-------------|
| `room_id` | FK (nullable) | Room-level hardware assignment |
| `room_location_id` | FK (nullable) | Location-level hardware |
| `cabinet_run_id` | FK (nullable) | Aggregate hardware for run |
| `cabinet_id` | FK (nullable) | Individual cabinet hardware |

**Component-Level Links (for specific components):**
| Column | Type | Description |
|--------|------|-------------|
| `door_id` | FK (nullable) | Specific door (for hinges) |
| `drawer_id` | FK (nullable) | Specific drawer (for slides) |
| `shelf_id` | FK (nullable) | Specific shelf (for shelf hardware) |
| `pullout_id` | FK (nullable) | Specific pullout (for accessory hardware) |

**Hardware Classification:**
| Column | Type | Description |
|--------|------|-------------|
| `hardware_type` | string | hinge, slide, shelf_pin, pullout, lazy_susan, organizer, knob, pull |
| `manufacturer` | string | Blum, Rev-a-Shelf, etc. |
| `model_number` | string | 71B9790, 562H-11CR-1, etc. |
| `quantity_required` | int | Number of units needed |
| `unit_of_measure` | string | Usually EA for hardware |

**Application Details:**
| Column | Type | Description |
|--------|------|-------------|
| `applied_to` | string | door, drawer, shelf, corner |
| `door_number` | int | Which door if multiple |
| `drawer_number` | int | Which drawer if multiple |
| `mounting_location` | string | left, right, top, bottom, center |

**Hinge Specifications:**
| Column | Type | Description |
|--------|------|-------------|
| `hinge_type` | string | concealed, overlay, inset, soft_close |
| `hinge_opening_angle` | int | 110, 120, 170 degrees |
| `overlay_dimension_mm` | decimal | Overlay in mm |

**Slide Specifications:**
| Column | Type | Description |
|--------|------|-------------|
| `slide_type` | string | undermount, side_mount, soft_close, push_to_open |
| `slide_length_inches` | decimal | 12, 15, 18, 21, 24 inches |
| `slide_weight_capacity_lbs` | int | Weight capacity |

**Shelf Pin Specifications:**
| Column | Type | Description |
|--------|------|-------------|
| `shelf_pin_type` | string | standard, glass, metal, plastic |
| `shelf_pin_diameter_mm` | decimal | Pin diameter (typically 5mm) |

**Accessory Specifications:**
| Column | Type | Description |
|--------|------|-------------|
| `accessory_width_inches` | decimal | Width (Rev-a-Shelf sizing) |
| `accessory_depth_inches` | decimal | Depth |
| `accessory_height_inches` | decimal | Height |
| `accessory_configuration` | string | single, double, chrome, maple |

**Finish/Color:**
| Column | Type | Description |
|--------|------|-------------|
| `finish` | string | nickel, chrome, oil_rubbed_bronze |
| `color_match` | string | cabinet_finish, stainless |

**Cost Tracking:**
| Column | Type | Description |
|--------|------|-------------|
| `unit_cost` | decimal | Cost per unit |
| `total_hardware_cost` | decimal | Calculated: quantity × unit_cost |

**Installation:**
| Column | Type | Description |
|--------|------|-------------|
| `installation_notes` | text | Special installation considerations |
| `install_sequence` | int | Order of installation |
| `requires_jig` | boolean | Requires installation jig/template |
| `jig_name` | string | Which jig to use |

**Kitting & Assembly:**
| Column | Type | Description |
|--------|------|-------------|
| `hardware_kitted` | boolean | Included in hardware kit |
| `hardware_kitted_at` | timestamp | When added to kit |
| `hardware_installed` | boolean | Installed on cabinet |
| `hardware_installed_at` | timestamp | When installed |
| `installed_by_user_id` | FK | Craftsman who installed |

**Material Status:**
| Column | Type | Description |
|--------|------|-------------|
| `hardware_allocated` | boolean | Reserved from inventory |
| `hardware_allocated_at` | timestamp | When allocated |
| `hardware_issued` | boolean | Physically issued |
| `hardware_issued_at` | timestamp | When issued |
| `substituted_product_id` | FK (nullable) | Alternative hardware |
| `substitution_reason` | text | Why substitution was made |

**Defects/Returns:**
| Column | Type | Description |
|--------|------|-------------|
| `has_defect` | boolean | Hardware item defective |
| `defect_description` | text | Description of defect |
| `returned_to_supplier` | boolean | Returned for replacement |

---

### 17. `pdf_page_annotations` (PDF Annotations)

Stores boxes drawn on PDF pages with hierarchical relationships to cabinet data.

**Hierarchy:**
- Level 1: Cabinet Run box (parent_annotation_id = null, links to cabinet_run_id)
- Level 2: Individual Cabinet boxes (parent_annotation_id = run box, links to cabinet_id)

| Column | Type | Description |
|--------|------|-------------|
| `id` | bigint | Primary key |
| `pdf_page_id` | FK | Which PDF page this annotation is on |
| `parent_annotation_id` | FK (nullable) | Parent annotation (null for top-level) |
| `annotation_type` | string | cabinet_run, cabinet, note, room |
| `label` | string | User-provided label |

**Bounding Box Coordinates:**
| Column | Type | Description |
|--------|------|-------------|
| `x` | decimal | X coordinate (left edge) in PDF units |
| `y` | decimal | Y coordinate (top edge) in PDF units |
| `width` | decimal | Width in PDF units |
| `height` | decimal | Height in PDF units |
| `measurement_width` | decimal | Actual measured width in inches |
| `measurement_height` | decimal | Actual measured height in inches |

**Linked Entities:**
| Column | Type | Description |
|--------|------|-------------|
| `room_id` | FK (nullable) | Link to room record |
| `room_location_id` | FK (nullable) | Link to room location record |
| `cabinet_run_id` | FK (nullable) | Link to cabinet run record |
| `cabinet_id` | FK (nullable) | Link to cabinet record |

**Visual Properties:**
| Column | Type | Description |
|--------|------|-------------|
| `room_type` | string | Room type for color coding |
| `color` | string | Annotation color |
| `visual_properties` | JSON | Color, stroke width, style |
| `nutrient_annotation_id` | text | Nutrient SDK annotation ID |
| `nutrient_data` | JSON | Full Nutrient annotation data |
| `metadata` | JSON | Additional metadata |
| `notes` | text | User notes |

---

### 18. `projects_tasks` (Task Management)

Tasks can be assigned to any level of the hierarchy.

| Column | Type | Description |
|--------|------|-------------|
| `id` | bigint | Primary key |
| `title` | string | Task title |
| `description` | text | Task description |
| `state` | string | Task state/status |
| `priority` | boolean | Priority flag |
| `deadline` | datetime | Task deadline |

**Time Tracking:**
| Column | Type | Description |
|--------|------|-------------|
| `allocated_hours` | decimal | Planned hours |
| `remaining_hours` | decimal | Remaining hours |
| `effective_hours` | decimal | Actual hours worked |
| `total_hours_spent` | decimal | Total hours |
| `overtime` | decimal | Overtime hours |
| `progress` | decimal | Completion percentage |

**Hierarchy Assignments:**
| Column | Type | Description |
|--------|------|-------------|
| `project_id` | FK | Assigned to project |
| `milestone_id` | FK | Assigned to milestone |
| `stage_id` | FK | Task stage |
| `parent_id` | FK (self) | Parent task (subtask support) |

**Cabinet Hierarchy Assignments:**
| Column | Type | Description |
|--------|------|-------------|
| `cabinet_id` | FK (nullable) | Assigned to cabinet |
| `section_id` | FK (nullable) | Assigned to cabinet section |
| `component_type` | string | door, drawer, shelf, pullout |
| `component_id` | bigint | Polymorphic component ID |

**Other Relationships:**
| Column | Type | Description |
|--------|------|-------------|
| `partner_id` | FK | Related partner/customer |
| `company_id` | FK | Company |
| `creator_id` | FK | Task creator |

---

### 19. `sales_order_line_items` (Order Line Items)

Detailed breakdown of what's being sold, linked to project entities.

| Column | Type | Description |
|--------|------|-------------|
| `id` | bigint | Primary key |
| `sales_order_id` | FK | Parent order |
| `sequence` | int | Display order on invoice |
| `line_item_type` | string | room, location, cabinet_run, cabinet, product, service, custom |

**Project Entity Links (one will be filled):**
| Column | Type | Description |
|--------|------|-------------|
| `project_id` | FK (nullable) | Project this line item belongs to |
| `room_id` | FK (nullable) | Room if room-level |
| `room_location_id` | FK (nullable) | Location if location-level |
| `cabinet_run_id` | FK (nullable) | Cabinet run if run-level |
| `cabinet_id` | FK (nullable) | Individual cabinet if cabinet-level |
| `product_id` | FK (nullable) | Inventory product if applicable |

**Description:**
| Column | Type | Description |
|--------|------|-------------|
| `description` | string | Line item description |
| `detailed_description` | text | Longer description with specifications |

**Quantity & Pricing:**
| Column | Type | Description |
|--------|------|-------------|
| `quantity` | decimal | Quantity of units |
| `unit_of_measure` | string | EA, LF, BF, SQFT, HR |
| `unit_price` | decimal | Price per unit |
| `subtotal` | decimal | quantity × unit_price |
| `discount_percentage` | decimal | Discount % |
| `discount_amount` | decimal | Calculated discount |
| `line_total` | decimal | Final line total |

**Tax:**
| Column | Type | Description |
|--------|------|-------------|
| `taxable` | boolean | Is this item taxable |
| `tax_rate` | decimal | Tax rate % |
| `tax_amount` | decimal | Calculated tax |

**Linear Feet Pricing (Woodworking):**
| Column | Type | Description |
|--------|------|-------------|
| `linear_feet` | decimal | Linear feet for this item |
| `complexity_tier` | int | 1-5 pricing tier |
| `base_rate_per_lf` | decimal | Base $/LF rate |
| `material_rate_per_lf` | decimal | Material upgrade $/LF |
| `combined_rate_per_lf` | decimal | Combined rate |

**Material Details:**
| Column | Type | Description |
|--------|------|-------------|
| `material_type` | string | Material description for client |
| `wood_species` | string | Wood species for client |
| `finish_type` | string | Finish description |
| `features_list` | text | Bulleted list of features |
| `hardware_list` | text | Hardware included |

**Notes:**
| Column | Type | Description |
|--------|------|-------------|
| `client_notes` | text | Notes visible to client |
| `internal_notes` | text | Internal notes (not visible) |

**Production Linkage:**
| Column | Type | Description |
|--------|------|-------------|
| `requires_production` | boolean | Does this need to be built |
| `production_status` | string | pending, in_progress, completed |
| `production_completed_at` | timestamp | When production completed |

---

## Inventory Tables

The inventory system tracks materials, stock levels, warehouses, and movements.

### Inventory Hierarchy

```
┌─────────────────────────────────────────────────────────────────────────┐
│                       inventories_warehouses                             │
│                   (Physical warehouse locations)                         │
└─────────────────────────────────────────────────────────────────────────┘
                                    │
                                    │ 1:N (warehouse_id)
                                    ▼
┌─────────────────────────────────────────────────────────────────────────┐
│                       inventories_locations                              │
│           (Bins, shelves, staging areas within warehouse)               │
└─────────────────────────────────────────────────────────────────────────┘
                                    │
                    ┌───────────────┼───────────────┐
                    │               │               │
                    ▼               ▼               ▼
            ┌─────────────┐ ┌─────────────┐ ┌─────────────┐
            │ inventories │ │ inventories │ │ inventories │
            │  _product   │ │   _lots     │ │   _moves    │
            │ _quantities │ │             │ │             │
            └─────────────┘ └─────────────┘ └─────────────┘
```

---

### 20. `inventories_warehouses` (Warehouse)

Physical warehouse locations.

| Column | Type | Description |
|--------|------|-------------|
| `id` | bigint | Primary key |
| `name` | string | Warehouse name |
| `code` | string | Short code |
| `sort` | int | Display order |
| `reception_steps` | string | Receiving workflow steps |
| `delivery_steps` | string | Delivery workflow steps |
| `company_id` | FK | Company |
| `partner_address_id` | FK | Address reference |

---

### 21. `inventories_locations` (Storage Location)

Specific storage locations within warehouses (bins, shelves, staging areas).

| Column | Type | Description |
|--------|------|-------------|
| `id` | bigint | Primary key |
| `name` | string | Location name |
| `full_name` | string | Full hierarchical name |
| `type` | string | Location type |
| `description` | string | Description |
| `barcode` | string | Barcode for scanning |

**Position (X/Y/Z):**
| Column | Type | Description |
|--------|------|-------------|
| `position_x` | int | Corridor X coordinate |
| `position_y` | int | Shelves Y coordinate |
| `position_z` | int | Height Z coordinate |

**Hierarchy:**
| Column | Type | Description |
|--------|------|-------------|
| `parent_id` | FK (self) | Parent location |
| `parent_path` | string | Full path hierarchy |
| `warehouse_id` | FK | Parent warehouse |
| `storage_category_id` | FK | Storage category |

**Inventory Settings:**
| Column | Type | Description |
|--------|------|-------------|
| `removal_strategy` | string | FIFO, LIFO, etc. |
| `cyclic_inventory_frequency` | int | Days between counts |
| `last_inventory_date` | date | Last inventory count |
| `next_inventory_date` | date | Scheduled next count |

**Flags:**
| Column | Type | Description |
|--------|------|-------------|
| `is_scrap` | boolean | Scrap location |
| `is_replenish` | boolean | Replenishment location |
| `is_dock` | boolean | Dock/staging area |

---

### 22. `inventories_product_quantities` (Stock Levels)

Current stock quantities per product/location/lot.

| Column | Type | Description |
|--------|------|-------------|
| `id` | bigint | Primary key |
| `product_id` | FK | Product reference |
| `location_id` | FK | Storage location |
| `lot_id` | FK (nullable) | Lot/batch reference |
| `package_id` | FK (nullable) | Package reference |

**Quantities:**
| Column | Type | Description |
|--------|------|-------------|
| `quantity` | decimal(15,4) | Current on-hand quantity |
| `reserved_quantity` | decimal(15,4) | Reserved for orders |
| `counted_quantity` | decimal(15,4) | Last counted quantity |
| `difference_quantity` | decimal(15,4) | Count variance |
| `inventory_diff_quantity` | decimal(15,4) | Inventory adjustment |
| `inventory_quantity_set` | boolean | Quantity manually set |

**Scheduling:**
| Column | Type | Description |
|--------|------|-------------|
| `scheduled_at` | date | Scheduled receipt date |
| `incoming_at` | datetime | Expected arrival |

**Other:**
| Column | Type | Description |
|--------|------|-------------|
| `storage_category_id` | FK | Storage category |
| `partner_id` | FK | Owner/consignee |
| `user_id` | FK | Assigned user |
| `company_id` | FK | Company |

---

### 23. `inventories_lots` (Lot/Batch Tracking)

Lot and batch numbers for traceability.

| Column | Type | Description |
|--------|------|-------------|
| `id` | bigint | Primary key |
| `name` | string | Lot/batch number |
| `description` | text | Description |
| `reference` | string | External reference |
| `properties` | JSON | Additional properties |

**Product/Location:**
| Column | Type | Description |
|--------|------|-------------|
| `product_id` | FK | Product reference |
| `uom_id` | FK | Unit of measure |
| `location_id` | FK | Primary location |

**Expiration Tracking:**
| Column | Type | Description |
|--------|------|-------------|
| `expiration_date` | datetime | Expiration date |
| `use_date` | datetime | Best use by date |
| `removal_date` | datetime | Remove from stock date |
| `alert_date` | datetime | Alert trigger date |
| `expiry_reminded` | boolean | Alert sent |

---

### 24. `inventories_operations` (Inventory Operations)

Receiving, shipping, and transfer operations.

| Column | Type | Description |
|--------|------|-------------|
| `id` | bigint | Primary key |
| `name` | string | Operation name/number |
| `description` | text | Description |
| `origin` | string | Source document reference |
| `move_type` | string | direct, one_step, two_step |
| `state` | string | draft, waiting, ready, done, cancel |

**Locations:**
| Column | Type | Description |
|--------|------|-------------|
| `source_location_id` | FK | Source location |
| `destination_location_id` | FK | Destination location |
| `operation_type_id` | FK | Operation type |

**Scheduling:**
| Column | Type | Description |
|--------|------|-------------|
| `scheduled_at` | datetime | Scheduled date |
| `deadline` | datetime | Due date |
| `closed_at` | datetime | Completion date |
| `has_deadline_issue` | boolean | Past due flag |

**Related Operations:**
| Column | Type | Description |
|--------|------|-------------|
| `back_order_id` | FK (self) | Back order reference |
| `return_id` | FK (self) | Return operation |
| `partner_id` | FK | Vendor/Customer |

**Status:**
| Column | Type | Description |
|--------|------|-------------|
| `is_favorite` | boolean | Starred |
| `is_printed` | boolean | Documents printed |
| `is_locked` | boolean | Locked from changes |

---

### 25. `inventories_moves` (Stock Moves)

Individual stock movement records.

| Column | Type | Description |
|--------|------|-------------|
| `id` | bigint | Primary key |
| `name` | string | Move description |
| `state` | string | draft, waiting, confirmed, assigned, done, cancel |
| `origin` | string | Source document |
| `reference` | string | Reference number |
| `procure_method` | string | make_to_stock, make_to_order |

**Product & Quantities:**
| Column | Type | Description |
|--------|------|-------------|
| `product_id` | FK | Product |
| `uom_id` | FK | Unit of measure |
| `product_qty` | decimal(15,4) | Quantity in product UOM |
| `product_uom_qty` | decimal(15,4) | Quantity in move UOM |
| `quantity` | decimal(15,4) | Actual moved quantity |

**Locations:**
| Column | Type | Description |
|--------|------|-------------|
| `source_location_id` | FK | Source location |
| `destination_location_id` | FK | Destination location |
| `final_location_id` | FK | Final destination |
| `warehouse_id` | FK | Warehouse |

**Scheduling:**
| Column | Type | Description |
|--------|------|-------------|
| `scheduled_at` | datetime | Scheduled date |
| `deadline` | datetime | Due date |
| `reservation_date` | date | Reservation date |
| `alert_date` | datetime | Alert date |

**Related Records:**
| Column | Type | Description |
|--------|------|-------------|
| `operation_id` | FK | Parent operation |
| `operation_type_id` | FK | Operation type |
| `rule_id` | FK | Procurement rule |
| `scrap_id` | FK | If scrapped |
| `package_level_id` | FK | Package level |
| `product_packaging_id` | FK | Packaging |
| `origin_returned_move_id` | FK (self) | Original move if return |

**Flags:**
| Column | Type | Description |
|--------|------|-------------|
| `is_favorite` | boolean | Starred |
| `is_picked` | boolean | Picked |
| `is_scraped` | boolean | Scrapped |
| `is_inventory` | boolean | Inventory adjustment |

**Serial Numbers:**
| Column | Type | Description |
|--------|------|-------------|
| `next_serial` | string | Next serial number |
| `next_serial_count` | int | Serial count |
| `description_picking` | text | Picking notes |

---

### 26. `inventories_move_lines` (Move Line Detail)

Detailed line items for stock moves (lot/serial level).

| Column | Type | Description |
|--------|------|-------------|
| `id` | bigint | Primary key |
| `move_id` | FK | Parent move |
| `operation_id` | FK | Parent operation |
| `product_id` | FK | Product |
| `uom_id` | FK | Unit of measure |

**Quantities:**
| Column | Type | Description |
|--------|------|-------------|
| `qty` | decimal(15,4) | Quantity |
| `uom_qty` | decimal(15,4) | UOM quantity |
| `is_picked` | boolean | Picked flag |

**Locations:**
| Column | Type | Description |
|--------|------|-------------|
| `source_location_id` | FK | Source location |
| `destination_location_id` | FK | Destination location |

**Lot/Serial:**
| Column | Type | Description |
|--------|------|-------------|
| `lot_id` | FK | Lot reference |
| `lot_name` | string | Lot name (for new lots) |

**Packaging:**
| Column | Type | Description |
|--------|------|-------------|
| `package_id` | FK | Source package |
| `result_package_id` | FK | Destination package |
| `package_level_id` | FK | Package level |

**Other:**
| Column | Type | Description |
|--------|------|-------------|
| `state` | string | Line state |
| `reference` | string | Reference |
| `picking_description` | string | Picking notes |
| `scheduled_at` | datetime | Scheduled date |
| `partner_id` | FK | Partner |

---

### 27. `tcs_material_inventory_mappings` (TCS Material Mapping)

Maps TCS pricing material categories to actual inventory products for BOM generation.

| Column | Type | Description |
|--------|------|-------------|
| `id` | bigint | Primary key |
| `tcs_material_slug` | string | TCS category: paint_grade, stain_grade, premium, custom_exotic |
| `wood_species` | string | Specific wood: Hard Maple, Poplar, Red Oak, etc. |
| `inventory_product_id` | FK | Actual inventory product SKU |
| `material_category_id` | FK | Woodworking category (Sheet Goods, Solid Wood) |

**Usage Multipliers:**
| Column | Type | Description |
|--------|------|-------------|
| `board_feet_per_lf` | decimal | BF per linear foot (solid wood) |
| `sheet_sqft_per_lf` | decimal | SQFT per linear foot (sheet goods) |

**Material Type Flags:**
| Column | Type | Description |
|--------|------|-------------|
| `is_box_material` | boolean | Used for cabinet boxes |
| `is_face_frame_material` | boolean | Used for face frames |
| `is_door_material` | boolean | Used for doors/drawers |

**Selection:**
| Column | Type | Description |
|--------|------|-------------|
| `priority` | int | Selection priority (lower = preferred) |
| `is_active` | boolean | Available for use |
| `notes` | text | Notes |

**TCS Material Categories:**
- **Paint Grade** (+$138/LF): Hard Maple, Poplar, Birch Plywood
- **Stain Grade** (+$156/LF): Red Oak, White Oak, Hard Maple (Stain)
- **Premium** (+$185/LF): Rifted White Oak, Black Walnut, Cherry
- **Custom/Exotic** (Price TBD): Specialty woods

---

## Complete Foreign Key Reference

| Child Table | Foreign Key | Parent Table | Notes |
|-------------|-------------|--------------|-------|
| `projects_rooms` | `project_id` | `projects_projects` | |
| `projects_room_locations` | `room_id` | `projects_rooms` | |
| `projects_cabinet_runs` | `room_location_id` | `projects_room_locations` | |
| `projects_cabinets` | `cabinet_run_id` | `projects_cabinet_runs` | |
| `projects_cabinet_sections` | `cabinet_specification_id` | `projects_cabinets` | |
| `projects_doors` | `cabinet_id` | `projects_cabinets` | |
| `projects_doors` | `section_id` | `projects_cabinet_sections` | Nullable |
| `projects_drawers` | `cabinet_id` | `projects_cabinets` | |
| `projects_drawers` | `section_id` | `projects_cabinet_sections` | Nullable |
| `projects_shelves` | `cabinet_id` | `projects_cabinets` | |
| `projects_shelves` | `section_id` | `projects_cabinet_sections` | Nullable |
| `projects_pullouts` | `cabinet_id` | `projects_cabinets` | |
| `projects_pullouts` | `section_id` | `projects_cabinet_sections` | Nullable |
| `projects_bom` | `cabinet_id` | `projects_cabinets` | Nullable |
| `projects_bom` | `cabinet_run_id` | `projects_cabinet_runs` | Nullable |
| `projects_bom` | `product_id` | `products_products` | |
| `hardware_requirements` | `room_id` | `projects_rooms` | Nullable |
| `hardware_requirements` | `room_location_id` | `projects_room_locations` | Nullable |
| `hardware_requirements` | `cabinet_id` | `projects_cabinets` | Nullable |
| `hardware_requirements` | `cabinet_run_id` | `projects_cabinet_runs` | Nullable |
| `hardware_requirements` | `product_id` | `products_products` | |
| `pdf_page_annotations` | `pdf_page_id` | `pdf_pages` | |
| `pdf_page_annotations` | `parent_annotation_id` | `pdf_page_annotations` | Self-reference, Nullable |
| `pdf_page_annotations` | `room_id` | `projects_rooms` | Nullable |
| `pdf_page_annotations` | `cabinet_run_id` | `projects_cabinet_runs` | Nullable |
| `pdf_page_annotations` | `cabinet_id` | `projects_cabinets` | Nullable |
| `projects_tasks` | `project_id` | `projects_projects` | Nullable |
| `projects_tasks` | `cabinet_id` | `projects_cabinets` | Nullable |
| `projects_tasks` | `section_id` | `projects_cabinet_sections` | Nullable |
| `sales_order_line_items` | `sales_order_id` | `sales_orders` | |
| `sales_order_line_items` | `project_id` | `projects_projects` | Nullable |
| `sales_order_line_items` | `room_id` | `projects_rooms` | Nullable |
| `sales_order_line_items` | `room_location_id` | `projects_room_locations` | Nullable |
| `sales_order_line_items` | `cabinet_run_id` | `projects_cabinet_runs` | Nullable |
| `sales_order_line_items` | `cabinet_id` | `projects_cabinets` | Nullable |
| **Component Product Links** | | | |
| `projects_doors` | `hinge_product_id` | `products_products` | Nullable |
| `projects_doors` | `decorative_hardware_product_id` | `products_products` | Nullable |
| `projects_drawers` | `product_id` | `products_products` | Nullable |
| `projects_drawers` | `slide_product_id` | `products_products` | Nullable |
| `projects_drawers` | `decorative_hardware_product_id` | `products_products` | Nullable |
| `projects_shelves` | `slide_product_id` | `products_products` | Nullable |
| `projects_pullouts` | `slide_product_id` | `products_products` | Nullable |
| `projects_cabinet_sections` | `product_id` | `products_products` | Nullable |
| `projects_cabinet_sections` | `hardware_product_id` | `products_products` | Nullable |
| `projects_cabinet_runs` | `default_hinge_product_id` | `products_products` | Nullable |
| `projects_cabinet_runs` | `default_slide_product_id` | `products_products` | Nullable |
| **Component-Level Hardware Links** | | | |
| `hardware_requirements` | `door_id` | `projects_doors` | Nullable |
| `hardware_requirements` | `drawer_id` | `projects_drawers` | Nullable |
| `hardware_requirements` | `shelf_id` | `projects_shelves` | Nullable |
| `hardware_requirements` | `pullout_id` | `projects_pullouts` | Nullable |
| **Preset Tables** | | | |
| `projects_pullout_presets` | `product_id` | `products_products` | Nullable |
| **Inventory Tables** | | | |
| `inventories_locations` | `warehouse_id` | `inventories_warehouses` | Nullable |
| `inventories_locations` | `parent_id` | `inventories_locations` | Self-reference, Nullable |
| `inventories_locations` | `storage_category_id` | `inventories_storage_categories` | Nullable |
| `inventories_product_quantities` | `product_id` | `products_products` | |
| `inventories_product_quantities` | `location_id` | `inventories_locations` | |
| `inventories_product_quantities` | `lot_id` | `inventories_lots` | Nullable |
| `inventories_product_quantities` | `package_id` | `inventories_packages` | Nullable |
| `inventories_lots` | `product_id` | `products_products` | |
| `inventories_lots` | `location_id` | `inventories_locations` | Nullable |
| `inventories_operations` | `operation_type_id` | `inventories_operation_types` | |
| `inventories_operations` | `source_location_id` | `inventories_locations` | |
| `inventories_operations` | `destination_location_id` | `inventories_locations` | |
| `inventories_operations` | `back_order_id` | `inventories_operations` | Self-reference, Nullable |
| `inventories_moves` | `operation_id` | `inventories_operations` | Nullable |
| `inventories_moves` | `product_id` | `products_products` | |
| `inventories_moves` | `source_location_id` | `inventories_locations` | |
| `inventories_moves` | `destination_location_id` | `inventories_locations` | |
| `inventories_moves` | `warehouse_id` | `inventories_warehouses` | Nullable |
| `inventories_move_lines` | `move_id` | `inventories_moves` | Nullable |
| `inventories_move_lines` | `operation_id` | `inventories_operations` | Nullable |
| `inventories_move_lines` | `product_id` | `products_products` | |
| `inventories_move_lines` | `lot_id` | `inventories_lots` | Nullable |
| `inventories_move_lines` | `source_location_id` | `inventories_locations` | |
| `inventories_move_lines` | `destination_location_id` | `inventories_locations` | |
| `tcs_material_inventory_mappings` | `inventory_product_id` | `products_products` | Nullable |
| `tcs_material_inventory_mappings` | `material_category_id` | `woodworking_material_categories` | Nullable |
| **Purchasing Tables** | | | |
| `purchases_orders` | `partner_id` | `partners_partners` | Vendor |
| `purchases_orders` | `requisition_id` | `purchases_requisitions` | Nullable |
| `purchases_order_lines` | `order_id` | `purchases_orders` | |
| `purchases_order_lines` | `product_id` | `products_products` | Nullable |
| `purchases_order_operations` | `purchase_order_id` | `purchases_orders` | |
| `purchases_order_operations` | `inventory_operation_id` | `inventories_operations` | |
| `inventories_moves` | `purchase_order_line_id` | `purchases_order_lines` | Nullable |
| **Task Tables** | | | |
| `projects_tasks` | `room_id` | `projects_rooms` | Nullable |
| `projects_tasks` | `room_location_id` | `projects_room_locations` | Nullable |
| `projects_tasks` | `cabinet_run_id` | `projects_cabinet_runs` | Nullable |
| `projects_task_users` | `task_id` | `projects_tasks` | |
| `projects_task_users` | `user_id` | `users` | |
| **Material Reservations** | | | |
| `projects_material_reservations` | `project_id` | `projects_projects` | |
| `projects_material_reservations` | `bom_id` | `projects_bom` | Nullable |
| `projects_material_reservations` | `product_id` | `products_products` | |
| `projects_material_reservations` | `warehouse_id` | `inventories_warehouses` | |
| `projects_material_reservations` | `move_id` | `inventories_moves` | Nullable |

---

## Complete System Flow

This section describes the end-to-end flow from project creation through to material management.

### Flow Diagram

```
┌─────────────────────────────────────────────────────────────────────────────────────┐
│                         1. PROJECT CREATION & DESIGN                                 │
└─────────────────────────────────────────────────────────────────────────────────────┘

┌─────────────────────┐     ┌──────────────────┐     ┌──────────────────────┐
│  projects_projects  │────▶│  projects_rooms  │────▶│ projects_room_locations│
├─────────────────────┤     └──────────────────┘     └──────────────────────┘
│ project_number      │                                        │
│ client_id (partner) │                                        ▼
│ status              │     ┌──────────────────────────────────────────────┐
└─────────────────────┘     │          projects_cabinet_runs               │
                            ├──────────────────────────────────────────────┤
                            │ linear_feet, material_type, wood_species     │
                            └──────────────────────────────────────────────┘
                                               │
                                               ▼
┌──────────────────────────────────────────────────────────────────────────────────────┐
│  projects_cabinets → projects_cabinet_sections → doors/drawers/shelves/pullouts      │
└──────────────────────────────────────────────────────────────────────────────────────┘

                            ┌────────────────────────────────────────────────────────┐
                            │                 2. SALES/QUOTING                        │
                            └────────────────────────────────────────────────────────┘

┌────────────────────┐         ┌─────────────────────────────────────────────────────┐
│    sales_orders    │────────▶│           sales_order_line_items                    │
├────────────────────┤         ├─────────────────────────────────────────────────────┤
│ partner_id         │         │ project_id          → links to project              │
│ total_amount       │         │ room_id             → links to room                 │
│ state              │         │ cabinet_run_id      → links to run                  │
│                    │         │ linear_feet         → pricing based on LF           │
│                    │         │ material_rate_per_lf→ material upgrade cost         │
│                    │         │ wood_species        │                               │
└────────────────────┘         └─────────────────────────────────────────────────────┘

                            ┌────────────────────────────────────────────────────────┐
                            │            3. BILL OF MATERIALS (BOM)                   │
                            └────────────────────────────────────────────────────────┘

┌────────────────────────────────────────────────────────────────────────────────────┐
│                              projects_bom                                           │
├────────────────────────────────────────────────────────────────────────────────────┤
│ cabinet_id          → individual cabinet material                                   │
│ cabinet_run_id      → aggregate for entire run                                      │
│ product_id          → LINKS TO products_products (material)                         │
│ component_name      → "box_sides", "face_frame", "doors"                           │
│ quantity_required   → amount needed                                                 │
│ quantity_with_waste → includes waste factor                                         │
│ total_sqft_required → for sheet goods                                              │
│ board_feet_required → for solid wood                                               │
│ material_allocated  → reserved from inventory                                       │
│ material_issued     → physically issued to production                               │
└────────────────────────────────────────────────────────────────────────────────────┘
          │
          │ (when material needed)
          ▼
┌────────────────────────────────────────────────────────────────────────────────────┐
│                        projects_material_reservations                               │
├────────────────────────────────────────────────────────────────────────────────────┤
│ project_id          → which project                                                 │
│ bom_id              → which BOM line                                               │
│ product_id          → which product                                                │
│ warehouse_id        → which warehouse                                              │
│ quantity_reserved   → how much reserved                                            │
│ status              → pending, reserved, issued, cancelled                         │
│ move_id             → LINKS TO inventories_moves when issued                       │
└────────────────────────────────────────────────────────────────────────────────────┘

                            ┌────────────────────────────────────────────────────────┐
                            │                4. PURCHASING                            │
                            └────────────────────────────────────────────────────────┘
                            (When materials not in stock)

┌────────────────────────┐         ┌─────────────────────────────────────────────────┐
│   purchases_orders     │────────▶│         purchases_order_lines                   │
├────────────────────────┤         ├─────────────────────────────────────────────────┤
│ name (PO-00001)        │         │ product_id    → products_products               │
│ partner_id (vendor)    │         │ product_qty   → amount ordered                  │
│ receipt_status         │         │ qty_received  → amount received so far          │
│ state                  │         │ price_unit    → cost per unit                   │
└────────────────────────┘         └─────────────────────────────────────────────────┘
          │                                        │
          │ (many-to-many)                         │ (direct FK)
          ▼                                        ▼
┌─────────────────────────────────┐    ┌─────────────────────────────────────────────┐
│ purchases_order_operations      │    │ inventories_moves                            │
├─────────────────────────────────┤    ├─────────────────────────────────────────────┤
│ purchase_order_id               │    │ purchase_order_line_id → tracks PO origin   │
│ inventory_operation_id          │────│ product_id                                   │
└─────────────────────────────────┘    │ quantity                                     │
                                       │ source_location_id (vendor)                  │
                                       │ destination_location_id (warehouse)          │
                                       └─────────────────────────────────────────────┘

                            ┌────────────────────────────────────────────────────────┐
                            │                5. RECEIVING                             │
                            └────────────────────────────────────────────────────────┘

┌─────────────────────────────┐         ┌───────────────────────────────────────────┐
│   inventories_operations    │────────▶│       inventories_move_lines              │
├─────────────────────────────┤         ├───────────────────────────────────────────┤
│ name (WH/IN/00001)          │         │ product_id                                │
│ operation_type_id (Receipt) │         │ lot_id        → if lot tracked            │
│ source_location_id          │         │ qty           → actual received           │
│ destination_location_id     │         │ is_picked     → confirmed received        │
│ state (draft→done)          │         │ source_location_id                        │
└─────────────────────────────┘         │ destination_location_id                   │
                                        └───────────────────────────────────────────┘
                                                       │
                                                       │ (on confirm)
                                                       ▼
                            ┌────────────────────────────────────────────────────────┐
                            │                6. INVENTORY                             │
                            └────────────────────────────────────────────────────────┘

┌────────────────────────────────────────────────────────────────────────────────────┐
│                     inventories_product_quantities                                  │
├────────────────────────────────────────────────────────────────────────────────────┤
│ product_id          → which product                                                │
│ location_id         → which location (warehouse bin)                               │
│ lot_id              → if lot tracked (nullable)                                    │
│ quantity            → ON-HAND stock                                                │
│ reserved_quantity   → reserved for projects/orders                                 │
└────────────────────────────────────────────────────────────────────────────────────┘
```

### Key Connection Points

| From | To | Link | Purpose |
|------|-----|------|---------|
| **Project** → **Sales** | `sales_order_line_items.project_id` | Links quote/invoice to project |
| **Cabinet** → **BOM** | `projects_bom.cabinet_id` | Material requirements per cabinet |
| **BOM** → **Reservation** | `projects_material_reservations.bom_id` | Reserve inventory for project |
| **Reservation** → **Move** | `projects_material_reservations.move_id` | Tracks when material issued |
| **PO** → **Operations** | `purchases_order_operations` | Links PO to receipt operations |
| **PO Line** → **Move** | `inventories_moves.purchase_order_line_id` | Tracks which PO created move |
| **Move** → **Quantities** | Updates `inventories_product_quantities` | Actual stock levels |
| **Products** → **All** | `product_id` FK everywhere | Single source of truth for materials |

### Workflow Summary

1. **Design Project** → Create cabinets, sections, components
2. **Generate Quote** → Sales order with line items linked to project entities  
3. **Generate BOM** → Calculate materials needed from cabinet specs
4. **Check Inventory** → See what's in stock vs. what needs ordering
5. **Create PO** → Purchase missing materials from vendors
6. **Receive Materials** → Confirm receipt, update inventory
7. **Reserve Materials** → Allocate inventory to project
8. **Issue to Production** → Move materials from warehouse to production floor

---

## Products & Materials Connection

The `products_products` table is extended with woodworking-specific columns for materials.

### Material Properties in Products

```
┌─────────────────────────────────────────────────────────────────────────────┐
│                         products_products                                    │
│                    (Main Products / Inventory Items)                         │
├─────────────────────────────────────────────────────────────────────────────┤
│  Woodworking-specific columns:                                               │
│  - thickness_inches     (0.75 for 3/4", 0.5 for 1/2")                       │
│  - wood_species         (hard_maple, white_oak, walnut)                      │
│  - material_type        (sheet_goods, solid_wood, hardware)                  │
│  - grade                (select, #1, paint_grade)                            │
│  - sheet_size           (4x8, 4x10)                                          │
│  - sqft_per_sheet       (32 for 4x8)                                         │
│  - material_category_id → FK to woodworking_material_categories              │
└─────────────────────────────────────────────────────────────────────────────┘
                                    │
                    ┌───────────────┴───────────────┐
                    │                               │
                    ▼                               ▼
┌───────────────────────────────┐   ┌─────────────────────────────────────────┐
│ woodworking_material_categories│   │    tcs_material_inventory_mappings      │
├───────────────────────────────┤   ├─────────────────────────────────────────┤
│ - PLYWOOD                     │   │ tcs_material_slug: "paint_grade"         │
│ - MDF                         │   │ wood_species: "Hard Maple"               │
│ - HARDWOOD                    │   │ inventory_product_id → products_products │
│ - SLIDES                      │   │ board_feet_per_lf: 2.5                   │
│ - HINGES                      │   │ is_box_material: true                    │
│ - etc.                        │   │ is_door_material: true                   │
└───────────────────────────────┘   └─────────────────────────────────────────┘
```

### Example: Selecting a Material

When you select "3/4" Maple Plywood" from `products_products`:

| Field | Value |
|-------|-------|
| `thickness_inches` | `0.75` |
| `wood_species` | `hard_maple` |
| `material_type` | `sheet_goods` |
| `sheet_size` | `4x8` |
| `sqft_per_sheet` | `32` |
| `cost_per_unit` | `90.00` |

This info is automatically available when linking via `product_id` FK.

---

## Task System

Tasks can be assigned to ANY level of the cabinet hierarchy.

### Task Table Structure

```
┌─────────────────────────────────────────────────────────────────────────────────────┐
│                              projects_tasks                                          │
│                        (Assignable to ANY hierarchy level)                           │
├─────────────────────────────────────────────────────────────────────────────────────┤
│                                                                                      │
│  HIERARCHY LINKS (all nullable - fill ONE based on assignment level):               │
│  ─────────────────────────────────────────────────────────────────────              │
│  project_id           → Entire project (e.g., "Design review")                      │
│  room_id              → Specific room (e.g., "Kitchen inspection")                  │
│  room_location_id     → Location (e.g., "Install island outlets")                   │
│  cabinet_run_id       → Run (e.g., "Cut all bases for north wall")                  │
│  cabinet_id           → Cabinet (e.g., "Assemble B36")                              │
│  section_id           → Section (e.g., "Install drawer stack")                      │
│  component_type +     → Component (e.g., "CNC cut door D1")                         │
│  component_id         → (Polymorphic: door, drawer, shelf, pullout)                 │
│                                                                                      │
├─────────────────────────────────────────────────────────────────────────────────────┤
│                                                                                      │
│  ASSIGNMENT & TRACKING:                                                              │
│  ─────────────────────                                                              │
│  users (many-to-many) → projects_task_users (assignees)                             │
│  creator_id           → Who created the task                                         │
│  started_by           → Who started work                                             │
│  completed_by         → Who completed it                                             │
│                                                                                      │
│  LIFECYCLE:                                                                          │
│  ──────────                                                                         │
│  state                → in_progress, done, cancelled, etc.                          │
│  started_at           → When work began (auto-set)                                   │
│  completed_at         → When completed (auto-set)                                    │
│  deadline             → Due date                                                     │
│                                                                                      │
│  TIME TRACKING:                                                                      │
│  ──────────────                                                                     │
│  allocated_hours      → Estimated time                                               │
│  effective_hours      → Actual time spent                                            │
│  timesheets           → hasMany(Timesheet) for detailed tracking                    │
│                                                                                      │
└─────────────────────────────────────────────────────────────────────────────────────┘
```

### Task Assignment by Level

```
┌─────────────────────────────────────────────────────────────────────────────────────┐
│ PROJECT LEVEL                                                                        │
│ project_id = 1                                                                       │
│ Task: "Final design review with client"                                             │
└─────────────────────────────────────────────────────────────────────────────────────┘
         │
         ▼
┌─────────────────────────────────────────────────────────────────────────────────────┐
│ ROOM LEVEL                                                                           │
│ room_id = 5 (Kitchen)                                                               │
│ Task: "Complete rough electrical inspection"                                        │
└─────────────────────────────────────────────────────────────────────────────────────┘
         │
         ▼
┌─────────────────────────────────────────────────────────────────────────────────────┐
│ LOCATION LEVEL                                                                       │
│ room_location_id = 12 (South Wall)                                                  │
│ Task: "Verify wall is square before install"                                        │
└─────────────────────────────────────────────────────────────────────────────────────┘
         │
         ▼
┌─────────────────────────────────────────────────────────────────────────────────────┐
│ RUN LEVEL                                                                            │
│ cabinet_run_id = 8 (Base Cabinets)                                                  │
│ Task: "CNC cut all box parts for run"                                               │
└─────────────────────────────────────────────────────────────────────────────────────┘
         │
         ▼
┌─────────────────────────────────────────────────────────────────────────────────────┐
│ CABINET LEVEL                                                                        │
│ cabinet_id = 42 (B36-001)                                                           │
│ Task: "Assemble cabinet box"                                                        │
└─────────────────────────────────────────────────────────────────────────────────────┘
         │
         ▼
┌─────────────────────────────────────────────────────────────────────────────────────┐
│ SECTION LEVEL                                                                        │
│ section_id = 88 (Drawer Bank)                                                       │
│ Task: "Install drawer slides"                                                       │
└─────────────────────────────────────────────────────────────────────────────────────┘
         │
         ▼
┌─────────────────────────────────────────────────────────────────────────────────────┐
│ COMPONENT LEVEL (Polymorphic)                                                        │
│ component_type = "drawer", component_id = 156                                       │
│ Task: "Sand drawer front to 180 grit"                                               │
└─────────────────────────────────────────────────────────────────────────────────────┘
```

### Task-Related Tables

| Table | Purpose |
|-------|---------|
| `projects_tasks` | Main task records with hierarchy links |
| `projects_task_users` | Many-to-many assignees (task_id, user_id) |
| `projects_task_stages` | Kanban stages (To Do, In Progress, Done) |
| `projects_task_tag` | Many-to-many tags |
| `timesheets` | Time entries linked to tasks |

### Task Lifecycle Tracking

The Task model automatically tracks state changes:

```php
// Automatic timestamps on state changes:
// When moved TO "in_progress" → sets started_at, started_by
// When moved TO "done" → sets completed_at, completed_by
// When re-opened from "done" → clears completed_at, completed_by
```

### Task Usage Examples

```php
// Create task at project level
Task::create([
    'title' => 'Final walkthrough',
    'project_id' => 1,
]);

// Create task at cabinet level
Task::create([
    'title' => 'Assemble B36',
    'project_id' => 1,
    'cabinet_id' => 42,
]);

// Create task at component level (polymorphic)
Task::create([
    'title' => 'CNC cut door panel',
    'project_id' => 1,
    'cabinet_id' => 42,
    'section_id' => 88,
    'component_type' => 'door',
    'component_id' => 156,
]);

// Assign users
$task->users()->attach([1, 2, 3]); // Assign 3 users
```

---

## Stage System

The stage system provides two distinct but complementary mechanisms for tracking progress:
1. **Project Stages** - Company-wide Kanban columns for visual project management
2. **Task Stages** - Per-project Kanban columns for task management within each project

```
┌─────────────────────────────────────────────────────────────────────────────┐
│                    COMPANY-WIDE PROJECT STAGES                               │
│                   (projects_project_stages table)                            │
│                                                                              │
│  ┌──────────┬─────────┬──────────┬──────────┬──────────┬───────────┬──────┐ │
│  │Discovery │ Design  │ Sourcing │Mat.Reserv│Mat.Issued│Production │Deliv.│ │
│  │  WIP:∞   │  WIP:∞  │  WIP:3   │  WIP:∞   │  WIP:∞   │  WIP:4    │WIP:∞ │ │
│  └──────────┴─────────┴──────────┴──────────┴──────────┴───────────┴──────┘ │
│       ↑          ↑          ↑          ↑          ↑          ↑         ↑    │
│    Proj A     Proj B     Proj C     Proj D     Proj E     Proj F    Proj G  │
└─────────────────────────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────────────────────────┐
│                PER-PROJECT TASK STAGES (for Project A)                       │
│                    (projects_task_stages table)                              │
│                                                                              │
│  ┌─────────────┬───────────────┬─────────────┬────────────┐                 │
│  │   To Do     │  In Progress  │    Done     │ Cancelled  │                 │
│  └─────────────┴───────────────┴─────────────┴────────────┘                 │
│        ↑              ↑              ↑             ↑                        │
│     Task 1         Task 2         Task 3       Task 4                       │
└─────────────────────────────────────────────────────────────────────────────┘
```

### `projects_project_stages` (Company-Wide Project Kanban)

Global stages that ALL projects move through. Used for the main Project Kanban board.

| Column | Type | Description |
|--------|------|-------------|
| `id` | bigint | Primary key |
| `name` | string | Stage display name ("Discovery", "Production", etc.) |
| `stage_key` | string(50) | Programmatic identifier (`discovery`, `production`) |
| `color` | string | Hex color for Kanban column display |
| `is_active` | boolean | Whether stage is visible/usable |
| `is_collapsed` | boolean | Default collapse state in Kanban view |
| `wip_limit` | int (nullable) | Maximum projects allowed in this stage (null = unlimited) |
| `max_days_in_stage` | int (nullable) | Warning threshold - days before alert |
| `expiry_warning_days` | int | Days before max to start showing warning (default: 3) |
| `notice_message` | text (nullable) | Custom alert message for this stage |
| `notice_severity` | enum | Alert level: `info`, `warning`, `danger` |
| `sort` | int | Display order (left to right) |
| `company_id` | FK (nullable) | Company scope |
| `creator_id` | FK (nullable) | User who created stage |
| `created_at` | timestamp | |
| `updated_at` | timestamp | |
| `deleted_at` | timestamp | Soft delete |

### Default TCS Project Stages

| Sort | Name | stage_key | WIP Limit | Purpose |
|------|------|-----------|-----------|---------|
| 1 | Discovery | `discovery` | - | Initial consultation, site visit, measurements |
| 2 | Design | `design` | - | CAD drawings, design iterations, approval |
| 3 | Sourcing | `sourcing` | 3 | Material ordering, vendor POs |
| 4 | Material Reserved | `material_reserved` | - | Materials locked in inventory |
| 5 | Material Issued | `material_issued` | - | Materials pulled to shop floor |
| 6 | Production | `production` | 4 | CNC cutting, assembly, finishing |
| 7 | Delivery | `delivery` | - | Shipping, installation, closeout |

### `projects_task_stages` (Per-Project Task Kanban)

Project-specific stages for organizing tasks within a single project. Each project has its own set of task stages.

| Column | Type | Description |
|--------|------|-------------|
| `id` | bigint | Primary key |
| `name` | string | Stage name ("To Do", "In Progress", "Done") |
| `project_id` | FK | Parent project (cascades on delete) |
| `is_active` | boolean | Whether stage is visible/usable |
| `is_collapsed` | boolean | Default collapse state |
| `sort` | int | Display order |
| `user_id` | FK (nullable) | Default assignee for this stage |
| `company_id` | FK (nullable) | Company scope |
| `creator_id` | FK (nullable) | User who created stage |
| `created_at` | timestamp | |
| `updated_at` | timestamp | |
| `deleted_at` | timestamp | Soft delete |

### Default Task Stages (Per Project)

| Sort | Name | Purpose |
|------|------|---------|
| 1 | To Do | Tasks not yet started |
| 2 | In Progress | Tasks actively being worked |
| 3 | Done | Completed tasks |
| 4 | Cancelled | Tasks that won't be done |

### Dual Stage Tracking on Projects

Projects have TWO parallel stage tracking mechanisms that stay synchronized:

| Field | Type | Purpose |
|-------|------|---------|
| `stage_id` | FK → projects_project_stages | Kanban column for visual management |
| `current_production_stage` | ENUM | Programmatic workflow position |
| `stage_entered_at` | timestamp | When project entered current stage |

**`current_production_stage` ENUM values:**
```
'discovery', 'design', 'sourcing', 'production', 'delivery'
```

**Synchronization:**
When advancing a project via `advanceToNextStage()`, both fields are updated:

```php
// Project model method
public function advanceToNextStage($force = false)
{
    // Update enum field
    $this->current_production_stage = $nextStage;
    
    // Sync stage_id if matching ProjectStage exists
    $matchingStage = ProjectStage::where('stage_key', $nextStage)->first();
    if ($matchingStage) {
        $this->stage_id = $matchingStage->id;
    }
    
    $this->stage_entered_at = now();
    $this->save();
}
```

### Stage Gate System

Projects cannot advance to the next stage until specific "gate" conditions are met. Gates are tracked via timestamp fields:

```
┌───────────────┐         ┌───────────────┐         ┌───────────────┐
│   DISCOVERY   │─────────│    DESIGN     │─────────│   SOURCING    │
│               │  Gate:  │               │  Gate:  │               │
│               │ deposit │               │ design  │               │
│               │ _paid_at│               │_approved│               │
└───────────────┘         └───────────────┘ _at     └───────────────┘
                                           redline_
                                           approved_at
        │                                                    │
        │                                                    │
        ▼                                                    ▼
┌───────────────┐         ┌───────────────┐         ┌───────────────┐
│   DELIVERY    │◄────────│  PRODUCTION   │◄────────│   SOURCING    │
│               │  Gate:  │               │  Gate:  │               │
│               │ bol_    │               │materials│               │
│               │ signed  │               │_staged  │               │
└───────────────┘         └───────────────┘         └───────────────┘
```

**Gate Fields by Location:**

| Transition | Gate Field(s) | Table |
|------------|---------------|-------|
| Discovery → Design | `deposit_paid_at` | `sales_orders` |
| Design → Sourcing | `design_approved_at`, `redline_approved_at` | `projects_projects` |
| Sourcing → Production | `materials_staged_at`, `all_materials_received_at` | `projects_projects` |
| Production → Delivery | (cabinet-level tracking) | `projects_cabinets` |
| Delivery → Complete | `delivered_at`, `customer_signoff_at` | `projects_projects` |

**All Stage Gate Fields on `projects_projects`:**

| Field | Type | Description |
|-------|------|-------------|
| `design_approved_at` | timestamp | Customer approved final design |
| `redline_approved_at` | timestamp | Final redline changes confirmed |
| `materials_staged_at` | timestamp | All materials staged in shop |
| `all_materials_received_at` | timestamp | All POs received and verified |
| `bol_created_at` | timestamp | Bill of lading created |
| `bol_signed_at` | timestamp | BOL signed by carrier/customer |
| `delivered_at` | timestamp | Physical delivery confirmed |
| `closeout_delivered_at` | timestamp | Closeout package delivered |
| `customer_signoff_at` | timestamp | Customer final signoff received |

**Payment Gate Fields on `sales_orders`:**

| Field | Type | Description |
|-------|------|-------------|
| `deposit_paid_at` | timestamp | Deposit payment received - gates Discovery→Design |
| `final_paid_at` | timestamp | Final payment received - gates project closure |

### Stage System Relationships

```php
// Project belongs to a ProjectStage (for Kanban)
Project::stage() → BelongsTo(ProjectStage)
ProjectStage::projects() → HasMany(Project)

// Task belongs to a TaskStage (within its project)
Task::stage() → BelongsTo(TaskStage)
TaskStage::tasks() → HasMany(Task)
TaskStage::project() → BelongsTo(Project)
```

### Stage FK Reference

| Table | Column | References | Notes |
|-------|--------|------------|-------|
| `projects_projects` | `stage_id` | `projects_project_stages.id` | Project Kanban column |
| `projects_tasks` | `stage_id` | `projects_task_stages.id` | Task Kanban column |
| `projects_task_stages` | `project_id` | `projects_projects.id` | Task stages belong to project |
| `projects_project_stages` | `company_id` | `companies.id` | Optional company scope |
| `projects_project_stages` | `creator_id` | `users.id` | Who created stage |
| `projects_task_stages` | `company_id` | `companies.id` | Optional company scope |
| `projects_task_stages` | `creator_id` | `users.id` | Who created stage |
| `projects_task_stages` | `user_id` | `users.id` | Default assignee |

---

## Milestone System

Milestones are key checkpoints within a project, typically tied to production stages. They help track progress and can have tasks assigned to them.

```
┌─────────────────────────────────────────────────────────────────────────────┐
│                      projects_milestone_templates                            │
│                  (Reusable templates for milestone creation)                 │
└─────────────────────────────────────────────────────────────────────────────┘
                                    │
                                    │ Used to seed/create
                                    ▼
┌─────────────────────────────────────────────────────────────────────────────┐
│                         projects_milestones                                  │
│               (Actual milestones tied to a specific project)                 │
└─────────────────────────────────────────────────────────────────────────────┘
                                    │
                                    │ 1:N (milestone_id)
                                    ▼
┌─────────────────────────────────────────────────────────────────────────────┐
│                           projects_tasks                                     │
│                   (Tasks can be assigned to milestones)                      │
└─────────────────────────────────────────────────────────────────────────────┘
```

### `projects_milestones` (Main Table)

| Column | Type | Description |
|--------|------|-------------|
| `id` | bigint | Primary key |
| `name` | string | Milestone name (indexed) |
| `project_id` | FK | Parent project (cascades on delete) |
| `deadline` | datetime | Target date (indexed) |
| `production_stage` | enum | `discovery`, `design`, `sourcing`, `production`, `delivery` |
| `is_critical` | bool | Critical milestones shown prominently |
| `is_completed` | bool | Completion status |
| `completed_at` | datetime | When completed (indexed) |
| `description` | text | Additional context/requirements |
| `sort_order` | int | Manual ordering within stage (0 = auto-sort by date) |
| `creator_id` | FK | User who created |
| `created_at` | timestamp | |
| `updated_at` | timestamp | |

### `projects_milestone_templates` (Templates Table)

Reusable templates for generating milestones when projects are created.

| Column | Type | Description |
|--------|------|-------------|
| `id` | bigint | Primary key |
| `name` | string | Template name (indexed) |
| `production_stage` | enum | Which stage this belongs to |
| `is_critical` | bool | Critical flag |
| `description` | text | Default description |
| `relative_days` | int | Days offset from project/stage start |
| `sort_order` | int | Display order |
| `is_active` | bool | Template active/inactive |
| `created_at` | timestamp | |
| `updated_at` | timestamp | |

### Default TCS Milestone Templates

The system seeds 22 milestone templates across all production stages:

| Stage | Milestones | Day Range |
|-------|------------|-----------|
| **Discovery** | Initial Consultation, Site Measurement, Material Selection, Scope Finalization | Days 2-14 |
| **Design** | Initial Concepts, Revisions & Approval, Shop Drawings, Cut List & Takeoff | Days 18-35 |
| **Sourcing** | Lumber Order, Hardware Order, Materials Received, Material Acclimation | Days 38-56 |
| **Production** | Rough Mill, Cabinet Boxes, Doors/Fronts, Sanding, Finishing, Hardware/QC | Days 63-112 |
| **Delivery** | Pre-Install Check, Delivery & Installation, Adjustments, Client Sign-off | Days 115-126 |

**Critical milestones** (15 total) are highlighted in timeline views.

### Milestone Model Methods

| Method | Purpose |
|--------|---------|
| `scopeCritical()` | Filter to critical milestones only |
| `scopeByStage($stage)` | Filter by production stage |
| `scopeOverdue()` | Get overdue (not completed, past deadline) |
| `scopeUpcoming()` | Get upcoming (not completed, future deadline) |
| `getIsOverdueAttribute()` | Check if milestone is past due |
| `getStageColorAttribute()` | Get hex color for the stage |
| `getStageIconAttribute()` | Get Heroicon name for the stage |

### Milestone Relationships

```php
// Project has many Milestones
Project::milestones() → HasMany(Milestone)
Milestone::project() → BelongsTo(Project)

// Task can belong to a Milestone
Task::milestone() → BelongsTo(Milestone)  // via milestone_id FK

// Project setting to enable/disable
Project::allow_milestones → boolean
```

### Milestone FK Reference

| Table | Column | References | Notes |
|-------|--------|------------|-------|
| `projects_milestones` | `project_id` | `projects_projects.id` | Cascade delete |
| `projects_milestones` | `creator_id` | `users.id` | Nullable |
| `projects_tasks` | `milestone_id` | `projects_milestones.id` | Nullable |

---

## Purchasing Tables

### `purchases_orders`

Purchase orders to vendors.

| Column | Type | Description |
|--------|------|-------------|
| `id` | bigint | Primary key |
| `name` | string | PO number (PO-00001) |
| `partner_id` | FK | Vendor |
| `requisition_id` | FK (nullable) | Source requisition |
| `state` | string | draft, sent, purchase, done, cancel |
| `receipt_status` | string | no, pending, partial, full |
| `invoice_status` | string | no, to_invoice, invoiced |
| `untaxed_amount` | decimal | Subtotal |
| `tax_amount` | decimal | Tax total |
| `total_amount` | decimal | Grand total |
| `ordered_at` | datetime | Order date |
| `approved_at` | datetime | Approval date |
| `planned_at` | datetime | Expected delivery |

### `purchases_order_lines`

Line items on purchase orders.

| Column | Type | Description |
|--------|------|-------------|
| `id` | bigint | Primary key |
| `order_id` | FK | Parent PO |
| `product_id` | FK (nullable) | Product being purchased |
| `name` | text | Line description |
| `product_qty` | decimal | Quantity ordered |
| `qty_received` | decimal | Quantity received so far |
| `qty_invoiced` | decimal | Quantity invoiced |
| `price_unit` | decimal | Unit price |
| `price_subtotal` | decimal | Line subtotal |
| `price_total` | decimal | Line total with tax |
| `uom_id` | FK | Unit of measure |
| `planned_at` | datetime | Expected delivery |

### `purchases_order_operations`

Links purchase orders to inventory receiving operations (many-to-many).

| Column | Type | Description |
|--------|------|-------------|
| `purchase_order_id` | FK | Purchase order |
| `inventory_operation_id` | FK | Inventory receipt operation |

---

## Inventory Operations Tables

### `inventories_operations`

Warehouse operations (receipts, deliveries, internal transfers).

| Column | Type | Description |
|--------|------|-------------|
| `id` | bigint | Primary key |
| `name` | string | Operation reference (WH/IN/00001) |
| `operation_type_id` | FK | Type: Receipt, Delivery, Internal |
| `source_location_id` | FK | From location |
| `destination_location_id` | FK | To location |
| `partner_id` | FK (nullable) | Vendor/customer |
| `state` | string | draft, waiting, ready, done, cancel |
| `scheduled_at` | datetime | Planned date |
| `closed_at` | datetime | Completion date |
| `back_order_id` | FK (nullable) | If split from another operation |

### `inventories_moves`

Individual product movements within an operation.

| Column | Type | Description |
|--------|------|-------------|
| `id` | bigint | Primary key |
| `operation_id` | FK (nullable) | Parent operation |
| `product_id` | FK | Product being moved |
| `purchase_order_line_id` | FK (nullable) | Source PO line (for receipts) |
| `product_qty` | decimal | Quantity to move |
| `quantity` | decimal | Actual quantity moved |
| `source_location_id` | FK | From location |
| `destination_location_id` | FK | To location |
| `state` | string | draft, waiting, assigned, done, cancel |
| `scheduled_at` | datetime | Planned date |

### `inventories_move_lines`

Detailed breakdown of moves (with lot/serial tracking).

| Column | Type | Description |
|--------|------|-------------|
| `id` | bigint | Primary key |
| `move_id` | FK (nullable) | Parent move |
| `operation_id` | FK (nullable) | Parent operation |
| `product_id` | FK | Product |
| `lot_id` | FK (nullable) | Lot/serial number |
| `qty` | decimal | Quantity |
| `source_location_id` | FK | From location |
| `destination_location_id` | FK | To location |
| `is_picked` | boolean | Confirmed picked/received |

### `inventories_product_quantities`

Current on-hand inventory by location and lot.

| Column | Type | Description |
|--------|------|-------------|
| `id` | bigint | Primary key |
| `product_id` | FK | Product |
| `location_id` | FK | Storage location |
| `lot_id` | FK (nullable) | Lot/serial if tracked |
| `quantity` | decimal | On-hand quantity |
| `reserved_quantity` | decimal | Reserved for orders |

---

## Material Reservation Tables

### `projects_material_reservations`

Reserves inventory for specific projects.

| Column | Type | Description |
|--------|------|-------------|
| `id` | bigint | Primary key |
| `project_id` | FK | Project |
| `bom_id` | FK (nullable) | BOM line |
| `product_id` | FK | Material product |
| `warehouse_id` | FK | Warehouse |
| `location_id` | FK (nullable) | Specific location |
| `quantity_reserved` | decimal | Amount reserved |
| `unit_of_measure` | string | UOM |
| `status` | enum | pending, reserved, issued, cancelled |
| `reserved_by` | FK (nullable) | User who reserved |
| `reserved_at` | timestamp | When reserved |
| `issued_at` | timestamp | When issued to production |
| `move_id` | FK (nullable) | Inventory move when issued |
| `expires_at` | timestamp | Reservation expiry |

---

## Sales System

The sales system handles quotes, orders, invoices, and deliveries. It connects projects to billing and inventory fulfillment.

### Sales Flow Diagram

```
┌─────────────────────────────────────────────────────────────────────────────────────┐
│                              SALES ORDER LIFECYCLE                                   │
└─────────────────────────────────────────────────────────────────────────────────────┘

┌──────────────────┐     ┌──────────────────┐     ┌──────────────────┐
│  Draft/Quote     │────▶│  Sent/Proposal   │────▶│  Sale Order      │
│  (state: draft)  │     │  (state: sent)   │     │  (state: sale)   │
└──────────────────┘     └──────────────────┘     └──────────────────┘
                                                          │
                    ┌─────────────────────────────────────┼─────────────────────────┐
                    │                                     │                         │
                    ▼                                     ▼                         ▼
        ┌───────────────────┐               ┌───────────────────┐      ┌───────────────────┐
        │    INVOICING      │               │    DELIVERY       │      │    PRODUCTION     │
        │ (invoice_status)  │               │(delivery_status)  │      │(production_status)│
        └───────────────────┘               └───────────────────┘      └───────────────────┘
               │                                     │
               ▼                                     ▼
        ┌───────────────────┐               ┌───────────────────────────────────────────┐
        │  accounts_account │               │         inventories_operations            │
        │      _moves       │               │         (Delivery Operation)              │
        │    (Invoices)     │               │    sale_order_id → links back to order    │
        └───────────────────┘               └───────────────────────────────────────────┘
                                                       │
                                                       ▼
                                            ┌───────────────────────────────────────────┐
                                            │           inventories_moves               │
                                            │  sale_order_line_id → links to line item  │
                                            └───────────────────────────────────────────┘
```

### Sales Tables Structure

#### `sales_orders` (Main Order/Quote)

| Column | Type | Description |
|--------|------|-------------|
| `id` | bigint | Primary key |
| `name` | string | Order reference (SO-00001) |
| `partner_id` | FK | Customer (partners_partners) |
| `partner_invoice_id` | FK | Invoice address |
| `partner_shipping_id` | FK | Shipping address |
| `user_id` | FK (nullable) | Salesperson |
| `team_id` | FK (nullable) | Sales team |
| `company_id` | FK | Company |
| `currency_id` | FK | Currency |
| `payment_term_id` | FK (nullable) | Payment terms |
| `journal_id` | FK (nullable) | Invoicing journal |
| **State & Status** | | |
| `state` | string | draft, sent, sale, done, cancel |
| `invoice_status` | string | no, to_invoice, invoiced |
| **Amounts** | | |
| `amount_untaxed` | decimal | Subtotal |
| `amount_tax` | decimal | Tax total |
| `amount_total` | decimal | Grand total |
| **Dates** | | |
| `date_order` | date | Order date |
| `validity_date` | date | Quote expiration |
| `commitment_date` | date | Promised delivery |
| **Signature** | | |
| `require_signature` | boolean | Requires signature |
| `signed_by` | string | Who signed |
| `signed_on` | date | When signed |

#### `sales_orders` (Woodworking Extensions)

| Column | Type | Description |
|--------|------|-------------|
| **Project Link** | | |
| `room_id` | FK (nullable) | Link to projects_rooms |
| **Order Types** | | |
| `woodworking_order_type` | string | deposit, progress_payment, final_payment, change_order, full_project |
| `is_change_order` | boolean | Is a change order |
| `original_order_id` | FK (nullable) | Reference to original order |
| `change_order_description` | text | What changed |
| **Payment Schedule** | | |
| `deposit_percentage` | decimal | Deposit % (30%) |
| `deposit_amount` | decimal | Calculated deposit |
| `balance_percentage` | decimal | Balance % (70%) |
| `balance_amount` | decimal | Calculated balance |
| `payment_terms` | string | e.g., "NET 5 deposit, NET 15 completion" |
| **Pricing** | | |
| `project_estimated_value` | decimal | Total estimate from rooms |
| `quoted_price_override` | decimal | Negotiated price override |
| `pricing_notes` | text | Special pricing notes |
| **Proposal Tracking** | | |
| `proposal_status` | string | draft, sent, viewed, accepted, rejected |
| `proposal_sent_at` | timestamp | When sent |
| `proposal_viewed_at` | timestamp | When client viewed |
| `proposal_accepted_at` | timestamp | When accepted |
| `proposal_sent_by_user_id` | FK | Who sent |
| **Production Control** | | |
| `production_authorized` | boolean | Deposit received, can start |
| `production_authorized_at` | timestamp | When authorized |
| **Templates** | | |
| `invoice_template` | string | Invoice template name |
| `proposal_template` | string | Proposal template name |
| **Notes** | | |
| `client_notes` | text | Visible to client |
| `internal_notes` | text | Internal only |

#### `sales_order_lines` (Generic Line Items)

Standard ERP order lines for products.

| Column | Type | Description |
|--------|------|-------------|
| `id` | bigint | Primary key |
| `order_id` | FK | Parent sales_orders |
| `product_id` | FK (nullable) | Product |
| `product_uom_id` | FK (nullable) | Unit of measure |
| `name` | string | Line description |
| `sort` | int | Display order |
| **Quantities** | | |
| `product_qty` | decimal | Quantity |
| `qty_delivered` | decimal | Delivered |
| `qty_invoiced` | decimal | Invoiced |
| `qty_to_invoice` | decimal | Remaining to invoice |
| **Pricing** | | |
| `price_unit` | decimal | Unit price |
| `discount` | decimal | Discount % |
| `price_subtotal` | decimal | Subtotal |
| `price_tax` | decimal | Tax amount |
| `price_total` | decimal | Total |
| **Margin** | | |
| `purchase_price` | decimal | Cost |
| `margin` | decimal | Profit |
| `margin_percent` | decimal | Margin % |
| **Inventory Links** | | |
| `warehouse_id` | FK (nullable) | Warehouse |
| `route_id` | FK (nullable) | Delivery route |
| **Flags** | | |
| `is_downpayment` | boolean | Is deposit line |
| `is_expense` | boolean | Is expense |

#### `sales_order_line_items` (TCS/Woodworking Line Items)

Detailed line items linked to project hierarchy.

| Column | Type | Description |
|--------|------|-------------|
| `id` | bigint | Primary key |
| `sales_order_id` | FK | Parent order |
| `sequence` | int | Display order |
| **Project Links** | | |
| `project_id` | FK (nullable) | Project |
| `room_id` | FK (nullable) | Room |
| `room_location_id` | FK (nullable) | Location |
| `cabinet_run_id` | FK (nullable) | Cabinet run |
| `cabinet_specification_id` | FK (nullable) | Individual cabinet |
| `product_id` | FK (nullable) | Product if applicable |
| **Line Item Details** | | |
| `line_item_type` | string | room, location, cabinet_run, cabinet, product, service, custom |
| `description` | string | Line description |
| `detailed_description` | text | Long description |
| **Quantity & Pricing** | | |
| `quantity` | decimal | Quantity |
| `unit_of_measure` | string | EA, LF, BF, SQFT, HR |
| `unit_price` | decimal | Price per unit |
| `subtotal` | decimal | quantity × unit_price |
| `discount_percentage` | decimal | Discount % |
| `discount_amount` | decimal | Discount amount |
| `line_total` | decimal | Final total |
| **Tax** | | |
| `taxable` | boolean | Is taxable |
| `tax_rate` | decimal | Tax rate % |
| `tax_amount` | decimal | Tax amount |
| **Linear Feet Pricing** | | |
| `linear_feet` | decimal | LF for this line |
| `complexity_tier` | int | 1-5 pricing tier |
| `base_rate_per_lf` | decimal | Base $/LF |
| `material_rate_per_lf` | decimal | Material upgrade $/LF |
| `combined_rate_per_lf` | decimal | Combined rate |
| **Material Details** | | |
| `material_type` | string | Material description |
| `wood_species` | string | Wood species |
| `finish_type` | string | Finish description |
| `features_list` | text | Feature bullets |
| `hardware_list` | text | Hardware included |
| **Production** | | |
| `requires_production` | boolean | Needs to be built |
| `production_status` | string | pending, in_progress, completed |
| `production_completed_at` | timestamp | When completed |
| **Notes** | | |
| `client_notes` | text | Visible to client |
| `internal_notes` | text | Internal only |

### Sales → Inventory Connection

```
┌─────────────────────────────────────────────────────────────────────────────────────┐
│                         SALES TO DELIVERY FLOW                                       │
└─────────────────────────────────────────────────────────────────────────────────────┘

┌────────────────────┐                    ┌────────────────────────────────────────────┐
│   sales_orders     │                    │           sales_order_lines                │
│   (SO-00001)       │───────────────────▶│                                            │
│                    │                    │  product_id → products_products            │
│                    │                    │  warehouse_id → inventories_warehouses     │
└────────────────────┘                    └────────────────────────────────────────────┘
         │                                              │
         │ (sale_order_id FK)                           │ (sale_order_line_id FK)
         ▼                                              ▼
┌────────────────────────────────────┐    ┌────────────────────────────────────────────┐
│    inventories_operations          │    │            inventories_moves               │
│    (Delivery Order: WH/OUT/00001)  │    │                                            │
├────────────────────────────────────┤    ├────────────────────────────────────────────┤
│  sale_order_id → links to order    │    │  sale_order_line_id → links to line        │
│  operation_type_id → "Delivery"    │    │  product_id                                │
│  source_location_id → warehouse    │    │  quantity                                  │
│  destination_location_id → customer│    │  source_location_id                        │
│  state → draft, ready, done        │    │  destination_location_id                   │
└────────────────────────────────────┘    └────────────────────────────────────────────┘
```

### Sales → Invoice Connection

```
┌────────────────────┐         ┌─────────────────────────┐         ┌─────────────────────────┐
│   sales_orders     │────────▶│  sales_order_invoices   │────────▶│  accounts_account_moves │
│   (SO-00001)       │         │   (pivot table)         │         │   (Invoice Record)      │
├────────────────────┤         ├─────────────────────────┤         ├─────────────────────────┤
│  invoice_status    │         │  order_id               │         │  move_type = out_invoice│
│  amount_total      │         │  move_id                │         │  partner_id             │
└────────────────────┘         └─────────────────────────┘         │  amount_total           │
                                                                   │  state                  │
                                                                   └─────────────────────────┘
```

### Supporting Sales Tables

| Table | Purpose |
|-------|---------|
| `sales_teams` | Sales team definitions |
| `sales_team_members` | Team member assignments |
| `sales_order_templates` | Reusable order templates |
| `sales_order_template_products` | Products in templates |
| `sales_order_options` | Optional items on orders |
| `sales_order_tags` | Order tagging (many-to-many) |
| `sales_order_line_taxes` | Tax lines per order line |
| `sales_order_line_invoices` | Line → invoice line mapping |
| `sales_advance_payment_invoices` | Advance/deposit invoice records |
| `document_templates` | Document generation templates |

### Sales FK Reference

| Table | Column | References | Notes |
|-------|--------|------------|-------|
| `sales_orders` | `partner_id` | `partners_partners` | Customer |
| `sales_orders` | `partner_invoice_id` | `partners_partners` | Invoice address |
| `sales_orders` | `partner_shipping_id` | `partners_partners` | Ship-to address |
| `sales_orders` | `user_id` | `users` | Salesperson |
| `sales_orders` | `team_id` | `sales_teams` | Sales team |
| `sales_orders` | `currency_id` | `currencies` | Currency |
| `sales_orders` | `payment_term_id` | `accounts_payment_terms` | Payment terms |
| `sales_orders` | `room_id` | `projects_rooms` | TCS: room link |
| `sales_orders` | `original_order_id` | `sales_orders` | Self-ref for change orders |
| `sales_order_lines` | `order_id` | `sales_orders` | Parent order |
| `sales_order_lines` | `product_id` | `products_products` | Product |
| `sales_order_lines` | `warehouse_id` | `inventories_warehouses` | Fulfillment warehouse |
| `sales_order_line_items` | `sales_order_id` | `sales_orders` | Parent order |
| `sales_order_line_items` | `project_id` | `projects_projects` | Project link |
| `sales_order_line_items` | `room_id` | `projects_rooms` | Room link |
| `sales_order_line_items` | `room_location_id` | `projects_room_locations` | Location link |
| `sales_order_line_items` | `cabinet_run_id` | `projects_cabinet_runs` | Run link |
| `sales_order_line_items` | `cabinet_specification_id` | `projects_cabinets` | Cabinet link |
| `sales_order_line_items` | `product_id` | `products_products` | Product link |
| `sales_order_invoices` | `order_id` | `sales_orders` | Order |
| `sales_order_invoices` | `move_id` | `accounts_account_moves` | Invoice |
| `inventories_operations` | `sale_order_id` | `sales_orders` | Delivery link |
| `inventories_moves` | `sale_order_line_id` | `sales_order_lines` | Line item link |

### Woodworking Order Type Examples

| Order Type | Use Case | Example |
|------------|----------|---------|
| `deposit` | 30% down payment | "Kitchen Cabinets - Deposit Invoice" |
| `progress_payment` | Mid-project payment | "Progress Payment - 50% Complete" |
| `final_payment` | Balance due on completion | "Final Payment - Kitchen Complete" |
| `change_order` | Scope changes | "Add Island - Change Order #1" |
| `full_project` | Single invoice for entire project | "Complete Kitchen Project" |

### Sales Workflow Example

```php
// 1. Create Quote from Project
$quote = SalesOrder::create([
    'partner_id' => $project->client_id,
    'state' => 'draft',
    'woodworking_order_type' => 'deposit',
    'deposit_percentage' => 30.00,
    'project_estimated_value' => $project->total_estimate,
]);

// 2. Add line items from cabinet runs
foreach ($project->rooms as $room) {
    foreach ($room->cabinetRuns as $run) {
        SalesOrderLineItem::create([
            'sales_order_id' => $quote->id,
            'project_id' => $project->id,
            'room_id' => $room->id,
            'cabinet_run_id' => $run->id,
            'line_item_type' => 'cabinet_run',
            'description' => "{$room->name} - {$run->name}",
            'linear_feet' => $run->linear_feet,
            'base_rate_per_lf' => $run->base_rate,
            'material_rate_per_lf' => $run->material_upgrade_rate,
            'line_total' => $run->total_price,
        ]);
    }
}

// 3. Calculate deposit
$quote->update([
    'deposit_amount' => $quote->amount_total * 0.30,
    'balance_amount' => $quote->amount_total * 0.70,
]);

// 4. Send proposal
$quote->update([
    'state' => 'sent',
    'proposal_status' => 'sent',
    'proposal_sent_at' => now(),
]);

// 5. Client accepts → Authorize production
$quote->update([
    'state' => 'sale',
    'proposal_status' => 'accepted',
    'proposal_accepted_at' => now(),
    'production_authorized' => true,
    'production_authorized_at' => now(),
]);

// 6. Create delivery order (automatic)
// inventories_operations created with sale_order_id = $quote->id
```

---

## Project-Level Sales with Staged Payments

A **single project** can have **multiple sales orders** for different payment stages. This is how you sell a project (not just line items) with deposit → progress → final payments.

### One Project, Multiple Orders

```
┌─────────────────────────────────────────────────────────────────────────────────────┐
│                              projects_projects                                       │
│                        (25 Friendship Lane Kitchen - $45,000)                        │
│                                   id = 123                                           │
└─────────────────────────────────────────────────────────────────────────────────────┘
                                        │
                                        │ project_id = 123 (one-to-many)
                                        │
        ┌───────────────────────────────┼───────────────────────────────┐
        │                               │                               │
        ▼                               ▼                               ▼
┌───────────────────┐       ┌───────────────────┐       ┌───────────────────┐
│   sales_orders    │       │   sales_orders    │       │   sales_orders    │
│   SO-00045        │       │   SO-00052        │       │   SO-00067        │
├───────────────────┤       ├───────────────────┤       ├───────────────────┤
│project_id: 123    │       │project_id: 123    │       │project_id: 123    │
│woodworking_order_ │       │woodworking_order_ │       │woodworking_order_ │
│type: "deposit"    │       │type: "progress_   │       │type: "final_      │
│                   │       │       payment"    │       │       payment"    │
│deposit_%: 30      │       │                   │       │                   │
│amount: $13,500    │       │amount: $15,750    │       │amount: $15,750    │
│                   │       │(35% @ delivery)   │       │(35% @ completion) │
│production_        │       │                   │       │                   │
│authorized: true   │       │                   │       │                   │
└───────────────────┘       └───────────────────┘       └───────────────────┘
         │                           │                           │
         │                           │                           │
         ▼                           ▼                           ▼
┌───────────────────┐       ┌───────────────────┐       ┌───────────────────┐
│ accounts_account_ │       │ accounts_account_ │       │ accounts_account_ │
│     moves         │       │     moves         │       │     moves         │
│   (Invoice #1)    │       │   (Invoice #2)    │       │   (Invoice #3)    │
│   state: posted   │       │   state: posted   │       │   state: draft    │
└───────────────────┘       └───────────────────┘       └───────────────────┘
```

### Key Relationships

| From | To | Link | Purpose |
|------|-----|------|---------|
| **Project** | **Sales Order** | `sales_orders.project_id` | Links all orders to ONE project |
| **Order** | **Original Order** | `sales_orders.original_order_id` | Change orders reference original |
| **Order** | **Invoice** | `sales_order_invoices` pivot | Links order to accounting |
| **Order** | **Delivery** | `inventories_operations.sale_order_id` | Delivery per order |

### Payment Stage Tracking

```
┌─────────────────────────────────────────────────────────────────────────────────────┐
│                           sales_orders (Payment Stage Fields)                        │
├─────────────────────────────────────────────────────────────────────────────────────┤
│                                                                                      │
│  STAGE IDENTIFIER:                                                                   │
│  ─────────────────                                                                   │
│  woodworking_order_type → "deposit" | "progress_payment" | "final_payment" |        │
│                           "change_order" | "full_project"                           │
│                                                                                      │
│  PAYMENT SCHEDULE:                                                                   │
│  ─────────────────                                                                   │
│  deposit_percentage      → 30.00 (30%)                                              │
│  deposit_amount          → 13,500.00                                                │
│  balance_percentage      → 70.00 (70%)                                              │
│  balance_amount          → 31,500.00                                                │
│  project_estimated_value → 45,000.00 (total project value)                          │
│                                                                                      │
│  PRODUCTION GATE:                                                                    │
│  ────────────────                                                                   │
│  production_authorized    → true (deposit received = green light)                   │
│  production_authorized_at → 2026-01-10 14:32:00                                     │
│                                                                                      │
│  CHANGE ORDER TRACKING:                                                              │
│  ─────────────────────                                                              │
│  is_change_order         → true/false                                               │
│  original_order_id       → FK to original sales_orders (self-reference)             │
│  change_order_description→ "Add island with seating - scope change"                 │
│                                                                                      │
└─────────────────────────────────────────────────────────────────────────────────────┘
```

### Complete Payment Flow Example

```php
// ═══════════════════════════════════════════════════════════════════════
// STAGE 1: DEPOSIT INVOICE (30%)
// Triggers: Design Approved → Proposal Accepted
// ═══════════════════════════════════════════════════════════════════════

$depositOrder = SalesOrder::create([
    'name' => 'SO-00045',
    'project_id' => $project->id,
    'partner_id' => $project->partner_id,
    'woodworking_order_type' => 'deposit',
    'deposit_percentage' => 30.00,
    'project_estimated_value' => $project->total_estimate, // $45,000
    'amount_total' => $project->total_estimate * 0.30,     // $13,500
    'state' => 'sale',
]);

// When deposit paid → authorize production
$depositOrder->update([
    'production_authorized' => true,
    'production_authorized_at' => now(),
]);

// ═══════════════════════════════════════════════════════════════════════
// STAGE 2: PROGRESS PAYMENT (35%)
// Triggers: Cabinets Delivered to Job Site
// ═══════════════════════════════════════════════════════════════════════

$progressOrder = SalesOrder::create([
    'name' => 'SO-00052',
    'project_id' => $project->id,  // Same project!
    'partner_id' => $project->partner_id,
    'woodworking_order_type' => 'progress_payment',
    'project_estimated_value' => $project->total_estimate,
    'amount_total' => $project->total_estimate * 0.35,     // $15,750
    'state' => 'sale',
]);

// ═══════════════════════════════════════════════════════════════════════
// STAGE 3: FINAL PAYMENT (35%)
// Triggers: Installation Complete + Punch List Signed Off
// ═══════════════════════════════════════════════════════════════════════

$finalOrder = SalesOrder::create([
    'name' => 'SO-00067',
    'project_id' => $project->id,  // Same project!
    'partner_id' => $project->partner_id,
    'woodworking_order_type' => 'final_payment',
    'project_estimated_value' => $project->total_estimate,
    'amount_total' => $project->total_estimate * 0.35,     // $15,750
    'state' => 'draft', // Ready when project completes
]);

// ═══════════════════════════════════════════════════════════════════════
// CHANGE ORDER (Mid-Project Scope Change)
// Triggers: Client Request for Additional Work
// ═══════════════════════════════════════════════════════════════════════

$changeOrder = SalesOrder::create([
    'name' => 'SO-00058',
    'project_id' => $project->id,
    'partner_id' => $project->partner_id,
    'woodworking_order_type' => 'change_order',
    'is_change_order' => true,
    'original_order_id' => $depositOrder->id,  // References original
    'change_order_description' => 'Add soft-close to all drawers - 15 units',
    'amount_total' => 450.00, // 15 × $30 upgrade
    'state' => 'sale',
]);
```

### Query: All Orders for a Project

```php
// Get all sales orders for project #123
$projectOrders = SalesOrder::where('project_id', 123)
    ->orderBy('created_at')
    ->get();

// Group by payment stage
$deposit = $projectOrders->where('woodworking_order_type', 'deposit')->first();
$progress = $projectOrders->where('woodworking_order_type', 'progress_payment');
$final = $projectOrders->where('woodworking_order_type', 'final_payment')->first();
$changeOrders = $projectOrders->where('is_change_order', true);

// Calculate totals
$totalInvoiced = $projectOrders->sum('amount_total');
$totalPaid = $projectOrders->where('invoice_status', 'paid')->sum('amount_total');
$outstanding = $totalInvoiced - $totalPaid;
```

### Production Milestones → Payment Triggers

```
┌─────────────────────────────────────────────────────────────────────────────────────┐
│                    MILESTONE STAGES → PAYMENT TRIGGERS                               │
└─────────────────────────────────────────────────────────────────────────────────────┘

┌──────────────┐    ┌──────────────┐    ┌──────────────┐    ┌──────────────┐
│  DISCOVERY   │───▶│    DESIGN    │───▶│   SOURCING   │───▶│  PRODUCTION  │
│              │    │              │    │              │    │              │
│              │    │ ▼ TRIGGER    │    │              │    │              │
│              │    │ Send Quote   │    │              │    │              │
└──────────────┘    └──────────────┘    └──────────────┘    └──────────────┘
                           │                                       │
                           ▼                                       ▼
                    ┌──────────────┐                        ┌──────────────┐
                    │  PROPOSAL    │                        │   DEPOSIT    │
                    │  ACCEPTED    │                        │    PAID      │
                    │              │                        │              │
                    │ ▼ TRIGGER    │                        │ ▼ TRIGGER    │
                    │ Deposit Inv  │                        │ production_  │
                    │              │                        │ authorized   │
                    └──────────────┘                        └──────────────┘


┌──────────────┐    ┌──────────────┐    ┌──────────────┐    ┌──────────────┐
│  PRODUCTION  │───▶│   DELIVERY   │───▶│   INSTALL    │───▶│  CLOSEOUT    │
│              │    │              │    │              │    │              │
│              │    │ ▼ TRIGGER    │    │              │    │ ▼ TRIGGER    │
│              │    │ Progress Inv │    │              │    │ Final Inv    │
│              │    │ (35%)        │    │              │    │ (35%)        │
└──────────────┘    └──────────────┘    └──────────────┘    └──────────────┘

projects_milestones.production_stage:
  - discovery
  - design
  - sourcing  
  - production
  - delivery

projects_projects.current_production_stage → tracks where project is
```

### Typical TCS Payment Schedule

| Stage | Trigger | % | Invoice Type |
|-------|---------|---|--------------|
| **Deposit** | Proposal accepted | 30% | `woodworking_order_type: deposit` |
| **Progress 1** | Cabinets delivered | 35% | `woodworking_order_type: progress_payment` |
| **Final** | Punch list complete | 35% | `woodworking_order_type: final_payment` |
| **Change Orders** | Scope changes | Varies | `is_change_order: true` |

### FK Summary for Project Sales

| Table | Column | Links To | Notes |
|-------|--------|----------|-------|
| `sales_orders` | `project_id` | `projects_projects` | **MANY orders per project** |
| `sales_orders` | `room_id` | `projects_rooms` | Optional room-specific invoice |
| `sales_orders` | `original_order_id` | `sales_orders` (self) | Change order → original |
| `sales_order_line_items` | `project_id` | `projects_projects` | Line item project link |
| `sales_order_line_items` | `room_id` | `projects_rooms` | Line item room link |
| `sales_order_line_items` | `cabinet_run_id` | `projects_cabinet_runs` | Line item run link |
| `sales_order_line_items` | `cabinet_specification_id` | `projects_cabinets` | Line item cabinet link |
