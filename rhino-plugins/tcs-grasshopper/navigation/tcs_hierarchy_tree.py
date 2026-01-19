"""
TCS Hierarchy Tree - Grasshopper Component
GHPython component for full project hierarchy visualization.

INPUTS:
    api_base: str - Base API URL from TCS API Connect
    auth_header: str - Authorization header from TCS API Connect
    project_id: int - Project ID from Project Selector
    expand_all: bool - Expand all tree nodes
    refresh: bool - Force refresh

OUTPUTS:
    tree_text: str - Formatted text tree for display
    tree_structure: str - JSON tree structure for custom visualization
    room_count: int - Total rooms
    location_count: int - Total locations
    cabinet_run_count: int - Total cabinet runs
    cabinet_count: int - Total cabinets
    total_linear_feet: float - Total linear feet across all cabinets
    error: str - Error message if any

USAGE IN GRASSHOPPER:
1. Connect project_id from TCS Project Selector
2. Use tree_text with Text Panel or Human UI Text Block
3. Use tree_structure for custom tree visualizations

TREE FORMAT:
```
Project: Kitchen Remodel
├── Kitchen
│   ├── North Wall
│   │   └── Run A (3 cabinets, 9' LF)
│   └── South Wall
│       └── Run B (4 cabinets, 12' LF)
└── Pantry
    └── Main Wall
        └── Run A (2 cabinets, 4' LF)
```
"""

import urllib2
import json
import ssl
import time

# Grasshopper component metadata
ghenv.Component.Name = "TCS Hierarchy Tree"
ghenv.Component.NickName = "TCS Tree"
ghenv.Component.Message = "v1.0"
ghenv.Component.Category = "TCS"
ghenv.Component.SubCategory = "Navigation"

# Create unverified SSL context for local dev
ssl_context = ssl.create_default_context()
ssl_context.check_hostname = False
ssl_context.verify_mode = ssl.CERT_NONE


def fetch_project_with_tree(base_url, auth, project_id):
    """
    Fetch project with full hierarchy.
    Returns (success, project_data, error_message).
    """
    try:
        # First get project info
        url = "{}/api/v1/projects/{}".format(base_url, project_id)
        request = urllib2.Request(url)
        request.add_header("Authorization", auth)
        request.add_header("Accept", "application/json")

        response = urllib2.urlopen(request, context=ssl_context, timeout=30)
        project = json.loads(response.read())
        project_data = project.get('data', project)

        # Then get tree
        tree_url = "{}/api/v1/projects/{}/tree".format(base_url, project_id)
        tree_request = urllib2.Request(tree_url)
        tree_request.add_header("Authorization", auth)
        tree_request.add_header("Accept", "application/json")

        tree_response = urllib2.urlopen(tree_request, context=ssl_context, timeout=30)
        tree = json.loads(tree_response.read())

        # Merge tree into project
        project_data['rooms'] = tree if isinstance(tree, list) else []

        return True, project_data, None

    except urllib2.HTTPError as e:
        return False, None, "HTTP Error {}: {}".format(e.code, str(e))
    except urllib2.URLError as e:
        return False, None, "Connection failed: {}".format(str(e.reason))
    except Exception as e:
        return False, None, "Error: {}".format(str(e))


def calculate_run_linear_feet(run):
    """Calculate linear feet for a cabinet run."""
    cabinets = run.get('cabinets') or run.get('children', [])
    total_width = sum(float(c.get('width', 0)) for c in cabinets)
    return total_width / 12.0


