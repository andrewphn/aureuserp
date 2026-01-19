"""
TCS Override Manager - Grasshopper Component
GHPython component for managing dimension and pricing overrides.

INPUTS:
    cabinet_id: int - Cabinet ID to manage overrides for
    override_width: float - Override width (0 to use calculated)
    override_height: float - Override height (0 to use calculated)
    override_depth: float - Override depth (0 to use calculated)
    override_price_lf: float - Override price per linear foot
    shelf_qty_override: int - Override shelf quantity
    drawer_qty_override: int - Override drawer quantity
    apply: bool - Apply overrides to persistent storage
    reset: bool - Reset all overrides for this cabinet

OUTPUTS:
    active_overrides: str - JSON of currently active overrides
    override_count: int - Number of active overrides
    width_overridden: bool - Width is overridden
    height_overridden: bool - Height is overridden
    depth_overridden: bool - Depth is overridden
    price_overridden: bool - Price is overridden
    effective_width: float - Width to use (override or original)
    effective_height: float - Height to use
    effective_depth: float - Depth to use
    effective_price_lf: float - Price per LF to use
    status_msg: str - Status message

USAGE IN GRASSHOPPER:
1. Connect cabinet_id from Cabinet List
2. Connect sliders for override values
3. Toggle apply to save overrides to document storage
4. Toggle reset to clear all overrides

STORAGE:
Overrides are stored in Rhino document user text under key:
    "TCS_OVERRIDES_{cabinet_id}"
This persists with the Rhino file.
"""

import json
import time

# Grasshopper component metadata
ghenv.Component.Name = "TCS Override Manager"
ghenv.Component.NickName = "TCS Overrides"
ghenv.Component.Message = "v1.0"
ghenv.Component.Category = "TCS"
ghenv.Component.SubCategory = "Calculator"

# Try to import Rhino for document storage
try:
    import Rhino
    import scriptcontext as sc
    HAS_RHINO = True
except ImportError:
    HAS_RHINO = False


def get_storage_key(cab_id):
    """Get document storage key for cabinet overrides."""
    return "TCS_OVERRIDES_{}".format(cab_id)


def load_overrides_from_document(cab_id):
    """
    Load overrides from Rhino document user text.
    Returns dict of overrides or empty dict.
    """
    if not HAS_RHINO:
        return {}

    try:
        key = get_storage_key(cab_id)
        doc = Rhino.RhinoDoc.ActiveDoc

        if doc:
            stored = doc.Strings.GetValue(key)
            if stored:
                return json.loads(stored)
    except:
        pass

    return {}


def save_overrides_to_document(cab_id, overrides):
    """
    Save overrides to Rhino document user text.
    Returns True on success.
    """
    if not HAS_RHINO:
        return False

    try:
        key = get_storage_key(cab_id)
        doc = Rhino.RhinoDoc.ActiveDoc

        if doc:
            if overrides:
                doc.Strings.SetString(key, json.dumps(overrides))
            else:
                doc.Strings.Delete(key)
            return True
    except:
        pass

    return False


def delete_overrides_from_document(cab_id):
    """Delete overrides from document storage."""
    return save_overrides_to_document(cab_id, None)


def build_overrides_dict(w, h, d, price_lf, shelf_qty, drawer_qty):
    """Build overrides dict from input values."""
    overrides = {}

    if w is not None and w > 0:
        overrides['width'] = float(w)

    if h is not None and h > 0:
        overrides['height'] = float(h)

    if d is not None and d > 0:
        overrides['depth'] = float(d)

    if price_lf is not None and price_lf > 0:
        overrides['price_per_lf'] = float(price_lf)

    if shelf_qty is not None and shelf_qty >= 0:
        overrides['shelf_quantity'] = int(shelf_qty)

    if drawer_qty is not None and drawer_qty >= 0:
        overrides['drawer_quantity'] = int(drawer_qty)

    if overrides:
        overrides['timestamp'] = time.strftime('%Y-%m-%d %H:%M:%S')

    return overrides


def merge_overrides(stored, current):
    """Merge current overrides with stored ones."""
    merged = stored.copy()
    merged.update(current)

    # Remove zero values (means "use default")
    keys_to_remove = [k for k, v in merged.items() if v == 0 and k != 'timestamp']
    for k in keys_to_remove:
        del merged[k]

    return merged


# ============================================================
# STICKY DICT FOR SESSION OVERRIDES
# ============================================================

if 'tcs_session_overrides' not in globals():
    tcs_session_overrides = {}


