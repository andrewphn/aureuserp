"""
Cabinet Rhino Builder Script - TEMPLATE
TCS Woodwork - Pure JSON Renderer with Miter Joints

THIS SCRIPT IS A TEMPLATE - DATA COMES FROM PHP
All math comes from CabinetMathAuditService.php.
If geometry is wrong, fix it in PHP services, not here.

HOW IT WORKS:
1. PHP generates JSON data from CabinetMathAuditService
2. PHP injects DATA variable into this template
3. Template executes in Rhino via MCP

USAGE (from PHP):
  $template = file_get_contents('cabinet_rhino_builder.py');
  $data = $rhinoExportService->generatePythonData($audit);
  $script = "DATA = " . json_encode($data) . "\n\n" . $template;
  // Execute $script via Rhino MCP

COMPONENT TYPES:
  - cabinet_box      (sides, bottom, back)
  - face_frame       (stiles, rails)
  - stretcher        (front, back, drawer support)
  - false_front      (faces)
  - false_front_backing
  - drawer_face      (fronts only)
  - drawer_box       (boxes only)
  - drawer_box_bottom
  - finished_end     (end panels)
  - toe_kick
"""

import rhinoscriptsyntax as rs

# ============================================================
# DATA - INJECTED BY PHP (DO NOT HARDCODE)
# This variable must be set before running this script
# ============================================================

# DATA = { ... }  # Injected by PHP

# ============================================================
# COLORS BY PART TYPE
# ============================================================

COLORS = {
    "cabinet_box": (139, 90, 43),
    "face_frame": (210, 180, 140),
    "stretcher": (160, 82, 45),
    "toe_kick": (105, 105, 105),
    "finished_end": (205, 133, 63),
    "false_front": (222, 184, 135),
    "false_front_backing": (188, 143, 143),
    "drawer_face": (245, 222, 179),
    "drawer_box": (255, 228, 181),
    "drawer_box_bottom": (255, 239, 213)
}

# ============================================================
# HELPER FUNCTIONS
# ============================================================

def delete_all():
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

def to_rhino(x, y, z, toe_kick_height):
    """
    Transform from CabinetMathAuditService coords to Rhino coords.
    Rhino X = Our X
    Rhino Y = Our Z (depth)
    Rhino Z = Our Y + toe_kick (shift to floor level)
    """
    return (x, z, y + toe_kick_height)

def create_box_from_part(part, toe_kick_height):
    """Create a box from part data using 8-corner method."""
    pos = part["position"]
    dim = part["dimensions"]
    x = pos["x"]
    y = pos["y"]
    z = pos["z"]
    w = dim["w"]
    h = dim["h"]
    d = dim["d"]

    if w <= 0 or h <= 0 or d <= 0:
        return None

    # 8 corner points for AddBox (more reliable than centered BOX)
    p1 = to_rhino(x, y, z, toe_kick_height)
    p2 = to_rhino(x + w, y, z, toe_kick_height)
    p3 = to_rhino(x + w, y, z + d, toe_kick_height)
    p4 = to_rhino(x, y, z + d, toe_kick_height)
    p5 = to_rhino(x, y + h, z, toe_kick_height)
    p6 = to_rhino(x + w, y + h, z, toe_kick_height)
    p7 = to_rhino(x + w, y + h, z + d, toe_kick_height)
    p8 = to_rhino(x, y + h, z + d, toe_kick_height)

    box = rs.AddBox([p1, p2, p3, p4, p5, p6, p7, p8])

    if box:
        name = part["part_name"]
        part_type = part["part_type"]
        color = COLORS.get(part_type, (128, 128, 128))
        rs.ObjectName(box, name)
        rs.ObjectColor(box, color)

    return box

def create_miter_cutter_solid(miter_cut, toe_kick_height):
    """
    Create a solid triangular prism to subtract for miter cut.
    Uses polyline -> planar surface -> extrude -> cap for valid brep.
    """
    vertices = miter_cut["vertices_xz"]
    y_range = miter_cut["y_range"]

    y_start = y_range["start"]
    y_end = y_range["end"]

    v0 = vertices[0]
    v1 = vertices[1]
    v2 = vertices[2]

    p1 = to_rhino(v0["x"], y_start, v0["z"], toe_kick_height)
    p2 = to_rhino(v1["x"], y_start, v1["z"], toe_kick_height)
    p3 = to_rhino(v2["x"], y_start, v2["z"], toe_kick_height)

    triangle = rs.AddPolyline([p1, p2, p3, p1])
    if not triangle:
        print("  Failed to create triangle polyline")
        return None

    surface = rs.AddPlanarSrf([triangle])
    rs.DeleteObject(triangle)

    if not surface or len(surface) == 0:
        print("  Failed to create planar surface")
        return None

    srf = surface[0]

    start_pt = p1
    end_pt = to_rhino(v0["x"], y_end, v0["z"], toe_kick_height)
    line = rs.AddLine(start_pt, end_pt)

    extruded = rs.ExtrudeSurface(srf, line)
    rs.DeleteObject(srf)
    rs.DeleteObject(line)

    if not extruded:
        print("  Failed to extrude surface")
        return None

    rs.CapPlanarHoles(extruded)

    return extruded

def apply_miter_cut(box, miter_cut, toe_kick_height, part_name):
    """Apply miter cut to a box using boolean difference."""
    if not miter_cut:
        return box

    cutter = create_miter_cutter_solid(miter_cut, toe_kick_height)
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

def main(component_types=None, clear_existing=True):
    """
    Build cabinet parts in Rhino.

    Args:
        component_types: List of part types to build, or None for all.
        clear_existing: If True, delete existing objects first.
    """
    # Check DATA exists
    if 'DATA' not in dir() and 'DATA' not in globals():
        print("ERROR: DATA not defined. This template requires DATA to be injected by PHP.")
        return 0

    print("=" * 50)
    print("TCS CABINET BUILDER")
    print("=" * 50)

    envelope = DATA["cabinet_envelope"]
    parts = DATA["parts"]
    toe_kick_height = envelope["toe_kick_height"]

    print("Cabinet: " + str(envelope["width"]) + " x " + str(envelope["height"]) + " x " + str(envelope["depth"]))
    print("Box Height: " + str(envelope["box_height"]))

    if component_types:
        print("Building components: " + str(component_types))
    else:
        print("Building ALL components")
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

    for part_key in parts:
        part = parts[part_key]
        part_type = part["part_type"]

        if component_types and part_type not in component_types:
            skipped += 1
            continue

        box = create_box_from_part(part, toe_kick_height)

        if box:
            pos = part["position"]
            dim = part["dimensions"]
            print("  " + part["part_name"] + ": Y=" + str(pos["y"]) + " to " + str(pos["y"] + dim["h"]))

            miter_cut = part.get("miter_cut")
            if miter_cut:
                box = apply_miter_cut(box, miter_cut, toe_kick_height, part["part_name"])

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
# EXECUTE (called after DATA is injected)
# ============================================================

# Check if COMPONENT_TYPES was specified (optional filter)
if 'COMPONENT_TYPES' in dir() or 'COMPONENT_TYPES' in globals():
    main(COMPONENT_TYPES)
else:
    main()
