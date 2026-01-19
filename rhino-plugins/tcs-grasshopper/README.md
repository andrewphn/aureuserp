# TCS Grasshopper Cabinet System

A complete Grasshopper-based UI for TCS cabinet design that connects to the TCS ERP API.

## Overview

This system provides:
- **API connectivity** to TCS ERP for project/cabinet data
- **Parametric cabinet design** with visual data flow
- **3D geometry generation** from calculated dimensions
- **Manual overrides** for any calculated value
- **Bidirectional sync** with TCS ERP

## Prerequisites

1. **Rhino 7+** with Grasshopper
2. **Human UI** plugin from Food4Rhino: https://www.food4rhino.com/en/app/human-ui
3. **TCS ERP API Token** from aureuserp.test admin panel

## Installation

1. Copy the component folders to your Grasshopper User Objects folder:
   ```
   %APPDATA%\Grasshopper\UserObjects\TCS\
   ```

2. Or reference the Python files directly in GHPython components.

## Component Groups

### Group 1: API & Authentication (`api/`)

| Component | File | Description |
|-----------|------|-------------|
| TCS API Connect | `tcs_api_connect.py` | API connection + auth token |
| TCS API Fetch | `tcs_api_fetch.py` | GET requests with caching |
| TCS API Write | `tcs_api_write.py` | POST/PUT/DELETE requests |

### Group 2: Hierarchy Navigation (`navigation/`)

| Component | File | Description |
|-----------|------|-------------|
| TCS Project Selector | `tcs_project_selector.py` | Project dropdown + details |
| TCS Room Navigator | `tcs_room_navigator.py` | Room → Location cascading |
| TCS Cabinet List | `tcs_cabinet_list.py` | Cabinet list with selection |
| TCS Hierarchy Tree | `tcs_hierarchy_tree.py` | Full tree visualization |

### Group 3: Cabinet Calculator (`calculator/`)

| Component | File | Description |
|-----------|------|-------------|
| TCS Cabinet Calculator | `tcs_cabinet_calc.py` | Call /calculate endpoint |
| TCS Cut List | `tcs_cut_list.py` | Display cut list table |
| TCS Override Manager | `tcs_override_manager.py` | Store/apply overrides |
| TCS Pricing | `tcs_pricing.py` | Pricing with overrides |

### Group 4: Geometry Generator (`geometry/`)

| Component | File | Description |
|-----------|------|-------------|
| TCS Cabinet Box | `tcs_cabinet_box.py` | Generate cabinet envelope |
| TCS Parts Generator | `tcs_parts_generator.py` | Generate individual parts |
| TCS Face Frame | `tcs_face_frame_geo.py` | Face frame geometry |
| TCS Drawer | `tcs_drawer_geo.py` | Drawer box geometry |

### Group 5: Human UI Panel (`ui/`)

| Component | File | Description |
|-----------|------|-------------|
| TCS Cabinet Panel | `tcs_cabinet_panel.py` | Panel data for Human UI |
| TCS Save to ERP | `tcs_save_to_erp.py` | Save changes to ERP |

## Quick Start

### 1. Set Up API Connection

```
[Text Panel: API URL] ─────┬──▶ [TCS API Connect]
                           │
[Text Panel: API Token] ───┘         │
                                     ▼
                              [api_base, auth_header]
```

### 2. Navigate to Cabinet

```
[TCS API Connect] ──▶ [TCS Project Selector] ──▶ [TCS Room Navigator] ──▶ [TCS Cabinet List]
                            │                          │                       │
                      [project_id]              [cabinet_run_id]          [cabinet_id]
```

### 3. Calculate and Generate Geometry

```
[cabinet_id] ──▶ [TCS Cabinet Calculator] ──▶ [TCS Parts Generator] ──▶ [Bake]
                        │                            │
                  [dimensions]                 [all_parts]
                        │
                        ▼
               [TCS Cut List] ──▶ [Human UI Table]
```

### 4. Create Human UI Panel

```
[TCS Cabinet Panel] ──┬──▶ [Human UI Text Block] (title)
                      │
                      ├──▶ [Human UI Sliders] (dimensions)
                      │
                      ├──▶ [Human UI Table] (cut list)
                      │
                      └──▶ [Human UI Buttons] (save/reset)
```

## Data Flow Diagram

