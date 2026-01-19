"""
TCS Cabinet Calculator - Grasshopper Component
GHPython component for calculating cabinet dimensions and pricing via API.

INPUTS:
    api_base: str - Base API URL from TCS API Connect
    auth_header: str - Authorization header from TCS API Connect
    cabinet_id: int - Cabinet ID from Cabinet List
    override_width: float - Override width (optional)
    override_height: float - Override height (optional)
    override_depth: float - Override depth (optional)
    calculate: bool - Trigger calculation

OUTPUTS:
    width: float - Final width (inches)
    height: float - Final height (inches)
    depth: float - Final depth (inches)
    linear_feet: float - Linear feet
    box_height: float - Internal box height
    toe_kick_height: float - Toe kick height
    unit_price: float - Price per linear foot
    total_price: float - Total price
    complexity_score: float - Complexity score
    cut_list_json: str - Full cut list as JSON
    calculation_json: str - Full calculation result as JSON
    error: str - Error message if any

USAGE IN GRASSHOPPER:
1. Connect cabinet_id from TCS Cabinet List
2. Optionally connect override sliders for dimensions
3. Toggle calculate to run calculation
4. Connect outputs to geometry generator

TCS CONSTRUCTION CONSTANTS:
    TOE_KICK_HEIGHT = 4.5"
    STRETCHER_DEPTH = 3.0"
    FACE_FRAME_STILE = 1.5"
    COMPONENT_GAP = 0.125"
"""

import urllib2
import json
import ssl

# Grasshopper component metadata
ghenv.Component.Name = "TCS Cabinet Calculator"
ghenv.Component.NickName = "TCS Calc"
ghenv.Component.Message = "v1.0"
ghenv.Component.Category = "TCS"
ghenv.Component.SubCategory = "Calculator"

# Create unverified SSL context for local dev
ssl_context = ssl.create_default_context()
ssl_context.check_hostname = False
ssl_context.verify_mode = ssl.CERT_NONE

# TCS Construction Constants
TCS_CONSTANTS = {
    'TOE_KICK_HEIGHT': 4.5,
    'STRETCHER_DEPTH': 3.0,
    'FACE_FRAME_STILE': 1.5,
    'FACE_FRAME_RAIL': 1.5,
    'COMPONENT_GAP': 0.125,
    'MATERIAL_THICKNESS': 0.75,  # 3/4" plywood
    'BACK_PANEL_THICKNESS': 0.25,  # 1/4" back
}


def fetch_cabinet(base_url, auth, cab_id):
    """
    Fetch cabinet data.
    Returns (success, cabinet_data, error_message).
    """
    try:
        url = "{}/api/v1/cabinets/{}".format(base_url, cab_id)

        request = urllib2.Request(url)
        request.add_header("Authorization", auth)
        request.add_header("Accept", "application/json")

        response = urllib2.urlopen(request, context=ssl_context, timeout=30)
        data = json.loads(response.read())

        return True, data.get('data', data), None

    except urllib2.HTTPError as e:
        return False, None, "HTTP Error {}: {}".format(e.code, str(e))
    except urllib2.URLError as e:
        return False, None, "Connection failed: {}".format(str(e.reason))
    except Exception as e:
        return False, None, "Error: {}".format(str(e))


def trigger_calculation(base_url, auth, cab_id):
    """
    Trigger cabinet calculation via API.
    Returns (success, calculation_result, error_message).
    """
    try:
        url = "{}/api/v1/cabinets/{}/calculate".format(base_url, cab_id)

        request = urllib2.Request(url, data="")
        request.get_method = lambda: 'POST'
        request.add_header("Authorization", auth)
        request.add_header("Accept", "application/json")
        request.add_header("Content-Type", "application/json")

        response = urllib2.urlopen(request, context=ssl_context, timeout=60)
        data = json.loads(response.read())

        return True, data.get('data', data), None

    except urllib2.HTTPError as e:
        error_body = ""
        try:
            error_body = e.read()
        except:
            pass
        return False, None, "HTTP Error {}: {}".format(e.code, error_body)
    except urllib2.URLError as e:
        return False, None, "Connection failed: {}".format(str(e.reason))
    except Exception as e:
        return False, None, "Error: {}".format(str(e))


def fetch_cut_list(base_url, auth, cab_id):
    """
    Fetch cabinet cut list.
    Returns (success, cut_list_data, error_message).
    """
    try:
        url = "{}/api/v1/cabinets/{}/cut-list".format(base_url, cab_id)

        request = urllib2.Request(url)
        request.add_header("Authorization", auth)
        request.add_header("Accept", "application/json")

        response = urllib2.urlopen(request, context=ssl_context, timeout=60)
        data = json.loads(response.read())

        return True, data.get('data', data), None

    except urllib2.HTTPError as e:
        return False, None, "HTTP Error {}: {}".format(e.code, str(e))
    except urllib2.URLError as e:
        return False, None, "Connection failed: {}".format(str(e.reason))
    except Exception as e:
        return False, None, "Error: {}".format(str(e))


def apply_overrides(cabinet_data, override_w, override_h, override_d):
    """Apply dimension overrides if provided."""
    dims = cabinet_data.copy()

    if override_w is not None and override_w > 0:
        dims['width_inches'] = override_w
        dims['length_inches'] = override_w

    if override_h is not None and override_h > 0:
        dims['height_inches'] = override_h

    if override_d is not None and override_d > 0:
        dims['depth_inches'] = override_d

    return dims


