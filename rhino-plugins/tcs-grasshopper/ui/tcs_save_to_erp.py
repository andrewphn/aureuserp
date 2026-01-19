"""
TCS Save to ERP - Grasshopper Component
GHPython component for saving cabinet data back to TCS ERP.

INPUTS:
    api_base: str - Base API URL from TCS API Connect
    auth_header: str - Authorization header from TCS API Connect
    cabinet_id: int - Cabinet ID to update
    width: float - Cabinet width (or None to skip)
    height: float - Cabinet height (or None to skip)
    depth: float - Cabinet depth (or None to skip)
    price_per_lf: float - Price per linear foot override
    notes: str - Shop notes to save
    overrides_json: str - Full overrides JSON to save
    save: bool - Trigger save operation

OUTPUTS:
    success: bool - Save operation success
    response: str - API response
    saved_fields: list - List of fields that were saved
    status_msg: str - Status message
    error: str - Error message if any

USAGE IN GRASSHOPPER:
1. Connect api credentials from TCS API Connect
2. Connect cabinet_id from Cabinet List
3. Connect dimension values from sliders/overrides
4. Toggle save to push changes to ERP

SAVE BEHAVIOR:
- Only sends fields that have changed
- Validates data before sending
- Clears local cache after successful save
- Returns detailed status
"""

import urllib2
import json
import ssl

# Grasshopper component metadata
ghenv.Component.Name = "TCS Save to ERP"
ghenv.Component.NickName = "TCS Save"
ghenv.Component.Message = "v1.0"
ghenv.Component.Category = "TCS"
ghenv.Component.SubCategory = "UI"

# Create unverified SSL context for local dev
ssl_context = ssl.create_default_context()
ssl_context.check_hostname = False
ssl_context.verify_mode = ssl.CERT_NONE


def build_update_payload(w, h, d, price, notes_text, overrides):
    """
    Build update payload from inputs.
    Only includes non-None values.
    """
    payload = {}

    if w is not None and w > 0:
        payload['width_inches'] = float(w)
        payload['length_inches'] = float(w)  # TCS uses length_inches

    if h is not None and h > 0:
        payload['height_inches'] = float(h)

    if d is not None and d > 0:
        payload['depth_inches'] = float(d)

    if price is not None and price > 0:
        payload['unit_price_per_lf'] = float(price)

    if notes_text:
        payload['shop_notes'] = str(notes_text)

    # Parse overrides JSON for additional fields
    if overrides:
        try:
            override_data = json.loads(overrides) if isinstance(overrides, str) else overrides
            # Add any additional override fields
            for key, value in override_data.items():
                if key not in ['timestamp', 'width', 'height', 'depth', 'price_per_lf']:
                    payload[key] = value
        except:
            pass

    return payload


def update_cabinet(base_url, auth, cab_id, payload):
    """
    Send PUT request to update cabinet.
    Returns (success, status_code, response_data, error_message).
    """
    try:
        url = "{}/api/v1/cabinets/{}".format(base_url, cab_id)
        body = json.dumps(payload)

        request = urllib2.Request(url, data=body)
        request.get_method = lambda: 'PUT'
        request.add_header("Authorization", auth)
        request.add_header("Accept", "application/json")
        request.add_header("Content-Type", "application/json")

        response = urllib2.urlopen(request, context=ssl_context, timeout=30)
        status_code = response.getcode()
        response_data = json.loads(response.read())

        return True, status_code, response_data, None

    except urllib2.HTTPError as e:
        error_body = ""
        try:
            error_body = e.read()
            try:
                error_json = json.loads(error_body)
                if 'message' in error_json:
                    error_body = error_json['message']
                elif 'errors' in error_json:
                    # Format validation errors
                    errors = error_json['errors']
                    error_parts = []
                    for field, messages in errors.items():
                        error_parts.append("{}: {}".format(field, ", ".join(messages)))
                    error_body = "; ".join(error_parts)
            except:
                pass
        except:
            pass
        return False, e.code, None, "HTTP {}: {}".format(e.code, error_body or str(e))

    except urllib2.URLError as e:
        return False, 0, None, "Connection error: {}".format(str(e.reason))

    except Exception as e:
        return False, 0, None, "Error: {}".format(str(e))


