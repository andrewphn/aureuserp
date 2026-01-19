# TCS Rhino Standards & Layer System

## Quick Start

**New to TCS Rhino workflow? Start here:**

1. [User Story & Goals](#user-story)
2. [Standard Operating Procedure](#standard-operating-procedure-sop)
3. [Workflow: Import DWG/PDF](#workflow-import-from-dwgpdf)
4. [Layer Reference](#layer-hierarchy)

---

## User Story

### As a TCS Cabinet Maker, I need to:

1. **Import architect drawings** (DWG/PDF) into Rhino as a reference layer
2. **Model cabinet parts** organized by material for CNC nesting
3. **Tag each part** with project/cabinet ID so mixed-project sheets can be sorted
4. **Export to V-Carve** with parts grouped by material thickness
5. **Track parts back to ERP** for job costing and inventory

### Acceptance Criteria

- [ ] Parts from different projects are identifiable when nested together
- [ ] Each part has: Project Code, Cabinet ID, Material, Cut Dimensions
- [ ] V-Carve receives parts organized by sheet material
- [ ] Parts can be traced back to ERP Cabinet record
- [ ] Legacy drawings can be migrated to new standard

### Why This Matters

When CNC operator loads a 4x8 sheet of 3/4" Baltic Birch, they need to know:
- Which project each part belongs to (SANK, FSHIP, etc.)
- What cabinet it's for (B36-001, W3030-002)
- Edgebanding requirements
- Machining operations (dados, shelf pins, etc.)

---

## Standard Operating Procedure (SOP)

### SOP-001: New Cabinet Drawing from Architect Plans

**Purpose:** Create TCS-standard Rhino drawing from architect DWG/PDF

**Time:** 15-30 minutes per cabinet

#### Step 1: Setup (Once per project)

```
1. Create new Rhino file
2. Run: RunPythonScript tcs_layer_setup.py
3. Verify layers created: TCS_Materials::3-4_PreFin, etc.
4. Save as: {PROJECT_CODE}_{Description}.3dm
   Example: SANK_Kitchen_Cabinets.3dm
```

#### Step 2: Import Reference Drawing

```
For DWG:
1. File > Import > Select .dwg file
2. Import options:
   - Units: Inches
   - Scale: 1:1
3. Select all imported geometry
4. Move to layer: "Reference::Architect_DWG"
5. Lock layer

For PDF:
1. File > Import > Select .pdf file
2. Place at origin
3. Scale to match dimensions (measure known dimension)
4. Move to layer: "Reference::Architect_PDF"
5. Lock layer
```

#### Step 3: Model Cabinet Parts

```
1. Identify cabinet from reference drawing
2. Determine cabinet ID: {PROJECT}-{TYPE}-{SEQ}
   Example: SANK-B36-001

3. For each part:
   a. Create box geometry on correct TCS_Materials layer
   b. Name the object (e.g., "Left Side")
   c. Add TCS metadata (see Step 4)

4. Use tcs_template.py as starting point for common cabinet types
```

#### Step 4: Add TCS Metadata

For each part, set User Text (Properties > Attribute User Text):

```
TCS_PART_ID      = SANK-B36-001-LeftSide
TCS_CABINET_ID   = SANK-B36-001
TCS_PROJECT_CODE = SANK
TCS_PART_TYPE    = cabinet_box
TCS_PART_NAME    = Left Side
TCS_MATERIAL     = 3-4_PreFin
TCS_THICKNESS    = 0.75
TCS_CUT_WIDTH    = 28.75
TCS_CUT_LENGTH   = 20.25
TCS_GRAIN        = vertical
TCS_EDGEBAND     = F
```

Or run Python script to auto-add metadata.

#### Step 5: Verify & Export

```
1. Run: verify_tcs_metadata() to check all parts tagged
2. Run: export_cut_list() to preview cut list
3. Save Rhino file
4. Export each material layer to V-Carve:
   - Select all on TCS_Materials::3-4_PreFin
   - Export as DXF for V-Carve
```

---

## Workflow: Import from DWG/PDF

### Importing Architect DWG Files

```python
# In Rhino Python editor:

import rhinoscriptsyntax as rs

# 1. Create reference layer
if not rs.IsLayer("Reference"):
    rs.AddLayer("Reference", (200, 200, 200))
if not rs.IsLayer("Reference::Architect_DWG"):
    rs.AddLayer("Reference::Architect_DWG", (180, 180, 180))

# 2. Import DWG (do this via File > Import menu)
# 3. After import, move to reference layer:
imported = rs.LastCreatedObjects()
if imported:
    for obj in imported:
        rs.ObjectLayer(obj, "Reference::Architect_DWG")
    print(f"Moved {len(imported)} objects to reference layer")

# 4. Lock the reference layer
rs.LayerLocked("Reference::Architect_DWG", True)
```

### Importing PDF as Reference

```python
# PDFs import as curves/hatches
# Scale to match real dimensions:

import rhinoscriptsyntax as rs

# After importing PDF:
# 1. Measure a known dimension in the PDF (e.g., 36" cabinet width)
# 2. Measure same dimension in Rhino
# 3. Calculate scale factor

known_real = 36.0  # Real dimension in inches
measured_in_rhino = 18.0  # What you measured

scale_factor = known_real / measured_in_rhino

# Select all PDF objects and scale
objs = rs.GetObjects("Select PDF objects to scale")
if objs:
    rs.ScaleObjects(objs, (0,0,0), (scale_factor, scale_factor, scale_factor))
    print(f"Scaled by {scale_factor}")
```

### Creating Parts from Reference

```python
# Trace over reference to create cabinet parts

import rhinoscriptsyntax as rs

# Example: Create left side panel from reference dimensions
# Measure from reference drawing:
width = 0.75      # 3/4" plywood
height = 28.75    # Cabinet box height
depth = 20.25     # Cabinet depth

# Cabinet identity
project = "SANK"
cab_type = "B36"
seq = 1
cabinet_id = f"{project}-{cab_type}-{seq:03d}"

# Create the box
corners = [
    (0, 0, 4),                    # Bottom-front-left (above toe kick)
    (width, 0, 4),
    (width, depth, 4),
    (0, depth, 4),
    (0, 0, 4 + height),
    (width, 0, 4 + height),
    (width, depth, 4 + height),
    (0, depth, 4 + height),
]

box = rs.AddBox(corners)
rs.ObjectName(box, "Left Side")
rs.ObjectLayer(box, "TCS_Materials::3-4_PreFin")

# Add TCS metadata
rs.SetUserText(box, "TCS_PART_ID", f"{cabinet_id}-Left_Side")
rs.SetUserText(box, "TCS_CABINET_ID", cabinet_id)
rs.SetUserText(box, "TCS_PROJECT_CODE", project)
rs.SetUserText(box, "TCS_PART_TYPE", "cabinet_box")
rs.SetUserText(box, "TCS_MATERIAL", "3-4_PreFin")
rs.SetUserText(box, "TCS_THICKNESS", "0.75")
rs.SetUserText(box, "TCS_CUT_WIDTH", str(height))
rs.SetUserText(box, "TCS_CUT_LENGTH", str(depth))
rs.SetUserText(box, "TCS_GRAIN", "vertical")
rs.SetUserText(box, "TCS_EDGEBAND", "F")

print(f"Created: {cabinet_id}-Left_Side")
```

---

## Overview

This document defines the TCS Woodwork standard for Rhino 3D cabinet drawings, optimized for bidirectional ERP integration and V-Carve CNC nesting.

**Goals:**
- Material-based layer organization (matches CNC sheet loading)
- Part metadata for edgebanding, machining, part IDs
- Bidirectional compatibility (ERP <-> Rhino)
- Consistent drawing standards across all projects

---

## Layer Hierarchy

### TCS_Materials (Primary - For V-Carve Nesting)

All cabinet parts are organized by material for CNC nesting:

```
TCS_Materials/
    3-4_PreFin      3/4" Prefinished Plywood    RGB(139, 90, 43)
    3-4_Medex       3/4" Medex MDF              RGB(65, 105, 225)
    3-4_RiftWO      3/4" Rift White Oak         RGB(210, 180, 140)
    1-2_Baltic      1/2" Baltic Birch           RGB(255, 228, 181)
    1-4_Plywood     1/4" Plywood                RGB(240, 230, 200)
    5-4_Hardwood    5/4" Hardwood               RGB(205, 133, 63)
```

### Layer Naming Convention

Format: `{thickness-fraction}_{material}`

| Thickness | Fraction Code | Common Materials |
|-----------|---------------|------------------|
| 3/4"      | `3-4`         | PreFin, Medex, RiftWO |
| 1/2"      | `1-2`         | Baltic |
| 1/4"      | `1-4`         | Plywood |
| 5/4" (1") | `5-4`         | Hardwood |

### TCS_PartTypes (Secondary - For Visualization)

For 3D visualization and rendering:

```
TCS_PartTypes/
    cabinet_box     RGB(139, 90, 43)
    face_frame      RGB(210, 180, 140)
    drawer_box      RGB(255, 228, 181)
    drawer_face     RGB(245, 222, 179)
    toe_kick        RGB(105, 105, 105)
    finished_end    RGB(205, 133, 63)
    stretcher       RGB(160, 82, 45)
```

### TCS_Dimensions & TCS_Annotations

```
TCS_Dimensions/
    overall         RGB(0, 150, 255)
    detail          RGB(0, 100, 200)

TCS_Annotations/
    labels          RGB(50, 50, 50)
    notes           RGB(100, 100, 100)
```

---

## Part Metadata (Rhino User Text)

Every cabinet part object must have TCS metadata stored as Rhino User Text attributes.

### Required Attributes

| Attribute | Description | Example |
|-----------|-------------|---------|
| `TCS_PART_ID` | Unique part identifier | `SANK-B36-001-LeftSide` |
| `TCS_CABINET_ID` | Parent cabinet ID (project + type + seq) | `SANK-B36-001` |
| `TCS_PROJECT_CODE` | Project identifier for V-Carve nesting | `SANK` |
| `TCS_PART_TYPE` | Part category | `cabinet_box` |
| `TCS_PART_NAME` | Human-readable name | `Left Side` |
| `TCS_MATERIAL` | Material layer | `3-4_PreFin` |

### CNC Attributes

| Attribute | Description | Example |
|-----------|-------------|---------|
| `TCS_THICKNESS` | Material thickness | `0.75` |
| `TCS_CUT_WIDTH` | Cut list width | `17` |
| `TCS_CUT_LENGTH` | Cut list length | `27.25` |
| `TCS_GRAIN` | Grain direction | `vertical`, `horizontal`, `none` |

### Processing Attributes

| Attribute | Description | Example |
|-----------|-------------|---------|
| `TCS_EDGEBAND` | Edges needing banding | `F,T` |
| `TCS_MACHINING` | CNC operations | `shelf_pins,dado_back` |
| `TCS_DADO` | Dado specification | `0.25 x 0.25 @ 0.5` |

---

## Part Type to Material Mapping

Default material assignments by part type:

| Part Type | Default Material | Notes |
|-----------|------------------|-------|
| `cabinet_box` | `3-4_PreFin` | Sides, bottom, back |
| `toe_kick` | `3-4_Medex` | Paint grade |
| `face_frame` | `5-4_Hardwood` | Hardwood stiles/rails |
| `drawer_face` | `3-4_RiftWO` | Visible face |
| `finished_end` | `3-4_RiftWO` | Exposed end panel |
| `stretcher` | `3-4_PreFin` | Structural |
| `drawer_box` | `1-2_Baltic` | Box sides, front, back |
| `drawer_box_bottom` | `1-4_Plywood` | Drawer bottom panel |

---

## Edgebanding Codes

| Code | Edge | Description |
|------|------|-------------|
| `F` | Front | Forward-facing exposed edge |
| `T` | Top | Top edge (vertical parts) |
| `B` | Bottom | Bottom edge (vertical parts) |
| `L` | Left | Left side edge |
| `R` | Right | Right side edge |

### Common Configurations

| Part Type | Typical Edgebanding |
|-----------|---------------------|
| Cabinet sides | `F` (front edge only) |
| Finished ends | `F,T` (front and top) |
| Shelves | `F` (front edge) |
| Stretchers | `F` (front edge) |
| Drawer faces | None (full veneer) |

---

## Machining Operations

| Code | Operation | Description |
|------|-----------|-------------|
| `shelf_pins` | System 32 drilling | 5mm holes, 32mm spacing |
| `dado_back` | Back panel dado | 3/4" from back, 1/4" deep |
| `dado_bottom` | Bottom panel dado | Drawer box bottom groove |
| `hinge_bore` | Cup hinge boring | 35mm European hinge |
| `slide_holes` | Slide mounting | Drawer slide pilot holes |

---

## Naming Conventions

### Project Codes

Each project gets a short code (3-6 characters) for identification when parts are nested together in V-Carve.

| Project Name | Code | Notes |
|--------------|------|-------|
| Sankaty | `SANK` | Client/location name |
| 25 Friendship Lane | `FSHIP` | Address abbreviation |
| Austin Residence | `AUST` | Client name |

### Cabinet IDs

Format: `{PROJECT_CODE}-{TYPE_CODE}-{SEQUENCE}`

This ensures parts from different projects can be identified when nested on the same V-Carve sheet.

**Cabinet Type Codes:**
| Code | Description |
|------|-------------|
| `B36` | Base 36" wide |
| `B24` | Base 24" wide |
| `W3030` | Wall 30" wide × 30" tall |
| `W3618` | Wall 36" wide × 18" tall |
| `T2484` | Tall 24" wide × 84" tall |
| `VAN36` | Vanity 36" wide |

Examples:
- `SANK-B36-001` - Sankaty, Base 36", cabinet #1
- `FSHIP-W3030-002` - Friendship Lane, Wall 30×30, cabinet #2
- `AUST-T2484-001` - Austin, Tall pantry cabinet

### Part IDs

Format: `{CABINET_ID}-{PART_NAME}`

Part names use descriptive labels matching CabinetXYZService output.

Examples:
- `SANK-B36-001-LeftSide`
- `SANK-B36-001-BottomPanel`
- `FSHIP-W3030-002-FaceFrame_TopRail`

---

## V-Carve CNC Integration

### Sheet Organization

Parts are nested by material layer. Each `TCS_Materials::*` layer represents a different CNC sheet:

1. **3-4_PreFin** - 4x8 sheet of 3/4" prefinished plywood
2. **3-4_RiftWO** - 4x8 sheet of 3/4" rift white oak
3. **1-2_Baltic** - 4x8 sheet of 1/2" Baltic birch
4. **1-4_Plywood** - 4x8 sheet of 1/4" plywood

### Export Workflow

1. ERP generates cabinet specifications
2. `RhinoExportService` creates Python script with TCS metadata
3. Script creates parts on material layers
4. Export each material layer to V-Carve for nesting
5. V-Carve reads `TCS_*` User Text for machining instructions

---

## Migration from Legacy Drawings

### Legacy Layer Formats

The system supports automatic migration from:
- `3/4 Medex` -> `TCS_Materials::3-4_Medex`
- `3/4" Rift WO` -> `TCS_Materials::3-4_RiftWO`
- `1/2 Baltic` -> `TCS_Materials::1-2_Baltic`
- `RiftWO 3/4Ply_Cab1` -> `TCS_Materials::3-4_RiftWO`

### Migration Script

Use `resources/rhino/tcs_migrate_sankaty.py`:

```python
# In Rhino Python editor:
import tcs_migrate_sankaty

# Preview changes (dry run)
tcs_migrate_sankaty.dry_run()

# Apply migration
tcs_migrate_sankaty.migrate()
```

---

## PHP Service Reference

### TcsMaterialService

Material layer configuration and part-to-material mapping:

```php
$materialService = new TcsMaterialService();

// Get material for a part
$material = $materialService->getMaterialForPart($part);
// Returns: '3-4_PreFin'

// Generate TCS metadata
$metadata = $materialService->generateTcsMetadata($part, 'SANK-KIT-01');
// Returns: ['TCS_PART_ID' => '...', 'TCS_MATERIAL' => '...', ...]

// Parse legacy layer name
$parsed = $materialService->parseLegacyLayerName('3/4 Medex');
// Returns: ['thickness' => 0.75, 'material' => 'Medex', 'layer' => '3-4_Medex']
```

### RhinoExportService

Export with TCS metadata:

```php
$exportService = new RhinoExportService();

// Generate Rhino data with TCS metadata
// cabinet_id format: {PROJECT_CODE}-{TYPE_CODE}-{SEQUENCE}
$rhinoData = $exportService->generateRhinoData($auditData, [
    'include_tcs_metadata' => true,
    'cabinet_id' => 'SANK-B36-001',  // Project + cabinet type + sequence
]);

// Export Python script with TCS layers and User Text
$script = $exportService->generatePythonScript($auditData);
```

### RhinoDataExtractor

Extract TCS metadata from Rhino drawings:

```php
$extractor = new RhinoDataExtractor($rhinoMcp);

// Analyze TCS layer structure
$analysis = $extractor->analyzeTcsLayers();

// Extract TCS metadata from objects
$metadata = $extractor->extractTcsMetadata($objectInfo);

// Parse material from layer name
$material = $extractor->parseMaterialLayer('3-4_Medex');
```

---

## Quick Reference

### Layer Setup (in Rhino)
```python
import tcs_layer_setup
tcs_layer_setup.setup_all_layers()
```

### Add Metadata to Object (in Rhino)
```python
import rhinoscriptsyntax as rs
rs.SetUserText(obj, "TCS_PART_ID", "SANK-B36-001-LeftSide")
rs.SetUserText(obj, "TCS_CABINET_ID", "SANK-B36-001")
rs.SetUserText(obj, "TCS_PROJECT_CODE", "SANK")
rs.SetUserText(obj, "TCS_MATERIAL", "3-4_PreFin")
```

### Read Metadata from Object (in Rhino)
```python
part_id = rs.GetUserText(obj, "TCS_PART_ID")
material = rs.GetUserText(obj, "TCS_MATERIAL")
```

---

## Verification Checklist

Before sending to CNC:

- [ ] All parts on `TCS_Materials::*` layers
- [ ] All parts have `TCS_PART_ID` User Text
- [ ] All parts have `TCS_MATERIAL` User Text
- [ ] Edgebanding specified in `TCS_EDGEBAND`
- [ ] Machining operations in `TCS_MACHINING`
- [ ] No orphan parts on legacy layers
- [ ] Part IDs match ERP Cabinet.id

---

## MCP Server Setup

The TCS ERP integrates with Rhino via MCP (Model Context Protocol) for bidirectional communication.

### Starting the MCP Connection

1. **In Rhino**, type command: `mcp_start`

2. This automatically starts both servers:
   - **Rhino MCP** on port 9876
   - **Grasshopper MCP** on port 9999 (opens GH and loads the client)

3. You should see:
   ```
   TCS MCP Started!
     - Rhino server: localhost:9876
     - Grasshopper server: localhost:9999
   ```

4. **To stop**: In Rhino's Python Editor, run `stop_server()`

### First-Time Setup (Create Alias)

To enable the `mcp_start` command:

1. **Rhino → Preferences → Aliases**
2. Click **+** to add new alias
3. **Command Name**: `mcp_start`
4. **Command Macro**: `-_RunPythonScript "/Users/andrewphan/tcsadmin/rhino-mcp/tcs_mcp_start.py"`

### Manual Start (Alternative)

If you only need Rhino (no Grasshopper):
```
-_RunPythonScript "/Users/andrewphan/tcsadmin/rhino-mcp/rhino_mcp/rhino_mcp_client.py"
```

If you need to open Grasshopper client separately:
```
/Users/andrewphan/tcsadmin/rhino-mcp/rhino_mcp/grasshopper_mcp_client.gh
```

### Available MCP Tools

| Tool | Description |
|------|-------------|
| `get_scene_info` | Get basic scene overview |
| `get_layers` | List all layers |
| `get_scene_objects_with_metadata` | Get objects with TCS metadata |
| `execute_rhino_code` | Run Python code in Rhino |
| `capture_viewport` | Screenshot current view |
| `execute_code_in_gh` | Run Python in Grasshopper |
| `get_gh_context` | Get Grasshopper definition info |

---

## Changelog

- **January 2026** - Initial TCS Rhino Standards specification
- **January 2026** - Added TcsMaterialService PHP integration
- **January 2026** - Added migration scripts for Sankaty project
- **January 2026** - Added MCP server setup instructions (SerjoschDuering/rhino-mcp)
