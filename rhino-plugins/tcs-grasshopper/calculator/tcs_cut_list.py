"""
TCS Cut List Display - Grasshopper Component
GHPython component for displaying and parsing cabinet cut list data.

INPUTS:
    cut_list_json: str - Cut list JSON from TCS Cabinet Calculator
    calculation_json: str - Full calculation JSON from TCS Cabinet Calculator
    filter_type: str - Part type filter (optional: "cabinet_box", "face_frame", etc.)

OUTPUTS:
    part_names: list - Part names for table display
    part_widths: list - Part widths in inches
    part_heights: list - Part heights in inches
    part_quantities: list - Part quantities
    part_types: list - Part type categories
    part_materials: list - Material assignments
    table_text: str - Formatted table for display
    total_parts: int - Total number of parts
    board_feet: float - Total board feet estimate
    cut_list_data: str - Parsed cut list as DataTree-compatible output
    error: str - Error message if any

USAGE IN GRASSHOPPER:
1. Connect cut_list_json from TCS Cabinet Calculator
2. Optionally set filter_type to show specific part categories
3. Use table_text with Text Panel or Human UI Table
4. Use individual outputs for custom displays

PART TYPES:
    cabinet_box: Sides, bottom, back
    face_frame: Stiles and rails
    stretcher: Front/back stretchers
    drawer_box: Drawer sides, front, back
    drawer_face: Drawer fronts
    door: Door panels
    shelf: Adjustable shelves
"""

import json

# Grasshopper component metadata
ghenv.Component.Name = "TCS Cut List Display"
ghenv.Component.NickName = "TCS CutList"
ghenv.Component.Message = "v1.0"
ghenv.Component.Category = "TCS"
ghenv.Component.SubCategory = "Calculator"

# Part type display names
PART_TYPE_NAMES = {
    'cabinet_box': 'Cabinet Box',
    'face_frame': 'Face Frame',
    'stretcher': 'Stretcher',
    'drawer_box': 'Drawer Box',
    'drawer_face': 'Drawer Face',
    'drawer_box_bottom': 'Drawer Bottom',
    'door': 'Door',
    'shelf': 'Shelf',
    'back_panel': 'Back Panel',
    'toe_kick': 'Toe Kick',
    'finished_end': 'Finished End',
    'false_front': 'False Front',
}

# Default material by part type
DEFAULT_MATERIALS = {
    'cabinet_box': '3/4" Plywood',
    'face_frame': '3/4" Solid Wood',
    'stretcher': '3/4" Plywood',
    'drawer_box': '1/2" Baltic Birch',
    'drawer_face': '3/4" Solid Wood',
    'drawer_box_bottom': '1/4" Plywood',
    'door': '3/4" MDF',
    'shelf': '3/4" Plywood',
    'back_panel': '1/4" Plywood',
    'toe_kick': '3/4" Plywood',
    'finished_end': '3/4" Plywood',
    'false_front': '3/4" Solid Wood',
}


def parse_cut_list(json_str):
    """
    Parse cut list JSON into list of parts.
    Returns list of dicts with standardized keys.
    """
    if not json_str:
        return []

    try:
        data = json.loads(json_str) if isinstance(json_str, str) else json_str
    except:
        return []

    parts = []

    # Handle different response structures
    if isinstance(data, dict):
        # Check for nested cut_list key
        if 'cut_list' in data:
            cut_list = data['cut_list']
        elif 'box' in data or 'face_frame' in data:
            # Flat structure with part categories
            cut_list = data
        else:
            cut_list = data
    elif isinstance(data, list):
        cut_list = data
    else:
        return []

    # Parse parts from different structures
    if isinstance(cut_list, list):
        for item in cut_list:
            parts.append(normalize_part(item))
    elif isinstance(cut_list, dict):
        # Parse by category
        for category, items in cut_list.items():
            if isinstance(items, list):
                for item in items:
                    part = normalize_part(item)
                    if not part.get('type'):
                        part['type'] = category
                    parts.append(part)
            elif isinstance(items, dict):
                # Single item or nested structure
                if 'parts' in items:
                    for item in items['parts']:
                        part = normalize_part(item)
                        part['type'] = category
                        parts.append(part)
                else:
                    # Treat as single part
                    part = normalize_part(items)
                    part['type'] = category
                    parts.append(part)

    return parts


def normalize_part(item):
    """Normalize part data to standard format."""
    if not isinstance(item, dict):
        return {'name': str(item), 'width': 0, 'height': 0, 'quantity': 1}

    return {
        'name': item.get('name', item.get('part_name', 'Unknown')),
        'width': float(item.get('width', item.get('w', 0))),
        'height': float(item.get('height', item.get('h', 0))),
        'depth': float(item.get('depth', item.get('d', 0))),
        'quantity': int(item.get('quantity', item.get('qty', 1))),
        'type': item.get('type', item.get('part_type', '')),
        'material': item.get('material', ''),
    }


