"""
Cabinet Rhino Builder Script
TCS Woodwork - Pure JSON Renderer with Miter Joints

THIS SCRIPT ONLY RENDERS - NO CALCULATIONS
All math comes from CabinetXYZService.php.
If geometry is wrong, fix it in PHP services, not here.

Source of Truth:
- CabinetXYZService.php -> positions_3d output
- CabinetXYZService.php -> calculateMiterJoints() for miter cuts
- ConstructionStandardsService.php -> assembly rules

To regenerate DATA: Run PHP export and copy output here.
"""

import rhinoscriptsyntax as rs

# ============================================================
# DATA FROM CabinetXYZService.php (regenerate with PHP export)
# joint_type: miter
# ============================================================

DATA = {
    "cabinet_envelope": {
        "width": 41.3125,
        "height": 32.75,
        "box_height": 28.75,
        "depth": 18.75,
        "toe_kick_height": 4
    },
    "parts": {
        "left_side": {
            "part_name": "Left Side",
            "part_type": "cabinet_box",
            "position": {"x": 0, "y": 0.75, "z": 1},
            "dimensions": {"w": 0.75, "h": 27.25, "d": 17}
        },
        "right_side": {
            "part_name": "Right Side",
            "part_type": "cabinet_box",
            "position": {"x": 40.5625, "y": 0.75, "z": 1},
            "dimensions": {"w": 0.75, "h": 27.25, "d": 17}
        },
        "bottom": {
            "part_name": "Bottom",
            "part_type": "cabinet_box",
            "position": {"x": 0, "y": 0, "z": 1},
            "dimensions": {"w": 41.3125, "h": 0.75, "d": 17}
        },
        "back": {
            "part_name": "Back",
            "part_type": "cabinet_box",
            "position": {"x": 0, "y": 0, "z": 18},
            "dimensions": {"w": 41.3125, "h": 28.75, "d": 0.75}
        },
        "toe_kick": {
            "part_name": "Toe Kick",
            "part_type": "cabinet_box",
            "position": {"x": 0.75, "y": -4, "z": 3},
            "dimensions": {"w": 39.8125, "h": 4, "d": 0.75}
        },
        "left_stile": {
            "part_name": "Left Stile",
            "part_type": "face_frame",
            "position": {"x": -1, "y": -4, "z": 0},
            "dimensions": {"w": 1.75, "h": 32.75, "d": 1},
            "miter_cut": {
                "type": "triangular_prism",
                "remove_from": "collision_zone_front",
                "vertices_xz": [
                    {"x": -1, "z": 0},
                    {"x": -0.25, "z": 1},
                    {"x": -0.25, "z": 0}
                ],
                "y_range": {"start": -4, "end": 28.75},
                "miter_angle": 45
            }
        },
        "right_stile": {
            "part_name": "Right Stile",
            "part_type": "face_frame",
            "position": {"x": 40.5625, "y": -4, "z": 0},
            "dimensions": {"w": 1.75, "h": 32.75, "d": 1},
            "miter_cut": {
                "type": "triangular_prism",
                "remove_from": "collision_zone_front",
                "vertices_xz": [
                    {"x": 42.3125, "z": 0},
                    {"x": 41.5625, "z": 1},
                    {"x": 41.5625, "z": 0}
                ],
                "y_range": {"start": -4, "end": 28.75},
                "miter_angle": 45
            }
        },
        "top_rail": {
            "part_name": "Top Rail",
            "part_type": "face_frame",
            "position": {"x": 0.75, "y": 26.5, "z": 0},
            "dimensions": {"w": 39.8125, "h": 1.5, "d": 0.75}
        },
        "front_stretcher": {
            "part_name": "Front Stretcher",
            "part_type": "stretcher",
            "position": {"x": 0, "y": 28, "z": 1},
            "dimensions": {"w": 41.3125, "h": 0.75, "d": 3}
        },
        "back_stretcher": {
            "part_name": "Back Stretcher",
            "part_type": "stretcher",
            "position": {"x": 0, "y": 28, "z": 14},
            "dimensions": {"w": 41.3125, "h": 0.75, "d": 3}
        },
        "drawer_divider_stretcher": {
            "part_name": "Drawer Divider Stretcher",
            "part_type": "stretcher",
            "position": {"x": 0.75, "y": 11.25, "z": 1},
            "dimensions": {"w": 39.8125, "h": 0.75, "d": 16}
        },
        "left_end_panel": {
            "part_name": "Left End Panel",
            "part_type": "finished_end",
            "position": {"x": -1, "y": -4, "z": 0},
            "dimensions": {"w": 0.75, "h": 32.75, "d": 19.25},
            "miter_cut": {
                "type": "triangular_prism",
                "remove_from": "collision_zone_back",
                "vertices_xz": [
                    {"x": -1, "z": 0},
                    {"x": -0.25, "z": 1},
                    {"x": -1, "z": 1}
                ],
                "y_range": {"start": -4, "end": 28.75},
                "miter_angle": 45
            }
        },
        "right_end_panel": {
            "part_name": "Right End Panel",
            "part_type": "finished_end",
            "position": {"x": 41.5625, "y": -4, "z": 0},
            "dimensions": {"w": 0.75, "h": 32.75, "d": 19.25},
            "miter_cut": {
                "type": "triangular_prism",
                "remove_from": "collision_zone_back",
                "vertices_xz": [
                    {"x": 42.3125, "z": 0},
                    {"x": 41.5625, "z": 1},
                    {"x": 42.3125, "z": 1}
                ],
                "y_range": {"start": -4, "end": 28.75},
                "miter_angle": 45
            }
        },
        "false_front_1_face": {
            "part_name": "False Front #1 Face",
            "part_type": "false_front",
            "position": {"x": 0.75, "y": 22.625, "z": 0},
            "dimensions": {"w": 39.8125, "h": 6, "d": 0.75}
        },
        "false_front_1_backing": {
            "part_name": "False Front #1 Backing",
            "part_type": "false_front_backing",
            "position": {"x": 0.75, "y": 21, "z": 0.75},
            "dimensions": {"w": 39.8125, "h": 7, "d": 0.75}
        },
        "drawer_1_face": {
            "part_name": "Upper Drawer Face",
            "part_type": "drawer_face",
            "position": {"x": 0.875, "y": 11.3125, "z": 0},
            "dimensions": {"w": 39.5625, "h": 11.1875, "d": 0.75}
        },
        "drawer_2_face": {
            "part_name": "Lower Drawer Face",
            "part_type": "drawer_face",
            "position": {"x": 0.875, "y": 0, "z": 0},
            "dimensions": {"w": 39.5625, "h": 11.1875, "d": 0.75}
        }
    }
}

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
    "drawer_face": (245, 222, 179)
}

