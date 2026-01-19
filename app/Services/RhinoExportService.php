<?php

namespace App\Services;

/**
 * RhinoExportService - Export cabinet data for Rhino 3D modeling
 *
 * Transforms CabinetXYZService output into Rhino-ready coordinates.
 * ALL coordinate transforms happen here in PHP - Python template is a pure renderer.
 *
 * COORDINATE SYSTEMS:
 *
 * CabinetXYZService (Internal):
 *   - Origin: Front-Bottom-Left corner of cabinet BOX (not toe kick)
 *   - X: Left → Right (positive)
 *   - Y: Bottom → Top (positive) - Y=0 is bottom of box
 *   - Z: Front → Back (positive)
 *   - Toe kick is in NEGATIVE Y (below box)
 *
 * Rhino (Output):
 *   - Origin: Front-Bottom-Left of CABINET (floor level, includes toe kick)
 *   - X: Left → Right (positive)
 *   - Y: Front → Back (positive)
 *   - Z: Bottom → Top (positive)
 *
 * TRANSFORMATION (done here in PHP):
 *   Rhino X = Internal X
 *   Rhino Y = Internal Z (depth axis swap)
 *   Rhino Z = Internal Y + toeKickHeight (shift up to floor level)
 *
 * @author TCS Woodwork
 * @since January 2026
 */
class RhinoExportService
{
    protected TcsMaterialService $materialService;
    protected ?string $cabinetId = null;
    protected ?string $projectNumber = null;
    protected ?string $cabinetNumber = null;
    protected bool $includeTcsMetadata = true;

    public function __construct(?TcsMaterialService $materialService = null)
    {
        $this->materialService = $materialService ?? new TcsMaterialService();
    }

    /**
     * Layer colors by part type (RGB 0-255)
     * Kept for 3D visualization compatibility
     */
    protected const LAYER_COLORS = [
        'cabinet_box' => [139, 90, 43],           // Saddle brown - plywood
        'face_frame' => [210, 180, 140],          // Tan - hardwood
        'stretcher' => [160, 82, 45],             // Sienna - structural
        'false_front' => [222, 184, 135],         // Burlywood - decorative
        'false_front_backing' => [188, 143, 143], // Rosy brown
        'drawer_face' => [245, 222, 179],         // Wheat - drawer fronts
        'drawer_box' => [255, 228, 181],          // Moccasin - drawer boxes
        'drawer_box_side' => [255, 218, 171],     // Lighter moccasin - drawer sides
        'drawer_box_front' => [255, 208, 161],    // Peach - drawer front panel
        'drawer_box_back' => [255, 198, 151],     // Darker peach - drawer back panel
        'drawer_box_bottom' => [240, 230, 200],   // Light tan - drawer bottom
        'toe_kick' => [105, 105, 105],            // Dim gray - hidden
        'hardware' => [192, 192, 192],            // Silver - metal
        'finished_end' => [205, 133, 63],         // Peru - end panels
        'dimensions' => [0, 150, 255],            // Blue - dimensions layer
    ];

    protected float $toeKickHeight = 0;

    /**
     * Transform a point from Internal coords to Rhino coords.
     *
     * Internal: X=left-right, Y=bottom-top (box origin), Z=front-back
     * Rhino:    X=left-right, Y=front-back, Z=bottom-top (floor origin)
     *
     * @param float $x Internal X
     * @param float $y Internal Y (from box bottom)
     * @param float $z Internal Z (depth)
     * @return array [rhinoX, rhinoY, rhinoZ]
     */
    protected function toRhino(float $x, float $y, float $z): array
    {
        return [
            $x,                              // Rhino X = Internal X
            $z,                              // Rhino Y = Internal Z (depth)
            $y + $this->toeKickHeight,       // Rhino Z = Internal Y + toe kick
        ];
    }

