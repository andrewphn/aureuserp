"""
TCS Parts Generator - Grasshopper Component
GHPython component for generating individual cabinet part geometry from cut list.

INPUTS:
    cut_list_json: str - Cut list JSON from TCS Cut List component
    calculation_json: str - Full calculation JSON from TCS Cabinet Calculator
    position_x: float - X position (default 0)
    position_y: float - Y position (default 0)
    position_z: float - Z position (default 0)
    cabinet_type: str - Cabinet type for construction rules
    explode_distance: float - Distance to explode parts (0 for assembled)
    filter_type: str - Part type filter (optional)
    generate: bool - Trigger geometry generation

OUTPUTS:
    all_parts: list - All part geometries as Breps
    part_names: list - Names for each part
    part_colors: list - Color tuples for each part
    cabinet_box_parts: list - Cabinet box component geometries
    face_frame_parts: list - Face frame component geometries
    drawer_parts: list - Drawer component geometries
    parts_data: str - Parts data as JSON with positions
    total_parts: int - Number of parts generated

USAGE IN GRASSHOPPER:
1. Connect cut_list_json from TCS Cut List
2. Set position for cabinet placement
3. Toggle generate to create geometry
4. Connect to Bake or custom preview

PART COLORS (RGB):
    cabinet_box: (139, 90, 43) - Brown
    face_frame: (210, 180, 140) - Tan
    stretcher: (160, 82, 45) - Sienna
    drawer_box: (255, 228, 181) - Light wood
    drawer_face: (245, 222, 179) - Wheat
    door: (222, 184, 135) - Burlywood
"""

import json

# Grasshopper component metadata
ghenv.Component.Name = "TCS Parts Generator"
ghenv.Component.NickName = "TCS Parts"
ghenv.Component.Message = "v1.0"
ghenv.Component.Category = "TCS"
ghenv.Component.SubCategory = "Geometry"

# Try to import Rhino geometry
try:
    import Rhino.Geometry as rg
    HAS_RHINO = True
except ImportError:
    HAS_RHINO = False

# TCS Construction Constants
TOE_KICK_HEIGHT = 4.5
MATERIAL_THICKNESS = 0.75
BACK_PANEL_THICKNESS = 0.25
STRETCHER_DEPTH = 3.0
FACE_FRAME_STILE = 1.5
FACE_FRAME_RAIL = 1.5

# Part Colors (RGB tuples)
PART_COLORS = {
    'cabinet_box': (139, 90, 43),
    'left_side': (139, 90, 43),
    'right_side': (139, 90, 43),
    'bottom': (139, 90, 43),
    'back_panel': (100, 80, 60),
    'face_frame': (210, 180, 140),
    'stile': (210, 180, 140),
    'rail': (210, 180, 140),
    'stretcher': (160, 82, 45),
    'toe_kick': (105, 105, 105),
    'finished_end': (205, 133, 63),
    'false_front': (222, 184, 135),
    'drawer_face': (245, 222, 179),
    'drawer_box': (255, 228, 181),
    'drawer_box_bottom': (255, 239, 213),
    'door': (222, 184, 135),
    'shelf': (180, 150, 100),
}


def parse_parts_from_json(json_str):
    """Parse parts from cut list or calculation JSON."""
    if not json_str:
        return []

    try:
        data = json.loads(json_str) if isinstance(json_str, str) else json_str
    except:
        return []

    parts = []

    # Handle different JSON structures
    if isinstance(data, dict):
        # Check for 'parts' key (from CabinetMathAuditService)
        if 'parts' in data:
            parts_dict = data['parts']
            for key, part in parts_dict.items():
                parts.append(normalize_part(part))

        # Check for 'cut_list' key
        elif 'cut_list' in data:
            for item in data['cut_list']:
                parts.append(normalize_part(item))

        # Check for category keys (box, face_frame, etc.)
        else:
            for category, items in data.items():
                if isinstance(items, list):
                    for item in items:
                        part = normalize_part(item)
                        if not part.get('type'):
                            part['type'] = category
                        parts.append(part)
                elif isinstance(items, dict) and 'parts' in items:
                    for item in items['parts']:
                        part = normalize_part(item)
                        part['type'] = category
                        parts.append(part)

    elif isinstance(data, list):
        for item in data:
            parts.append(normalize_part(item))

    return parts


