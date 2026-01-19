"""
TCS Drawer Geometry - Grasshopper Component
GHPython component for generating drawer box and face geometry.

INPUTS:
    opening_width: float - Face frame opening width
    opening_height: float - Face frame opening height (for drawer face)
    cavity_depth: float - Cabinet interior depth for drawer box
    drawer_height: float - Drawer box height (default 4")
    slide_type: str - Slide type (blum_tandem, side_mount, undermount)
    overlay: float - Drawer face overlay on opening (default 0.5")
    reveal: float - Gap between drawer faces (default 0.125")
    position_x: float - X position
    position_y: float - Y position (depth position)
    position_z: float - Z position (height of opening bottom)
    generate: bool - Trigger geometry generation

OUTPUTS:
    drawer_box: Brep - Drawer box geometry (4 sides + bottom)
    drawer_face: Brep - Drawer front panel geometry
    drawer_assembly: list - All drawer components
    box_parts: list - Individual box part geometries
    drawer_dimensions: str - Drawer dimensions text
    drawer_data: str - Full drawer data as JSON

USAGE IN GRASSHOPPER:
1. Connect opening dimensions from Face Frame component
2. Set cavity_depth from Cabinet Calculator
3. Choose slide type for proper deductions
4. Toggle generate to create geometry

BLUM TANDEM SLIDE SPECIFICATIONS:
    Side Deduction: 0.625" (5/8") per side = 1.25" total width deduction
    Height Deduction: 0.8125" (13/16")
    Bottom Clearance: 0.5"

DRAWER BOX CONSTRUCTION:
    Material: 1/2" Baltic Birch (0.5")
    Bottom: 1/4" plywood in dado (0.25")
    Dado depth: 0.25" from bottom edge
"""

import json

# Grasshopper component metadata
ghenv.Component.Name = "TCS Drawer Geometry"
ghenv.Component.NickName = "TCS Drawer"
ghenv.Component.Message = "v1.0"
ghenv.Component.Category = "TCS"
ghenv.Component.SubCategory = "Geometry"

# Try to import Rhino geometry
try:
    import Rhino.Geometry as rg
    HAS_RHINO = True
except ImportError:
    HAS_RHINO = False

# Drawer Box Constants
BOX_MATERIAL_THICKNESS = 0.5  # 1/2" Baltic Birch
BOTTOM_THICKNESS = 0.25  # 1/4" plywood
DADO_HEIGHT = 0.5  # Distance from bottom of side to dado
FACE_THICKNESS = 0.75  # 3/4" drawer face

# Slide Specifications
SLIDE_SPECS = {
    'blum_tandem': {
        'side_deduction': 0.625,  # Per side
        'height_deduction': 0.8125,
        'bottom_clearance': 0.5,
        'min_depth': 12.0,
    },
    'blum_563h': {  # Alias for Tandem
        'side_deduction': 0.625,
        'height_deduction': 0.8125,
        'bottom_clearance': 0.5,
        'min_depth': 12.0,
    },
    'side_mount': {
        'side_deduction': 0.5,
        'height_deduction': 0.5,
        'bottom_clearance': 0.25,
        'min_depth': 10.0,
    },
    'undermount': {
        'side_deduction': 0.5,
        'height_deduction': 0.75,
        'bottom_clearance': 0.375,
        'min_depth': 12.0,
    },
}

DEFAULT_OVERLAY = 0.5
DEFAULT_REVEAL = 0.125


def get_slide_spec(slide_type):
    """Get slide specifications by type."""
    return SLIDE_SPECS.get(
        slide_type.lower() if slide_type else 'blum_tandem',
        SLIDE_SPECS['blum_tandem']
    )