    /**
     * Generate Rhino-ready data with all coordinates pre-transformed.
     *
     * The output can be rendered directly in Rhino without any math.
     *
     * @param array $auditData Output from CabinetMathAuditService::generateFullAudit()
     * @param array $options Export options (include_tcs_metadata, cabinet_id, etc.)
     * @return array Rhino-ready data structure
     */
    public function generateRhinoData(array $auditData, array $options = []): array
    {
        $this->includeTcsMetadata = $options['include_tcs_metadata'] ?? true;
        $this->cabinetId = $options['cabinet_id'] ?? $auditData['cabinet_id'] ?? 'CAB-001';

        // Store additional ERP identification for TCS metadata generation
        $this->projectNumber = $auditData['project_number'] ?? null;
        $this->cabinetNumber = $auditData['cabinet_number'] ?? null;

        $this->toeKickHeight = $auditData['input_specs']['toe_kick_height'] ?? 4.0;
        $cabinetHeight = $auditData['input_specs']['height'] ?? 32.75;
        $boxHeight = $cabinetHeight - $this->toeKickHeight;

        $parts = [];
        $rawParts = $auditData['positions_3d']['parts'] ?? [];

        foreach ($rawParts as $partKey => $part) {
            $rhinoPart = $this->transformPartToRhino($partKey, $part);
            if ($rhinoPart) {
                $parts[$partKey] = $rhinoPart;
            }
        }

        $result = [
            'cabinet_envelope' => [
                'width' => $auditData['input_specs']['width'] ?? 0,
                'height' => $cabinetHeight,
                'box_height' => $boxHeight,
                'depth' => $auditData['input_specs']['depth'] ?? 0,
                'toe_kick_height' => $this->toeKickHeight,
            ],
            'coordinate_system' => [
                'origin' => 'Front-Bottom-Left of cabinet (floor level)',
                'x_axis' => 'Left → Right',
                'y_axis' => 'Front → Back',
                'z_axis' => 'Bottom → Top',
                'units' => 'inches',
            ],
            'parts' => $parts,
            // Dimensions are MEASURED by Rhino from actual geometry (not pre-calculated)
        ];

        // Add TCS layer hierarchy for V-Carve nesting
        if ($this->includeTcsMetadata) {
            $result['tcs_layers'] = $this->materialService->getTcsLayerHierarchy();
            $result['cabinet_id'] = $this->cabinetId;
            $result['project_number'] = $this->projectNumber;
            $result['cabinet_number'] = $this->cabinetNumber;
            $result['full_code'] = $auditData['full_code'] ?? null;
        }

        return $result;
    }

    /**
     * Transform a single part to Rhino coordinates.
     *
     * Converts position from Internal to Rhino system.
     * Swaps dimensions to match Rhino axis convention.
     *
     * @param string $partKey Part identifier
     * @param array $part Part data from CabinetXYZService
     * @return array|null Rhino-ready part data
     */
    protected function transformPartToRhino(string $partKey, array $part): ?array
    {
        $position = $part['position'] ?? ['x' => 0, 'y' => 0, 'z' => 0];
        $dimensions = $part['dimensions'] ?? ['w' => 0, 'h' => 0, 'd' => 0];
        $partType = $part['part_type'] ?? 'cabinet_box';

        // Skip parts with zero dimensions (except drawer boxes which may have box_parts)
        if ($dimensions['w'] <= 0 && $dimensions['h'] <= 0 && $dimensions['d'] <= 0) {
            if ($partType !== 'drawer_box' || !isset($part['box_parts'])) {
                return null;
            }
        }

        // Transform corner position to Rhino coords
        $rhinoCorner = $this->toRhino(
            $position['x'],
            $position['y'],
            $position['z']
        );

        // Transform dimensions to Rhino axis convention
        // Internal: w=X, h=Y(up), d=Z(back)
        // Rhino:    w=X, d=Y(back), h=Z(up)
        $rhinoDimensions = [
            'w' => $dimensions['w'],  // X dimension (width)
            'h' => $dimensions['h'],  // Z dimension (height in Rhino)
            'd' => $dimensions['d'],  // Y dimension (depth in Rhino)
        ];

        $rhinoPart = [
            'part_name' => $part['part_name'] ?? $partKey,
            'part_type' => $partType,
            'position' => [
                'x' => $rhinoCorner[0],
                'y' => $rhinoCorner[1],
                'z' => $rhinoCorner[2],
            ],
            'dimensions' => $rhinoDimensions,
            'color' => self::LAYER_COLORS[$partType] ?? [128, 128, 128],
        ];

        // Transform miter cut vertices if present
        if (isset($part['miter_cut'])) {
            $rhinoPart['miter_cut'] = $this->transformMiterCutToRhino($part['miter_cut']);
        }

        // Add TCS metadata for V-Carve CNC nesting
        if ($this->includeTcsMetadata && $this->cabinetId) {
            $rhinoPart['tcs_layer'] = $this->materialService->getMaterialForPart($part);
            $rhinoPart['tcs_metadata'] = $this->materialService->generateTcsMetadata(
                $part,
                $this->cabinetId,
                $partKey,
                $this->projectNumber,
                $this->cabinetNumber
            );
        }

        return $rhinoPart;
    }