def normalize_part(item):
    """Normalize part data to standard format."""
    if not isinstance(item, dict):
        return {}

    # Handle both dimension formats
    position = item.get('position', {})
    dimensions = item.get('dimensions', {})

    return {
        'name': item.get('part_name', item.get('name', 'Unknown')),
        'type': item.get('part_type', item.get('type', '')),
        # Dimensions
        'width': float(dimensions.get('w', item.get('width', item.get('w', 0)))),
        'height': float(dimensions.get('h', item.get('height', item.get('h', 0)))),
        'depth': float(dimensions.get('d', item.get('depth', item.get('d', 0)))),
        # Position
        'x': float(position.get('x', item.get('x', 0))),
        'y': float(position.get('y', item.get('y', 0))),
        'z': float(position.get('z', item.get('z', 0))),
        # Metadata
        'quantity': int(item.get('quantity', item.get('qty', 1))),
        'material': item.get('material', ''),
    }


def create_part_brep(part, base_x, base_y, base_z, toe_kick_h, explode=0):
    """
    Create Brep geometry for a part.
    Uses TCS coordinate system (Y-up internally, transformed to Rhino Z-up).
    """
    if not HAS_RHINO:
        return None

    w = part.get('width', 0)
    h = part.get('height', 0)
    d = part.get('depth', 0)

    if w <= 0 or h <= 0 or d <= 0:
        return None

    # Get part position
    px = part.get('x', 0)
    py = part.get('y', 0)  # This is height in TCS system
    pz = part.get('z', 0)  # This is depth in TCS system

    # Transform from TCS (Y-up) to Rhino (Z-up)
    # Rhino X = TCS X
    # Rhino Y = TCS Z (depth)
    # Rhino Z = TCS Y + toe_kick_height
    rhino_x = base_x + px
    rhino_y = base_y + pz
    rhino_z = base_z + py + toe_kick_h

    # Apply explode offset based on part type
    if explode and explode > 0:
        part_type = part.get('type', '')
        if 'left' in part_type.lower():
            rhino_x -= explode
        elif 'right' in part_type.lower():
            rhino_x += explode
        elif 'back' in part_type.lower():
            rhino_y += explode
        elif 'bottom' in part_type.lower():
            rhino_z -= explode
        elif 'top' in part_type.lower() or 'stretcher' in part_type.lower():
            rhino_z += explode

    try:
        # Create box using 8 corner points (more reliable)
        corners = [
            rg.Point3d(rhino_x, rhino_y, rhino_z),
            rg.Point3d(rhino_x + w, rhino_y, rhino_z),
            rg.Point3d(rhino_x + w, rhino_y + d, rhino_z),
            rg.Point3d(rhino_x, rhino_y + d, rhino_z),
            rg.Point3d(rhino_x, rhino_y, rhino_z + h),
            rg.Point3d(rhino_x + w, rhino_y, rhino_z + h),
            rg.Point3d(rhino_x + w, rhino_y + d, rhino_z + h),
            rg.Point3d(rhino_x, rhino_y + d, rhino_z + h),
        ]

        box = rg.Box(
            rg.Plane.WorldXY,
            rg.Interval(rhino_x, rhino_x + w),
            rg.Interval(rhino_y, rhino_y + d),
            rg.Interval(rhino_z, rhino_z + h)
        )

        return box.ToBrep()

    except Exception as e:
        print("Part creation failed: {}".format(str(e)))
        return None


