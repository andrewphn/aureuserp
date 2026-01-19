"""
TCS Cabinet Box Generator - Grasshopper Component
GHPython component for generating cabinet box envelope geometry.

INPUTS:
    width: float - Cabinet width in inches
    height: float - Cabinet height in inches
    depth: float - Cabinet depth in inches
    cabinet_type: str - Cabinet type (base, wall, tall)
    position_x: float - X position (default 0)
    position_y: float - Y position (default 0)
    position_z: float - Z position (default 0)
    include_toe_kick: bool - Include toe kick (default True for base)
    generate: bool - Trigger geometry generation

OUTPUTS:
    box_geometry: Brep - Cabinet box envelope geometry
    toe_kick_geometry: Brep - Toe kick geometry (if applicable)
    interior_box: Brep - Interior cavity geometry
    origin_point: Point3d - Cabinet origin point
    bounding_box: Box - Overall bounding box
    dimensions_text: str - Dimensions for annotation
    envelope_data: str - Cabinet envelope data as JSON

USAGE IN GRASSHOPPER:
1. Connect dimensions from Cabinet Calculator
2. Set position for placement in Rhino
3. Toggle generate to create geometry
4. Connect outputs to Bake or further geometry operations

COORDINATE SYSTEM:
    Origin: Bottom-left-front corner of cabinet
    X: Width (left to right)
    Y: Depth (front to back)
    Z: Height (bottom to top)

    For base cabinets:
    - Z=0 is floor level
    - Toe kick extends from Z=0 to Z=TOE_KICK_HEIGHT
    - Cabinet box starts at Z=TOE_KICK_HEIGHT
"""

import json

# Grasshopper component metadata
ghenv.Component.Name = "TCS Cabinet Box"
ghenv.Component.NickName = "TCS Box"
ghenv.Component.Message = "v1.0"
ghenv.Component.Category = "TCS"
ghenv.Component.SubCategory = "Geometry"

# Try to import Rhino geometry
try:
    import Rhino.Geometry as rg
    HAS_RHINO = True
except ImportError:
    HAS_RHINO = False
    print("Warning: Rhino.Geometry not available")

# TCS Construction Constants
TOE_KICK_HEIGHT = 4.5
TOE_KICK_SETBACK = 3.0
MATERIAL_THICKNESS = 0.75
BACK_PANEL_THICKNESS = 0.25


def has_toe_kick(cab_type):
    """Determine if cabinet type has toe kick."""
    no_toe_kick_types = ['wall', 'upper', 'tall_upper']
    return cab_type.lower() not in no_toe_kick_types


def calculate_box_dimensions(ext_width, ext_height, ext_depth, cab_type):
    """
    Calculate cabinet box dimensions from exterior.
    Returns dict with all relevant dimensions.
    """
    has_tk = has_toe_kick(cab_type)
    toe_kick_h = TOE_KICK_HEIGHT if has_tk else 0.0

    return {
        'exterior': {
            'width': ext_width,
            'height': ext_height,
            'depth': ext_depth,
        },
        'box': {
            'width': ext_width,
            'height': ext_height - toe_kick_h,
            'depth': ext_depth,
        },
        'interior': {
            'width': ext_width - (2 * MATERIAL_THICKNESS),
            'height': ext_height - toe_kick_h - MATERIAL_THICKNESS,
            'depth': ext_depth - MATERIAL_THICKNESS - BACK_PANEL_THICKNESS,
        },
        'toe_kick': {
            'height': toe_kick_h,
            'setback': TOE_KICK_SETBACK,
            'enabled': has_tk,
        },
        'material_thickness': MATERIAL_THICKNESS,
    }


def create_box_brep(x, y, z, w, h, d):
    """
    Create a box Brep from corner point and dimensions.
    Returns Rhino Brep or None.
    """
    if not HAS_RHINO:
        return None

    if w <= 0 or h <= 0 or d <= 0:
        return None

    try:
        # Create base plane at corner
        origin = rg.Point3d(x, y, z)
        plane = rg.Plane(origin, rg.Vector3d.XAxis, rg.Vector3d.YAxis)

        # Create interval for box
        x_interval = rg.Interval(0, w)
        y_interval = rg.Interval(0, d)
        z_interval = rg.Interval(0, h)

        # Create box
        box = rg.Box(plane, x_interval, y_interval, z_interval)
        return box.ToBrep()

    except Exception as e:
        print("Box creation failed: {}".format(str(e)))
        return None


def create_toe_kick_brep(x, y, z, w, d, setback):
    """
    Create toe kick geometry.
    Toe kick is recessed from front by setback amount.
    """
    if not HAS_RHINO:
        return None

    try:
        # Toe kick dimensions
        tk_width = w
        tk_depth = d - setback
        tk_height = TOE_KICK_HEIGHT

        # Position is at back of toe kick recess
        tk_x = x
        tk_y = y + setback
        tk_z = z

        return create_box_brep(tk_x, tk_y, tk_z, tk_width, tk_height, tk_depth)

    except Exception as e:
        print("Toe kick creation failed: {}".format(str(e)))
        return None


def create_interior_brep(x, y, z, dims):
    """
    Create interior cavity geometry.
    This represents the usable interior space.
    """
    if not HAS_RHINO:
        return None

    try:
        int_dims = dims['interior']
        toe_kick_h = dims['toe_kick']['height'] if dims['toe_kick']['enabled'] else 0

        # Interior starts after left side panel, bottom panel, and front face frame
        int_x = x + MATERIAL_THICKNESS
        int_y = y + dims['material_thickness']  # After face frame
        int_z = z + toe_kick_h + MATERIAL_THICKNESS

        return create_box_brep(
            int_x, int_y, int_z,
            int_dims['width'],
            int_dims['height'],
            int_dims['depth']
        )

    except Exception as e:
        print("Interior creation failed: {}".format(str(e)))
        return None


