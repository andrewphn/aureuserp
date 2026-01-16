# TCS Cabinet Formulas & Dimensions Reference

> **Source**: Bryan Patton, Levi (Lead Craftsman), January 2025-2026
> **Status**: Official TCS Woodwork construction standards

---

## Table of Contents

1. [TCS Standard Constants](#tcs-standard-constants)
2. [Cabinet Box Formulas](#cabinet-box-formulas)
3. [Face Frame Formulas](#face-frame-formulas)
4. [Stretcher Formulas](#stretcher-formulas)
5. [Drawer Box Formulas](#drawer-box-formulas)
6. [Door Formulas](#door-formulas)
7. [False Front Formulas](#false-front-formulas)
8. [Shelf Formulas](#shelf-formulas)
9. [Database Fields Reference](#database-fields-reference)

---

## TCS Standard Constants

### Cabinet Heights
| Type | Height | Notes |
|------|--------|-------|
| Base Cabinet | 34.75" | 34 3/4" (+ 1.25" countertop = 36" finished) |
| Wall 30" | 30" | Standard wall cabinet |
| Wall 36" | 36" | Tall wall cabinet |
| Wall 42" | 42" | Extra tall wall cabinet |
| Tall Cabinet | 84" | Standard pantry/utility |
| Tall Cabinet 96" | 96" | Full-height pantry |
| Countertop | 1.25" | 1 1/4" thickness |

### Toe Kick
| Dimension | Value | Notes |
|-----------|-------|-------|
| Height | 4.5" | 4 1/2" standard |
| Recess | 3.0" | 3" from face |

### Material Thickness
| Material | Thickness | Notes |
|----------|-----------|-------|
| Box Material | 0.75" | 3/4" prefinished maple plywood |
| Back Panel | 0.75" | 3/4" (TCS uses full thickness backs) |
| Side Panel | 0.75" | 3/4" |
| Face Frame | 0.75" | 3/4" (5/4 hardwood) |
| Drawer Sides | 0.5" | 1/2" plywood |
| Drawer Bottom | 0.25" | 1/4" plywood |

### Face Frame
| Dimension | Value | Notes |
|-----------|-------|-------|
| Stile Width | 1.5" | 1 1/2" standard |
| Wide Stile | 1.75" | 1 3/4" optional |
| Rail Width | 1.5" | 1 1/2" standard |
| Door Gap | 0.125" | 1/8" gap to door |

### Stretchers
| Dimension | Value | Notes |
|-----------|-------|-------|
| Depth | 3.0" | 3" (TCS standard) |
| Thickness | 0.75" | 3/4" |
| Min Depth | 2.5" | 2 1/2" minimum |
| Max Depth | 4.0" | 4" maximum |

### Gaps & Offsets
| Dimension | Value | Notes |
|-----------|-------|-------|
| Back Wall Gap | 0.25" | 1/4" cabinet sits off wall |
| Finished End Gap | 0.25" | 1/4" gap between panel and cabinet |
| Finished End Extension | 0.5" | 1/2" toward wall for scribe |
| Sink Side Extension | 0.75" | 3/4" sides extend for sink cabinets |

---

## Cabinet Box Formulas

### Available Depth (from wall)
```
Cabinet Depth = Wall Space - Back Wall Gap
Cabinet Depth = Wall Space - 0.25"
```

### Box Height (above toe kick)
```
Box Height = Cabinet Height - Toe Kick Height
Box Height = Cabinet Height - 4.5"

Example: 34.75" - 4.5" = 30.25" box height
```

### Side Panel Height
```
// Standard base cabinet (with stretchers)
Side Height = Box Height - Stretcher Thickness
Side Height = Box Height - 0.75"

Example: 30.25" - 0.75" = 29.5" side height

// Sink cabinet (no stretchers)
Side Height = Box Height + Sink Side Extension
Side Height = Box Height + 0.75"

Example: 30.25" + 0.75" = 31.0" side height
```

### Interior Dimensions
```
Interior Width = Cabinet Width - (2 × Side Thickness)
Interior Width = Cabinet Width - (2 × 0.75")
Interior Width = Cabinet Width - 1.5"

Interior Depth = Cabinet Depth - Back Thickness
Interior Depth = Cabinet Depth - 0.75"

Interior Height = Box Height - Stretcher Thickness
Interior Height = Box Height - 0.75"
```

### Bottom Panel Dimensions
```
Bottom Width = Cabinet Width - (2 × Side Thickness)
Bottom Width = Cabinet Width - 1.5"

Bottom Depth = Cabinet Depth - Back Thickness
Bottom Depth = Cabinet Depth - 0.75"

Example (36" wide, 24" deep cabinet):
  Bottom Width = 36" - 1.5" = 34.5"
  Bottom Depth = 24" - 0.75" = 23.25"
```

### Back Panel Dimensions
```
Back Width = Cabinet Width - (2 × Side Thickness)
Back Width = Cabinet Width - 1.5"

Back Height = Box Height (full height, not reduced)

Example (36" wide, 30.25" box height):
  Back Width = 36" - 1.5" = 34.5"
  Back Height = 30.25"
```

### Finished End Panel (Edge Cabinets)
```
Panel Height = Box Height
Panel Depth = Cabinet Depth + Finished End Extension
Panel Depth = Cabinet Depth + 0.5"

Gap From Cabinet = 0.25"
```

---

## Face Frame Formulas

### Opening Width Calculation
```
Total Stiles Width = (2 + Mid Stile Count) × Stile Width
Total Opening Width = Cabinet Width - Total Stiles Width
Single Opening Width = Total Opening Width / Opening Count

Opening Count = Mid Stile Count + 1

Example (36" cabinet, 0 mid stiles, 1.5" stiles):
  Total Stiles Width = (2 + 0) × 1.5" = 3"
  Total Opening Width = 36" - 3" = 33"
  Single Opening Width = 33" / 1 = 33"

Example (36" cabinet, 1 mid stile):
  Total Stiles Width = (2 + 1) × 1.5" = 4.5"
  Total Opening Width = 36" - 4.5" = 31.5"
  Single Opening Width = 31.5" / 2 = 15.75"
```

### Opening Height Calculation
```
Total Rails Height = 2 × Rail Width
Total Rails Height = 2 × 1.5" = 3"

Opening Height = Face Height - Total Rails Height
Opening Height = Box Height - 3"

Example (30.25" box height):
  Opening Height = 30.25" - 3" = 27.25"
```

### Door Dimensions (from opening)
```
Door Width = Opening Width - (2 × Door Gap)
Door Width = Opening Width - (2 × 0.125")
Door Width = Opening Width - 0.25"

Door Height = Opening Height - (2 × Door Gap)
Door Height = Opening Height - 0.25"

Example (33" × 27.25" opening):
  Door Width = 33" - 0.25" = 32.75"
  Door Height = 27.25" - 0.25" = 27"
```

### Reverse Calculation (Opening → Cabinet)
```
Required Cabinet Width = Desired Opening Width + Total Stiles Width
Required Cabinet Height = Desired Opening Height + Total Rails Height + Toe Kick

Example (need 30" opening):
  Cabinet Width = 30" + 3" = 33" minimum
```

### Stile & Rail Cut Lengths
```
Stile Length = Box Height (full height)

Rail Length = Cabinet Width - (2 × Stile Width)
Rail Length = Cabinet Width - 3"

Example (36" cabinet, 30.25" box):
  Stile Length = 30.25"
  Rail Length = 36" - 3" = 33"
```

---

## Stretcher Formulas

### Stretcher Count
```
Required Stretchers = 2 + Drawer Count

2 = Front stretcher + Back stretcher
+1 per drawer (for slide mounting)

Example (cabinet with 3 drawers):
  Stretchers = 2 + 3 = 5 stretchers
```

### Stretcher Dimensions
```
Stretcher Width = Cabinet Width - (2 × Side Thickness)
Stretcher Width = Cabinet Width - 1.5"

Stretcher Depth = 3.0" (TCS standard)
Stretcher Thickness = 0.75"

Example (36" cabinet):
  Stretcher Width = 36" - 1.5" = 34.5"
  Stretcher Depth = 3"
  Stretcher Thickness = 0.75"
```

### Stretcher Positioning
```
// Front stretcher
Position From Front = 0" (at front edge)

// Back stretcher
Position From Front = Cabinet Depth - Stretcher Depth
Position From Front = Cabinet Depth - 3"

// Drawer support stretchers
// Position between drawer openings, splitting the gap between faces
Position From Top = Sum(Face Heights Above) + (Gap Count × 0.125")
Position From Bottom = Box Height - Position From Top
```

### Sink Cabinet (No Stretchers)
```
Stretcher Count = 0
Side Height = Box Height + 0.75" (extends up)
Top = Open for sink/plumbing access
```

---

## Drawer Box Formulas

### Blum TANDEM 563H Specifications

#### Side Thickness Options
| Drawer Side | Deduction | Inside Width Deduction |
|-------------|-----------|------------------------|
| 5/8" (16mm) | 0.40625" (13/32") | 1.3125" (1-5/16") |
| 1/2" (13mm) | 0.625" (5/8") | 1.65625" (1-21/32") |

#### Height Deductions
```
Top Clearance = 0.25" (6mm)
Bottom Clearance = 0.5625" (9/16" / 14mm)
Total Height Deduction = 0.8125" (13/16" / 20mm)
```

### Drawer Box Dimensions
```
// Using 1/2" drawer sides (TCS standard)
Box Outside Width = Opening Width - Side Deduction
Box Outside Width = Opening Width - 0.625"

Box Height = Opening Height - Height Deduction
Box Height = Opening Height - 0.8125"
Box Height (Shop) = Round Down to 1/2" increment

Box Depth = Slide Length
Box Depth (Shop) = Slide Length + 0.25" (for bottom dado)

Example (12" wide × 6" high opening, 18" slides):
  Box Width = 12" - 0.625" = 11.375"
  Box Height = 6" - 0.8125" = 5.1875" → Shop: 5"
  Box Depth = 18"
  Box Depth (Shop) = 18.25"
```

### Drawer Piece Dimensions
```
// Sides (2 pcs)
Side Width = Box Height
Side Length = Box Depth (Shop)
Side Thickness = 0.5"

// Front & Back (2 pcs)
Front/Back Width = Box Height
Front/Back Length = Box Width - (2 × Side Thickness)
Front/Back Length = Box Width - 1"
Front/Back Thickness = 0.5"

// Bottom (1 pc)
Bottom Width = Box Width - (2 × 0.375")  // In dado groove
Bottom Length = Box Depth - (2 × 0.375")
Bottom Thickness = 0.25"
```

### Bottom Panel Dado Specifications
```
Dado Height = 0.5" up from bottom edge
Dado Depth = 0.25"
Bottom Clearance in Dado = 0.0625" (1/16")
```

### Minimum Cabinet Depths for Blum Slides
| Slide Length | Minimum Cabinet Depth |
|--------------|----------------------|
| 21" | 21.9375" (21-15/16") |
| 18" | 18.90625" (18-29/32") |
| 15" | 15.90625" (15-29/32") |
| 12" | 12.90625" (12-29/32") |
| 9" | 10.46875" (10-15/32") |

**Shop Practice**: Min Depth = Slide Length + 0.75"

---

## Door Formulas

### Door Dimensions (from face frame opening)
```
Door Width = Opening Width - (2 × Door Gap)
Door Width = Opening Width - 0.25"

Door Height = Opening Height - (2 × Door Gap)
Door Height = Opening Height - 0.25"
```

### Double Door Configuration
```
Single Door Width = (Opening Width - Mid Stile Width - (4 × Door Gap)) / 2
Single Door Width = (Opening Width - 1.5" - 0.5") / 2

Example (33" opening with mid stile):
  Single Door Width = (33" - 1.5" - 0.5") / 2 = 15.5"
```

### Hinge Quantity
```
Door Height ≤ 40" → 2 hinges
Door Height > 40" and ≤ 60" → 3 hinges
Door Height > 60" → 4 hinges
```

---

## False Front Formulas

### TCS Rule: All False Fronts Have Backing
```
Backing = ALWAYS present
Backing Purpose = False front support + Functions as stretcher
```

### False Front Panel
```
Panel Width = Opening Width (or as specified)
Panel Height = Opening Height (or as specified)
Panel Thickness = 0.75"
```

### Backing Dimensions
```
Backing Width = Panel Width
Backing Height = Panel Height + Overhang
Backing Height = Panel Height + 1.0" (typical)
Backing Thickness = 0.75"

// Backing extends past panel to reach stretcher position
```

### Cut List Output
```
Part 1: False Front Panel
  - Width × Height × 0.75"

Part 2: Backing/Stretcher
  - Width × (Height + 1") × 0.75"
  - Note: "Backing serves dual purpose: false front backing AND stretcher"
```

---

## Shelf Formulas

### Adjustable Shelf Dimensions
```
Shelf Width = Interior Width - (2 × Pin Clearance)
Shelf Width = Interior Width - 0.125"

Shelf Depth = Interior Depth - Front Clearance
Shelf Depth = Interior Depth - 0.25"
```

### Pin Hole Layout
```
Pin Setback Front = 2.0" from front edge
Pin Setback Back = 2.0" from back edge
Pin Vertical Spacing = 2.0" between holes
Pin Hole Diameter = 5mm (0.1969")
```

### Center Support (for deep cabinets)
```
IF Cabinet Depth ≥ 28" THEN
  Add 3rd column of pin holes at depth ÷ 2
  (Prevents shelf sag)
```

### Notch Specifications
```
Standard Notch Depth = 0.375" (3/8")
Deep Notch Depth = 0.625" (5/8")
```

---

## Database Fields Reference

### Cabinet (`projects_cabinets`)

| Field | Type | Description |
|-------|------|-------------|
| `length_inches` | decimal(8,2) | Cabinet width (front face) |
| `width_inches` | decimal(8,2) | Cabinet width (legacy) |
| `depth_inches` | decimal(8,2) | Cabinet depth (front to back) |
| `height_inches` | decimal(8,2) | Cabinet total height |
| `linear_feet` | decimal(8,2) | Length in linear feet |
| `cabinet_level` | string | Pricing level |
| `material_category` | string | Material type |
| `top_construction_type` | string | 'stretchers', 'full_top', 'none' |
| `stretcher_height_inches` | decimal(8,3) | Stretcher depth override |
| `sink_requires_extended_sides` | boolean | True for sink cabinets |
| `sink_side_extension_inches` | decimal(8,3) | Side extension amount |
| `face_frame_stile_width_inches` | decimal(8,3) | Stile width override |
| `face_frame_rail_width_inches` | decimal(8,3) | Rail width override |
| `face_frame_mid_stile_count` | integer | Number of mid stiles |
| `face_frame_door_gap_inches` | decimal(8,3) | Door gap override |
| `construction_template_id` | integer | FK to construction template |

### Stretcher (`projects_stretchers`)

| Field | Type | Description |
|-------|------|-------------|
| `width_inches` | float | Stretcher width (= cabinet inside width) |
| `depth_inches` | float | Stretcher depth (front to back, typically 3") |
| `thickness_inches` | float | Stretcher thickness (0.75") |
| `position` | string | 'front', 'back', 'drawer_support' |
| `stretcher_number` | integer | Stretcher number in cabinet |
| `position_from_front_inches` | float | Distance from cabinet front |
| `position_from_top_inches` | float | Distance from cabinet top |
| `position_from_bottom_inches` | float | Distance from cabinet bottom |
| `supports_drawer` | boolean | Whether this stretcher supports a drawer |
| `drawer_id` | integer | FK to linked drawer |
| `cut_width_shop_inches` | float | Shop-rounded width (to 1/16") |
| `cut_depth_shop_inches` | float | Shop-rounded depth (to 1/16") |

### Drawer (`projects_drawers`)

| Field | Type | Description |
|-------|------|-------------|
| `front_width_inches` | float | Drawer front width |
| `front_height_inches` | float | Drawer front height |
| `box_width_inches` | float | Interior box width |
| `box_depth_inches` | float | Interior box depth |
| `box_height_inches` | float | Interior box height |
| `box_thickness` | float | Material thickness (0.5") |
| `slide_type` | string | Slide hardware type |
| `slide_model` | string | Slide model number |
| `slide_length_inches` | float | Slide length |
| `slide_quantity` | integer | Number of slides (typically 2) |
| `soft_close` | boolean | Soft-close feature |
| `position_in_opening_inches` | float | Position within opening |
| `consumed_height_inches` | float | Height consumed in opening |

### Faceframe (`projects_faceframes`)

| Field | Type | Description |
|-------|------|-------------|
| `stile_width` | decimal(8,3) | Stile width (1.5" default) |
| `rail_width` | decimal(8,3) | Rail width (1.5" default) |
| `material_thickness` | decimal(8,3) | Thickness (0.75" default) |
| `face_frame_type` | string | 'standard', 'false_frame', 'beaded' |
| `joinery_type` | string | 'pocket_hole', 'dowel', 'mortise_tenon' |
| `overlay_type` | string | 'full_overlay', 'partial_overlay', 'inset' |
| `beaded_face_frame` | boolean | Has beaded profile |
| `flush_with_carcass` | boolean | Flush with cabinet box |
| `overhang_left` | decimal(8,3) | Left overhang amount |
| `overhang_right` | decimal(8,3) | Right overhang amount |

### Door (`projects_doors`)

| Field | Type | Description |
|-------|------|-------------|
| `width_inches` | float | Door width |
| `height_inches` | float | Door height |
| `thickness_inches` | float | Door thickness |
| `rail_width_inches` | float | Door rail width |
| `stile_width_inches` | float | Door stile width |
| `profile_type` | string | Door profile style |
| `hinge_type` | string | Hinge type |
| `hinge_model` | string | Hinge model number |
| `hinge_quantity` | integer | Number of hinges |
| `hinge_side` | string | 'left' or 'right' |
| `has_glass` | boolean | Glass panel door |
| `glass_type` | string | Type of glass |

### False Front (`projects_false_fronts`)

| Field | Type | Description |
|-------|------|-------------|
| `width_inches` | float | Panel width |
| `height_inches` | float | Panel height |
| `thickness_inches` | float | Panel thickness (0.75") |
| `false_front_type` | string | 'fixed' or 'tilt_out' |
| `has_backing` | boolean | Always true (TCS rule) |
| `backing_height_inches` | float | Backing height (extends past face) |
| `backing_thickness_inches` | float | Backing thickness (0.75") |
| `backing_is_stretcher` | boolean | Always true (TCS rule) |

### Cabinet Section (`projects_cabinet_sections`)

| Field | Type | Description |
|-------|------|-------------|
| `width_inches` | float | Section width |
| `height_inches` | float | Section height |
| `position_from_left_inches` | float | Position from cabinet left |
| `position_from_bottom_inches` | float | Position from cabinet bottom |
| `opening_width_inches` | float | Opening width inside face frame |
| `opening_height_inches` | float | Opening height inside face frame |
| `section_type` | string | 'door', 'drawer_bank', 'open_shelf', etc. |
| `layout_direction` | string | 'vertical', 'horizontal', 'grid' |
| `section_width_ratio` | float | Width ratio in cabinet |
| `section_height_ratio` | float | Height ratio in cabinet |

---

## Quick Reference Card

### Base Cabinet (36"W × 34.75"H × 24"D)

```
Cabinet Dimensions:
  Width: 36"
  Height: 34.75"
  Depth: 24" (includes 0.25" wall gap allowance)

Box Height: 34.75" - 4.5" = 30.25"

Bottom Panel: 34.5" × 23.25" × 0.75"
Side Panels: 23.25" × 29.5" × 0.75" (reduced for stretchers)
Back Panel: 34.5" × 30.25" × 0.75"
Stretchers: 34.5" × 3" × 0.75" (qty: 2 + drawer count)

Face Frame:
  Stiles: 1.5" × 30.25" (qty: 2)
  Rails: 1.5" × 33" (qty: 2)
  Opening: 33" × 27.25"
  Door: 32.75" × 27"
```

### Sink Cabinet Variation
```
Stretchers: NONE
Side Height: 30.25" + 0.75" = 31" (full height + extension)
Top: Open for sink access
```

### Edge Cabinet Addition
```
Finished End Panel:
  Height: 30.25" (box height)
  Depth: 24.5" (cabinet depth + 0.5")
  Gap from cabinet: 0.25"
```