```
┌─────────────────────────────────────────────────────────────────────┐
│                     TCS CABINET GRASSHOPPER DEFINITION               │
├─────────────────────────────────────────────────────────────────────┤
│                                                                      │
│  ┌──────────────┐    ┌─────────────────┐    ┌──────────────────┐   │
│  │ API Connect  │───▶│ Project Select  │───▶│ Room Navigator   │   │
│  │ (auth token) │    │ (Human UI)      │    │ (cascading)      │   │
│  └──────────────┘    └─────────────────┘    └────────┬─────────┘   │
│                                                       │              │
│                                                       ▼              │
│  ┌──────────────┐    ┌─────────────────┐    ┌──────────────────┐   │
│  │ Geometry     │◀───│ Cabinet Calc    │◀───│ Cabinet Select   │   │
│  │ Generator    │    │ (API + overrides)│    │ (from run)       │   │
│  └──────┬───────┘    └─────────────────┘    └──────────────────┘   │
│         │                     │                                      │
│         ▼                     ▼                                      │
│  ┌──────────────┐    ┌─────────────────┐                            │
│  │ Bake to      │    │ Override Panel  │                            │
│  │ Rhino        │    │ (Human UI)      │                            │
│  └──────────────┘    └─────────────────┘                            │
│                                                                      │
└─────────────────────────────────────────────────────────────────────┘
```

## TCS Construction Constants

These constants are used throughout the system for accurate cabinet construction:

```python
TOE_KICK_HEIGHT = 4.5"
TOE_KICK_SETBACK = 3.0"
STRETCHER_DEPTH = 3.0"
FACE_FRAME_STILE = 1.5"
FACE_FRAME_RAIL = 1.5"
MATERIAL_THICKNESS = 0.75"  # 3/4" plywood
BACK_PANEL_THICKNESS = 0.25"  # 1/4" back
COMPONENT_GAP = 0.125"

# Blum TANDEM 563H Slide
BLUM_SIDE_DEDUCTION = 0.625"  # 5/8" per side
BLUM_HEIGHT_DEDUCTION = 0.8125"  # 13/16"
```

## Coordinate System

The system uses a Y-up coordinate system internally, transformed to Rhino's Z-up:

```
TCS Internal:           Rhino:
Y = Height (up)         Z = Height (up)
Z = Depth               Y = Depth
X = Width               X = Width

Transform: (x, y, z) → (x, z, y + toe_kick_height)
```

## API Endpoints Used

| Category | Endpoints |
|----------|-----------|
| Projects | `GET/POST/PUT/DELETE /api/v1/projects` |
| Project Tree | `GET /api/v1/projects/{id}/tree` |
| Rooms | `GET/POST/PUT/DELETE /api/v1/rooms` |
| Locations | `GET/POST/PUT/DELETE /api/v1/room-locations` |
| Cabinet Runs | `GET/POST/PUT/DELETE /api/v1/cabinet-runs` |
| Cabinets | `GET/POST/PUT/DELETE /api/v1/cabinets` |
| Calculate | `POST /api/v1/cabinets/{id}/calculate` |
| Cut List | `GET /api/v1/cabinets/{id}/cut-list` |

## TCS Unified Specification

This system follows the **TCS Unified Rhino/ERP Specification v1.0.0**.

See: `docs/rhino/TCS_UNIFIED_SPEC.md`

### Key Metadata Fields

All Grasshopper-generated geometry MUST include these User Text attributes:

| Field | Format | Example |
|-------|--------|---------|
| `TCS_PART_ID` | `{SHORT_CODE}-{CABINET_NUM}-{PART}` | `AUST-BTH1-B1-C1-LeftSide` |
| `TCS_CABINET_ID` | `{SHORT_CODE}-{CABINET_NUM}` | `AUST-BTH1-B1-C1` |
| `TCS_ERP_ID` | Numeric | `123` |
| `TCS_MATERIAL` | Layer name | `3-4_PreFin` |
| `TCS_PART_TYPE` | Part category | `cabinet_box` |
| `TCS_THICKNESS` | Inches | `0.75` |
| `TCS_HAS_OVERRIDES` | Boolean string | `true` or `false` |
| `TCS_OVERRIDES` | JSON | `{"width": 36.5}` |

### Cabinet ID Format

Cabinet IDs use the short project code (4 letters) from the project name:

```
Project Number: TCS-001-9AustinFarmRoad
Cabinet Number: BTH1-B1-C1
Cabinet ID:     AUST-BTH1-B1-C1

Short code extraction:
  "9AustinFarmRoad" → "AUST" (remove leading digits, take 4 chars)
  "15WSankaty" → "SANK" (remove number + direction)
```

### Material Layers

Geometry should be placed on `TCS_Materials::` sublayers:

| Layer | Thickness | Description |
|-------|-----------|-------------|
| `TCS_Materials::3-4_PreFin` | 0.75" | Cabinet box parts |
| `TCS_Materials::3-4_Medex` | 0.75" | Paint grade (toe kicks) |
| `TCS_Materials::3-4_RiftWO` | 0.75" | Rift white oak (drawer faces) |
| `TCS_Materials::1-2_Baltic` | 0.50" | Drawer box parts |
| `TCS_Materials::1-4_Plywood` | 0.25" | Backs and drawer bottoms |
| `TCS_Materials::5-4_Hardwood` | 1.00" | Face frames |

## Override System

Overrides are stored in object User Text (per unified spec):

```python
# Object User Text (on each part)
"TCS_HAS_OVERRIDES" = "true"
"TCS_OVERRIDES" = '{"width": 36.0, "height": 34.5}'

# Legacy document-level storage (deprecated)
"TCS_OVERRIDES_{cabinet_id}"
```

Override data structure:
```json
{
    "width": 36.0,
    "height": 34.5,
    "depth": 24.0,
    "price_per_lf": 125.0,
    "timestamp": "2025-01-19 12:00:00"
}
```

## Human UI Integration

### Recommended Human UI Components

1. **Create Dropdown** - For project/room/cabinet selection
2. **Create Slider** - For dimension overrides with toggle
3. **Create Toggle** - For override enables
4. **Create Button** - For save/reset actions
5. **Create Table** - For cut list display
6. **Text Block** - For status and pricing info

### Panel Layout Example

```
┌─────────────────────────────────────────────────────┐
│ CABINET: B1 - 36" Base               [🔄 Refresh]  │
├─────────────────────────────────────────────────────┤
│ EXTERIOR DIMENSIONS                                  │
│ Width:   [====36====] ☑ Override                    │
│ Height:  [===34.5===] ☐                             │
│ Depth:   [====24====] ☐                             │
├─────────────────────────────────────────────────────┤
│ CUT LIST                                             │
│ ┌───────────────┬────────┬────────┬───────┐        │
│ │ Part          │ Width  │ Height │ Qty   │        │
│ ├───────────────┼────────┼────────┼───────┤        │
│ │ Left Side     │ 23.25  │ 34.5   │ 1     │        │
│ │ Right Side    │ 23.25  │ 34.5   │ 1     │        │
│ │ Bottom        │ 34.5   │ 23.25  │ 1     │        │
│ └───────────────┴────────┴────────┴───────┘        │
├─────────────────────────────────────────────────────┤
│ PRICING                                              │
│ Linear Feet: 3.0'                                   │
│ $/LF: $125.00                                       │
│ Total: $375.00                                      │
├─────────────────────────────────────────────────────┤
│ [💾 Save to ERP]  [↩ Reset Overrides]               │
└─────────────────────────────────────────────────────┘
```

## Troubleshooting

### Connection Issues
- Verify API URL ends with no trailing slash
- Check API token is valid and not expired
- For local dev, SSL verification is disabled

### Geometry Issues
- Ensure all dimensions are positive numbers
- Check cabinet_type is valid (base, wall, tall)
- Verify toe kick settings for wall cabinets

### Cache Issues
- Use the `refresh` input to bypass cache
- Caches expire after 60-120 seconds
- Save operations automatically clear caches

## Development

### Adding New Components

1. Create Python file in appropriate folder
2. Include standard header with INPUTS/OUTPUTS
3. Set `ghenv.Component` metadata
4. Handle missing Rhino gracefully (`HAS_RHINO` check)
5. Print debug info to component output

### Testing

1. Open Rhino and Grasshopper
2. Create GHPython component
3. Copy/paste component code
4. Set input/output names to match code
5. Connect to other TCS components

## Version History

- **v1.0** - Initial release with full component set
  - API connectivity
  - Navigation hierarchy
  - Calculator integration
  - Geometry generation
  - Human UI support