def get_part_color(part_type):
    """Get color tuple for part type."""
    # Check exact match
    if part_type in PART_COLORS:
        return PART_COLORS[part_type]

    # Check partial matches
    type_lower = part_type.lower()
    for key, color in PART_COLORS.items():
        if key in type_lower:
            return color

    # Default color
    return (128, 128, 128)


def filter_parts_by_type(parts, filter_type):
    """Filter parts by type string."""
    if not filter_type:
        return parts
    return [p for p in parts if filter_type.lower() in p.get('type', '').lower()]


# ============================================================
# MAIN COMPONENT LOGIC
# ============================================================

# Initialize outputs
all_parts = []
part_names = []
part_colors = []
cabinet_box_parts = []
face_frame_parts = []
drawer_parts = []
parts_data = "[]"
total_parts = 0

# Validate inputs
if not generate:
    print("Set generate=True to create geometry")
elif not cut_list_json and not calculation_json:
    print("Cut list or calculation JSON required")
else:
    # Parse parts
    parts = parse_parts_from_json(cut_list_json) if cut_list_json else []
    if not parts and calculation_json:
        parts = parse_parts_from_json(calculation_json)

    if not parts:
        print("No parts found in JSON data")
    else:
        # Apply filter if specified
        if filter_type:
            parts = filter_parts_by_type(parts, filter_type)

        # Get position
        pos_x = position_x if position_x else 0.0
        pos_y = position_y if position_y else 0.0
        pos_z = position_z if position_z else 0.0

        # Determine toe kick height
        cab_type = cabinet_type if cabinet_type else 'base'
        toe_kick_h = TOE_KICK_HEIGHT if cab_type.lower() not in ['wall', 'upper'] else 0

        # Get explode distance
        explode = explode_distance if explode_distance else 0

        # Generate geometry for each part
        generated_parts = []

        for part in parts:
            brep = create_part_brep(part, pos_x, pos_y, pos_z, toe_kick_h, explode)

            if brep:
                all_parts.append(brep)
                part_names.append(part.get('name', 'Unknown'))
                part_colors.append(get_part_color(part.get('type', '')))

                # Categorize by type
                ptype = part.get('type', '').lower()
                if 'cabinet_box' in ptype or 'side' in ptype or 'bottom' in ptype or 'back' in ptype:
                    cabinet_box_parts.append(brep)
                elif 'face_frame' in ptype or 'stile' in ptype or 'rail' in ptype:
                    face_frame_parts.append(brep)
                elif 'drawer' in ptype:
                    drawer_parts.append(brep)

                # Store part data with position
                generated_parts.append({
                    'name': part.get('name'),
                    'type': part.get('type'),
                    'width': part.get('width'),
                    'height': part.get('height'),
                    'depth': part.get('depth'),
                    'position': {
                        'x': pos_x + part.get('x', 0),
                        'y': pos_y + part.get('z', 0),
                        'z': pos_z + part.get('y', 0) + toe_kick_h,
                    }
                })

        total_parts = len(all_parts)
        parts_data = json.dumps(generated_parts, indent=2)

        print("Generated {} parts".format(total_parts))
        print("  Cabinet Box: {}".format(len(cabinet_box_parts)))
        print("  Face Frame: {}".format(len(face_frame_parts)))
        print("  Drawers: {}".format(len(drawer_parts)))


# ============================================================
# HELPER FUNCTIONS
# ============================================================

def get_parts_summary():
    """Get summary of generated parts."""
    if total_parts == 0:
        return "No parts generated"

    type_counts = {}
    for name in part_names:
        ptype = name.split('_')[0] if '_' in name else name
        type_counts[ptype] = type_counts.get(ptype, 0) + 1

    lines = ["Parts Summary:", "-" * 30]
    for ptype, count in sorted(type_counts.items()):
        lines.append("  {}: {}".format(ptype, count))
    lines.append("-" * 30)
    lines.append("Total: {}".format(total_parts))

    return "\n".join(lines)


# Print component info
print("")
print("TCS Parts Generator")
print("=" * 40)
print(get_parts_summary())