# ============================================================
# MAIN COMPONENT LOGIC
# ============================================================

# Initialize outputs
box_geometry = None
toe_kick_geometry = None
interior_box = None
origin_point = None
bounding_box = None
dimensions_text = ""
envelope_data = "{}"

# Validate inputs
if not width or width <= 0:
    print("Width required")
elif not height or height <= 0:
    print("Height required")
elif not depth or depth <= 0:
    print("Depth required")
elif not generate:
    print("Set generate=True to create geometry")
else:
    # Get position (default to origin)
    pos_x = position_x if position_x else 0.0
    pos_y = position_y if position_y else 0.0
    pos_z = position_z if position_z else 0.0

    # Get cabinet type (default to base)
    cab_type = cabinet_type if cabinet_type else 'base'

    # Calculate dimensions
    dims = calculate_box_dimensions(width, height, depth, cab_type)

    # Determine toe kick status
    has_tk = dims['toe_kick']['enabled']
    if include_toe_kick is not None:
        has_tk = include_toe_kick and dims['toe_kick']['enabled']
    toe_kick_h = TOE_KICK_HEIGHT if has_tk else 0.0

    # Create geometry
    if HAS_RHINO:
        # Create main cabinet box (starts above toe kick)
        box_geometry = create_box_brep(
            pos_x, pos_y, pos_z + toe_kick_h,
            dims['box']['width'],
            dims['box']['height'],
            dims['box']['depth']
        )

        # Create toe kick if applicable
        if has_tk:
            toe_kick_geometry = create_toe_kick_brep(
                pos_x, pos_y, pos_z,
                width, depth, TOE_KICK_SETBACK
            )

        # Create interior cavity
        interior_box = create_interior_brep(pos_x, pos_y, pos_z, dims)

        # Create origin point
        origin_point = rg.Point3d(pos_x, pos_y, pos_z)

        # Create bounding box
        bb_min = rg.Point3d(pos_x, pos_y, pos_z)
        bb_max = rg.Point3d(pos_x + width, pos_y + depth, pos_z + height)
        bounding_box = rg.Box(
            rg.Plane.WorldXY,
            rg.Interval(pos_x, pos_x + width),
            rg.Interval(pos_y, pos_y + depth),
            rg.Interval(pos_z, pos_z + height)
        )

        print("Created cabinet box: {:.1f}\" x {:.1f}\" x {:.1f}\"".format(
            width, height, depth
        ))

    # Format dimensions text
    dimensions_text = '{:.1f}" W x {:.1f}" H x {:.1f}" D'.format(
        width, height, depth
    )

    # Build envelope data JSON
    envelope_data = json.dumps({
        'position': {'x': pos_x, 'y': pos_y, 'z': pos_z},
        'dimensions': dims,
        'cabinet_type': cab_type,
        'has_toe_kick': has_tk,
        'toe_kick_height': toe_kick_h,
    }, indent=2)


# ============================================================
# HELPER FUNCTIONS
# ============================================================

def get_corner_points():
    """Get 8 corner points of cabinet bounding box."""
    if not HAS_RHINO or not width or not height or not depth:
        return []

    x = position_x if position_x else 0
    y = position_y if position_y else 0
    z = position_z if position_z else 0

    return [
        rg.Point3d(x, y, z),
        rg.Point3d(x + width, y, z),
        rg.Point3d(x + width, y + depth, z),
        rg.Point3d(x, y + depth, z),
        rg.Point3d(x, y, z + height),
        rg.Point3d(x + width, y, z + height),
        rg.Point3d(x + width, y + depth, z + height),
        rg.Point3d(x, y + depth, z + height),
    ]


def get_face_plane(face_name):
    """Get plane for cabinet face (front, back, left, right, top, bottom)."""
    if not HAS_RHINO:
        return None

    x = position_x if position_x else 0
    y = position_y if position_y else 0
    z = position_z if position_z else 0

    planes = {
        'front': rg.Plane(rg.Point3d(x + width/2, y, z + height/2), rg.Vector3d.YAxis),
        'back': rg.Plane(rg.Point3d(x + width/2, y + depth, z + height/2), -rg.Vector3d.YAxis),
        'left': rg.Plane(rg.Point3d(x, y + depth/2, z + height/2), -rg.Vector3d.XAxis),
        'right': rg.Plane(rg.Point3d(x + width, y + depth/2, z + height/2), rg.Vector3d.XAxis),
        'top': rg.Plane(rg.Point3d(x + width/2, y + depth/2, z + height), rg.Vector3d.ZAxis),
        'bottom': rg.Plane(rg.Point3d(x + width/2, y + depth/2, z), -rg.Vector3d.ZAxis),
    }

    return planes.get(face_name.lower())


# Print component info
print("")
print("TCS Cabinet Box Generator")
print("=" * 40)
if width and height and depth:
    print("Dimensions: {}".format(dimensions_text))
    cab_type = cabinet_type if cabinet_type else 'base'
    print("Type: {}".format(cab_type.title()))
    if has_toe_kick(cab_type):
        print("Toe Kick: {:.1f}\"".format(TOE_KICK_HEIGHT))
    print("Position: ({:.1f}, {:.1f}, {:.1f})".format(
        position_x if position_x else 0,
        position_y if position_y else 0,
        position_z if position_z else 0
    ))
else:
    print("Enter dimensions to generate geometry")
