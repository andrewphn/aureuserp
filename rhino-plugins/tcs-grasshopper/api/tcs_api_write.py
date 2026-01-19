"""
TCS API Write - Grasshopper Component
GHPython component for writing data to TCS ERP API (POST/PUT/PATCH/DELETE).

INPUTS:
    api_base: str - Base API URL from TCS API Connect
    auth_header: str - Authorization header from TCS API Connect
    endpoint: str - API endpoint (e.g., "/api/v1/cabinets/123")
    method: str - HTTP method (POST, PUT, PATCH, DELETE)
    payload: str - JSON payload for request body
    execute: bool - Trigger execution (button/toggle)

OUTPUTS:
    response: str - JSON response data
    success: bool - Request success status
    status_code: int - HTTP status code
    created_id: int - ID of created resource (for POST)
    error: str - Error message if any

USAGE IN GRASSHOPPER:
1. Connect api_base and auth_header from TCS API Connect
2. Set endpoint and method
3. Set payload as JSON for POST/PUT/PATCH
4. Toggle execute to send request

SAFETY:
- Requires execute toggle to be TRUE before sending
- Logs all write operations for audit trail
- Returns detailed error messages

EXAMPLE OPERATIONS:
    POST /api/v1/cabinets - Create new cabinet
    PUT /api/v1/cabinets/123 - Update cabinet
    PATCH /api/v1/cabinets/123 - Partial update
    DELETE /api/v1/cabinets/123 - Delete cabinet
    POST /api/v1/cabinets/123/calculate - Trigger calculation
"""

import urllib2
import json
import ssl
import time

# Grasshopper component metadata
ghenv.Component.Name = "TCS API Write"
ghenv.Component.NickName = "TCS Write"
ghenv.Component.Message = "v1.0"
ghenv.Component.Category = "TCS"
ghenv.Component.SubCategory = "API"

# Create unverified SSL context for local dev
ssl_context = ssl.create_default_context()
ssl_context.check_hostname = False
ssl_context.verify_mode = ssl.CERT_NONE

# Valid HTTP methods for write operations
VALID_METHODS = ['POST', 'PUT', 'PATCH', 'DELETE']


def send_request(base_url, auth, endpoint, http_method, body=None):
    """
    Send HTTP request to API.
    Returns (success, status_code, data, error_message).
    """
    try:
        url = "{}{}".format(base_url, endpoint)

        # Encode body if provided
        body_data = None
        if body and http_method in ['POST', 'PUT', 'PATCH']:
            if isinstance(body, dict):
                body_data = json.dumps(body)
            else:
                body_data = body

        # Create request with custom method
        request = urllib2.Request(url, data=body_data)
        request.get_method = lambda: http_method
        request.add_header("Authorization", auth)
        request.add_header("Accept", "application/json")
        request.add_header("Content-Type", "application/json")

        response = urllib2.urlopen(request, context=ssl_context, timeout=30)
        status_code = response.getcode()
        response_data = response.read()

        # Parse JSON response
        if response_data:
            try:
                parsed = json.loads(response_data)
                return True, status_code, parsed, None
            except:
                return True, status_code, response_data, None
        else:
            return True, status_code, None, None

    except urllib2.HTTPError as e:
        error_body = ""
        try:
            error_body = e.read()
            # Try to parse error as JSON for better messages
            try:
                error_json = json.loads(error_body)
                if 'message' in error_json:
                    error_body = error_json['message']
                elif 'errors' in error_json:
                    error_body = json.dumps(error_json['errors'])
            except:
                pass
        except:
            pass
        return False, e.code, None, "HTTP {}: {}".format(e.code, error_body or str(e))

    except urllib2.URLError as e:
        return False, 0, None, "Connection error: {}".format(str(e.reason))

    except Exception as e:
        return False, 0, None, "Error: {}".format(str(e))


def parse_payload(payload_str):
    """Parse JSON payload string into dict."""
    if not payload_str:
        return None
    try:
        return json.loads(payload_str)
    except Exception as e:
        raise ValueError("Invalid JSON payload: {}".format(str(e)))


def extract_created_id(response_data):
    """Extract ID from created resource response."""
    if isinstance(response_data, dict):
        # Try common ID locations
        if 'data' in response_data and isinstance(response_data['data'], dict):
            return response_data['data'].get('id')
        return response_data.get('id')
    return None


# ============================================================
# AUDIT LOG
# ============================================================

# Initialize audit log in sticky dict
if 'tcs_api_audit_log' not in globals():
    tcs_api_audit_log = []


