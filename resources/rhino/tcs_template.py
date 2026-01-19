"""
TCS Cabinet Template for Rhino
==============================

This is a TEMPLATE file for creating new TCS cabinet drawings.
Copy this file and modify the CABINET_DATA section for your cabinet.

Usage:
1. Copy this file to your project folder
2. Edit CABINET_DATA with your cabinet specs
3. Run in Rhino: RunPythonScript tcs_template.py

Requirements:
- Rhino 7 or 8
- TCS layer hierarchy (run tcs_layer_setup.py first)

@author TCS Woodwork
@since January 2026
"""

import rhinoscriptsyntax as rs
import json

# ============================================================
# CABINET DATA - EDIT THIS SECTION
# ============================================================

CABINET_DATA = {
    # Cabinet Identity - TWO OPTIONS:
    #
    # Option 1: ERP format (from project_number + cabinet_number)
    # "project_number": "TCS-001-9AustinFarmRoad",  # Full project number from ERP
    # "cabinet_number": "BTH1-B1-C1",               # Cabinet number from ERP
    #
    # Option 2: Simple format (for manual drawing)
    "project_code": "SANK",           # 4-char project code (e.g., SANK, FSHIP, AUST)
    "cabinet_type": "B36",            # Cabinet type code (B36, W3030, T2484, VAN36)
    "sequence": 1,                    # Cabinet sequence number

    # Overall Dimensions (inches)
    "width": 36,
    "height": 32.75,
    "depth": 21,
    "toe_kick_height": 4,

    # Material Overrides (optional - defaults based on part type)
    # Uncomment to override default materials:
    # "materials": {
    #     "cabinet_box": "3-4_RiftWO",  # Override box material
    #     "toe_kick": "3-4_PreFin",     # Override toe kick material
    # },

    # Construction Options
    "has_face_frame": True,
    "face_frame_stile_width": 1.5,
    "face_frame_rail_width": 1.5,

    # Components
    "drawers": [
        {"height": 5, "position": "top"},
        {"height": 7, "position": "middle"},
    ],
    "doors": [
        {"width": 17.25, "height": 20, "position": "bottom_left"},
        {"width": 17.25, "height": 20, "position": "bottom_right"},
    ],
    "shelves": [
        {"position_from_bottom": 10, "adjustable": True},
    ],
}


# ============================================================
# TCS MATERIAL CONFIGURATION
# ============================================================

TCS_MATERIALS = {
    '3-4_PreFin': {'thickness': 0.75, 'color': (139, 90, 43), 'desc': '3/4" Prefinished Plywood'},
    '3-4_Medex': {'thickness': 0.75, 'color': (65, 105, 225), 'desc': '3/4" Medex MDF'},
    '3-4_RiftWO': {'thickness': 0.75, 'color': (210, 180, 140), 'desc': '3/4" Rift White Oak'},
    '1-2_Baltic': {'thickness': 0.5, 'color': (255, 228, 181), 'desc': '1/2" Baltic Birch'},
    '1-4_Plywood': {'thickness': 0.25, 'color': (240, 230, 200), 'desc': '1/4" Plywood'},
    '5-4_Hardwood': {'thickness': 1.0, 'color': (205, 133, 63), 'desc': '5/4" Hardwood'},
}

PART_TYPE_TO_MATERIAL = {
    'cabinet_box': '3-4_PreFin',
    'toe_kick': '3-4_Medex',
    'face_frame': '5-4_Hardwood',
    'drawer_face': '3-4_RiftWO',
    'finished_end': '3-4_RiftWO',
    'stretcher': '3-4_PreFin',
    'drawer_box': '1-2_Baltic',
    'drawer_box_bottom': '1-4_Plywood',
    'shelf': '3-4_PreFin',
}

EDGEBAND_BY_TYPE = {
    'cabinet_box': 'F',
    'finished_end': 'F,T',
    'stretcher': 'F',
    'shelf': 'F',
}

GRAIN_BY_TYPE = {
    'cabinet_box': 'vertical',
    'face_frame': 'vertical',
    'drawer_face': 'horizontal',
    'finished_end': 'vertical',
    'stretcher': 'horizontal',
    'toe_kick': 'none',
    'drawer_box': 'horizontal',
    'drawer_box_bottom': 'none',
    'shelf': 'horizontal',
}


# ============================================================
# TCS LAYER FUNCTIONS
# ============================================================

