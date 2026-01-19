"""
TCS Room Navigator - Grasshopper Component
GHPython component for navigating Project → Room → Location hierarchy.

INPUTS:
    api_base: str - Base API URL from TCS API Connect
    auth_header: str - Authorization header from TCS API Connect
    project_id: int - Selected project ID from Project Selector
    selected_room_index: int - Room selection index
    selected_location_index: int - Location selection index
    refresh: bool - Force refresh

OUTPUTS:
    room_names: list - Room names for dropdown
    room_ids: list - Room IDs
    selected_room_id: int - Selected room ID
    selected_room_name: str - Selected room name
    location_names: list - Location names for dropdown
    location_ids: list - Location IDs
    selected_location_id: int - Selected location ID
    selected_location_name: str - Selected location name
    cabinet_run_names: list - Cabinet run names at selected location
    cabinet_run_ids: list - Cabinet run IDs
    tree_data: str - Full tree JSON for visualization
    error: str - Error message if any

USAGE IN GRASSHOPPER:
1. Connect project_id from TCS Project Selector
2. Connect room_names to Human UI Dropdown for room selection
3. Connect location_names to Human UI Dropdown for location selection
4. Use selected_location_id for cabinet run filtering

CASCADING DROPDOWNS:
- Room dropdown filters available Locations
- Location dropdown shows Cabinet Runs at that position
"""

import urllib2
import json
import ssl
import time

# Grasshopper component metadata
ghenv.Component.Name = "TCS Room Navigator"
ghenv.Component.NickName = "TCS Rooms"
ghenv.Component.Message = "v1.0"
ghenv.Component.Category = "TCS"
ghenv.Component.SubCategory = "Navigation"

# Create unverified SSL context for local dev
ssl_context = ssl.create_default_context()
ssl_context.check_hostname = False
ssl_context.verify_mode = ssl.CERT_NONE

# Cache settings
CACHE_TTL = 60


def fetch_project_tree(base_url, auth, project_id):
    """
    Fetch full project hierarchy tree.
    Returns (success, tree_data, error_message).
    """
    try:
        url = "{}/api/v1/projects/{}/tree".format(base_url, project_id)

        request = urllib2.Request(url)
        request.add_header("Authorization", auth)
        request.add_header("Accept", "application/json")

        response = urllib2.urlopen(request, context=ssl_context, timeout=30)
        data = json.loads(response.read())

        # API returns array of rooms directly for /tree endpoint
        return True, data, None

    except urllib2.HTTPError as e:
        return False, None, "HTTP Error {}: {}".format(e.code, str(e))
    except urllib2.URLError as e:
        return False, None, "Connection failed: {}".format(str(e.reason))
    except Exception as e:
        return False, None, "Error: {}".format(str(e))


def fetch_rooms(base_url, auth, project_id):
    """
    Fetch rooms for a project with nested locations and cabinet runs.
    Returns (success, rooms_list, error_message).
    """
    try:
        url = "{}/api/v1/projects/{}/rooms?include=locations.cabinetRuns".format(
            base_url, project_id
        )

        request = urllib2.Request(url)
        request.add_header("Authorization", auth)
        request.add_header("Accept", "application/json")

        response = urllib2.urlopen(request, context=ssl_context, timeout=30)
        data = json.loads(response.read())

        rooms = data.get('data', data) if isinstance(data, dict) else data
        return True, rooms, None

    except urllib2.HTTPError as e:
        return False, [], "HTTP Error {}: {}".format(e.code, str(e))
    except urllib2.URLError as e:
        return False, [], "Connection failed: {}".format(str(e.reason))
    except Exception as e:
        return False, [], "Error: {}".format(str(e))


def format_room_name(room):
    """Format room name for dropdown."""
    name = room.get('name', 'Unnamed Room')
    room_type = room.get('type', '')

    if room_type:
        return "{} ({})".format(name, room_type)
    return name


def format_location_name(location):
    """Format location name for dropdown."""
    name = location.get('name', 'Unnamed Location')
    position = location.get('position', '')

    if position:
        return "{} - {}".format(position, name)
    return name


def format_cabinet_run_name(run):
    """Format cabinet run name for dropdown."""
    name = run.get('name', '')
    run_type = run.get('run_type', '')

    if name:
        if run_type:
            return "{} ({})".format(name, run_type)
        return name
    return "Run #{}".format(run.get('id', '?'))


# ============================================================
# STICKY DICT CACHE
# ============================================================

