"""
TCS Face Frame Geometry - Grasshopper Component
GHPython component for generating face frame geometry for cabinets.

INPUTS:
    width: float - Cabinet exterior width
    height: float - Cabinet exterior height (minus toe kick for base)
    cabinet_type: str - Cabinet type (base, wall, tall)
    stile_width: float - Face frame stile width (default 1.5")
    rail_width: float - Face frame rail width (default 1.5")
    opening_count: int - Number of horizontal openings (1 for single, 2 for double door)
    position_x: float - X position (default 0)
    position_y: float - Y position (default 0)
    position_z: float - Z position (default 0)
    generate: bool - Trigger geometry generation

OUTPUTS:
    face_frame: Brep - Combined face frame geometry
    left_stile: Brep - Left stile geometry
    right_stile: Brep - Right stile geometry
    top_rail: Brep - Top rail geometry
    bottom_rail: Brep - Bottom rail geometry
    center_stile: Brep - Center stile (if double opening)
    mullion_rails: list - Any mullion rails
    opening_rects: list - Opening rectangles for door/drawer placement
    face_frame_data: str - Face frame dimensions as JSON

USAGE IN GRASSHOPPER:
1. Connect width and height from Cabinet Calculator
2. Set stile/rail widths or use defaults
3. Toggle generate to create geometry
4. Use opening_rects for door/drawer fitting

FACE FRAME CONSTRUCTION:
    - Stiles run full height (vertical members)
    - Rails fit between stiles (horizontal members)
    - Material thickness is 3/4" (0.75")
    - Joints are typically pocket screws or mortise/tenon
"""

import json

# Grasshopper component metadata
ghenv.Component.Name = "TCS Face Frame Geometry"
ghenv.Component.NickName = "TCS FaceFrame"
ghenv.Component.Message = "v1.0"
ghenv.Component.Category = "TCS"
ghenv.Component.SubCategory = "Geometry"

# Try to import Rhino geometry
try:
    import Rhino.Geometry as rg
    HAS_RHINO = True
except ImportError:
    HAS_RHINO = False

# TCS Face Frame Constants
DEFAULT_STILE_WIDTH = 1.5
DEFAULT_RAIL_WIDTH = 1.5
MATERIAL_THICKNESS = 0.75
TOE_KICK_HEIGHT = 4.5


def create_box_brep(x, y, z, w, h, d):
    """Create a box Brep from corner and dimensions."""
    if not HAS_RHINO:
        return None

    if w <= 0 or h <= 0 or d <= 0:
        return None

    try:
        box = rg.Box(
            rg.Plane.WorldXY,
            rg.Interval(x, x + w),
            rg.Interval(y, y + d),
            rg.Interval(z, z + h)
        )
        return box.ToBrep()
    except:
        return None


def calculate_face_frame(ext_width, ext_height, stile_w, rail_w, num_openings, cab_type):
    """
    Calculate face frame member dimensions and positions.
    Returns dict with all face frame data.
    """
    # Face frame sits on front of cabinet box
    # For base cabinets, face frame starts at toe kick height
    toe_kick_h = TOE_KICK_HEIGHT if cab_type.lower() not in ['wall', 'upper'] else 0
    box_height = ext_height - toe_kick_h

    # Left stile: full height, at left edge
    left_stile = {
        'name': 'left_stile',
        'width': stile_w,
        'height': box_height,
        'depth': MATERIAL_THICKNESS,
        'x': 0,
        'y': 0,
        'z': toe_kick_h,
    }

    # Right stile: full height, at right edge
    right_stile = {
        'name': 'right_stile',
        'width': stile_w,
        'height': box_height,
        'depth': MATERIAL_THICKNESS,
        'x': ext_width - stile_w,
        'y': 0,
        'z': toe_kick_h,
    }

    # Rail width is between stiles
    rail_length = ext_width - (2 * stile_w)

    # Top rail: between stiles, at top
    top_rail = {
        'name': 'top_rail',
        'width': rail_length,
        'height': rail_w,
        'depth': MATERIAL_THICKNESS,
        'x': stile_w,
        'y': 0,
        'z': toe_kick_h + box_height - rail_w,
    }

    # Bottom rail: between stiles, at bottom
    bottom_rail = {
        'name': 'bottom_rail',
        'width': rail_length,
        'height': rail_w,
        'depth': MATERIAL_THICKNESS,
        'x': stile_w,
        'y': 0,
        'z': toe_kick_h,
    }

    # Center stile for double openings
    center_stile = None
    if num_openings >= 2:
        center_x = (ext_width / 2) - (stile_w / 2)
        center_stile = {
            'name': 'center_stile',
            'width': stile_w,
            'height': box_height - (2 * rail_w),  # Between rails
            'depth': MATERIAL_THICKNESS,
            'x': center_x,
            'y': 0,
            'z': toe_kick_h + rail_w,
        }

    # Calculate openings
    openings = []
    opening_width = rail_length / num_openings if num_openings > 0 else rail_length
    opening_height = box_height - (2 * rail_w)

    for i in range(num_openings):
        opening_x = stile_w + (i * (opening_width + stile_w if i > 0 else 0))
        if i > 0 and center_stile:
            opening_x = stile_w + opening_width + stile_w

        openings.append({
            'index': i,
            'width': opening_width - (stile_w if i > 0 else 0),
            'height': opening_height,
            'x': stile_w if i == 0 else stile_w + opening_width + stile_w,
            'z': toe_kick_h + rail_w,
        })

    return {
        'left_stile': left_stile,
        'right_stile': right_stile,
        'top_rail': top_rail,
        'bottom_rail': bottom_rail,
        'center_stile': center_stile,
        'openings': openings,
        'total_width': ext_width,
        'total_height': box_height,
        'toe_kick_height': toe_kick_h,
    }