    /**
     * Transform miter cut data to Rhino coordinates.
     *
     * @param array $miterCut Miter cut data from CabinetXYZService
     * @return array Rhino-ready miter cut data
     */
    protected function transformMiterCutToRhino(array $miterCut): array
    {
        $vertices = $miterCut['vertices_xz'] ?? [];
        $yRange = $miterCut['y_range'] ?? ['start' => 0, 'end' => 0];

        // Transform vertices (they're in XZ plane, Y is the extrusion direction)
        $rhinoVertices = [];
        foreach ($vertices as $v) {
            // In Rhino: vertex X stays X, vertex Z becomes Y (depth)
            // The Y range (extrusion) becomes Z range (height)
            $rhinoVertices[] = [
                'x' => $v['x'],
                'y' => $v['z'],  // Internal Z -> Rhino Y
            ];
        }

        // Y range becomes Z range in Rhino (shifted by toe kick)
        $rhinoZRange = [
            'start' => $yRange['start'] + $this->toeKickHeight,
            'end' => $yRange['end'] + $this->toeKickHeight,
        ];

        return [
            'vertices_xy' => $rhinoVertices,  // Now in Rhino XY plane
            'z_range' => $rhinoZRange,        // Extrusion along Rhino Z
        ];
    }

    /**
     * Generate complete executable Python script with pre-transformed Rhino coords.
     *
     * The Python script is a PURE RENDERER - no coordinate math needed.
     *
     * @param array $auditData Output from CabinetMathAuditService
     * @param array|null $componentTypes Optional filter for specific component types
     * @return string Complete Python script ready for Rhino execution
     */
    public function generatePythonScript(array $auditData, ?array $componentTypes = null): string
    {
        $data = $this->generateRhinoData($auditData);

        // Build the script with injected data
        $script = "# AUTO-GENERATED by RhinoExportService\n";
        $script .= "# All coordinates are PRE-TRANSFORMED to Rhino system\n";
        $script .= "# Python template is a PURE RENDERER - no math needed\n\n";
        $script .= "DATA = " . json_encode($data, JSON_PRETTY_PRINT) . "\n\n";

        // Add component filter if specified
        if ($componentTypes) {
            $script .= "COMPONENT_TYPES = " . json_encode($componentTypes) . "\n\n";
        }

        // Inline the template (pure renderer)
        $script .= $this->getPythonTemplate();

        return $script;
    }

