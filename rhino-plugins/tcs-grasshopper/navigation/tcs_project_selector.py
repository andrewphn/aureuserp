"""
TCS Project Selector - Grasshopper Component
GHPython component for selecting projects from TCS ERP.

INPUTS:
    api_base: str - Base API URL from TCS API Connect
    auth_header: str - Authorization header from TCS API Connect
    refresh: bool - Force refresh project list
    selected_index: int - Index of selected project (from Value List)

OUTPUTS:
    project_names: list - Project names for Human UI dropdown
    project_ids: list - Corresponding project IDs
    selected_id: int - ID of selected project
    selected_name: str - Name of selected project
    partner_name: str - Partner/client name
    project_data: str - Full project JSON data
    error: str - Error message if any

USAGE IN GRASSHOPPER:
1. Connect api_base and auth_header from TCS API Connect
2. Connect project_names to Human UI Dropdown
3. Connect dropdown selection to selected_index
4. Use selected_id for downstream components

HUMAN UI INTEGRATION:
- project_names feeds into Create Dropdown
- selected_index comes from Dropdown output
- partner_name can display in Text Block
"""

import urllib2
import json
import ssl
import time

# Grasshopper component metadata
ghenv.Component.Name = "TCS Project Selector"
ghenv.Component.NickName = "TCS Projects"
ghenv.Component.Message = "v1.0"
ghenv.Component.Category = "TCS"
ghenv.Component.SubCategory = "Navigation"

# Create unverified SSL context for local dev
ssl_context = ssl.create_default_context()
ssl_context.check_hostname = False
ssl_context.verify_mode = ssl.CERT_NONE

# Cache settings
CACHE_TTL = 120  # 2 minutes


def fetch_projects(base_url, auth, include_relations=True):
    """
    Fetch all projects from API.
    Returns (success, projects_list, error_message).
    """
    try:
        # Build URL with common relations
        url = "{}/api/v1/projects?per_page=100".format(base_url)
        if include_relations:
            url += "&include=partner"

        request = urllib2.Request(url)
        request.add_header("Authorization", auth)
        request.add_header("Accept", "application/json")

        response = urllib2.urlopen(request, context=ssl_context, timeout=30)
        data = json.loads(response.read())

        projects = data.get('data', [])
        return True, projects, None

    except urllib2.HTTPError as e:
        return False, [], "HTTP Error {}: {}".format(e.code, str(e))
    except urllib2.URLError as e:
        return False, [], "Connection failed: {}".format(str(e.reason))
    except Exception as e:
        return False, [], "Error: {}".format(str(e))


def format_project_name(project):
    """Format project name for dropdown display."""
    name = project.get('name', 'Unnamed')
    project_number = project.get('project_number', '')
    status = project.get('status', '')

    # Format: "P001 - Kitchen Remodel (in_progress)"
    parts = []
    if project_number:
        parts.append(project_number)
    parts.append(name)
    if status:
        parts.append("({})".format(status))

    return " - ".join(parts) if project_number else " ".join(parts)


def get_partner_name(project):
    """Extract partner name from project data."""
    partner = project.get('partner')
    if partner:
        return partner.get('name', 'Unknown Partner')

    # Fallback to partner_id if relation not loaded
    partner_id = project.get('partner_id')
    if partner_id:
        return "Partner #{}".format(partner_id)

    return "No Partner"


# ============================================================
# STICKY DICT CACHE
# ============================================================

if 'tcs_project_cache' not in globals():
    tcs_project_cache = {}


def is_cache_valid():
    """Check if project cache is still valid."""
    if not tcs_project_cache:
        return False
    timestamp = tcs_project_cache.get('timestamp', 0)
    return (time.time() - timestamp) < CACHE_TTL


# ============================================================
# MAIN COMPONENT LOGIC
# ============================================================

# Initialize outputs
project_names = []
project_ids = []
selected_id = None
selected_name = ""
partner_name = ""
project_data = ""
error = ""

# Validate inputs
if not api_base:
    error = "API base URL required - connect TCS API Connect"
elif not auth_header:
    error = "Auth header required - connect TCS API Connect"
else:
    # Check cache
    if not refresh and is_cache_valid():
        projects = tcs_project_cache.get('projects', [])
        print("Using cached projects (age: {:.1f}s)".format(
            time.time() - tcs_project_cache['timestamp']
        ))
    else:
        # Fetch fresh data
        ok, projects, err = fetch_projects(api_base, auth_header)

        if ok:
            tcs_project_cache['projects'] = projects
            tcs_project_cache['timestamp'] = time.time()
            print("Fetched {} projects".format(len(projects)))
        else:
            error = err
            projects = []

    # Build output lists
    if projects:
        for proj in projects:
            project_names.append(format_project_name(proj))
            project_ids.append(proj.get('id'))

        # Handle selection
        if selected_index is not None and 0 <= selected_index < len(projects):
            selected_project = projects[selected_index]
            selected_id = selected_project.get('id')
            selected_name = selected_project.get('name', '')
            partner_name = get_partner_name(selected_project)
            project_data = json.dumps(selected_project)

            print("Selected: {} (ID: {})".format(selected_name, selected_id))
            print("Partner: {}".format(partner_name))
        elif projects:
            # Default to first project if no selection
            selected_project = projects[0]
            selected_id = selected_project.get('id')
            selected_name = selected_project.get('name', '')
            partner_name = get_partner_name(selected_project)
            project_data = json.dumps(selected_project)


# ============================================================
# HELPER FUNCTIONS FOR HUMAN UI
# ============================================================

def create_dropdown_items(names, ids):
    """
    Create items for Human UI dropdown.
    Returns list of tuples (display_name, value).
    """
    return list(zip(names, ids))


def get_project_summary(project_json):
    """
    Get summary text for project display.
    """
    try:
        proj = json.loads(project_json) if isinstance(project_json, str) else project_json
        lines = [
            "Project: {}".format(proj.get('name', 'Unknown')),
            "Number: {}".format(proj.get('project_number', 'N/A')),
            "Status: {}".format(proj.get('status', 'Unknown')),
            "Partner: {}".format(get_partner_name(proj)),
        ]
        return "\n".join(lines)
    except:
        return "No project selected"


# Print component info
print("")
print("TCS Project Selector")
print("=" * 40)
print("Projects loaded: {}".format(len(project_names)))
if selected_name:
    print("Selected: {}".format(selected_name))
if error:
    print("Error: {}".format(error))
