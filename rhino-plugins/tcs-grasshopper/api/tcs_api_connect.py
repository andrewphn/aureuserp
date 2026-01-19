"""
TCS API Connect - Grasshopper Component
GHPython component for TCS ERP API authentication and connection testing.

INPUTS:
    api_url: str - Base API URL (e.g., "http://aureuserp.test")
    api_token: str - API bearer token
    test_connection: bool - Trigger connection test

OUTPUTS:
    connected: bool - Connection status
    auth_header: str - Authorization header for other components
    api_base: str - Validated base URL
    status_msg: str - Connection status message
    error: str - Error message if any

USAGE IN GRASSHOPPER:
1. Add GHPython component
2. Set inputs: api_url (text), api_token (text), test_connection (bool/button)
3. Connect outputs to downstream API components
"""

import urllib2
import json
import ssl

# Grasshopper component metadata
ghenv.Component.Name = "TCS API Connect"
ghenv.Component.NickName = "TCS Connect"
ghenv.Component.Message = "v1.0"
ghenv.Component.Category = "TCS"
ghenv.Component.SubCategory = "API"

# Create unverified SSL context for local dev
ssl_context = ssl.create_default_context()
ssl_context.check_hostname = False
ssl_context.verify_mode = ssl.CERT_NONE


def normalize_url(url):
    """Ensure URL has no trailing slash."""
    if url:
        return url.rstrip('/')
    return ""


def test_api_connection(base_url, token):
    """
    Test API connection by fetching projects list.
    Returns (success, message, data).
    """
    try:
        url = "{}/api/v1/projects?per_page=1".format(base_url)

        request = urllib2.Request(url)
        request.add_header("Authorization", "Bearer {}".format(token))
        request.add_header("Accept", "application/json")
        request.add_header("Content-Type", "application/json")

        response = urllib2.urlopen(request, context=ssl_context, timeout=10)
        data = json.loads(response.read())

        # Check response structure
        if "data" in data:
            project_count = len(data.get("data", []))
            total = data.get("meta", {}).get("total", project_count)
            return True, "Connected - {} projects available".format(total), data
        else:
            return True, "Connected - API responded", data

    except urllib2.HTTPError as e:
        if e.code == 401:
            return False, "Authentication failed - check API token", None
        elif e.code == 403:
            return False, "Access forbidden - check API permissions", None
        elif e.code == 404:
            return False, "API endpoint not found - check URL", None
        else:
            return False, "HTTP Error {}: {}".format(e.code, str(e)), None

    except urllib2.URLError as e:
        return False, "Connection failed: {}".format(str(e.reason)), None

    except Exception as e:
        return False, "Error: {}".format(str(e)), None


def get_api_info(base_url, token):
    """
    Get API information and validate connection.
    Returns dict with API capabilities.
    """
    info = {
        "base_url": base_url,
        "endpoints": {
            "projects": "/api/v1/projects",
            "rooms": "/api/v1/rooms",
            "locations": "/api/v1/room-locations",
            "cabinet_runs": "/api/v1/cabinet-runs",
            "cabinets": "/api/v1/cabinets",
            "calculate": "/api/v1/cabinets/{id}/calculate",
            "cut_list": "/api/v1/cabinets/{id}/cut-list",
        },
        "token_valid": False,
        "message": ""
    }

    success, message, _ = test_api_connection(base_url, token)
    info["token_valid"] = success
    info["message"] = message

    return info


# ============================================================
# MAIN COMPONENT LOGIC
# ============================================================

# Initialize outputs
connected = False
auth_header = ""
api_base = ""
status_msg = "Not connected"
error = ""

# Validate inputs
if not api_url:
    error = "API URL required"
    status_msg = "Missing API URL"
elif not api_token:
    error = "API Token required"
    status_msg = "Missing API Token"
else:
    # Normalize URL
    api_base = normalize_url(api_url)

    # Build auth header
    auth_header = "Bearer {}".format(api_token)

    # Test connection if requested
    if test_connection:
        success, message, data = test_api_connection(api_base, api_token)
        connected = success
        status_msg = message

        if not success:
            error = message
    else:
        # Just validate inputs without testing
        status_msg = "Ready - click test_connection to verify"
        connected = False

# Store connection info in sticky dict for other components
if 'tcs_api_connection' not in globals():
    tcs_api_connection = {}

tcs_api_connection = {
    'base_url': api_base,
    'token': api_token if api_token else "",
    'connected': connected,
    'auth_header': auth_header
}

# Print status for debugging
print("TCS API Connect")
print("=" * 40)
print("Base URL: {}".format(api_base))
print("Token: {}...".format(api_token[:10] if api_token and len(api_token) > 10 else "[hidden]"))
print("Status: {}".format(status_msg))
if error:
    print("Error: {}".format(error))