# ============================================================
# HELPER FUNCTIONS
# ============================================================

def delete_all():
    all_objs = rs.AllObjects()
    if all_objs:
        rs.DeleteObjects(all_objs)
        print("Deleted " + str(len(all_objs)) + " objects")

def to_rhino(x, y, z, toe_kick_height):
    """
    Transform from CabinetXYZService coords to Rhino coords.
    Rhino X = Our X
    Rhino Y = Our Z (depth)
    Rhino Z = Our Y + toe_kick (shift to floor level)
    """
    return (x, z, y + toe_kick_height)

def create_box_from_part(part, toe_kick_height):
    """Create a box from part data."""
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

    The miter_cut contains:
    - vertices_xz: 3 points defining triangle in XZ plane
    - y_range: start and end Y values (full height of cut)
    """
    vertices = miter_cut["vertices_xz"]
    y_range = miter_cut["y_range"]

    y_start = y_range["start"]
    y_end = y_range["end"]

    # Get triangle vertices in XZ
    v0 = vertices[0]
    v1 = vertices[1]
    v2 = vertices[2]

    # Create triangle at bottom (y_start)
    p1 = to_rhino(v0["x"], y_start, v0["z"], toe_kick_height)
    p2 = to_rhino(v1["x"], y_start, v1["z"], toe_kick_height)
    p3 = to_rhino(v2["x"], y_start, v2["z"], toe_kick_height)

    # Create closed polyline for triangle
    triangle = rs.AddPolyline([p1, p2, p3, p1])
    if not triangle:
        print("  Failed to create triangle polyline")
        return None

    # Create planar surface from closed polyline
    surface = rs.AddPlanarSrf([triangle])
    rs.DeleteObject(triangle)

    if not surface or len(surface) == 0:
        print("  Failed to create planar surface")
        return None

    srf = surface[0]

    # Create extrusion direction (from y_start to y_end)
    start_pt = p1
    end_pt = to_rhino(v0["x"], y_end, v0["z"], toe_kick_height)
    line = rs.AddLine(start_pt, end_pt)

    # Extrude surface along direction
    extruded = rs.ExtrudeSurface(srf, line)
    rs.DeleteObject(srf)
    rs.DeleteObject(line)

    if not extruded:
        print("  Failed to extrude surface")
        return None

    # Cap the ends to make it a solid
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

    # Boolean difference
    result = rs.BooleanDifference([box], [cutter], delete_input=True)

    if result and len(result) > 0:
        print("  Applied miter to " + part_name)
        return result[0]
    else:
        print("  Warning: Boolean failed for " + part_name)
        # Clean up cutter if boolean failed
        if rs.IsObject(cutter):
            rs.DeleteObject(cutter)
        return box

# ============================================================
# MAIN
# ============================================================

def main():
    print("=" * 50)
    print("TCS CABINET - WITH MITER JOINTS")
    print("=" * 50)

    envelope = DATA["cabinet_envelope"]
    parts = DATA["parts"]
    toe_kick_height = envelope["toe_kick_height"]

    print("Cabinet: " + str(envelope["width"]) + " x " + str(envelope["height"]) + " x " + str(envelope["depth"]))
    print("Box Height: " + str(envelope["box_height"]))
    print("")

    delete_all()
    print("")

    print("Creating parts:")
    created = 0

    for part_key in parts:
        part = parts[part_key]

        # Create the base box
        box = create_box_from_part(part, toe_kick_height)

        if box:
            pos = part["position"]
            dim = part["dimensions"]
            print("  " + part["part_name"] + ": Y=" + str(pos["y"]) + " to " + str(pos["y"] + dim["h"]))

            # Apply miter cut if present
            miter_cut = part.get("miter_cut")
            if miter_cut:
                box = apply_miter_cut(box, miter_cut, toe_kick_height, part["part_name"])

            created = created + 1

    print("")
    print("Created " + str(created) + " objects")

    rs.ZoomExtents()
    print("=" * 50)

main()
