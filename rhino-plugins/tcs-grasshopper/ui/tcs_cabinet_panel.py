"""
TCS Cabinet Panel - Grasshopper Component
GHPython component for creating Human UI cabinet control panel.

This component generates the data structures needed for Human UI
to create an interactive cabinet design panel.

INPUTS:
    cabinet_data: str - Cabinet JSON from TCS Cabinet List
    calculation_json: str - Calculation JSON from TCS Cabinet Calculator
    cut_list_json: str - Cut list JSON from TCS Cut List
    pricing_json: str - Pricing JSON from TCS Pricing
    override_json: str - Override JSON from TCS Override Manager
    show_advanced: bool - Show advanced options

OUTPUTS:
    panel_title: str - Panel title text
    dimension_sliders: list - Slider configurations for dimensions
    dimension_values: list - Current dimension values
    dimension_overridden: list - Which dimensions are overridden
    cut_list_table: list - Cut list formatted for table
    pricing_text: str - Formatted pricing summary
    status_text: str - Current status/messages
    button_labels: list - Action button labels
    panel_data: str - Full panel configuration as JSON

USAGE IN GRASSHOPPER:
1. Connect data from other TCS components
2. Wire outputs to Human UI components:
   - panel_title → Text Block
   - dimension_sliders → Slider configurations
   - cut_list_table → Create Table
   - pricing_text → Text Block
   - button_labels → Button Bank

HUMAN UI COMPONENTS NEEDED:
- Create Dropdown (for project/room/cabinet selection)
- Create Slider (for dimension overrides)
- Create Toggle (for override enables)
- Create Button (for save/reset actions)
- Create Table (for cut list display)
- Text Block (for status and pricing)
"""

import json

# Grasshopper component metadata
ghenv.Component.Name = "TCS Cabinet Panel"
ghenv.Component.NickName = "TCS Panel"
ghenv.Component.Message = "v1.0"
ghenv.Component.Category = "TCS"
ghenv.Component.SubCategory = "UI"

# Default dimension ranges
DIMENSION_RANGES = {
    'width': {'min': 6, 'max': 60, 'step': 0.5, 'default': 36},
    'height': {'min': 12, 'max': 96, 'step': 0.5, 'default': 34.5},
    'depth': {'min': 12, 'max': 36, 'step': 0.5, 'default': 24},
    'price_per_lf': {'min': 50, 'max': 300, 'step': 5, 'default': 125},
}


def parse_json(json_str):
    """Safely parse JSON string."""
    if not json_str:
        return {}
    try:
        return json.loads(json_str) if isinstance(json_str, str) else json_str
    except:
        return {}


def extract_cabinet_info(cabinet_data):
    """Extract cabinet info for panel title."""
    if not cabinet_data:
        return "No Cabinet Selected", {}

    data = parse_json(cabinet_data)

    name = data.get('name', data.get('label', ''))
    cab_type = data.get('cabinet_type', data.get('cabinet_level', 'base'))
    width = data.get('width', data.get('width_inches', data.get('length_inches', 0)))

    if name:
        title = '{} - {}"{} {}'.format(
            name,
            int(width) if width else '?',
            '',
            cab_type.title()
        )
    else:
        title = "Cabinet #{}".format(data.get('id', '?'))

    return title, data


def build_dimension_sliders(cabinet_data, overrides):
    """
    Build slider configurations for Human UI.
    Returns list of slider configs.
    """
    data = parse_json(cabinet_data)
    override_data = parse_json(overrides)

    sliders = []
    values = []
    overridden = []

    # Width slider
    width = float(data.get('width', data.get('width_inches', data.get('length_inches', DIMENSION_RANGES['width']['default']))))
    width_overridden = 'width' in override_data
    sliders.append({
        'name': 'Width',
        'min': DIMENSION_RANGES['width']['min'],
        'max': DIMENSION_RANGES['width']['max'],
        'step': DIMENSION_RANGES['width']['step'],
        'unit': '"',
    })
    values.append(override_data.get('width', width))
    overridden.append(width_overridden)

    # Height slider
    height = float(data.get('height', data.get('height_inches', DIMENSION_RANGES['height']['default'])))
    height_overridden = 'height' in override_data
    sliders.append({
        'name': 'Height',
        'min': DIMENSION_RANGES['height']['min'],
        'max': DIMENSION_RANGES['height']['max'],
        'step': DIMENSION_RANGES['height']['step'],
        'unit': '"',
    })
    values.append(override_data.get('height', height))
    overridden.append(height_overridden)

    # Depth slider
    depth = float(data.get('depth', data.get('depth_inches', DIMENSION_RANGES['depth']['default'])))
    depth_overridden = 'depth' in override_data
    sliders.append({
        'name': 'Depth',
        'min': DIMENSION_RANGES['depth']['min'],
        'max': DIMENSION_RANGES['depth']['max'],
        'step': DIMENSION_RANGES['depth']['step'],
        'unit': '"',
    })
    values.append(override_data.get('depth', depth))
    overridden.append(depth_overridden)

    return sliders, values, overridden


