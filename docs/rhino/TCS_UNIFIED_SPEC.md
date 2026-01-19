# TCS Unified Rhino/ERP Specification

## Overview

This specification defines the canonical format for TCS cabinet data exchange between:
- **AureusERP** (Laravel/PHP backend)
- **Rhino/Grasshopper** (parametric design)
- **V-Carve** (CNC nesting and machining)

All systems MUST use these formats for interoperability.

---

## 1. Cabinet Identification

### Primary Identifiers

| Field | Format | Example | Description |
|-------|--------|---------|-------------|
| `TCS_ERP_ID` | Integer | `123` | Database primary key |
| `TCS_PROJECT_NUMBER` | String | `TCS-001-9AustinFarmRoad` | Full ERP project number |
| `TCS_CABINET_NUMBER` | String | `BTH1-B1-C1` | ERP cabinet_number field |
| `TCS_CABINET_ID` | String | `AUST-BTH1-B1-C1` | **Canonical Rhino ID** |
| `TCS_FULL_CODE` | String | `TCS-001-9AustinFarmRoad-BTH1-SW-B1` | Full ERP code (reference only) |

### Cabinet ID Generation

The `TCS_CABINET_ID` is the primary identifier for Rhino objects:

```
TCS_CABINET_ID = {SHORT_PROJECT_CODE}-{CABINET_NUMBER}

Where:
  SHORT_PROJECT_CODE = First 4 letters of project name (uppercase)
  CABINET_NUMBER     = ERP cabinet_number field
```

**Examples:**
| Project Number | Cabinet Number | TCS_CABINET_ID |
|----------------|----------------|----------------|
| `TCS-001-9AustinFarmRoad` | `BTH1-B1-C1` | `AUST-BTH1-B1-C1` |
| `TCS-002-15WSankaty` | `KIT-01` | `SANK-KIT-01` |
| `TCS-003-SmithResidence` | `MBR-W1-C3` | `SMIT-MBR-W1-C3` |

**Short Code Extraction Rules:**
1. Split project_number by `-`, take 3rd part (name)
2. Remove leading digits: `9AustinFarmRoad` → `AustinFarmRoad`
3. Remove direction prefix if present: `15WSankaty` → `Sankaty`
4. Take first 4 characters, uppercase: `AUST`, `SANK`, `SMIT`

---

## 2. Part Identification

### Part ID Format

```
TCS_PART_ID = {TCS_CABINET_ID}-{PART_NAME}

Where:
  PART_NAME = Descriptive name (PascalCase or snake_case)
```

**Examples:**
| Cabinet ID | Part Name | TCS_PART_ID |
|------------|-----------|-------------|
| `AUST-BTH1-B1-C1` | `LeftSide` | `AUST-BTH1-B1-C1-LeftSide` |
| `AUST-BTH1-B1-C1` | `Bottom` | `AUST-BTH1-B1-C1-Bottom` |
| `SANK-KIT-01` | `DrawerFace_1` | `SANK-KIT-01-DrawerFace_1` |

### Standard Part Names

Use these canonical names for consistency:

**Box Parts:**
- `LeftSide`, `RightSide`
- `Top`, `Bottom`
- `Back`
- `Stretcher`, `Stretcher_Front`, `Stretcher_Back`

**Face Parts:**
- `FaceFrame`
- `FinishedEnd_Left`, `FinishedEnd_Right`

**Drawer Parts:**
- `DrawerFace_1`, `DrawerFace_2`, etc.
- `DrawerBox_1_Left`, `DrawerBox_1_Right`, `DrawerBox_1_Front`, `DrawerBox_1_Back`
- `DrawerBottom_1`

**Door Parts:**
- `Door_Left`, `Door_Right`
- `Door_1`, `Door_2`, etc.

**Other:**
- `ToeKick`
- `Shelf_1`, `Shelf_2`, etc.
- `Divider_Vertical`, `Divider_Horizontal`

---

## 3. Material System

### Material Layer Names

Format: `{THICKNESS_FRACTION}_{MATERIAL_CODE}`

| Layer Name | Thickness | Description |
|------------|-----------|-------------|
| `3-4_PreFin` | 0.75" | 3/4" Prefinished Plywood |
| `3-4_Medex` | 0.75" | 3/4" Medex MDF (paint grade) |
| `3-4_RiftWO` | 0.75" | 3/4" Rift White Oak |
| `1-2_Baltic` | 0.50" | 1/2" Baltic Birch |
| `1-4_Plywood` | 0.25" | 1/4" Plywood (backs, bottoms) |
| `5-4_Hardwood` | 1.00" | 5/4" Hardwood (face frames) |

### Part Type to Material Mapping

