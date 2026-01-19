# Rhino Cabinet Extraction System

## Overview

The Rhino Cabinet Extraction System connects TCS Rhino 3D drawings to the AureusERP cabinet management system. It extracts cabinet specifications, dimensions, and metadata from Rhino files via the MCP (Model Context Protocol) and maps them to ERP Cabinet records.

## Architecture

```
┌─────────────────────────────────────────────────────────────────────┐
│                         RHINO (Running)                              │
│                                                                      │
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐               │
│  │ Plan Views   │  │ Elevations   │  │ Detail Views │               │
│  │ (Width×Depth)│  │ (Width×Height)│ │ (Components) │               │
│  └──────────────┘  └──────────────┘  └──────────────┘               │
│                                                                      │
│  Groups: Austin-Vanity, Austin-W/D                                   │
│  Layers: TCS_Materials::3-4_Medex, Dimensions::Face Frames, etc.    │
└──────────────────────────────────────────────────────────────────────┘
                              │
                              │ MCP Protocol (via mcp-cli)
                              ▼
┌─────────────────────────────────────────────────────────────────────┐
│                      RhinoMCPService.php                             │
│                                                                      │
│  • getDocumentInfo()      - Document metadata                        │
│  • getGroups()            - Cabinet groups                           │
│  • getDimensions()        - Linear dimension annotations             │
│  • getTextObjects()       - Text labels (view names, cabinet IDs)    │
│  • getBlockInstances()    - Fixtures (sinks, faucets)                │
│  • getBoundingBox()       - Object dimensions                        │
│  • executeRhinoScript()   - Custom RhinoScript Python code           │
└──────────────────────────────────────────────────────────────────────┘
                              │
                              ▼
┌─────────────────────────────────────────────────────────────────────┐
│                    RhinoDataExtractor.php                            │
│                                                                      │
│  1. discoverViews()       - Find Plan View/Elevation labels          │
│  2. getGroups()           - Identify cabinet groups                  │
│  3. extractDimensions()   - Get all dimension annotations            │
│  4. buildCabinetFromViews() - Combine view data into 3D specs        │
│  5. extractComponents()   - Count doors, drawers, shelves            │
│  6. extractFixtures()     - Detect sinks, faucets from blocks        │
│  7. extractTcsMetadata()  - Parse TCS User Text attributes           │
└──────────────────────────────────────────────────────────────────────┘
                              │
                              ▼
┌─────────────────────────────────────────────────────────────────────┐
│                    RhinoToCabinetMapper.php                          │
│                                                                      │
│  • mapToCabinetData()     - Convert to Cabinet model fields          │
│  • validateDimensions()   - Check against construction standards     │
│  • createCabinets()       - Insert/update Cabinet records            │
│  • generatePreviewReport() - Human review summary                    │
└──────────────────────────────────────────────────────────────────────┘
                              │
                              ▼
┌─────────────────────────────────────────────────────────────────────┐
│                         ERP Cabinet System                           │
│                                                                      │
│  Project → Room → Cabinet Run → Cabinet → Cabinet Sections           │
└──────────────────────────────────────────────────────────────────────┘
```

## TCS Drawing Conventions

### View Organization

TCS Rhino drawings are 2D layouts (all geometry at Z=0) organized as multiple views arranged spatially in the XY plane:

| View Type | Shows | Extracts | Y Position |
|-----------|-------|----------|------------|
| **Plan View** | Top-down (Width × Depth) | Cabinet footprint, room layout | Lower |
| **Elevation** | Front-facing (Width × Height) | Face frame, doors, drawers | Upper |
| **Detail View** | Close-up components | Joinery, hardware | Varies |

### Cabinet Naming Convention

Groups follow the pattern: `{Project}-{CabinetType}`

Examples:
- `Austin-Vanity` → Bathroom vanity for Austin project
- `Austin-W/D` → Washer/Dryer cabinet
- `Sankaty-Base-1` → Base cabinet #1 for Sankaty project

### Layer Structure

#### TCS Standard Format (New)
```
TCS_Materials::3-4_Medex        → 3/4" Medex
TCS_Materials::1-2_Baltic       → 1/2" Baltic Birch
TCS_Materials::3-4_Rift_WO      → 3/4" Rift White Oak
Dimensions::Face Frames         → Face frame dimensions
Dimensions::Openings            → Door/drawer openings
Construction                    → Construction notes
```

#### Legacy Format (Supported)
```
3/4 PreFin                      → 3/4" Prefinished
3/4" Rift WO                    → 3/4" Rift White Oak
1/2 Baltic                      → 1/2" Baltic Birch
Labels                          → Text labels
0 (Default)                     → Mixed geometry
```

### TCS User Text Attributes

