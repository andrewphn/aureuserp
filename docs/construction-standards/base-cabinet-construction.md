# TCS Base Cabinet Construction Standard

> **Source**: Bryan Patton, January 2026
> **Status**: Default construction standard for all base cabinets

## Cabinet Run Context

At the **cabinet run level**:
- **Countertop** sits on top of the entire run
- **Toe kick** runs along the bottom of the run
- **Back wall gap**: Cabinets sit **1/4" off the back wall** for safety

```
        Wall
         │
         │◄─ 1/4" gap
         │
    ┌────┴────┐
    │ cabinet │
    │   back  │
    └─────────┘
```

## Construction Sequence

Base cabinets are built in this specific order:

### 1. Bottom Piece
- Defined first
- Sets the foundation for the cabinet box
- Width = Cabinet width - (2 × side panel thickness)
- Depth = Cabinet depth - back panel thickness

### 2. Side Panels (×2)
- **Height is reduced** by material thickness of the stretchers (rails)
- Standard side height = Box height - stretcher thickness (0.75")
- This reduction allows stretchers to sit flush on top

### 3. Stretchers/Rails (×2 minimum)
- Run on top of the sides
- Support the countertop
- Front stretcher + Back stretcher = 2 minimum
- Additional stretchers added for drawers (behind each drawer)

### 4. Back Panel
- Full 3/4" plywood (TCS standard)
- Fits between sides
- Height = Box height (full height, not reduced)

## Sink Cabinet Variation

When cabinet is designated as a **sink cabinet**:

1. **Question asked**: "Is this a sink cabinet?"
2. **If YES**:
   - Side panels return to **regular full height** (no stretcher reduction)
   - **Top stretchers are removed** (open top for sink/plumbing access)
   - Sides extend up by `sink_side_extension` (0.75" default)

```
Standard Base:          Sink Base:
┌─────────────┐        ┌─────────────┐
│ [stretcher] │        │             │  ← No stretchers
│ [stretcher] │        │             │
├─────────────┤        ├─────────────┤
│             │        │             │
│    box      │        │    box      │
│             │        │             │
└─────────────┘        └─────────────┘
```

## Edge Cabinet (Finished End Panel)

When cabinet is on an **edge of the run** (left end or right end):

1. That edge gets a **finished end panel** (face)
2. **1/4" gap** between face panel and cabinet box
3. Face panel is **extended 1/2" toward the wall**
   - Accounts for wall unevenness
   - Installers scribe/fit on site

```
           ┌─ 1/2" extension (toward wall)
           │
           ▼
    ┌──────────┐
    │          │
    │  face    │  ← Finished end panel
    │  panel   │
    │          │
    └──────────┘
         │
         └─ 1/4" gap from cabinet box
```

## Dimension Calculations

### Available Depth Calculation

When given a wall depth, the actual cabinet depth accounts for the back wall gap:

```php
$wallDepth = 24.5;        // Available space from wall
$backWallGap = 0.25;      // 1/4" safety gap from wall

// Actual cabinet depth
$cabinetDepth = $wallDepth - $backWallGap; // 24.25"
```

### Standard Base Cabinet (with stretchers)

```php
// Given inputs
$cabinetWidth = 36;      // inches
$cabinetHeight = 34.75;  // inches (standard base)
$cabinetDepth = 24;      // inches (already accounts for back wall gap)
$toeKickHeight = 4.5;    // inches
$materialThickness = 0.75; // inches (3/4" plywood)
$stretcherThickness = 0.75; // inches

// Box height (above toe kick)
$boxHeight = $cabinetHeight - $toeKickHeight; // 30.25"

// Side panel height (reduced for stretchers)
$sidePanelHeight = $boxHeight - $stretcherThickness; // 29.5"

// Bottom panel dimensions
$bottomWidth = $cabinetWidth - (2 * $materialThickness); // 34.5"
$bottomDepth = $cabinetDepth - $materialThickness; // 23.25"

// Back panel dimensions
$backWidth = $cabinetWidth - (2 * $materialThickness); // 34.5"
$backHeight = $boxHeight; // 30.25" (full height)

// Stretcher dimensions
$stretcherWidth = $cabinetWidth - (2 * $materialThickness); // 34.5"
$stretcherDepth = 3.0; // inches (standard)
```

### Sink Cabinet Variation

```php
// Side panels go to full height (no stretcher reduction)
$sidePanelHeight = $boxHeight + $sinkSideExtension; // 31.0"

// No stretchers
$stretcherCount = 0;
```

### Edge Cabinet (Finished End)

```php
// Finished end panel dimensions
$finishedEndHeight = $boxHeight;
$finishedEndDepth = $cabinetDepth + 0.5; // 1/2" extension toward wall

// Gap from cabinet box
$finishedEndGap = 0.25; // 1/4" gap
```

## Related Constants

From `Cabinet.php`:

```php
// Heights
public const STANDARD_HEIGHTS = [
    'base' => 34.75,  // 34 3/4" (countertop makes 36")
];

// Toe Kick
public const STANDARD_TOE_KICK_HEIGHT = 4.5;
public const STANDARD_TOE_KICK_RECESS = 3.0;

// Stretchers
public const STANDARD_STRETCHER_HEIGHT = 3.0;

// Materials
public const DEFAULT_BOX_THICKNESS = 0.75;
public const DEFAULT_BACK_THICKNESS = 0.75;

// Sink
public const SINK_SIDE_EXTENSION = 0.75;

// Back Wall Gap
public const BACK_WALL_GAP = 0.25;

// Finished End Panel
public const FINISHED_END_GAP = 0.25;
public const FINISHED_END_WALL_EXTENSION = 0.5;
```

## Database Fields

Relevant fields on `projects_cabinets` table:

| Field | Type | Description |
|-------|------|-------------|
| `top_construction_type` | string | 'stretchers', 'full_top', 'none' |
| `stretcher_height_inches` | decimal | Stretcher depth (default 3.0") |
| `sink_requires_extended_sides` | boolean | True for sink cabinets |
| `sink_side_extension_inches` | decimal | How much to extend sides (0.75") |

## Construction Template Override

These defaults can be overridden via `ConstructionTemplate`:

```php
// In ConstructionStandardsService::FALLBACK_DEFAULTS
'stretcher_depth' => 3.0,
'stretcher_thickness' => 0.75,
'sink_side_extension' => 0.75,
'box_material_thickness' => 0.75,
'back_panel_thickness' => 0.75,
```

## Component Generation Order

When auto-generating cabinet components:

1. Calculate box dimensions
2. Create bottom piece
3. Create side panels (with stretcher reduction if applicable)
4. Create stretchers (if not sink cabinet)
5. Create back panel
6. Check for edge position → add finished end panel if needed