def build_tree_text(project, expand_all=True):
    """
    Build formatted text tree.
    Returns (tree_text, stats_dict).
    """
    lines = []
    stats = {
        'rooms': 0,
        'locations': 0,
        'cabinet_runs': 0,
        'cabinets': 0,
        'linear_feet': 0.0
    }

    project_name = project.get('name', 'Unknown Project')
    project_number = project.get('project_number', '')

    header = "Project: {}".format(project_name)
    if project_number:
        header = "[{}] {}".format(project_number, project_name)
    lines.append(header)
    lines.append("=" * len(header))

    rooms = project.get('rooms', [])
    stats['rooms'] = len(rooms)

    for i, room in enumerate(rooms):
        is_last_room = (i == len(rooms) - 1)
        room_prefix = "└── " if is_last_room else "├── "
        room_connector = "    " if is_last_room else "│   "

        room_name = room.get('name', 'Unnamed Room')
        lines.append("{}{}".format(room_prefix, room_name))

        locations = room.get('locations') or room.get('children', [])
        stats['locations'] += len(locations)

        for j, location in enumerate(locations):
            is_last_loc = (j == len(locations) - 1)
            loc_prefix = "└── " if is_last_loc else "├── "
            loc_connector = "    " if is_last_loc else "│   "

            loc_name = location.get('name', 'Unnamed Location')
            lines.append("{}{}{}".format(room_connector, loc_prefix, loc_name))

            runs = location.get('cabinetRuns') or location.get('cabinet_runs') or location.get('children', [])
            stats['cabinet_runs'] += len(runs)

            for k, run in enumerate(runs):
                is_last_run = (k == len(runs) - 1)
                run_prefix = "└── " if is_last_run else "├── "

                run_name = run.get('name', 'Run #{}'.format(run.get('id', '?')))
                cabinets = run.get('cabinets') or run.get('children', [])
                cab_count = len(cabinets)
                stats['cabinets'] += cab_count

                run_lf = calculate_run_linear_feet(run)
                stats['linear_feet'] += run_lf

                run_info = "{} ({} cab, {:.1f}' LF)".format(run_name, cab_count, run_lf)
                lines.append("{}{}{}{}".format(room_connector, loc_connector, run_prefix, run_info))

                # Optionally show cabinets
                if expand_all and cabinets:
                    for m, cab in enumerate(cabinets):
                        is_last_cab = (m == len(cabinets) - 1)
                        cab_prefix = "└── " if is_last_cab else "├── "
                        cab_connector = "    " if is_last_run else "│   "

                        cab_name = cab.get('name', cab.get('label', ''))
                        cab_width = cab.get('width', 0)
                        cab_type = cab.get('cabinet_type', 'base')

                        cab_info = '{} - {}" {}'.format(
                            cab_name if cab_name else "Cabinet",
                            int(cab_width),
                            cab_type.title()
                        )
                        lines.append("{}{}{}{}{}".format(
                            room_connector, loc_connector, cab_connector, cab_prefix, cab_info
                        ))

    # Add summary
    lines.append("")
    lines.append("-" * 40)
    lines.append("Summary:")
    lines.append("  Rooms: {}".format(stats['rooms']))
    lines.append("  Locations: {}".format(stats['locations']))
    lines.append("  Cabinet Runs: {}".format(stats['cabinet_runs']))
    lines.append("  Cabinets: {}".format(stats['cabinets']))
    lines.append("  Total Linear Feet: {:.1f}'".format(stats['linear_feet']))

    return "\n".join(lines), stats


def build_tree_structure(project):
    """
    Build JSON tree structure for custom visualization.
    """
    def process_node(node, node_type):
        result = {
            'id': node.get('id'),
            'name': node.get('name', 'Unknown'),
            'type': node_type,
        }

        children = []
        if node_type == 'project':
            for room in node.get('rooms', []):
                children.append(process_node(room, 'room'))
        elif node_type == 'room':
            for loc in node.get('locations') or node.get('children', []):
                children.append(process_node(loc, 'location'))
        elif node_type == 'location':
            for run in loc.get('cabinetRuns') or loc.get('cabinet_runs') or loc.get('children', []):
                children.append(process_node(run, 'cabinet_run'))
        elif node_type == 'cabinet_run':
            for cab in node.get('cabinets') or node.get('children', []):
                children.append({
                    'id': cab.get('id'),
                    'name': cab.get('name', cab.get('label', '')),
                    'type': 'cabinet',
                    'width': cab.get('width'),
                    'height': cab.get('height'),
                    'depth': cab.get('depth'),
                    'cabinet_type': cab.get('cabinet_type'),
                })

        if children:
            result['children'] = children

        return result

    return process_node(project, 'project')


# ============================================================
# MAIN COMPONENT LOGIC
# ============================================================

# Initialize outputs
tree_text = ""
tree_structure = ""
room_count = 0
location_count = 0
cabinet_run_count = 0
cabinet_count = 0
total_linear_feet = 0.0
error = ""

# Validate inputs
if not api_base:
    error = "API base URL required"
elif not auth_header:
    error = "Auth header required"
elif not project_id:
    error = "Project ID required"
else:
    # Fetch project with tree
    ok, project, err = fetch_project_with_tree(api_base, auth_header, project_id)

    if ok:
        # Build text tree
        tree_text, stats = build_tree_text(project, expand_all if expand_all is not None else True)

        # Extract stats
        room_count = stats['rooms']
        location_count = stats['locations']
        cabinet_run_count = stats['cabinet_runs']
        cabinet_count = stats['cabinets']
        total_linear_feet = stats['linear_feet']

        # Build JSON structure
        tree_structure = json.dumps(build_tree_structure(project), indent=2)

        print("Loaded project tree: {} rooms, {} cabinets".format(room_count, cabinet_count))
    else:
        error = err
        print("Failed to load tree: {}".format(err))


# Print tree to component output
if tree_text:
    print("")
    print(tree_text)