For V-Carve integration, objects can have User Text attributes:

| Attribute | Description | Example |
|-----------|-------------|---------|
| `TCS_PART_ID` | Unique part identifier | `AUS-VAN-LS-001` |
| `TCS_CABINET_ID` | Parent cabinet ID | `Austin-Vanity` |
| `TCS_PART_TYPE` | Part type code | `LEFT_SIDE`, `BOTTOM` |
| `TCS_PART_NAME` | Human-readable name | `Left Side Panel` |
| `TCS_MATERIAL` | Material code | `Medex`, `Baltic` |
| `TCS_THICKNESS` | Thickness in inches | `0.75` |
| `TCS_GRAIN` | Grain direction | `HORIZONTAL`, `VERTICAL` |
| `TCS_EDGEBAND` | Edge banding spec | `L1,L2` (edges to band) |
| `TCS_MACHINING` | Machining operations | `DADO,RABBET` |
| `TCS_CUT_WIDTH` | Cut width in inches | `23.25` |
| `TCS_CUT_LENGTH` | Cut length in inches | `31.5` |
| `TCS_DADO` | Dado specification | `0.25 x 0.25 @ 0.5` |

## Usage

### CLI Commands

#### Analyze Rhino Document
```bash
# Basic analysis
php artisan rhino:analyze-views

# Detailed with dimensions
php artisan rhino:analyze-views --detailed

# JSON output for processing
php artisan rhino:analyze-views --json
```

#### Import Cabinets
```bash
# Dry run (preview without saving)
php artisan rhino:import-cabinets --dry-run

# Import to specific project
php artisan rhino:import-cabinets --project=123

# Import to specific room
php artisan rhino:import-cabinets --project=123 --room=456

# Update existing cabinets by name match
php artisan rhino:import-cabinets --project=123 --update

# Show detailed dimension data
php artisan rhino:import-cabinets --dry-run --detailed
```

### Filament Admin Integration

The import action is available in:
- **CabinetsRelationManager** (Project → Cabinets tab)
- Header action: "Import from Rhino"

The modal shows:
1. Rhino connection status
2. Room selection dropdown
3. Material/finish overrides
4. Preview of detected cabinets
5. Import confirmation

### Programmatic Usage

```php
use App\Services\RhinoDataExtractor;
use App\Services\RhinoToCabinetMapper;

// Extract from open Rhino document
$extractor = app(RhinoDataExtractor::class);
$data = $extractor->extractCabinets();

// $data contains:
// - cabinets: Array of cabinet data
// - views: Discovered views with bounds
// - fixtures: Detected sinks, faucets
// - raw_data: Groups, text labels, dimensions

// Map to Cabinet model
$mapper = app(RhinoToCabinetMapper::class);
$mapped = $mapper->mapAllCabinets($data, [
    'project_id' => 123,
    'room_id' => 456,
]);

// Preview report
$report = $mapper->generatePreviewReport($mapped);

// Create records (set $dryRun = false to save)
$mapper->createCabinets($mapped, $dryRun = true);
```

## Dimension Extraction Logic

### View Detection

1. Find text labels containing "Plan View" or "Elevation"
2. Define view bounds around each label (±250 X, -100/+300 Y)
3. Associate dimensions with views based on center position

### Dimension Interpretation

| View Type | Horizontal Dim | Vertical Dim |
|-----------|----------------|--------------|
| Elevation | Width | Height |
| Plan | Width | Depth |

### Heuristic Identification

Since linear dimensions don't store orientation directly, we infer:

| Value Range | Likely Purpose |
|-------------|----------------|
| 0.5" - 1.0" | Gap/reveal |
| 1.4" - 2.1" | Face frame stile/rail |
| 3.5" - 4.5" | Toe kick |
| 9" - 48" | Width |
| 20" - 96" | Height |
| 10" - 36" | Depth |

### Face Frame Detection

Face frame construction is detected by finding dimensions in the 1.5" - 2.0" range:

```php
// From Rhino: 1.75" (1-3/4") face frame stile
// TCS Standard: 1.5"
// Difference: +0.25" (wider than standard)
```

## Validation Against Construction Standards

The system compares extracted dimensions against `ConstructionTemplate`:

| Rhino Measurement | TCS Standard Field |
|-------------------|-------------------|
| Face frame stile | `face_frame_stile_width` |
| Toe kick height | `toe_kick_height` |
| Cabinet height | `base_cabinet_height` |
| Door gap | `face_frame_door_gap` |
| Reveal | `face_frame_reveal_gap` |

Warnings are generated for discrepancies exceeding tolerance.

## Fixture Detection

Block instances with attributes are detected as fixtures:

| Block Attribute | Fixture Data |
|-----------------|--------------|
| `PRODUCT` | Product type (Bathroom Sink, Faucets) |
| `MODELNUMBER` | Model number (K-20000, K-13132-3B) |
| `MANUFACTURER` | Manufacturer (Kohler) |
| `MATERIAL` | Material (Vitreous China, Brass) |

Example extraction:
```
Kohler K-20000 Caxton (Undermount Bathroom Sink)
Kohler K-13132-3B Pinstripe (Faucet)
```

## Human Review Workflow

```
┌────────────────────────────────────────────────────────────────────┐
│ STEP 1: Auto-Extract from Rhino                                    │
│                                                                    │
│ ✓ Cabinet groups detected                                          │
│ ✓ View associations found                                          │
│ ✓ Dimensions extracted                                             │
│ ✓ Fixtures identified                                              │
└────────────────────────────────────────────────────────────────────┘
                              │
                              ▼
┌────────────────────────────────────────────────────────────────────┐
│ STEP 2: Review & Verify                                            │
│                                                                    │
│ EXTRACTED: Austin-Vanity                                           │
│                                                                    │
│ Dimensions (from Rhino):                                           │
│   Width:  [29.9925] " ← from elevation                            │
│   Height: [41.3175] " ← from elevation                            │
│   Depth:  [18     ] " ← from plan view                            │
│                                                                    │
│ Face Frame:                                                        │
│   Stile: [1.75] " ← detected (TCS std: 1.5")                      │
│                                                                    │
│ Components:                                                        │
│   ☑ Drawer (2 detected)                                           │
│   ☑ Door (3 detected)                                             │
│                                                                    │
│ REQUIRES SELECTION:                                                │
│   Material:   [Select...▼]                                        │
│   Finish:     [Select...▼]                                        │
│   Door Style: [Select...▼]                                        │
└────────────────────────────────────────────────────────────────────┘
                              │
                              ▼
┌────────────────────────────────────────────────────────────────────┐
│ STEP 3: Create Cabinet Records                                     │
│                                                                    │
│ ✓ Cabinet record created                                           │
│ ✓ Linked to Project/Room                                          │
│ ✓ Sections created for doors/drawers                              │
│ ✓ Construction template applied                                    │
└────────────────────────────────────────────────────────────────────┘
```

## Files

| File | Purpose | Status |
|------|---------|--------|
| `app/Services/RhinoMCPService.php` | MCP communication layer | ✓ Validated |
| `app/Services/RhinoDataExtractor.php` | Extraction logic | ✓ Validated |
| `app/Services/RhinoToCabinetMapper.php` | Data mapping | ✓ Validated |
| `app/Services/TcsMaterialService.php` | Material parsing | ✓ Validated |
| `app/Console/Commands/ImportRhinoCabinets.php` | CLI import | ✓ Validated |
| `app/Console/Commands/AnalyzeRhinoViews.php` | CLI analysis | ✓ Validated |

## Workflow Validation

Run the validation script to verify all components:

```bash
# Quick validation
php artisan tinker --execute="
\$components = [
    'RhinoMCPService' => App\Services\RhinoMCPService::class,
    'RhinoDataExtractor' => App\Services\RhinoDataExtractor::class,
    'RhinoToCabinetMapper' => App\Services\RhinoToCabinetMapper::class,
    'TcsMaterialService' => App\Services\TcsMaterialService::class,
];
foreach (\$components as \$name => \$class) {
    echo class_exists(\$class) ? '✓' : '✗';
    echo \" \$name\\n\";
}
"
```

### MCP Tools Used

| Tool | Purpose |
|------|---------|
| `get_document_info` | Get document metadata and object count |
| `execute_rhinoscript_python_code` | Run Python scripts in Rhino |
| `get_object_info` | Get specific object details |
| `select_objects` | Select objects by filter |

## Requirements

- Rhino must be running with MCP server active
- Claude Code CLI installed (`~/.claude/local/`)
- MCP server `rhino` configured in `.mcp.json`

## Troubleshooting

### "Failed to connect to Rhino"
- Ensure Rhino is running
- Check MCP server is active in Rhino
- Verify `.mcp.json` has `rhino` server configured

### No dimensions found
- Check dimension layer (`Dimensions::Face Frames`)
- Ensure dimensions are linear (not angular/radial)
- Verify dimensions use standard annotation style

### Views not detected
- Check for "Plan View" or "Elevation" text labels
- Labels must be on visible layers
- Text must match patterns: "Plan View", "Elevation", "Wall Elevation"

### Cabinets missing data
- Verify groups are named correctly (`Project-Cabinet`)
- Check cabinet labels match group names
- Ensure both Plan View and Elevation exist for cabinet