def calculate_derived_dimensions(ext_width, ext_height, ext_depth, cabinet_type='base'):
    """
    Calculate derived dimensions from exterior using TCS standards.
    Returns dict with all calculated dimensions.
    """
    c = TCS_CONSTANTS

    # Determine toe kick based on cabinet type
    if cabinet_type in ['wall', 'upper']:
        toe_kick = 0.0
    else:
        toe_kick = c['TOE_KICK_HEIGHT']

    # Box height = exterior height - toe kick
    box_height = ext_height - toe_kick

    # Interior width = exterior - 2x material thickness - face frame stiles
    interior_width = ext_width - (2 * c['MATERIAL_THICKNESS'])

    # Interior height = box height - stretcher - material thickness
    interior_height = box_height - c['STRETCHER_DEPTH'] - c['MATERIAL_THICKNESS']

    # Interior depth = exterior depth - face frame rail - back panel
    interior_depth = ext_depth - c['FACE_FRAME_STILE'] - c['BACK_PANEL_THICKNESS']

    # Cavity depth (for drawer/door openings)
    cavity_depth = interior_depth - c['COMPONENT_GAP']

    return {
        'exterior': {
            'width': ext_width,
            'height': ext_height,
            'depth': ext_depth,
        },
        'box': {
            'width': ext_width,
            'height': box_height,
            'depth': ext_depth,
        },
        'interior': {
            'width': interior_width,
            'height': interior_height,
            'depth': interior_depth,
        },
        'toe_kick_height': toe_kick,
        'box_height': box_height,
        'cavity_depth': cavity_depth,
        'constants': c,
    }


# ============================================================
# MAIN COMPONENT LOGIC
# ============================================================

# Initialize outputs
width = 0.0
height = 0.0
depth = 0.0
linear_feet = 0.0
box_height = 0.0
toe_kick_height = 0.0
unit_price = 0.0
total_price = 0.0
complexity_score = 0.0
cut_list_json = ""
calculation_json = ""
error = ""

# Validate inputs
if not api_base:
    error = "API base URL required"
elif not auth_header:
    error = "Auth header required"
elif not cabinet_id:
    error = "Cabinet ID required - connect TCS Cabinet List"
else:
    # Fetch cabinet data first
    ok, cabinet, err = fetch_cabinet(api_base, auth_header, cabinet_id)

    if not ok:
        error = err
    else:
        # Apply overrides
        cabinet = apply_overrides(
            cabinet,
            override_width,
            override_height,
            override_depth
        )

        # Extract base dimensions
        width = float(cabinet.get('width_inches') or cabinet.get('length_inches', 0))
        height = float(cabinet.get('height_inches', 0))
        depth = float(cabinet.get('depth_inches', 0))

        # Get cabinet type
        cabinet_type = cabinet.get('cabinet_level', cabinet.get('construction_type', 'base'))

        # Calculate derived dimensions
        derived = calculate_derived_dimensions(width, height, depth, cabinet_type)
        box_height = derived['box_height']
        toe_kick_height = derived['toe_kick_height']

        # Calculate linear feet
        linear_feet = width / 12.0

        # Extract pricing if available
        unit_price = float(cabinet.get('unit_price_per_lf', 0))
        total_price = float(cabinet.get('total_price', 0))
        complexity_score = float(cabinet.get('complexity_score', 0))

        # Store derived calculations
        calculation_json = json.dumps(derived, indent=2)

        # Trigger API calculation if requested
        if calculate:
            print("Triggering API calculation...")
            ok, calc_result, err = trigger_calculation(api_base, auth_header, cabinet_id)

            if ok:
                print("Calculation successful")
                calculation_json = json.dumps(calc_result, indent=2)

                # Extract pricing from calculation result
                pricing = calc_result.get('pricing', {})
                unit_price = float(pricing.get('unit_price_per_lf', unit_price))
                total_price = float(pricing.get('total_price', total_price))

                complexity = calc_result.get('complexity', {})
                complexity_score = float(complexity.get('score', complexity_score))

                # Fetch cut list
                ok, cut_list, err = fetch_cut_list(api_base, auth_header, cabinet_id)
                if ok:
                    cut_list_json = json.dumps(cut_list, indent=2)
                else:
                    print("Cut list fetch failed: {}".format(err))
            else:
                print("Calculation failed: {}".format(err))
                # Don't set error - local calculations still valid


# ============================================================
# HELPER FUNCTIONS
# ============================================================

def get_dimension_summary():
    """Get formatted dimension summary."""
    return '{}" W x {}" H x {}" D ({:.2f}\' LF)'.format(
        width, height, depth, linear_feet
    )


def get_pricing_summary():
    """Get formatted pricing summary."""
    return '${:.2f}/LF x {:.2f}\' = ${:.2f}'.format(
        unit_price, linear_feet, total_price
    )


# Print component info
print("")
print("TCS Cabinet Calculator")
print("=" * 40)
print("Cabinet ID: {}".format(cabinet_id if cabinet_id else "[none]"))
if width > 0:
    print("Dimensions: {}".format(get_dimension_summary()))
    print("Box Height: {}\"".format(box_height))
    print("Toe Kick: {}\"".format(toe_kick_height))
    print("")
    print("Pricing: {}".format(get_pricing_summary()))
    print("Complexity: {}".format(complexity_score))
if error:
    print("Error: {}".format(error))