| Part Type | Default Material | Can Override |
|-----------|------------------|--------------|
| `cabinet_box` | `3-4_PreFin` | Yes |
| `toe_kick` | `3-4_Medex` | Yes |
| `face_frame` | `5-4_Hardwood` | Yes |
| `drawer_face` | `3-4_RiftWO` | Yes |
| `finished_end` | `3-4_RiftWO` | Yes |
| `stretcher` | `3-4_PreFin` | Yes |
| `drawer_box` | `1-2_Baltic` | No |
| `drawer_box_bottom` | `1-4_Plywood` | No |

### Material Layer Colors (RGB)

```
3-4_PreFin:   [139, 90, 43]    # Saddle brown
3-4_Medex:    [65, 105, 225]   # Royal blue
3-4_RiftWO:   [210, 180, 140]  # Tan
1-2_Baltic:   [255, 228, 181]  # Moccasin
1-4_Plywood:  [240, 230, 200]  # Light tan
5-4_Hardwood: [205, 133, 63]   # Peru
```

---

## 4. Rhino Layer Hierarchy

### Standard Layer Structure

```
TCS_Materials/                    # Material-based (for V-Carve nesting)
├── 3-4_PreFin/
├── 3-4_Medex/
├── 3-4_RiftWO/
├── 1-2_Baltic/
├── 1-4_Plywood/
└── 5-4_Hardwood/

TCS_Projects/                     # Project organization
├── {SHORT_CODE}/                 # e.g., AUST, SANK
│   └── {CABINET_ID}/            # e.g., AUST-BTH1-B1-C1
│       └── (geometry on material layers)

TCS_Dimensions/                   # Dimension annotations
TCS_Annotations/                  # Text, leaders, notes
TCS_Construction/                 # Construction geometry (hidden)
```

### Layer Assignment Rules

1. **Geometry** goes on `TCS_Materials::{material}` layer
2. **Dimensions** go on `TCS_Dimensions` layer
3. **Annotations** go on `TCS_Annotations` layer
4. **Objects are GROUPED** by cabinet: group name = `TCS_CABINET_ID`

---

## 5. User Text Metadata

### Required Attributes (All Parts)

Every part object MUST have these Rhino User Text attributes:

```
TCS_PART_ID        = "AUST-BTH1-B1-C1-LeftSide"
TCS_CABINET_ID     = "AUST-BTH1-B1-C1"
TCS_ERP_ID         = "123"
TCS_MATERIAL       = "3-4_PreFin"
TCS_PART_TYPE      = "cabinet_box"
TCS_THICKNESS      = "0.75"
```

### Dimensional Attributes

```
TCS_CUT_WIDTH      = "23.25"      # Width for cutting (inches)
TCS_CUT_LENGTH     = "30.5"       # Length for cutting (inches)
TCS_GRAIN          = "vertical"   # vertical | horizontal | none
```

### Processing Attributes

```
TCS_EDGEBAND       = "F,T"                    # Edges needing banding
TCS_MACHINING      = "shelf_pins,dado_back"   # CNC operations
TCS_DADO           = "0.25x0.25@0.5"          # depth x width @ height
```

### Override Attributes (Grasshopper)

```
TCS_HAS_OVERRIDES  = "true"       # Boolean flag
TCS_OVERRIDES      = "{...}"     # JSON object with override values
```

### Full Example

```
Object: Box (GUID: abc123...)
Layer: TCS_Materials::3-4_PreFin
Group: AUST-BTH1-B1-C1

User Text:
  TCS_PART_ID      = AUST-BTH1-B1-C1-LeftSide
  TCS_CABINET_ID   = AUST-BTH1-B1-C1
  TCS_ERP_ID       = 123
  TCS_PROJECT_NUMBER = TCS-001-9AustinFarmRoad
  TCS_CABINET_NUMBER = BTH1-B1-C1
  TCS_MATERIAL     = 3-4_PreFin
  TCS_PART_TYPE    = cabinet_box
  TCS_THICKNESS    = 0.75
  TCS_CUT_WIDTH    = 23.25
  TCS_CUT_LENGTH   = 30.5
  TCS_GRAIN        = vertical
  TCS_EDGEBAND     = F
  TCS_MACHINING    = shelf_pins,dado_back
  TCS_DADO         = 0.25x0.375@0.5
```

---

## 6. Edgebanding Codes

### Edge Identifiers

| Code | Edge | Description |
|------|------|-------------|
| `F` | Front | Exposed front edge |
| `B` | Back | Back edge (rarely banded) |
| `T` | Top | Top edge |
| `O` | Bottom | Bottom edge |
| `L` | Left | Left edge |
| `R` | Right | Right edge |

### Format

Comma-separated list of edges requiring banding:

```
TCS_EDGEBAND = "F"       # Front only
TCS_EDGEBAND = "F,T"     # Front and top
TCS_EDGEBAND = "F,T,B"   # Front, top, bottom
TCS_EDGEBAND = ""        # No banding (drawer faces, etc.)
```

### Default Banding by Part Type

| Part Type | Default Edgeband |
|-----------|------------------|
| `cabinet_box` (sides) | `F` |
| `finished_end` | `F,T` |
| `stretcher` | `F` |
| `shelf` | `F` |
| `drawer_face` | (none - full face) |
| `door` | (none - full face) |

---

## 7. Machining Codes

### Operation Codes

| Code | Operation | Tool | Notes |
|------|-----------|------|-------|
| `shelf_pins` | 5mm shelf pin holes | 5mm drill | 32mm system |
| `dado_back` | Back panel dado | 1/4" straight | 3/4" from back edge |
| `dado_bottom` | Bottom panel dado | 1/4" straight | From bottom edge |
| `hinge_bore` | 35mm cup hinge | 35mm Forstner | European hinges |
| `slide_holes` | Drawer slide pilots | 3mm drill | Per slide specs |
| `adjustable_shelf` | System 32 rows | 5mm drill | Both sides |
| `cam_lock` | Cam lock boring | 20mm + 8mm | RTA assembly |

### Format

Comma-separated list of operations:

```
TCS_MACHINING = "shelf_pins,dado_back"
TCS_MACHINING = "hinge_bore"
TCS_MACHINING = ""  # No machining
```

---

## 8. Coordinate System

### Rhino Standard (Z-Up)

All TCS geometry uses Rhino's default coordinate system:
- **X**: Width (left to right)
- **Y**: Depth (front to back)
- **Z**: Height (bottom to top)

### Cabinet Origin

Cabinet origin is at **front-left-bottom** corner:
- (0, 0, 0) = Front-left-bottom of cabinet
- X+ = Toward right side
- Y+ = Toward back
- Z+ = Toward top

### Grasshopper Internal (Y-Up)

Grasshopper components may use Y-up internally but MUST transform to Z-up when outputting to Rhino.

---

## 9. Data Flow

### ERP → Rhino (Export)

```
1. CabinetMathAuditService generates part data
2. TcsMaterialService adds TCS metadata
3. RhinoExportService creates Python script
4. Python script creates geometry in Rhino
5. Geometry placed on TCS_Materials layers
6. User Text set with all TCS_* attributes
7. Objects grouped by TCS_CABINET_ID
```

### Rhino → ERP (Extraction)

```
1. RhinoDataExtractor queries Rhino via MCP
2. Parse TCS_* User Text from objects
3. Map TCS_CABINET_ID → ERP cabinet
4. Extract dimensions, validate against ERP
5. Report discrepancies or sync changes
```

### Grasshopper → Rhino → ERP

```
1. Grasshopper generates parametric geometry
2. Geometry baked with TCS_* User Text
3. TCS_HAS_OVERRIDES = true if modified
4. TCS_OVERRIDES contains JSON diff
5. Sync to ERP via API (optional)
```

---

## 10. Implementation Checklist

### PHP Services

- [ ] `TcsMaterialService::generateTcsMetadata()` - Generates all TCS_* fields
- [ ] `TcsMaterialService::buildCabinetId()` - Creates canonical cabinet ID
- [ ] `TcsMaterialService::getShortProjectCode()` - Extracts 4-char code
- [ ] `RhinoExportService` - Sets all User Text in Python output
- [ ] `RhinoDataExtractor` - Parses all TCS_* fields on extraction

### Grasshopper Components

- [ ] `TcsMetadata.cs` - Shared metadata generation
- [ ] `CabinetGeometry.cs` - Output with TCS_* User Text
- [ ] Use `TCS_CABINET_ID` format (not numeric ID)
- [ ] Place geometry on `TCS_Materials::*` layers
- [ ] Store overrides in `TCS_OVERRIDES` (JSON)

### Rhino Scripts

- [ ] `tcs_layer_setup.py` - Creates layer hierarchy
- [ ] `tcs_template.py` - Shared functions for ID generation
- [ ] `tcs_migrate_*.py` - Migration scripts for existing drawings

### V-Carve Integration

- [ ] Import by layer (`TCS_Materials::3-4_PreFin`, etc.)
- [ ] Read `TCS_CUT_WIDTH`, `TCS_CUT_LENGTH` for nesting
- [ ] Read `TCS_PART_ID` for part labeling
- [ ] Read `TCS_EDGEBAND` for processing queue

---

## 11. Versioning

This specification: **v1.0.0**

Changes to this specification require:
1. Update version number
2. Update all implementing systems
3. Migration path for existing data

### Changelog

- **v1.0.0** (2025-01-19): Initial unified specification