def log_operation(endpoint, method, payload, success, status_code, response):
    """Log write operation for audit trail."""
    entry = {
        'timestamp': time.strftime('%Y-%m-%d %H:%M:%S'),
        'endpoint': endpoint,
        'method': method,
        'payload_preview': str(payload)[:100] if payload else None,
        'success': success,
        'status_code': status_code,
        'response_preview': str(response)[:100] if response else None
    }
    tcs_api_audit_log.append(entry)

    # Keep only last 50 entries
    if len(tcs_api_audit_log) > 50:
        tcs_api_audit_log.pop(0)


def get_audit_log():
    """Get recent audit log entries."""
    return tcs_api_audit_log


# ============================================================
# MAIN COMPONENT LOGIC
# ============================================================

# Initialize outputs
response = ""
success = False
status_code = 0
created_id = None
error = ""

# Validate inputs
if not api_base:
    error = "API base URL required - connect TCS API Connect"
elif not auth_header:
    error = "Auth header required - connect TCS API Connect"
elif not endpoint:
    error = "Endpoint required"
elif not method:
    error = "HTTP method required (POST, PUT, PATCH, DELETE)"
elif method.upper() not in VALID_METHODS:
    error = "Invalid method: {}. Use: {}".format(method, ', '.join(VALID_METHODS))
elif not execute:
    # Safety check - don't execute unless explicitly triggered
    print("TCS API Write - Ready")
    print("Set execute=True to send request")
    print("Method: {}".format(method.upper() if method else "[not set]"))
    print("Endpoint: {}".format(endpoint if endpoint else "[not set]"))
else:
    # Execute the request
    http_method = method.upper()

    # Parse payload
    try:
        body = parse_payload(payload)
    except ValueError as e:
        error = str(e)
        body = None

    if not error:
        # Require payload for POST/PUT/PATCH (except for action endpoints)
        if http_method in ['POST', 'PUT', 'PATCH']:
            # Allow empty payload for action endpoints like /calculate
            if not body and not any(action in endpoint for action in ['/calculate', '/sync', '/refresh']):
                print("Warning: No payload provided for {} request".format(http_method))

        # Send request
        ok, code, response_data, err = send_request(
            api_base, auth_header, endpoint, http_method, body
        )

        status_code = code
        success = ok

        if ok:
            response = json.dumps(response_data) if response_data else ""
            created_id = extract_created_id(response_data)

            print("Success: {} {} - Status {}".format(http_method, endpoint, code))
            if created_id:
                print("Created ID: {}".format(created_id))
        else:
            error = err
            print("Failed: {} {} - {}".format(http_method, endpoint, err))

        # Log operation
        log_operation(endpoint, http_method, body, ok, code, response_data)

        # Clear API cache after write operation (in fetch component)
        if 'tcs_api_cache' in globals():
            tcs_api_cache.clear()
            print("Cleared API cache")


# ============================================================
# HELPER FUNCTIONS FOR BUILDING PAYLOADS
# ============================================================

def build_cabinet_payload(name, width, height, depth, cabinet_type='base',
                          cabinet_run_id=None, **kwargs):
    """
    Build cabinet creation/update payload.

    Args:
        name: Cabinet name (e.g., "B1")
        width: Width in inches
        height: Height in inches
        depth: Depth in inches
        cabinet_type: Type (base, wall, tall)
        cabinet_run_id: Parent cabinet run ID
        **kwargs: Additional fields

    Returns:
        dict: Payload ready for API
    """
    payload = {
        'name': name,
        'width': float(width),
        'height': float(height),
        'depth': float(depth),
        'cabinet_type': cabinet_type,
    }

    if cabinet_run_id:
        payload['cabinet_run_id'] = int(cabinet_run_id)

    # Add any additional fields
    payload.update(kwargs)

    return payload


def build_override_payload(cabinet_id, overrides):
    """
    Build payload for cabinet overrides.

    Args:
        cabinet_id: Cabinet ID
        overrides: Dict of field->value overrides

    Returns:
        dict: Payload ready for API
    """
    return {
        'cabinet_id': int(cabinet_id),
        'overrides': overrides
    }


# Print component info
print("")
print("TCS API Write")
print("=" * 40)
print("Method: {}".format(method.upper() if method else "[not set]"))
print("Endpoint: {}".format(endpoint if endpoint else "[not set]"))
print("Execute: {}".format(execute))
if execute and success:
    print("Status: {} ({})".format(status_code, "OK" if success else "FAILED"))
if error:
    print("Error: {}".format(error))