# ============================================================
# MAIN COMPONENT LOGIC
# ============================================================

# Initialize outputs
active_overrides = "{}"
override_count = 0
width_overridden = False
height_overridden = False
depth_overridden = False
price_overridden = False
effective_width = 0.0
effective_height = 0.0
effective_depth = 0.0
effective_price_lf = 0.0
status_msg = "No cabinet selected"

if cabinet_id:
    # Handle reset first
    if reset:
        delete_overrides_from_document(cabinet_id)
        if cabinet_id in tcs_session_overrides:
            del tcs_session_overrides[cabinet_id]
        status_msg = "Overrides reset for cabinet {}".format(cabinet_id)
        print(status_msg)
    else:
        # Load stored overrides
        stored_overrides = load_overrides_from_document(cabinet_id)

        # Build current overrides from inputs
        current_overrides = build_overrides_dict(
            override_width,
            override_height,
            override_depth,
            override_price_lf,
            shelf_qty_override,
            drawer_qty_override
        )

        # Merge with stored
        all_overrides = merge_overrides(stored_overrides, current_overrides)

        # Apply to document if requested
        if apply and current_overrides:
            save_overrides_to_document(cabinet_id, all_overrides)
            status_msg = "Saved {} overrides for cabinet {}".format(
                len(all_overrides) - 1 if 'timestamp' in all_overrides else len(all_overrides),
                cabinet_id
            )
            print(status_msg)
        else:
            status_msg = "Overrides loaded for cabinet {}".format(cabinet_id)

        # Store in session
        tcs_session_overrides[cabinet_id] = all_overrides

        # Set outputs
        active_overrides = json.dumps(all_overrides, indent=2)
        override_count = len([k for k in all_overrides.keys() if k != 'timestamp'])

        width_overridden = 'width' in all_overrides
        height_overridden = 'height' in all_overrides
        depth_overridden = 'depth' in all_overrides
        price_overridden = 'price_per_lf' in all_overrides

        effective_width = all_overrides.get('width', 0.0)
        effective_height = all_overrides.get('height', 0.0)
        effective_depth = all_overrides.get('depth', 0.0)
        effective_price_lf = all_overrides.get('price_per_lf', 0.0)


# ============================================================
# HELPER FUNCTIONS
# ============================================================

def get_override_summary():
    """Get human-readable override summary."""
    if override_count == 0:
        return "No overrides active"

    parts = []
    if width_overridden:
        parts.append('Width: {}"'.format(effective_width))
    if height_overridden:
        parts.append('Height: {}"'.format(effective_height))
    if depth_overridden:
        parts.append('Depth: {}"'.format(effective_depth))
    if price_overridden:
        parts.append('Price: ${}/LF'.format(effective_price_lf))

    return ", ".join(parts)


def get_all_cabinet_overrides():
    """Get all stored overrides across all cabinets."""
    if not HAS_RHINO:
        return {}

    try:
        doc = Rhino.RhinoDoc.ActiveDoc
        if not doc:
            return {}

        all_overrides = {}
        # Iterate through all string keys
        for i in range(doc.Strings.Count):
            key = doc.Strings.GetKey(i)
            if key and key.startswith("TCS_OVERRIDES_"):
                cab_id = key.replace("TCS_OVERRIDES_", "")
                value = doc.Strings.GetValue(key)
                if value:
                    try:
                        all_overrides[cab_id] = json.loads(value)
                    except:
                        pass

        return all_overrides
    except:
        return {}


def clear_all_overrides():
    """Clear all cabinet overrides from document."""
    if not HAS_RHINO:
        return 0

    try:
        doc = Rhino.RhinoDoc.ActiveDoc
        if not doc:
            return 0

        keys_to_delete = []
        for i in range(doc.Strings.Count):
            key = doc.Strings.GetKey(i)
            if key and key.startswith("TCS_OVERRIDES_"):
                keys_to_delete.append(key)

        for key in keys_to_delete:
            doc.Strings.Delete(key)

        return len(keys_to_delete)
    except:
        return 0


# Print component info
print("")
print("TCS Override Manager")
print("=" * 40)
print("Cabinet ID: {}".format(cabinet_id if cabinet_id else "[none]"))
print("Active Overrides: {}".format(override_count))
if override_count > 0:
    print(get_override_summary())
print("")
print("Status: {}".format(status_msg))
if not HAS_RHINO:
    print("Warning: Rhino not available - overrides not persisted")