    /**
     * Get the Python template for pure rendering.
     *
     * This template does NO coordinate math - just draws boxes at given Rhino XYZ.
     *
     * @return string Python template code
     */
    protected function getPythonTemplate(): string
    {
        return <<<'PYTHON'
"""
Cabinet Rhino Builder - PURE RENDERER
TCS Woodwork

This script is a PURE RENDERER - all coordinates are pre-transformed by PHP.
DO NOT add any coordinate math here. If positions are wrong, fix PHP services.

Coordinate System (already transformed):
  - Origin: Front-Bottom-Left of cabinet (floor level)
  - X: Left → Right
  - Y: Front → Back
  - Z: Bottom → Top
"""

import rhinoscriptsyntax as rs

# ============================================================
# HELPER FUNCTIONS
# ============================================================

def delete_all():
    """Delete all objects in the document."""
    all_objs = rs.AllObjects()
    if all_objs:
        rs.DeleteObjects(all_objs)
        print("Deleted " + str(len(all_objs)) + " objects")

def delete_by_type(part_types):
    """Delete only objects matching specified part types."""
    all_objs = rs.AllObjects()
    deleted = 0
    if all_objs:
        for obj in all_objs:
            name = rs.ObjectName(obj)
            if name:
                for part_key, part in DATA["parts"].items():
                    if part["part_name"] == name and part["part_type"] in part_types:
                        rs.DeleteObject(obj)
                        deleted += 1
                        break
    if deleted > 0:
        print("Deleted " + str(deleted) + " objects of types: " + str(part_types))

# ============================================================
# TCS LAYER AND METADATA FUNCTIONS
# ============================================================

def create_tcs_layer_hierarchy():
    """Create TCS material layer hierarchy for V-Carve nesting."""
    if "tcs_layers" not in DATA:
        return

    tcs_data = DATA["tcs_layers"]
    parent = tcs_data.get("parent", "TCS_Materials")

    # Create parent layer
    if not rs.IsLayer(parent):
        rs.AddLayer(parent)
        print("Created parent layer: " + parent)

    # Create material layers
    for layer in tcs_data.get("layers", []):
        full_path = layer["full_path"]
        color = layer["color"]

        if not rs.IsLayer(full_path):
            rs.AddLayer(full_path, color)
            print("  Created: " + full_path)

def set_tcs_user_text(obj, metadata):
    """Set TCS metadata as Rhino User Text attributes."""
    if not metadata or not obj:
        return

    for key, value in metadata.items():
        if value is not None:
            rs.SetUserText(obj, key, str(value))

def assign_to_tcs_layer(obj, tcs_layer):
    """Assign object to TCS material layer."""
    if not tcs_layer:
        return

    full_layer = "TCS_Materials::" + tcs_layer

    # Ensure layer exists
    if not rs.IsLayer(full_layer):
        # Create with default color
        rs.AddLayer(full_layer)

    rs.ObjectLayer(obj, full_layer)

def create_box(part):
    """
    Create a box from part data. Coordinates are already in Rhino system.

    Uses 8-corner AddBox method for precise corner placement.
    Also sets TCS metadata and layer assignment for V-Carve nesting.
    """
    pos = part["position"]
    dim = part["dimensions"]

    x = pos["x"]
    y = pos["y"]
    z = pos["z"]
    w = dim["w"]  # X dimension
    h = dim["h"]  # Z dimension (height)
    d = dim["d"]  # Y dimension (depth)

    if w <= 0 or h <= 0 or d <= 0:
        return None

    # 8 corners - coordinates are ALREADY in Rhino system
    # Bottom face (z)
    p1 = (x,     y,     z)
    p2 = (x + w, y,     z)
    p3 = (x + w, y + d, z)
    p4 = (x,     y + d, z)
    # Top face (z + h)
    p5 = (x,     y,     z + h)
    p6 = (x + w, y,     z + h)
    p7 = (x + w, y + d, z + h)
    p8 = (x,     y + d, z + h)

    box = rs.AddBox([p1, p2, p3, p4, p5, p6, p7, p8])

    if box:
        rs.ObjectName(box, part["part_name"])
        color = part.get("color", (128, 128, 128))
        rs.ObjectColor(box, color)

        # Set TCS metadata as User Text (for V-Carve extraction)
        tcs_metadata = part.get("tcs_metadata")
        if tcs_metadata:
            set_tcs_user_text(box, tcs_metadata)

        # Assign to TCS material layer (for nesting)
        tcs_layer = part.get("tcs_layer")
        if tcs_layer:
            assign_to_tcs_layer(box, tcs_layer)

    return box

def create_miter_cutter(miter_cut):
    """
    Create a solid triangular prism for miter cut subtraction.
    Coordinates are already in Rhino system.
    """
    vertices = miter_cut["vertices_xy"]
    z_range = miter_cut["z_range"]

    z_start = z_range["start"]
    z_end = z_range["end"]

    v0 = vertices[0]
    v1 = vertices[1]
    v2 = vertices[2]

    # Triangle at z_start
    p1 = (v0["x"], v0["y"], z_start)
    p2 = (v1["x"], v1["y"], z_start)
    p3 = (v2["x"], v2["y"], z_start)

    triangle = rs.AddPolyline([p1, p2, p3, p1])
    if not triangle:
        return None

    surface = rs.AddPlanarSrf([triangle])
    rs.DeleteObject(triangle)

    if not surface or len(surface) == 0:
        return None

    srf = surface[0]

    # Extrude along Z
    line = rs.AddLine(p1, (v0["x"], v0["y"], z_end))
    extruded = rs.ExtrudeSurface(srf, line)
    rs.DeleteObject(srf)
    rs.DeleteObject(line)

    if not extruded:
        return None

    rs.CapPlanarHoles(extruded)
    return extruded

def apply_miter_cut(box, miter_cut, part_name):
    """Apply miter cut to a box using boolean difference."""
    if not miter_cut:
        return box

    cutter = create_miter_cutter(miter_cut)
    if not cutter:
        print("  Warning: Could not create miter cutter for " + part_name)
        return box

    result = rs.BooleanDifference([box], [cutter], delete_input=True)

    if result and len(result) > 0:
        print("  Applied miter to " + part_name)
        return result[0]
    else:
        print("  Warning: Boolean failed for " + part_name)
        if rs.IsObject(cutter):
            rs.DeleteObject(cutter)
        return box

# ============================================================
# MAIN
# ============================================================

def main(component_types=None, clear_existing=True, create_tcs_layers=True):
    """
    Build cabinet parts in Rhino.

    Args:
        component_types: List of part types to build, or None for all.
        clear_existing: If True, delete existing objects first.
        create_tcs_layers: If True, create TCS material layer hierarchy.
    """
    if 'DATA' not in globals():
        print("ERROR: DATA not defined.")
        return 0

    print("=" * 50)
    print("TCS CABINET BUILDER (Pure Renderer)")
    print("=" * 50)

    # Create TCS layer hierarchy for V-Carve nesting
    if create_tcs_layers:
        create_tcs_layer_hierarchy()

    envelope = DATA["cabinet_envelope"]
    parts = DATA["parts"]
    cabinet_id = DATA.get("cabinet_id", "CABINET")

    print("Cabinet ID: " + cabinet_id)
    print("Cabinet: " + str(envelope["width"]) + " x " + str(envelope["height"]) + " x " + str(envelope["depth"]))
    print("Coordinate System: " + DATA.get("coordinate_system", {}).get("origin", "Rhino standard"))

    if component_types:
        print("Building: " + str(component_types))
    else:
        print("Building: ALL")
    print("")

    if clear_existing:
        if component_types:
            delete_by_type(component_types)
        else:
            delete_all()
    print("")

    print("Creating parts:")
    created = 0
    skipped = 0

    for part_key, part in parts.items():
        part_type = part["part_type"]

        if component_types and part_type not in component_types:
            skipped += 1
            continue

        box = create_box(part)

        if box:
            pos = part["position"]
            dim = part["dimensions"]
            print("  " + part["part_name"] + ": Z=" + str(pos["z"]) + " to " + str(pos["z"] + dim["h"]))

            miter_cut = part.get("miter_cut")
            if miter_cut:
                box = apply_miter_cut(box, miter_cut, part["part_name"])

            created += 1

    print("")
    print("Created " + str(created) + " objects")
    if skipped > 0:
        print("Skipped " + str(skipped) + " objects (filtered)")

    rs.ZoomExtents()
    print("=" * 50)

    return created

# ============================================================
# COMPONENT BUILD FUNCTIONS
# ============================================================

def build_cabinet_box():
    return main(["cabinet_box"])

def build_face_frame():
    return main(["face_frame"])

def build_stretchers():
    return main(["stretcher"])

def build_drawers():
    return main(["drawer_face", "drawer_box", "drawer_box_bottom"])

def build_drawer_faces():
    return main(["drawer_face"])

def build_drawer_boxes():
    return main(["drawer_box", "drawer_box_bottom"])

def build_false_fronts():
    return main(["false_front", "false_front_backing"])

def build_end_panels():
    return main(["finished_end"])

def build_toe_kick():
    return main(["toe_kick"])

def build_all():
    return main()

# ============================================================
# DIMENSIONS - MEASURED FROM RHINO GEOMETRY
# ============================================================

def setup_dimension_style():
    """Create TCS_Cabinet dimension style with FRACTIONAL display."""
    style_name = "TCS_Cabinet"

    if not rs.IsDimStyle(style_name):
        rs.AddDimStyle(style_name)

    # Configure style for cabinet drawings
    rs.DimStyleTextHeight(style_name, 0.75)
    rs.DimStyleArrowSize(style_name, 0.5)
    rs.DimStyleExtension(style_name, 0.25)
    rs.DimStyleOffset(style_name, 0.25)
    rs.DimStyleLinearPrecision(style_name, 4)  # 1/16" precision

    # Set to FRACTIONAL display (InchesFractional)
    try:
        import Rhino
        doc = Rhino.RhinoDoc.ActiveDoc
        idx = doc.DimStyles.FindName(style_name)
        if idx:
            ds = doc.DimStyles[idx.Index]
            ds.DimensionLengthDisplay = Rhino.DocObjects.DimensionStyle.LengthDisplay.InchesFractional
            ds.LengthResolution = 4  # 1/16" precision
            doc.DimStyles.Modify(ds, idx.Index, False)
    except Exception as e:
        print("Note: Could not set fractional format: " + str(e))

    return style_name

def snap_to_sixteenth(decimal_val):
    """Snap decimal to nearest 1/16" to fix floating point issues."""
    sixteenths = round(decimal_val * 16)
    return sixteenths / 16.0

def decimal_to_fraction(decimal_val):
    """Convert decimal inches to fractional string for verification."""
    # First snap to nearest 1/16" to fix floating point drift
    snapped = snap_to_sixteenth(decimal_val)

    whole = int(snapped)
    frac = snapped - whole

    # Common cabinet fractions (to 1/16")
    sixteenths = int(round(frac * 16))

    if sixteenths == 0:
        return str(whole) + '"' if whole > 0 else '0"'
    elif sixteenths == 16:
        return str(whole + 1) + '"'

    # Simplify fraction
    num = sixteenths
    denom = 16

    # Reduce to lowest terms
    for divisor in [8, 4, 2]:
        if num % divisor == 0 and denom % divisor == 0:
            num = num // divisor
            denom = denom // divisor
            break

    if whole > 0:
        return str(whole) + "-" + str(int(num)) + "/" + str(int(denom)) + '"'
    else:
        return str(int(num)) + "/" + str(int(denom)) + '"'

def format_dual(decimal_val):
    """Format as both fractional AND decimal for verification."""
    snapped = snap_to_sixteenth(decimal_val)
    frac_str = decimal_to_fraction(decimal_val)
    # Show: FRACTIONAL (decimal)
    return frac_str + " (" + str(round(snapped, 4)) + ")"

def create_dimensions_layer():
    """Create Dimensions layer (hidden by default)."""
    layer_name = "Dimensions"
    layer_color = [0, 150, 255]

    if not rs.IsLayer(layer_name):
        rs.AddLayer(layer_name, layer_color)

    # Hidden by default - toggle on when needed
    rs.LayerVisible(layer_name, False)

    return layer_name

def generate_part_verification_report():
    """
    Generate detailed verification report measuring EACH PART individually.

    Measures each part's bounding box and reports fractional dimensions.
    This confirms Rhino geometry matches expected specifications.
    Also outputs JSON data for each part.
    """
    import json

    all_objs = rs.AllObjects()
    if not all_objs:
        print("No objects to verify")
        return

    print("")
    print("=" * 70)
    print("COMPONENT VERIFICATION REPORT")
    print("=" * 70)
    print("{:<30} {:>12} {:>12} {:>12}".format("Part Name", "Width", "Height", "Depth"))
    print("-" * 70)

    # Collect JSON data for all parts
    parts_json = []

    for obj in all_objs:
        name = rs.ObjectName(obj)
        if not name or name.startswith("DIM_"):
            continue  # Skip dimensions

        bbox = rs.BoundingBox(obj)
        if bbox and len(bbox) >= 8:
            min_pt = bbox[0]
            max_pt = bbox[6]

            w = max_pt[0] - min_pt[0]
            h = max_pt[2] - min_pt[2]  # Z is height in Rhino
            d = max_pt[1] - min_pt[1]  # Y is depth in Rhino

            w_frac = decimal_to_fraction(w)
            h_frac = decimal_to_fraction(h)
            d_frac = decimal_to_fraction(d)

            print("{:<30} {:>12} {:>12} {:>12}".format(name, w_frac, h_frac, d_frac))

            # Build JSON entry
            part_data = {
                "name": name,
                "position": {
                    "x": round(min_pt[0], 4),
                    "y": round(min_pt[1], 4),
                    "z": round(min_pt[2], 4),
                    "x_frac": decimal_to_fraction(min_pt[0]),
                    "y_frac": decimal_to_fraction(min_pt[1]),
                    "z_frac": decimal_to_fraction(min_pt[2])
                },
                "dimensions": {
                    "width": round(snap_to_sixteenth(w), 4),
                    "height": round(snap_to_sixteenth(h), 4),
                    "depth": round(snap_to_sixteenth(d), 4),
                    "width_frac": w_frac,
                    "height_frac": h_frac,
                    "depth_frac": d_frac
                },
                "bounding_box": {
                    "min": [round(min_pt[0], 4), round(min_pt[1], 4), round(min_pt[2], 4)],
                    "max": [round(max_pt[0], 4), round(max_pt[1], 4), round(max_pt[2], 4)]
                }
            }
            parts_json.append(part_data)

    print("-" * 70)
    print("All measurements taken from Rhino BoundingBox (1/16\" precision)")
    print("=" * 70)

    # Output JSON
    print("")
    print("=" * 70)
    print("PARTS JSON DATA")
    print("=" * 70)
    print(json.dumps(parts_json, indent=2))
    print("=" * 70)

def build_dimensions():
    """
    Build dimensions by MEASURING actual Rhino geometry.

    Uses Rhino's bounding box to get real measurements from created objects.
    Dimensions layer is HIDDEN by default.
    """
    # Get all cabinet objects
    all_objs = rs.AllObjects()
    if not all_objs:
        print("No objects to dimension")
        return 0

    # Get overall bounding box of all geometry
    bbox = rs.BoundingBox(all_objs)
    if not bbox or len(bbox) < 8:
        print("Could not get bounding box")
        return 0

    # BBox corners: 0=min, 6=max (opposite corners)
    # bbox[0] = (min_x, min_y, min_z)
    # bbox[1] = (max_x, min_y, min_z)
    # bbox[3] = (min_x, max_y, min_z)
    # bbox[4] = (min_x, min_y, max_z)
    min_pt = bbox[0]
    max_pt = bbox[6]

    cab_width = max_pt[0] - min_pt[0]
    cab_depth = max_pt[1] - min_pt[1]
    cab_height = max_pt[2] - min_pt[2]

    print("")
    print("=" * 50)
    print("RHINO MEASURED DIMENSIONS (Fractional + Decimal)")
    print("=" * 50)
    print("  Width:  " + format_dual(cab_width))
    print("  Depth:  " + format_dual(cab_depth))
    print("  Height: " + format_dual(cab_height))

    # Get toe kick height from envelope data
    toe_kick = DATA.get("cabinet_envelope", {}).get("toe_kick_height", 4.0)
    box_height = cab_height - toe_kick

    print("")
    print("  Toe Kick:   " + format_dual(toe_kick))
    print("  Box Height: " + format_dual(box_height))
    print("=" * 50)

    # Setup
    style_name = setup_dimension_style()
    layer_name = create_dimensions_layer()

    prev_layer = rs.CurrentLayer()
    rs.CurrentLayer(layer_name)

    created = 0
    off1 = 2.5
    off2 = 5.0
    off3 = 7.5

    # ============================================================
    # FRONT VIEW DIMENSIONS (measured from bbox)
    # ============================================================

    # Overall Width
    d = rs.AddAlignedDimension(
        (min_pt[0], min_pt[1], min_pt[2]),
        (max_pt[0], min_pt[1], min_pt[2]),
        (cab_width/2, min_pt[1], min_pt[2] - off1),
        style_name
    )
    if d:
        rs.ObjectName(d, "DIM_Width")
        created += 1

    # Overall Height
    d = rs.AddAlignedDimension(
        (min_pt[0], min_pt[1], min_pt[2]),
        (min_pt[0], min_pt[1], max_pt[2]),
        (min_pt[0] - off1, min_pt[1], cab_height/2),
        style_name
    )
    if d:
        rs.ObjectName(d, "DIM_Height")
        created += 1

    # Toe Kick Height
    d = rs.AddAlignedDimension(
        (min_pt[0], min_pt[1], min_pt[2]),
        (min_pt[0], min_pt[1], toe_kick),
        (min_pt[0] - off2, min_pt[1], toe_kick/2),
        style_name
    )
    if d:
        rs.ObjectName(d, "DIM_ToeKick")
        created += 1

    # Box Height
    d = rs.AddAlignedDimension(
        (min_pt[0], min_pt[1], toe_kick),
        (min_pt[0], min_pt[1], max_pt[2]),
        (min_pt[0] - off3, min_pt[1], toe_kick + box_height/2),
        style_name
    )
    if d:
        rs.ObjectName(d, "DIM_BoxHeight")
        created += 1

    # ============================================================
    # SIDE VIEW DIMENSIONS (measured from bbox)
    # ============================================================

    # Overall Depth
    d = rs.AddAlignedDimension(
        (max_pt[0], min_pt[1], min_pt[2]),
        (max_pt[0], max_pt[1], min_pt[2]),
        (max_pt[0] + off1, cab_depth/2, min_pt[2]),
        style_name
    )
    if d:
        rs.ObjectName(d, "DIM_Depth")
        created += 1

    # Restore layer
    rs.CurrentLayer(prev_layer)

    print("Created " + str(created) + " dimensions on '" + layer_name + "' layer (HIDDEN)")
    print("Toggle layer visibility to show dimensions")

    return created

# ============================================================
# EXECUTE
# ============================================================

if 'COMPONENT_TYPES' in dir() or 'COMPONENT_TYPES' in globals():
    main(COMPONENT_TYPES)
    generate_part_verification_report()
    build_dimensions()
else:
    main()
    generate_part_verification_report()
    build_dimensions()
PYTHON;
    }