def ensure_tcs_layers():
    """Create TCS layer hierarchy if not exists."""
    parent = "TCS_Materials"

    if not rs.IsLayer(parent):
        rs.AddLayer(parent, (128, 128, 128))
        print("Created: " + parent)

    for name, config in TCS_MATERIALS.items():
        full_name = parent + "::" + name
        if not rs.IsLayer(full_name):
            rs.AddLayer(full_name, config['color'])
            print("Created: " + full_name)


def get_material_for_part(part_type, overrides=None):
    """Get material layer for part type."""
    if overrides and part_type in overrides:
        return overrides[part_type]
    return PART_TYPE_TO_MATERIAL.get(part_type, '3-4_PreFin')


def assign_to_layer(obj, material):
    """Assign object to TCS material layer."""
    layer = "TCS_Materials::" + material
    if rs.IsLayer(layer):
        rs.ObjectLayer(obj, layer)


# ============================================================
# TCS METADATA FUNCTIONS
# ============================================================

def get_short_project_code(project_number):
    """
    Extract 4-char project code from full project number.

    Examples:
        "TCS-001-9AustinFarmRoad" -> "AUST"
        "TCS-0554-15WSankaty" -> "SANK"
    """
    import re
    parts = project_number.split('-')

    if len(parts) >= 3:
        name_part = parts[2]
        # Remove leading digits and optional direction letter (N/S/E/W)
        # Pattern handles "9Austin" or "15WSankaty"
        match = re.match(r'^\d+([NSEW])(?=[A-Z])', name_part)
        if match:
            name_part = re.sub(r'^\d+[NSEW]', '', name_part)
        else:
            name_part = re.sub(r'^\d+', '', name_part)
        return name_part[:4].upper()

    # Fallback: first 4 letters
    letters = re.sub(r'[^A-Za-z]', '', project_number)
    return letters[:4].upper()


def build_cabinet_id_from_erp(project_number, cabinet_number):
    """
    Build cabinet ID from ERP data.

    Example: ("TCS-001-9AustinFarmRoad", "BTH1-B1-C1") -> "AUST-BTH1-B1-C1"
    """
    short_code = get_short_project_code(project_number)
    return short_code + "-" + cabinet_number


def build_cabinet_id(project_code, cabinet_type, sequence):
    """
    Build cabinet ID from simple format.

    Example: ("SANK", "B36", 1) -> "SANK-B36-001"
    """
    return "%s-%s-%03d" % (project_code.upper(), cabinet_type.upper(), sequence)


def build_part_id(cabinet_id, part_name):
    """Build part ID: SANK-B36-001-LeftSide"""
    safe_name = part_name.replace(' ', '_').replace('-', '_')
    return cabinet_id + "-" + safe_name


def set_tcs_metadata(obj, cabinet_id, part_name, part_type, material, cut_width=None, cut_length=None, thickness=None):
    """Set all TCS metadata on an object."""
    project_code = cabinet_id.split('-')[0]

    rs.SetUserText(obj, "TCS_PART_ID", build_part_id(cabinet_id, part_name))
    rs.SetUserText(obj, "TCS_CABINET_ID", cabinet_id)
    rs.SetUserText(obj, "TCS_PROJECT_CODE", project_code)
    rs.SetUserText(obj, "TCS_PART_TYPE", part_type)
    rs.SetUserText(obj, "TCS_PART_NAME", part_name)
    rs.SetUserText(obj, "TCS_MATERIAL", material)

    if thickness:
        rs.SetUserText(obj, "TCS_THICKNESS", str(thickness))
    if cut_width:
        rs.SetUserText(obj, "TCS_CUT_WIDTH", str(cut_width))
    if cut_length:
        rs.SetUserText(obj, "TCS_CUT_LENGTH", str(cut_length))

    grain = GRAIN_BY_TYPE.get(part_type, 'none')
    rs.SetUserText(obj, "TCS_GRAIN", grain)

    edgeband = EDGEBAND_BY_TYPE.get(part_type)
    if edgeband:
        rs.SetUserText(obj, "TCS_EDGEBAND", edgeband)


# ============================================================
# GEOMETRY CREATION
# ============================================================