def format_cut_list_table(cut_list_json):
    """
    Format cut list for Human UI table.
    Returns list of row lists.
    """
    data = parse_json(cut_list_json)

    if not data:
        return [["No cut list data", "", "", ""]]

    # Handle different structures
    parts = []
    if isinstance(data, list):
        parts = data
    elif isinstance(data, dict):
        if 'cut_list' in data:
            parts = data['cut_list']
        else:
            # Flatten category structure
            for category, items in data.items():
                if isinstance(items, list):
                    for item in items:
                        item['category'] = category
                        parts.append(item)

    if not parts:
        return [["No parts in cut list", "", "", ""]]

    # Build table rows
    rows = []
    for part in parts:
        if isinstance(part, dict):
            name = part.get('name', part.get('part_name', 'Unknown'))
            width = part.get('width', part.get('w', 0))
            height = part.get('height', part.get('h', 0))
            qty = part.get('quantity', part.get('qty', 1))

            rows.append([
                name[:20],
                '{:.2f}"'.format(float(width)),
                '{:.2f}"'.format(float(height)),
                str(qty)
            ])

    return rows if rows else [["No valid parts", "", "", ""]]


def format_pricing_text(pricing_json, calculation_json):
    """Format pricing for display."""
    pricing = parse_json(pricing_json)
    calc = parse_json(calculation_json)

    if not pricing and not calc:
        return "No pricing data"

    # Try to extract pricing info
    if pricing:
        lf = pricing.get('linear_feet', 0)
        price_lf = pricing.get('effective_price_per_lf', pricing.get('base_price_per_lf', 0))
        total = pricing.get('total_price', 0)
        complexity_adj = pricing.get('complexity_adjustment', 0)
    elif calc and 'pricing' in calc:
        p = calc['pricing']
        lf = calc.get('dimensions', {}).get('linear_feet', 0)
        price_lf = p.get('unit_price_per_lf', 0)
        total = p.get('total_price', 0)
        complexity_adj = p.get('adjustment_amount', 0)
    else:
        return "No pricing calculated"

    lines = [
        "PRICING",
        "=" * 30,
        "Linear Feet: {:.2f}'".format(float(lf)),
        "$/Linear Ft: ${:.2f}".format(float(price_lf)),
        "",
        "Subtotal: ${:.2f}".format(float(lf) * float(price_lf)),
        "Adjustments: ${:.2f}".format(float(complexity_adj)),
        "-" * 30,
        "TOTAL: ${:.2f}".format(float(total)),
    ]

    return "\n".join(lines)


def get_button_labels(show_adv):
    """Get action button labels."""
    basic = ["Save to ERP", "Reset Overrides", "Refresh"]
    advanced = ["Export Cut List", "Calculate", "Sync to Rhino"]

    if show_adv:
        return basic + advanced
    return basic


# ============================================================
# MAIN COMPONENT LOGIC
# ============================================================

# Initialize outputs
panel_title = "TCS Cabinet Panel"
dimension_sliders = []
dimension_values = []
dimension_overridden = []
cut_list_table = []
pricing_text = ""
status_text = "Ready"
button_labels = []
panel_data = "{}"

# Extract cabinet info for title
panel_title, cab_info = extract_cabinet_info(cabinet_data)

# Build dimension sliders
dimension_sliders, dimension_values, dimension_overridden = build_dimension_sliders(
    cabinet_data,
    override_json
)

# Format cut list table
cut_list_table = format_cut_list_table(cut_list_json)

# Format pricing
pricing_text = format_pricing_text(pricing_json, calculation_json)

# Get button labels
show_adv = show_advanced if show_advanced is not None else False
button_labels = get_button_labels(show_adv)

# Build status text
status_parts = []
if cabinet_data:
    status_parts.append("Cabinet loaded")
if calculation_json:
    status_parts.append("Calculated")
if any(dimension_overridden):
    override_count = sum(1 for o in dimension_overridden if o)
    status_parts.append("{} override(s) active".format(override_count))

status_text = " | ".join(status_parts) if status_parts else "No data loaded"

# Build full panel data
panel_data = json.dumps({
    'title': panel_title,
    'cabinet_id': cab_info.get('id') if cab_info else None,
    'dimensions': {
        'sliders': dimension_sliders,
        'values': dimension_values,
        'overridden': dimension_overridden,
    },
    'cut_list_rows': len(cut_list_table),
    'buttons': button_labels,
    'status': status_text,
}, indent=2)


# ============================================================
# HELPER FUNCTIONS FOR HUMAN UI WIRING
# ============================================================

def get_slider_domains():
    """
    Get slider domains for Human UI Create Slider.
    Returns list of (min, max) tuples.
    """
    return [(s['min'], s['max']) for s in dimension_sliders]


def get_slider_names():
    """Get slider names for labels."""
    return [s['name'] for s in dimension_sliders]


def get_table_headers():
    """Get table headers for Human UI Create Table."""
    return ["Part", "Width", "Height", "Qty"]


def format_for_human_ui_dropdown(items, id_field='id', name_field='name'):
    """
    Format list for Human UI dropdown.
    Returns (names_list, ids_list).
    """
    if not items:
        return [], []

    names = []
    ids = []

    for item in items:
        if isinstance(item, dict):
            names.append(str(item.get(name_field, 'Unknown')))
            ids.append(item.get(id_field))
        else:
            names.append(str(item))
            ids.append(item)

    return names, ids


# Print component info
print("")
print("TCS Cabinet Panel")
print("=" * 40)
print("Title: {}".format(panel_title))
print("Sliders: {}".format(len(dimension_sliders)))
print("Cut List Rows: {}".format(len(cut_list_table)))
print("Buttons: {}".format(len(button_labels)))
print("Status: {}".format(status_text))