if 'tcs_room_cache' not in globals():
    tcs_room_cache = {}


def get_cache_key(project_id):
    return "project_{}".format(project_id)


def is_cache_valid(project_id):
    key = get_cache_key(project_id)
    if key not in tcs_room_cache:
        return False
    timestamp = tcs_room_cache[key].get('timestamp', 0)
    return (time.time() - timestamp) < CACHE_TTL


# ============================================================
# MAIN COMPONENT LOGIC
# ============================================================

# Initialize outputs
room_names = []
room_ids = []
selected_room_id = None
selected_room_name = ""
location_names = []
location_ids = []
selected_location_id = None
selected_location_name = ""
cabinet_run_names = []
cabinet_run_ids = []
tree_data = ""
error = ""

# Validate inputs
if not api_base:
    error = "API base URL required"
elif not auth_header:
    error = "Auth header required"
elif not project_id:
    error = "Project ID required - connect TCS Project Selector"
else:
    # Check cache
    cache_key = get_cache_key(project_id)

    if not refresh and is_cache_valid(project_id):
        rooms = tcs_room_cache[cache_key].get('rooms', [])
        print("Using cached rooms (age: {:.1f}s)".format(
            time.time() - tcs_room_cache[cache_key]['timestamp']
        ))
    else:
        # Try tree endpoint first, fall back to rooms endpoint
        ok, tree, err = fetch_project_tree(api_base, auth_header, project_id)

        if ok and tree:
            # Tree endpoint returns flat room list
            rooms = tree if isinstance(tree, list) else []
        else:
            # Fallback to standard rooms endpoint
            ok, rooms, err = fetch_rooms(api_base, auth_header, project_id)

        if ok:
            tcs_room_cache[cache_key] = {
                'rooms': rooms,
                'timestamp': time.time()
            }
            print("Fetched {} rooms for project {}".format(len(rooms), project_id))
        else:
            error = err
            rooms = []

    # Store full tree data
    tree_data = json.dumps(rooms)

    # Build room dropdown
    if rooms:
        for room in rooms:
            room_names.append(format_room_name(room))
            room_ids.append(room.get('id'))

        # Handle room selection
        room_index = selected_room_index if selected_room_index is not None else 0
        if 0 <= room_index < len(rooms):
            selected_room = rooms[room_index]
            selected_room_id = selected_room.get('id')
            selected_room_name = selected_room.get('name', '')

            # Get locations for selected room
            # Handle both nested 'locations' and 'children' keys
            locations = selected_room.get('locations') or selected_room.get('children', [])

            if locations:
                for loc in locations:
                    location_names.append(format_location_name(loc))
                    location_ids.append(loc.get('id'))

                # Handle location selection
                loc_index = selected_location_index if selected_location_index is not None else 0
                if 0 <= loc_index < len(locations):
                    selected_loc = locations[loc_index]
                    selected_location_id = selected_loc.get('id')
                    selected_location_name = selected_loc.get('name', '')

                    # Get cabinet runs for selected location
                    runs = selected_loc.get('cabinetRuns') or selected_loc.get('cabinet_runs') or selected_loc.get('children', [])

                    for run in runs:
                        cabinet_run_names.append(format_cabinet_run_name(run))
                        cabinet_run_ids.append(run.get('id'))


# ============================================================
# HELPER FUNCTIONS
# ============================================================

def get_hierarchy_path():
    """Get current selection path as string."""
    parts = []
    if selected_room_name:
        parts.append(selected_room_name)
    if selected_location_name:
        parts.append(selected_location_name)
    return " > ".join(parts) if parts else "No selection"


def count_cabinet_runs(rooms):
    """Count total cabinet runs across all rooms."""
    total = 0
    for room in rooms:
        locations = room.get('locations') or room.get('children', [])
        for loc in locations:
            runs = loc.get('cabinetRuns') or loc.get('cabinet_runs') or loc.get('children', [])
            total += len(runs)
    return total


# Print component info
print("")
print("TCS Room Navigator")
print("=" * 40)
print("Project ID: {}".format(project_id if project_id else "[none]"))
print("Rooms: {}".format(len(room_names)))
print("Locations: {}".format(len(location_names)))
print("Cabinet Runs: {}".format(len(cabinet_run_names)))
if selected_room_name or selected_location_name:
    print("Path: {}".format(get_hierarchy_path()))
if error:
    print("Error: {}".format(error))