def create_box(x, y, z, w, h, d, name, part_type, cabinet_id, material_overrides=None):
    """
    Create a box with TCS metadata.

    Rhino coordinate system:
    - X: Left to Right
    - Y: Front to Back
    - Z: Bottom to Top
    """
    if w <= 0 or h <= 0 or d <= 0:
        return None

    # 8 corners
    p1 = (x, y, z)
    p2 = (x + w, y, z)
    p3 = (x + w, y + d, z)
    p4 = (x, y + d, z)
    p5 = (x, y, z + h)
    p6 = (x + w, y, z + h)
    p7 = (x + w, y + d, z + h)
    p8 = (x, y + d, z + h)

    box = rs.AddBox([p1, p2, p3, p4, p5, p6, p7, p8])

    if box:
        rs.ObjectName(box, name)

        # Material and layer
        material = get_material_for_part(part_type, material_overrides)
        mat_config = TCS_MATERIALS.get(material, {})
        rs.ObjectColor(box, mat_config.get('color', (128, 128, 128)))
        assign_to_layer(box, material)

        # TCS metadata
        thickness = mat_config.get('thickness', 0.75)
        # Cut dimensions: width=height of part, length=depth of part for vertical parts
        if part_type == 'cabinet_box':
            cut_w, cut_l = h, d  # Vertical parts
        else:
            cut_w, cut_l = w, d

        set_tcs_metadata(box, cabinet_id, name, part_type, material, cut_w, cut_l, thickness)

    return box


# ============================================================
# CABINET BUILDER
# ============================================================

def build_cabinet(data):
    """Build complete cabinet from data specification."""

    # Build cabinet ID - support both ERP and simple formats
    if 'project_number' in data and 'cabinet_number' in data:
        # ERP format
        cabinet_id = build_cabinet_id_from_erp(
            data['project_number'],
            data['cabinet_number']
        )
    else:
        # Simple format
        cabinet_id = build_cabinet_id(
            data['project_code'],
            data['cabinet_type'],
            data['sequence']
        )

    print("=" * 60)
    print("TCS CABINET BUILDER")
    print("Cabinet ID: " + cabinet_id)
    print("=" * 60)

    # Dimensions
    width = data['width']
    height = data['height']
    depth = data['depth']
    toe_kick_h = data['toe_kick_height']
    box_height = height - toe_kick_h

    # Material thickness
    ply_3_4 = 0.75
    ply_1_4 = 0.25

    # Material overrides
    mat_overrides = data.get('materials', {})

    parts_created = []

    # ========== TOE KICK ==========
    # Position: front of cabinet, below box
    tk = create_box(
        x=ply_3_4,              # Inset from sides
        y=0,                    # Front
        z=0,                    # Floor
        w=width - (2 * ply_3_4),
        h=toe_kick_h,
        d=ply_3_4,              # Thin panel
        name="Toe Kick",
        part_type="toe_kick",
        cabinet_id=cabinet_id,
        material_overrides=mat_overrides
    )
    if tk:
        parts_created.append("Toe Kick")

    # ========== LEFT SIDE ==========
    left = create_box(
        x=0,
        y=0,
        z=toe_kick_h,
        w=ply_3_4,
        h=box_height,
        d=depth,
        name="Left Side",
        part_type="cabinet_box",
        cabinet_id=cabinet_id,
        material_overrides=mat_overrides
    )
    if left:
        parts_created.append("Left Side")

    # ========== RIGHT SIDE ==========
    right = create_box(
        x=width - ply_3_4,
        y=0,
        z=toe_kick_h,
        w=ply_3_4,
        h=box_height,
        d=depth,
        name="Right Side",
        part_type="cabinet_box",
        cabinet_id=cabinet_id,
        material_overrides=mat_overrides
    )
    if right:
        parts_created.append("Right Side")

    # ========== BOTTOM ==========
    bottom = create_box(
        x=ply_3_4,
        y=0,
        z=toe_kick_h,
        w=width - (2 * ply_3_4),
        h=ply_3_4,
        d=depth - ply_1_4,      # Minus back panel
        name="Bottom Panel",
        part_type="cabinet_box",
        cabinet_id=cabinet_id,
        material_overrides=mat_overrides
    )
    if bottom:
        parts_created.append("Bottom Panel")

    # ========== BACK ==========
    back = create_box(
        x=ply_3_4,
        y=depth - ply_1_4,
        z=toe_kick_h,
        w=width - (2 * ply_3_4),
        h=box_height,
        d=ply_1_4,
        name="Back Panel",
        part_type="cabinet_box",
        cabinet_id=cabinet_id,
        material_overrides=mat_overrides
    )
    if back:
        # Override material for back (always 1/4")
        rs.SetUserText(back, "TCS_MATERIAL", "1-4_Plywood")
        rs.SetUserText(back, "TCS_THICKNESS", "0.25")
        assign_to_layer(back, "1-4_Plywood")
        parts_created.append("Back Panel")

    # ========== TOP STRETCHERS ==========
    stretcher_height = 3  # Standard stretcher height

    # Front stretcher
    front_str = create_box(
        x=ply_3_4,
        y=0,
        z=toe_kick_h + box_height - stretcher_height,
        w=width - (2 * ply_3_4),
        h=stretcher_height,
        d=ply_3_4,
        name="Front Stretcher",
        part_type="stretcher",
        cabinet_id=cabinet_id,
        material_overrides=mat_overrides
    )
    if front_str:
        parts_created.append("Front Stretcher")

    # Back stretcher
    back_str = create_box(
        x=ply_3_4,
        y=depth - ply_3_4 - ply_1_4,
        z=toe_kick_h + box_height - stretcher_height,
        w=width - (2 * ply_3_4),
        h=stretcher_height,
        d=ply_3_4,
        name="Back Stretcher",
        part_type="stretcher",
        cabinet_id=cabinet_id,
        material_overrides=mat_overrides
    )
    if back_str:
        parts_created.append("Back Stretcher")

    # ========== SUMMARY ==========
    print("")
    print("Created %d parts:" % len(parts_created))
    for part in parts_created:
        print("  - " + part)
    print("")
    print("Cabinet: %dW x %.2fH x %dD" % (width, height, depth))
    print("Box Height: %.2f" % box_height)
    print("=" * 60)

    rs.ZoomExtents()

    return parts_created