def create_frame_member_brep(member, base_x, base_y, base_z):
    """Create Brep for a face frame member."""
    if not member:
        return None

    return create_box_brep(
        base_x + member['x'],
        base_y + member['y'],
        base_z + member['z'],
        member['width'],
        member['height'],
        member['depth']
    )


def create_opening_rectangle(opening, base_x, base_y, base_z):
    """Create rectangle curve for face frame opening."""
    if not HAS_RHINO or not opening:
        return None

    try:
        x = base_x + opening['x']
        y = base_y
        z = base_z + opening['z']
        w = opening['width']
        h = opening['height']

        # Create rectangle in XZ plane (front face)
        corners = [
            rg.Point3d(x, y, z),
            rg.Point3d(x + w, y, z),
            rg.Point3d(x + w, y, z + h),
            rg.Point3d(x, y, z + h),
        ]

        return rg.Polyline(corners + [corners[0]]).ToNurbsCurve()
    except:
        return None


# ============================================================
# MAIN COMPONENT LOGIC
# ============================================================

# Initialize outputs
face_frame = None
left_stile = None
right_stile = None
top_rail = None
bottom_rail = None
center_stile = None
mullion_rails = []
opening_rects = []
face_frame_data = "{}"

# Validate inputs
if not width or width <= 0:
    print("Width required")
elif not height or height <= 0:
    print("Height required")
elif not generate:
    print("Set generate=True to create geometry")
else:
    # Get parameters
    stile_w = stile_width if stile_width and stile_width > 0 else DEFAULT_STILE_WIDTH
    rail_w = rail_width if rail_width and rail_width > 0 else DEFAULT_RAIL_WIDTH
    num_openings = opening_count if opening_count and opening_count > 0 else 1
    cab_type = cabinet_type if cabinet_type else 'base'

    pos_x = position_x if position_x else 0.0
    pos_y = position_y if position_y else 0.0
    pos_z = position_z if position_z else 0.0

    # Calculate face frame
    ff_data = calculate_face_frame(width, height, stile_w, rail_w, num_openings, cab_type)

    if HAS_RHINO:
        # Create member geometry
        left_stile = create_frame_member_brep(ff_data['left_stile'], pos_x, pos_y, pos_z)
        right_stile = create_frame_member_brep(ff_data['right_stile'], pos_x, pos_y, pos_z)
        top_rail = create_frame_member_brep(ff_data['top_rail'], pos_x, pos_y, pos_z)
        bottom_rail = create_frame_member_brep(ff_data['bottom_rail'], pos_x, pos_y, pos_z)

        if ff_data['center_stile']:
            center_stile = create_frame_member_brep(ff_data['center_stile'], pos_x, pos_y, pos_z)

        # Combine into single brep
        all_members = [b for b in [left_stile, right_stile, top_rail, bottom_rail, center_stile] if b]
        if all_members:
            try:
                face_frame = rg.Brep.JoinBreps(all_members, 0.001)
                if face_frame:
                    face_frame = face_frame[0]
            except:
                face_frame = all_members[0]  # Fallback to first member

        # Create opening rectangles
        for opening in ff_data['openings']:
            rect = create_opening_rectangle(opening, pos_x, pos_y, pos_z)
            if rect:
                opening_rects.append(rect)

        print("Created face frame: {} openings".format(len(ff_data['openings'])))

    # Store face frame data as JSON
    face_frame_data = json.dumps(ff_data, indent=2)


# ============================================================
# HELPER FUNCTIONS
# ============================================================

def get_face_frame_summary():
    """Get face frame dimensions summary."""
    if not width or not height:
        return "No face frame calculated"

    stile_w = stile_width if stile_width and stile_width > 0 else DEFAULT_STILE_WIDTH
    rail_w = rail_width if rail_width and rail_width > 0 else DEFAULT_RAIL_WIDTH

    lines = [
        "Face Frame:",
        "  Overall: {:.1f}\" x {:.1f}\"".format(width, height),
        "  Stiles: {:.2f}\" wide".format(stile_w),
        "  Rails: {:.2f}\" wide".format(rail_w),
        "  Openings: {}".format(opening_count if opening_count else 1),
    ]

    return "\n".join(lines)


# Print component info
print("")
print("TCS Face Frame Geometry")
print("=" * 40)
print(get_face_frame_summary())