    /**
     * Export to JSON file for debugging or external use.
     *
     * @param array $auditData Output from CabinetMathAuditService
     * @param string $outputPath File path to save JSON
     * @return string Path to saved file
     */
    public function exportToJsonFile(array $auditData, string $outputPath): string
    {
        $rhinoData = $this->generateRhinoData($auditData);

        file_put_contents(
            $outputPath,
            json_encode($rhinoData, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
        );

        return $outputPath;
    }

    /**
     * Export a standalone Python script file that can be executed directly in Rhino.
     *
     * This generates a complete .py file that can be:
     * - Drag-and-dropped into Rhino
     * - Run via RunPythonScript command
     * - Executed from Rhino's Python editor
     *
     * NO MCP connection required - completely standalone.
     *
     * @param array $auditData Output from CabinetMathAuditService
     * @param string $outputPath File path to save Python script (.py)
     * @param array|null $componentTypes Optional filter for specific component types
     * @return string Path to saved file
     */
    public function exportToPythonFile(array $auditData, string $outputPath, ?array $componentTypes = null): string
    {
        $script = $this->generatePythonScript($auditData, $componentTypes);

        file_put_contents($outputPath, $script);

        return $outputPath;
    }

    /**
     * Export cabinet to a directory with all necessary files for Rhino.
     *
     * Creates:
     * - cabinet_data.json - Raw data for reference
     * - build_cabinet.py - Main build script
     * - build_cabinet_box.py - Just cabinet box
     * - build_face_frame.py - Just face frame
     * - build_drawers.py - Just drawers
     *
     * @param array $auditData Output from CabinetMathAuditService
     * @param string $outputDir Directory to save files
     * @param string $cabinetName Optional name for the cabinet (used in filenames)
     * @return array List of created files
     */
    public function exportToDirectory(array $auditData, string $outputDir, string $cabinetName = 'cabinet'): array
    {
        // Ensure directory exists
        if (!is_dir($outputDir)) {
            mkdir($outputDir, 0755, true);
        }

        $files = [];
        $safeName = preg_replace('/[^a-zA-Z0-9_-]/', '_', $cabinetName);

        // Export JSON data
        $jsonPath = $outputDir . '/' . $safeName . '_data.json';
        $this->exportToJsonFile($auditData, $jsonPath);
        $files['json'] = $jsonPath;

        // Export main build script (all components)
        $mainPath = $outputDir . '/' . $safeName . '_build_all.py';
        $this->exportToPythonFile($auditData, $mainPath);
        $files['build_all'] = $mainPath;

        // Export component-specific scripts
        $componentScripts = [
            'cabinet_box' => ['cabinet_box', 'stretcher', 'toe_kick'],
            'face_frame' => ['face_frame'],
            'drawers' => ['drawer_face', 'drawer_box_side', 'drawer_box_front', 'drawer_box_back', 'drawer_box_bottom'],
            'false_fronts' => ['false_front', 'false_front_backing'],
        ];

        foreach ($componentScripts as $scriptName => $types) {
            $scriptPath = $outputDir . '/' . $safeName . '_build_' . $scriptName . '.py';
            $this->exportToPythonFile($auditData, $scriptPath, $types);
            $files['build_' . $scriptName] = $scriptPath;
        }

        return $files;
    }

    /**
     * Get layer color for a part type.
     *
     * @param string $partType Part type identifier
     * @return array RGB color array
     */
    public function getLayerColor(string $partType): array
    {
        return self::LAYER_COLORS[$partType] ?? [128, 128, 128];
    }
}
