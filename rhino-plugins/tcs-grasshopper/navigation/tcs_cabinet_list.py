"""
TCS Cabinet List - Grasshopper Component
GHPython component for listing and selecting cabinets from a cabinet run.

INPUTS:
    api_base: str - Base API URL from TCS API Connect
    auth_header: str - Authorization header from TCS API Connect
    cabinet_run_id: int - Cabinet run ID from Room Navigator
    selected_index: int - Cabinet selection index
    refresh: bool - Force refresh

OUTPUTS:
    cabinet_names: list - Cabinet names for dropdown
    cabinet_ids: list - Cabinet IDs
    selected_id: int - Selected cabinet ID
    selected_name: str - Selected cabinet name
    cabinet_type: str - Cabinet type (base, wall, tall)
    dimensions: str - Formatted dimensions "W x H x D"
    width: float - Cabinet width in inches
    height: float - Cabinet height in inches
    depth: float - Cabinet depth in inches
    cabinet_data: str - Full cabinet JSON
    cabinet_count: int - Number of cabinets in run
    error: str - Error message if any

USAGE IN GRASSHOPPER:
1. Connect cabinet_run_id from TCS Room Navigator (use cabinet_run_ids with Value List)
2. Connect cabinet_names to Human UI Dropdown
3. Use selected_id for calculator and geometry components

CABINET NAMING:
- Displays as "B1 - 36\" Base" format
- Includes type and dimensions in name
"""

import urllib2
import json
import ssl
import time

# Grasshopper component metadata
ghenv.Component.Name = "TCS Cabinet List"
ghenv.Component.NickName = "TCS Cabinets"
ghenv.Component.Message = "v1.0"
ghenv.Component.Category = "TCS"
ghenv.Component.SubCategory = "Navigation"

# Create unverified SSL context for local dev
ssl_context = ssl.create_default_context()
ssl_context.check_hostname = False
ssl_context.verify_mode = ssl.CERT_NONE

# Cache settings
CACHE_TTL = 60


def fetch_cabinets(base_url, auth, run_id):
    """
    Fetch cabinets for a cabinet run.
    Returns (success, cabinets_list, error_message).
    """
    try:
        url = "{}/api/v1/cabinet-runs/{}/cabinets".format(base_url, run_id)

        request = urllib2.Request(url)
        request.add_header("Authorization", auth)
        request.add_header("Accept", "application/json")

        response = urllib2.urlopen(request, context=ssl_context, timeout=30)
        data = json.loads(response.read())

        cabinets = data.get('data', data) if isinstance(data, dict) else data
        return True, cabinets, None

    except urllib2.HTTPError as e:
        return False, [], "HTTP Error {}: {}".format(e.code, str(e))
    except urllib2.URLError as e:
        return False, [], "Connection failed: {}".format(str(e.reason))
    except Exception as e:
        return False, [], "Error: {}".format(str(e))


def format_cabinet_name(cabinet):
    """
    Format cabinet name for dropdown.
    Format: "B1 - 36\" Base" or "W1 - 30x15 Wall"
    """
    name = cabinet.get('name', cabinet.get('label', ''))
    cab_type = cabinet.get('cabinet_type', 'base')
    w = cabinet.get('width', 0)
    h = cabinet.get('height', 0)

    # Type display names
    type_names = {
        'base': 'Base',
        'wall': 'Wall',
        'tall': 'Tall',
        'upper': 'Upper',
        'vanity': 'Vanity',
        'drawer_base': 'Drawer Base',
    }
    type_display = type_names.get(cab_type, cab_type.title())

    if name:
        return '{} - {}"{} {}'.format(name, int(w), 'x{}"'.format(int(h)) if cab_type == 'wall' else '', type_display)
    else:
        return '{}"{} {} #{}'.format(int(w), 'x{}"'.format(int(h)) if cab_type == 'wall' else '', type_display, cabinet.get('id', '?'))


def format_dimensions(cabinet):
    """Format dimensions as W x H x D string."""
    w = cabinet.get('width', 0)
    h = cabinet.get('height', 0)
    d = cabinet.get('depth', 0)
    return '{}" x {}" x {}"'.format(w, h, d)


def get_cabinet_type_display(cabinet):
    """Get cabinet type for display."""
    cab_type = cabinet.get('cabinet_type', 'unknown')
    return cab_type.replace('_', ' ').title()


# ============================================================
# STICKY DICT CACHE
# ============================================================

if 'tcs_cabinet_cache' not in globals():
    tcs_cabinet_cache = {}