# ============================================================
# VERIFICATION
# ============================================================

def verify_tcs_metadata():
    """Verify all objects have TCS metadata."""
    all_objs = rs.AllObjects()

    if not all_objs:
        print("No objects in document")
        return

    print("")
    print("=" * 70)
    print("TCS METADATA VERIFICATION")
    print("=" * 70)
    print("%-25s %-15s %-12s %-15s" % ("Name", "Cabinet ID", "Material", "Type"))
    print("-" * 70)

    missing = []

    for obj in all_objs:
        name = rs.ObjectName(obj) or "Unnamed"

        cabinet_id = rs.GetUserText(obj, "TCS_CABINET_ID") or ""
        material = rs.GetUserText(obj, "TCS_MATERIAL") or ""
        part_type = rs.GetUserText(obj, "TCS_PART_TYPE") or ""

        if not cabinet_id or not material:
            missing.append(name)

        print("%-25s %-15s %-12s %-15s" % (name, cabinet_id, material, part_type))

    print("-" * 70)

    if missing:
        print("\nWARNING: %d objects missing TCS metadata:" % len(missing))
        for m in missing:
            print("  - " + m)
    else:
        print("\nAll objects have TCS metadata!")

    print("=" * 70)


def export_cut_list():
    """Export cut list from TCS metadata."""
    all_objs = rs.AllObjects()

    if not all_objs:
        print("No objects in document")
        return

    print("")
    print("=" * 80)
    print("TCS CUT LIST")
    print("=" * 80)
    print("%-35s %-12s %-8s %-8s %-6s" % ("Part ID", "Material", "Width", "Length", "Thick"))
    print("-" * 80)

    for obj in all_objs:
        part_id = rs.GetUserText(obj, "TCS_PART_ID") or "Unknown"
        material = rs.GetUserText(obj, "TCS_MATERIAL") or ""
        cut_w = rs.GetUserText(obj, "TCS_CUT_WIDTH") or ""
        cut_l = rs.GetUserText(obj, "TCS_CUT_LENGTH") or ""
        thickness = rs.GetUserText(obj, "TCS_THICKNESS") or ""

        print("%-35s %-12s %-8s %-8s %-6s" % (part_id, material, cut_w, cut_l, thickness))

    print("=" * 80)


# ============================================================
# MAIN
# ============================================================

def main():
    """Main entry point."""
    # Ensure layers exist
    ensure_tcs_layers()

    # Build cabinet
    build_cabinet(CABINET_DATA)

    # Verify metadata
    verify_tcs_metadata()


if __name__ == "__main__":
    main()


# ============================================================
# UTILITY FUNCTIONS (call manually as needed)
# ============================================================

def clear_all():
    """Delete all objects."""
    objs = rs.AllObjects()
    if objs:
        rs.DeleteObjects(objs)
        print("Deleted %d objects" % len(objs))

def rebuild():
    """Clear and rebuild cabinet."""
    clear_all()
    main()
