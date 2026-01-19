"""
TCS API Fetch - Grasshopper Component
GHPython component for fetching data from TCS ERP API with caching.

INPUTS:
    api_base: str - Base API URL from TCS API Connect
    auth_header: str - Authorization header from TCS API Connect
    endpoint: str - API endpoint (e.g., "/api/v1/projects")
    params: str - Query parameters as JSON (optional)
    refresh: bool - Force refresh (bypass cache)

OUTPUTS:
    data: str - JSON response data
    success: bool - Request success status
    count: int - Number of items returned
    total: int - Total items available (from pagination)
    error: str - Error message if any
    cached: bool - Whether data came from cache

USAGE IN GRASSHOPPER:
1. Connect api_base and auth_header from TCS API Connect
2. Set endpoint (text panel or value list)
3. Optional: Set params as JSON for filtering/pagination
4. Toggle refresh to bypass cache

EXAMPLE ENDPOINTS:
    /api/v1/projects
    /api/v1/projects/{id}
    /api/v1/projects/{id}/tree
    /api/v1/rooms
    /api/v1/cabinet-runs
    /api/v1/cabinets
    /api/v1/cabinets/{id}/cut-list
"""

import urllib2
import json
import ssl
import time
import hashlib

# Grasshopper component metadata
ghenv.Component.Name = "TCS API Fetch"
ghenv.Component.NickName = "TCS Fetch"
ghenv.Component.Message = "v1.0"
ghenv.Component.Category = "TCS"
ghenv.Component.SubCategory = "API"

# Create unverified SSL context for local dev
ssl_context = ssl.create_default_context()
ssl_context.check_hostname = False
ssl_context.verify_mode = ssl.CERT_NONE

# Cache settings
CACHE_TTL = 60  # Cache time-to-live in seconds


def get_cache_key(endpoint, params):
    """Generate unique cache key from endpoint and params."""
    param_str = json.dumps(params, sort_keys=True) if params else ""
    key_str = "{}:{}".format(endpoint, param_str)
    return hashlib.md5(key_str.encode('utf-8')).hexdigest()


def is_cache_valid(cache_entry):
    """Check if cache entry is still valid."""
    if not cache_entry:
        return False
    timestamp = cache_entry.get('timestamp', 0)
    return (time.time() - timestamp) < CACHE_TTL


def fetch_api(base_url, auth, endpoint, query_params=None):
    """
    Fetch data from API endpoint.
    Returns (success, data, error_message).
    """
    try:
        # Build URL with query parameters
        url = "{}{}".format(base_url, endpoint)

        if query_params:
            param_parts = []
            for key, value in query_params.items():
                if isinstance(value, bool):
                    value = "true" if value else "false"
                elif isinstance(value, (list, dict)):
                    value = json.dumps(value)
                param_parts.append("{}={}".format(
                    urllib2.quote(str(key)),
                    urllib2.quote(str(value))
                ))
            if param_parts:
                url += "?" + "&".join(param_parts)

        request = urllib2.Request(url)
        request.add_header("Authorization", auth)
        request.add_header("Accept", "application/json")
        request.add_header("Content-Type", "application/json")

        response = urllib2.urlopen(request, context=ssl_context, timeout=30)
        response_data = json.loads(response.read())

        return True, response_data, None

    except urllib2.HTTPError as e:
        error_body = ""
        try:
            error_body = e.read()
        except:
            pass
        return False, None, "HTTP {}: {}".format(e.code, error_body or str(e))

    except urllib2.URLError as e:
        return False, None, "Connection error: {}".format(str(e.reason))

    except Exception as e:
        return False, None, "Error: {}".format(str(e))


def parse_params(params_str):
    """Parse JSON params string into dict."""
    if not params_str:
        return {}
    try:
        return json.loads(params_str)
    except:
        return {}


def extract_pagination(response_data):
    """Extract count and total from paginated response."""
    if isinstance(response_data, dict):
        data = response_data.get('data', [])
        meta = response_data.get('meta', {})

        count = len(data) if isinstance(data, list) else 1
        total = meta.get('total', count)

        return count, total
    elif isinstance(response_data, list):
        return len(response_data), len(response_data)
    else:
        return 1, 1


# ============================================================
# STICKY DICT CACHE
# ============================================================

# Initialize cache in sticky dict (persists across component updates)
if 'tcs_api_cache' not in globals():
    tcs_api_cache = {}


# ============================================================
# MAIN COMPONENT LOGIC
# ============================================================

# Initialize outputs
data = ""
success = False
count = 0
total = 0
error = ""
cached = False

# Validate inputs
if not api_base:
    error = "API base URL required - connect TCS API Connect"
elif not auth_header:
    error = "Auth header required - connect TCS API Connect"
elif not endpoint:
    error = "Endpoint required (e.g., /api/v1/projects)"
else:
    # Parse query parameters
    query_params = parse_params(params) if params else {}

    # Check cache first (unless refresh requested)
    cache_key = get_cache_key(endpoint, query_params)
    cache_entry = tcs_api_cache.get(cache_key)

    if not refresh and is_cache_valid(cache_entry):
        # Use cached data
        data = cache_entry['data']
        success = True
        count = cache_entry.get('count', 0)
        total = cache_entry.get('total', 0)
        cached = True
        print("Using cached data (age: {:.1f}s)".format(
            time.time() - cache_entry['timestamp']
        ))
    else:
        # Fetch fresh data
        ok, response_data, err = fetch_api(api_base, auth_header, endpoint, query_params)

        if ok:
            success = True
            data = json.dumps(response_data)
            count, total = extract_pagination(response_data)
            cached = False

            # Store in cache
            tcs_api_cache[cache_key] = {
                'data': data,
                'count': count,
                'total': total,
                'timestamp': time.time()
            }

            print("Fetched fresh data: {} items ({} total)".format(count, total))
        else:
            error = err
            print("Fetch failed: {}".format(err))


# ============================================================
# HELPER FUNCTIONS FOR DOWNSTREAM COMPONENTS
# ============================================================

def parse_response(json_str):
    """
    Parse JSON response string.
    Call this from downstream components to work with the data.
    """
    if not json_str:
        return None
    try:
        return json.loads(json_str)
    except:
        return None


def get_data_items(json_str):
    """
    Extract data items from paginated response.
    Returns list of items.
    """
    response = parse_response(json_str)
    if not response:
        return []

    if isinstance(response, dict):
        return response.get('data', [])
    elif isinstance(response, list):
        return response
    else:
        return [response]


def get_item_names(json_str, name_field='name'):
    """
    Extract names from data items for dropdown lists.
    """
    items = get_data_items(json_str)
    return [item.get(name_field, 'Unknown') for item in items if isinstance(item, dict)]


def get_item_ids(json_str):
    """
    Extract IDs from data items.
    """
    items = get_data_items(json_str)
    return [item.get('id') for item in items if isinstance(item, dict)]


# Print debug info
print("TCS API Fetch")
print("=" * 40)
print("Endpoint: {}".format(endpoint if endpoint else "[not set]"))
print("Success: {}".format(success))
print("Cached: {}".format(cached))
if error:
    print("Error: {}".format(error))