def calculate_drawer_box(opening_w, cavity_d, drawer_h, slide_spec):
    """
    Calculate drawer box dimensions based on opening and slide type.
    Returns dict with all drawer box dimensions.
    """
    # Box width = opening - (2 * side deduction)
    box_width = opening_w - (2 * slide_spec['side_deduction'])

    # Box height = drawer height - height deduction
    box_height = drawer_h - slide_spec['height_deduction']

    # Box depth = cavity depth - clearance (typically 1" less than cavity)
    box_depth = cavity_d - 1.0

    # Ensure minimum depth
    box_depth = max(box_depth, slide_spec['min_depth'])

    # Side dimensions
    side_width = box_depth - BOX_MATERIAL_THICKNESS  # Sides fit behind front
    side_height = box_height

    # Front/back dimensions
    front_back_width = box_width - (2 * BOX_MATERIAL_THICKNESS)
    front_back_height = box_height

    # Bottom dimensions (fits in dado)
    bottom_width = box_width - (2 * BOX_MATERIAL_THICKNESS) + (2 * 0.25)  # Into dados
    bottom_depth = box_depth - BOX_MATERIAL_THICKNESS + 0.25  # Into back dado

    return {
        'box': {
            'width': box_width,
            'height': box_height,
            'depth': box_depth,
        },
        'sides': {
            'width': side_width,
            'height': side_height,
            'thickness': BOX_MATERIAL_THICKNESS,
        },
        'front_back': {
            'width': front_back_width,
            'height': front_back_height,
            'thickness': BOX_MATERIAL_THICKNESS,
        },
        'bottom': {
            'width': bottom_width,
            'depth': bottom_depth,
            'thickness': BOTTOM_THICKNESS,
        },
        'slide_spec': slide_spec,
    }


def calculate_drawer_face(opening_w, opening_h, overlay_val, reveal_val):
    """
    Calculate drawer face dimensions.
    Face overlays the opening and has reveal gaps.
    """
    # Face width = opening + (2 * overlay) - reveal on sides
    face_width = opening_w + (2 * overlay_val)

    # Face height = opening height + overlay (top and bottom share reveal)
    face_height = opening_h - reveal_val

    return {
        'width': face_width,
        'height': face_height,
        'thickness': FACE_THICKNESS,
        'overlay': overlay_val,
        'reveal': reveal_val,
    }


def create_box_brep(x, y, z, w, h, d):
    """Create a box Brep."""
    if not HAS_RHINO or w <= 0 or h <= 0 or d <= 0:
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


def create_drawer_box_geometry(dims, base_x, base_y, base_z):
    """
    Create drawer box geometry (4 sides + bottom).
    Returns dict of Breps.
    """
    parts = {}

    box = dims['box']
    sides = dims['sides']
    fb = dims['front_back']
    bottom = dims['bottom']

    # Left side
    parts['left_side'] = create_box_brep(
        base_x,
        base_y,
        base_z,
        BOX_MATERIAL_THICKNESS,
        sides['height'],
        sides['width']
    )

    # Right side
    parts['right_side'] = create_box_brep(
        base_x + box['width'] - BOX_MATERIAL_THICKNESS,
        base_y,
        base_z,
        BOX_MATERIAL_THICKNESS,
        sides['height'],
        sides['width']
    )

    # Front
    parts['front'] = create_box_brep(
        base_x + BOX_MATERIAL_THICKNESS,
        base_y,
        base_z,
        fb['width'],
        fb['height'],
        BOX_MATERIAL_THICKNESS
    )

    # Back
    parts['back'] = create_box_brep(
        base_x + BOX_MATERIAL_THICKNESS,
        base_y + box['depth'] - BOX_MATERIAL_THICKNESS,
        base_z,
        fb['width'],
        fb['height'],
        BOX_MATERIAL_THICKNESS
    )

    # Bottom (in dado, raised from bottom edge)
    parts['bottom'] = create_box_brep(
        base_x + BOX_MATERIAL_THICKNESS - 0.25,
        base_y + 0.25,
        base_z + DADO_HEIGHT,
        bottom['width'],
        BOTTOM_THICKNESS,
        bottom['depth']
    )

    return parts


def create_drawer_face_geometry(face_dims, opening_x, opening_y, opening_z, overlay_val):
    """Create drawer face panel geometry."""
    # Face is positioned to overlay the opening
    face_x = opening_x - overlay_val
    face_y = opening_y - FACE_THICKNESS  # In front of cabinet
    face_z = opening_z

    return create_box_brep(
        face_x,
        face_y,
        face_z,
        face_dims['width'],
        face_dims['height'],
        face_dims['thickness']
    )