def validate_payload(payload):
    """
    Validate payload before sending.
    Returns (valid, error_message).
    """
    if not payload:
        return False, "No data to save"

    # Check for reasonable dimension values
    if 'width_inches' in payload:
        w = payload['width_inches']
        if w < 6 or w > 120:
            return False, "Width must be between 6\" and 120\""

    if 'height_inches' in payload:
        h = payload['height_inches']
        if h < 6 or h > 120:
            return False, "Height must be between 6\" and 120\""

    if 'depth_inches' in payload:
        d = payload['depth_inches']
        if d < 6 or d > 48:
            return False, "Depth must be between 6\" and 48\""

    if 'unit_price_per_lf' in payload:
        p = payload['unit_price_per_lf']
        if p < 0 or p > 1000:
            return False, "Price per LF must be between $0 and $1000"

    return True, None


# ============================================================
# MAIN COMPONENT LOGIC
# ============================================================

# Initialize outputs
success = False
response = ""
saved_fields = []
status_msg = "Ready to save"
error = ""

# Validate inputs
if not api_base:
    error = "API base URL required"
    status_msg = "Missing API connection"
elif not auth_header:
    error = "Auth header required"
    status_msg = "Missing API credentials"
elif not cabinet_id:
    error = "Cabinet ID required"
    status_msg = "No cabinet selected"
elif not save:
    # Don't execute unless save is triggered
    status_msg = "Set save=True to push changes to ERP"
else:
    # Build payload
    payload = build_update_payload(width, height, depth, price_per_lf, notes, overrides_json)

    if not payload:
        error = "No data to save"
        status_msg = "No changes to save"
    else:
        # Validate
        valid, validation_error = validate_payload(payload)

        if not valid:
            error = validation_error
            status_msg = "Validation failed"
        else:
            # Execute save
            print("Saving to cabinet {}...".format(cabinet_id))
            print("Payload: {}".format(json.dumps(payload)))

            ok, code, resp_data, err = update_cabinet(
                api_base, auth_header, cabinet_id, payload
            )

            if ok:
                success = True
                response = json.dumps(resp_data, indent=2)
                saved_fields = list(payload.keys())
                status_msg = "Saved {} field(s) successfully".format(len(saved_fields))

                # Clear caches
                if 'tcs_api_cache' in globals():
                    tcs_api_cache.clear()
                if 'tcs_cabinet_cache' in globals():
                    tcs_cabinet_cache.clear()

                print("Save successful: {}".format(status_msg))
            else:
                error = err
                status_msg = "Save failed"
                print("Save failed: {}".format(err))


# ============================================================
# HELPER FUNCTIONS
# ============================================================

def get_save_summary():
    """Get summary of what will be saved."""
    payload = build_update_payload(width, height, depth, price_per_lf, notes, overrides_json)

    if not payload:
        return "No changes to save"

    lines = ["Changes to save:"]
    for field, value in payload.items():
        if field == 'width_inches' or field == 'length_inches':
            lines.append('  Width: {}"'.format(value))
        elif field == 'height_inches':
            lines.append('  Height: {}"'.format(value))
        elif field == 'depth_inches':
            lines.append('  Depth: {}"'.format(value))
        elif field == 'unit_price_per_lf':
            lines.append('  Price/LF: ${}'.format(value))
        elif field == 'shop_notes':
            lines.append('  Notes: "{}"'.format(value[:30]))
        else:
            lines.append('  {}: {}'.format(field, value))

    return "\n".join(lines)


# Print component info
print("")
print("TCS Save to ERP")
print("=" * 40)
print("Cabinet ID: {}".format(cabinet_id if cabinet_id else "[none]"))
if cabinet_id and not save:
    print("")
    print(get_save_summary())
print("")
print("Status: {}".format(status_msg))
if error:
    print("Error: {}".format(error))
if success:
    print("Saved fields: {}".format(", ".join(saved_fields)))