def filter_parts(parts, filter_type):
    """Filter parts by type."""
    if not filter_type:
        return parts
    return [p for p in parts if p.get('type', '').lower() == filter_type.lower()]


def calculate_board_feet(parts):
    """
    Calculate estimated board feet for cut list.
    BF = (Length x Width x Thickness) / 144
    """
    total = 0.0
    for part in parts:
        w = part.get('width', 0)
        h = part.get('height', 0)
        qty = part.get('quantity', 1)
        thickness = 0.75  # Default 3/4"

        # Estimate BF (using width as length, height as width)
        bf = (w * h * thickness * qty) / 144.0
        total += bf

    return total


def format_table(parts):
    """Format parts as ASCII table."""
    if not parts:
        return "No parts in cut list"

    lines = []

    # Header
    header = "{:<20} {:>8} {:>8} {:>5} {:>15}".format(
        "Part", "Width", "Height", "Qty", "Type"
    )
    lines.append(header)
    lines.append("-" * len(header))

    # Parts
    for part in parts:
        name = part.get('name', 'Unknown')[:20]
        width = part.get('width', 0)
        height = part.get('height', 0)
        qty = part.get('quantity', 1)
        part_type = PART_TYPE_NAMES.get(part.get('type', ''), part.get('type', ''))

        line = '{:<20} {:>7.2f}" {:>7.2f}" {:>5} {:>15}'.format(
            name, width, height, qty, part_type[:15]
        )
        lines.append(line)

    # Footer
    lines.append("-" * len(header))
    lines.append("Total: {} parts".format(len(parts)))

    return "\n".join(lines)


def get_default_material(part_type):
    """Get default material for part type."""
    return DEFAULT_MATERIALS.get(part_type, '3/4" Plywood')


# ============================================================
# MAIN COMPONENT LOGIC
# ============================================================

# Initialize outputs
part_names = []
part_widths = []
part_heights = []
part_quantities = []
part_types = []
part_materials = []
table_text = ""
total_parts = 0
board_feet = 0.0
cut_list_data = ""
error = ""

# Parse cut list
parts = []

if cut_list_json:
    parts = parse_cut_list(cut_list_json)
elif calculation_json:
    # Try to extract cut list from calculation result
    parts = parse_cut_list(calculation_json)

if not parts:
    table_text = "No cut list data. Run calculation first."
else:
    # Apply filter if specified
    if filter_type:
        parts = filter_parts(parts, filter_type)

    if not parts:
        table_text = "No parts match filter: {}".format(filter_type)
    else:
        # Build output lists
        for part in parts:
            part_names.append(part.get('name', 'Unknown'))
            part_widths.append(part.get('width', 0))
            part_heights.append(part.get('height', 0))
            part_quantities.append(part.get('quantity', 1))
            part_types.append(part.get('type', ''))
            part_materials.append(
                part.get('material') or get_default_material(part.get('type', ''))
            )

        # Calculate totals
        total_parts = len(parts)
        board_feet = calculate_board_feet(parts)

        # Format table
        table_text = format_table(parts)

        # Store as JSON for further processing
        cut_list_data = json.dumps(parts, indent=2)


# ============================================================
# HELPER FUNCTIONS
# ============================================================

def get_parts_by_type():
    """Group parts by type for categorized display."""
    grouped = {}
    for part in parts:
        ptype = part.get('type', 'other')
        if ptype not in grouped:
            grouped[ptype] = []
        grouped[ptype].append(part)
    return grouped


def get_parts_for_human_ui_table():
    """
    Format parts for Human UI Table component.
    Returns list of row lists.
    """
    rows = []
    for part in parts:
        row = [
            part.get('name', 'Unknown'),
            '{:.2f}"'.format(part.get('width', 0)),
            '{:.2f}"'.format(part.get('height', 0)),
            str(part.get('quantity', 1)),
        ]
        rows.append(row)
    return rows


def export_csv():
    """Export cut list as CSV string."""
    lines = ["Part,Width,Height,Quantity,Type,Material"]
    for part in parts:
        line = '{},{:.2f},{:.2f},{},{},{}'.format(
            part.get('name', ''),
            part.get('width', 0),
            part.get('height', 0),
            part.get('quantity', 1),
            part.get('type', ''),
            part.get('material', get_default_material(part.get('type', ''))),
        )
        lines.append(line)
    return "\n".join(lines)


# Print component info
print("")
print("TCS Cut List Display")
print("=" * 40)
if filter_type:
    print("Filter: {}".format(filter_type))
print("Total Parts: {}".format(total_parts))
print("Est. Board Feet: {:.2f}".format(board_feet))
print("")
print(table_text)
if error:
    print("Error: {}".format(error))