# ============================================================
# MAIN COMPONENT LOGIC
# ============================================================

# Initialize outputs
drawer_box = None
drawer_face = None
drawer_assembly = []
box_parts = []
drawer_dimensions = ""
drawer_data = "{}"

# Validate inputs
if not opening_width or opening_width <= 0:
    print("Opening width required")
elif not opening_height or opening_height <= 0:
    print("Opening height required")
elif not cavity_depth or cavity_depth <= 0:
    print("Cavity depth required")
elif not generate:
    print("Set generate=True to create geometry")
else:
    # Get parameters
    drawer_h = drawer_height if drawer_height and drawer_height > 0 else 4.0
    slide_spec = get_slide_spec(slide_type)
    overlay_val = overlay if overlay is not None else DEFAULT_OVERLAY
    reveal_val = reveal if reveal is not None else DEFAULT_REVEAL

    pos_x = position_x if position_x else 0.0
    pos_y = position_y if position_y else 0.0
    pos_z = position_z if position_z else 0.0

    # Calculate dimensions
    box_dims = calculate_drawer_box(opening_width, cavity_depth, drawer_h, slide_spec)
    face_dims = calculate_drawer_face(opening_width, opening_height, overlay_val, reveal_val)

    # Store full data
    full_data = {
        'box': box_dims,
        'face': face_dims,
        'position': {'x': pos_x, 'y': pos_y, 'z': pos_z},
        'slide_type': slide_type if slide_type else 'blum_tandem',
    }
    drawer_data = json.dumps(full_data, indent=2)

    if HAS_RHINO:
        # Position drawer box inside cabinet (offset from face)
        box_x = pos_x + slide_spec['side_deduction']
        box_y = pos_y + FACE_THICKNESS  # Behind the face
        box_z = pos_z + slide_spec['bottom_clearance']

        # Create box parts
        parts = create_drawer_box_geometry(box_dims, box_x, box_y, box_z)

        for name, brep in parts.items():
            if brep:
                box_parts.append(brep)
                drawer_assembly.append(brep)

        # Combine box parts
        if box_parts:
            try:
                combined = rg.Brep.JoinBreps(box_parts, 0.001)
                if combined:
                    drawer_box = combined[0]
            except:
                drawer_box = box_parts[0]

        # Create drawer face
        drawer_face = create_drawer_face_geometry(face_dims, pos_x, pos_y, pos_z, overlay_val)
        if drawer_face:
            drawer_assembly.append(drawer_face)

        print("Created drawer: {:.2f}\" x {:.2f}\" x {:.2f}\" box".format(
            box_dims['box']['width'],
            box_dims['box']['height'],
            box_dims['box']['depth']
        ))

    # Format dimensions text
    drawer_dimensions = "Box: {:.2f}\" W x {:.2f}\" H x {:.2f}\" D\nFace: {:.2f}\" W x {:.2f}\" H".format(
        box_dims['box']['width'],
        box_dims['box']['height'],
        box_dims['box']['depth'],
        face_dims['width'],
        face_dims['height']
    )


# ============================================================
# HELPER FUNCTIONS
# ============================================================

def get_drawer_summary():
    """Get drawer specifications summary."""
    if not opening_width:
        return "No drawer calculated"

    slide_spec = get_slide_spec(slide_type)

    lines = [
        "Drawer Specifications:",
        "  Opening: {:.2f}\" x {:.2f}\"".format(opening_width, opening_height or 0),
        "  Slide Type: {}".format(slide_type if slide_type else 'Blum Tandem'),
        "  Side Deduction: {:.3f}\"".format(slide_spec['side_deduction']),
        "  Height Deduction: {:.4f}\"".format(slide_spec['height_deduction']),
    ]

    return "\n".join(lines)


# Print component info
print("")
print("TCS Drawer Geometry")
print("=" * 40)
print(get_drawer_summary())
if drawer_dimensions:
    print("")
    print(drawer_dimensions)