def get_cache_key(run_id):
    return "run_{}".format(run_id)


def is_cache_valid(run_id):
    key = get_cache_key(run_id)
    if key not in tcs_cabinet_cache:
        return False
    timestamp = tcs_cabinet_cache[key].get('timestamp', 0)
    return (time.time() - timestamp) < CACHE_TTL


# ============================================================
# MAIN COMPONENT LOGIC
# ============================================================

# Initialize outputs
cabinet_names = []
cabinet_ids = []
selected_id = None
selected_name = ""
cabinet_type = ""
dimensions = ""
width = 0.0
height = 0.0
depth = 0.0
cabinet_data = ""
cabinet_count = 0
error = ""

# Validate inputs
if not api_base:
    error = "API base URL required"
elif not auth_header:
    error = "Auth header required"
elif not cabinet_run_id:
    error = "Cabinet run ID required - connect Room Navigator"
else:
    # Check cache
    cache_key = get_cache_key(cabinet_run_id)

    if not refresh and is_cache_valid(cabinet_run_id):
        cabinets = tcs_cabinet_cache[cache_key].get('cabinets', [])
        print("Using cached cabinets (age: {:.1f}s)".format(
            time.time() - tcs_cabinet_cache[cache_key]['timestamp']
        ))
    else:
        # Fetch fresh data
        ok, cabinets, err = fetch_cabinets(api_base, auth_header, cabinet_run_id)

        if ok:
            tcs_cabinet_cache[cache_key] = {
                'cabinets': cabinets,
                'timestamp': time.time()
            }
            print("Fetched {} cabinets for run {}".format(len(cabinets), cabinet_run_id))
        else:
            error = err
            cabinets = []

    cabinet_count = len(cabinets)

    # Build cabinet dropdown
    if cabinets:
        # Sort by position/order if available
        sorted_cabs = sorted(
            cabinets,
            key=lambda c: (c.get('sort_order', 999), c.get('position_x', 0), c.get('name', ''))
        )

        for cab in sorted_cabs:
            cabinet_names.append(format_cabinet_name(cab))
            cabinet_ids.append(cab.get('id'))

        # Handle selection
        cab_index = selected_index if selected_index is not None else 0
        if 0 <= cab_index < len(sorted_cabs):
            selected_cab = sorted_cabs[cab_index]
            selected_id = selected_cab.get('id')
            selected_name = selected_cab.get('name', selected_cab.get('label', ''))
            cabinet_type = get_cabinet_type_display(selected_cab)
            dimensions = format_dimensions(selected_cab)
            width = float(selected_cab.get('width', 0))
            height = float(selected_cab.get('height', 0))
            depth = float(selected_cab.get('depth', 0))
            cabinet_data = json.dumps(selected_cab)


# ============================================================
# HELPER FUNCTIONS
# ============================================================

def get_cabinet_summary(cabinet_json):
    """Get summary text for cabinet display."""
    try:
        cab = json.loads(cabinet_json) if isinstance(cabinet_json, str) else cabinet_json
        lines = [
            "Cabinet: {}".format(cab.get('name', 'Unknown')),
            "Type: {}".format(get_cabinet_type_display(cab)),
            "Dimensions: {}".format(format_dimensions(cab)),
            "ID: {}".format(cab.get('id', 'N/A')),
        ]
        return "\n".join(lines)
    except:
        return "No cabinet selected"


def get_run_summary():
    """Get summary of current cabinet run."""
    return "Cabinet Run {} - {} cabinets".format(
        cabinet_run_id if cabinet_run_id else "?",
        cabinet_count
    )


def get_linear_feet():
    """Calculate total linear feet for the run."""
    total_width = 0
    if 'tcs_cabinet_cache' in globals():
        cache_key = get_cache_key(cabinet_run_id) if cabinet_run_id else ""
        if cache_key in tcs_cabinet_cache:
            for cab in tcs_cabinet_cache[cache_key].get('cabinets', []):
                total_width += float(cab.get('width', 0))
    return total_width / 12.0  # Convert inches to feet


# Print component info
print("")
print("TCS Cabinet List")
print("=" * 40)
print("Cabinet Run ID: {}".format(cabinet_run_id if cabinet_run_id else "[none]"))
print("Cabinets: {}".format(cabinet_count))
if selected_name:
    print("Selected: {} ({})".format(selected_name, cabinet_type))
    print("Dimensions: {}".format(dimensions))
if cabinet_run_id:
    print("Linear Feet: {:.2f}'".format(get_linear_feet()))
if error:
    print("Error: {}".format(error))
