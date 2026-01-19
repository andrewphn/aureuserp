"""
TCS Layer Setup Script for Rhino

Creates the TCS material layer hierarchy for V-Carve CNC nesting.
Run in Rhino: RunPythonScript tcs_layer_setup.py

Alias: tcs_setup

Layer Structure:
  TCS_Materials/
    3-4_PreFin      (3/4" Prefinished Plywood)
    3-4_Medex       (3/4" Medex MDF)
    3-4_RiftWO      (3/4" Rift White Oak)
    1-2_Baltic      (1/2" Baltic Birch)
    1-4_Plywood     (1/4" Plywood)
    5-4_Hardwood    (5/4" Hardwood)

@author TCS Woodwork
@since January 2026
"""

import rhinoscriptsyntax as rs


# ============================================================
# TCS MATERIAL LAYER CONFIGURATION
# ============================================================

TCS_MATERIAL_LAYERS = {
    '3-4_PreFin': {
        'color': (139, 90, 43),
        'description': '3/4" Prefinished Plywood',
        'thickness': 0.75,
    },
    '3-4_Medex': {
        'color': (65, 105, 225),
        'description': '3/4" Medex MDF (paint grade)',
        'thickness': 0.75,
    },
    '3-4_RiftWO': {
        'color': (210, 180, 140),
        'description': '3/4" Rift White Oak',
        'thickness': 0.75,
    },
    '1-2_Baltic': {
        'color': (255, 228, 181),
        'description': '1/2" Baltic Birch',
        'thickness': 0.5,
    },
    '1-4_Plywood': {
        'color': (240, 230, 200),
        'description': '1/4" Plywood (backs, bottoms)',
        'thickness': 0.25,
    },
    '5-4_Hardwood': {
        'color': (205, 133, 63),
        'description': '5/4" Hardwood (face frames)',
        'thickness': 1.0,
    },
}

# Secondary layer hierarchy for organization
TCS_SECONDARY_LAYERS = {
    'TCS_PartTypes': {
        'cabinet_box': (139, 90, 43),
        'face_frame': (210, 180, 140),
        'drawer_box': (255, 228, 181),
        'drawer_face': (245, 222, 179),
        'toe_kick': (105, 105, 105),
        'finished_end': (205, 133, 63),
        'stretcher': (160, 82, 45),
    },
    'TCS_Dimensions': {
        'overall': (0, 150, 255),
        'detail': (0, 100, 200),
    },
    'TCS_Annotations': {
        'labels': (50, 50, 50),
        'notes': (100, 100, 100),
    },
}


# ============================================================
# LAYER CREATION FUNCTIONS
# ============================================================

def create_layer_if_not_exists(name, color=None, parent=None):
    """Create a layer if it doesn't exist."""
    if parent:
        full_name = parent + "::" + name
    else:
        full_name = name

    if not rs.IsLayer(full_name):
        if parent and not rs.IsLayer(parent):
            rs.AddLayer(parent)

        rs.AddLayer(full_name, color)
        return True
    return False


def setup_tcs_material_layers():
    """Create TCS material layer hierarchy."""
    print("=" * 50)
    print("TCS LAYER SETUP")
    print("=" * 50)

    parent = "TCS_Materials"
    created = 0

    # Create parent layer
    if create_layer_if_not_exists(parent, (128, 128, 128)):
        print("Created parent: " + parent)
        created += 1
    else:
        print("Parent exists: " + parent)

    # Create material layers
    for name, config in TCS_MATERIAL_LAYERS.items():
        full_name = parent + "::" + name
        if create_layer_if_not_exists(name, config['color'], parent):
            print("  Created: %s (%s)" % (name, config['description']))
            created += 1
        else:
            print("  Exists: " + name)

    return created


def setup_secondary_layers():
    """Create secondary layer hierarchies for part types and annotations."""
    print("")
    print("Creating secondary layers...")
    created = 0

    for parent, children in TCS_SECONDARY_LAYERS.items():
        if create_layer_if_not_exists(parent, (128, 128, 128)):
            print("Created parent: " + parent)
            created += 1

        for name, color in children.items():
            if create_layer_if_not_exists(name, color, parent):
                print("  Created: %s::%s" % (parent, name))
                created += 1

    return created


def setup_all_layers():
    """Set up all TCS layers."""
    total = 0
    total += setup_tcs_material_layers()
    total += setup_secondary_layers()

    print("")
    print("=" * 50)
    print("TCS Layer Setup Complete: %d layers created" % total)
    print("=" * 50)

    return total


# ============================================================
# HELPER FUNCTIONS
# ============================================================

def list_tcs_layers():
    """List all TCS layers in the document."""
    all_layers = rs.LayerNames()
    tcs_layers = [l for l in all_layers if l.startswith('TCS_')]

    print("TCS Layers in document:")
    for layer in sorted(tcs_layers):
        print("  " + layer)

    return tcs_layers


def set_tcs_user_text(obj, metadata):
    """
    Set TCS metadata as Rhino User Text attributes.

    Args:
        obj: Rhino object GUID
        metadata: Dictionary of TCS_ prefixed attributes
    """
    if not obj or not metadata:
        return False

    for key, value in metadata.items():
        if value is not None:
            rs.SetUserText(obj, key, str(value))

    return True


def get_tcs_user_text(obj):
    """
    Get all TCS metadata from a Rhino object.

    Args:
        obj: Rhino object GUID

    Returns:
        Dictionary of TCS_ prefixed attributes
    """
    if not obj:
        return {}

    keys = rs.GetUserText(obj)
    if not keys:
        return {}

    metadata = {}
    for key in keys:
        if key.startswith('TCS_'):
            metadata[key] = rs.GetUserText(obj, key)

    return metadata


def assign_object_to_material_layer(obj, material_layer):
    """
    Assign an object to a TCS material layer.

    Args:
        obj: Rhino object GUID
        material_layer: Material layer name (e.g., '3-4_PreFin')
    """
    full_layer = "TCS_Materials::" + material_layer

    if not rs.IsLayer(full_layer):
        # Get config if available
        config = TCS_MATERIAL_LAYERS.get(material_layer, {'color': (128, 128, 128)})
        create_layer_if_not_exists(material_layer, config['color'], "TCS_Materials")

    rs.ObjectLayer(obj, full_layer)
    return True


# ============================================================
# MIGRATION HELPERS
# ============================================================

def get_layer_migration_map():
    """
    Get mapping from legacy layer names to TCS format.

    Returns:
        Dictionary mapping legacy names to TCS layer names
    """
    return {
        # Fraction format
        '3/4 Medex': '3-4_Medex',
        '3/4 PreFin': '3-4_PreFin',
        '3/4" Rift WO': '3-4_RiftWO',
        '3/4 Rift WO': '3-4_RiftWO',
        '1/2 Baltic': '1-2_Baltic',
        '1/4 Plywood': '1-4_Plywood',
        '5/4 Hardwood': '5-4_Hardwood',

        # With quotes
        '3/4" Medex': '3-4_Medex',
        '3/4" PreFin': '3-4_PreFin',
        '1/2" Baltic': '1-2_Baltic',
        '1/4" Plywood': '1-4_Plywood',
        '5/4" Hardwood': '5-4_Hardwood',

        # Partial matches (RiftWO variations)
        'RiftWO': '3-4_RiftWO',
        'Rift WO': '3-4_RiftWO',
    }


def analyze_legacy_layers():
    """
    Analyze document for legacy material layers that need migration.

    Returns:
        Dictionary with analysis results
    """
    all_layers = rs.LayerNames()
    migration_map = get_layer_migration_map()

    legacy = []
    tcs = []
    unmapped = []

    for layer in all_layers:
        if layer.startswith('TCS_Materials::'):
            tcs.append(layer)
        elif any(legacy_name in layer for legacy_name in migration_map.keys()):
            # Find the matching legacy name
            for legacy_name, tcs_name in migration_map.items():
                if legacy_name in layer:
                    legacy.append({
                        'current': layer,
                        'legacy_match': legacy_name,
                        'target': "TCS_Materials::" + tcs_name,
                    })
                    break
        else:
            # Check if it looks like a material layer
            if any(x in layer.lower() for x in ['ply', 'medex', 'baltic', 'hardwood', 'rift']):
                unmapped.append(layer)

    return {
        'tcs_count': len(tcs),
        'legacy_count': len(legacy),
        'unmapped_count': len(unmapped),
        'tcs_layers': tcs,
        'legacy_layers': legacy,
        'unmapped_layers': unmapped,
        'migration_needed': len(legacy) > 0,
    }


# ============================================================
# RHINO COMMAND ALIAS
# ============================================================

def register_tcs_commands():
    """
    Register TCS commands as Rhino aliases.

    After running this once, you can use:
        tcs_setup      - Set up all TCS layers
        tcs_layers     - List TCS layers
        tcs_analyze    - Analyze legacy layers for migration
    """
    try:
        import Rhino

        script_path = __file__

        # Define aliases
        aliases = {
            'tcs_setup': '_-RunPythonScript "%s"' % script_path,
            'tcs_layers': '_-RunPythonScript "%s" list' % script_path,
            'tcs_analyze': '_-RunPythonScript "%s" analyze' % script_path,
        }

        for alias, command in aliases.items():
            Rhino.ApplicationSettings.CommandAliasList.Add(alias, command)

        print("TCS commands registered:")
        print("  tcs_setup   - Set up all TCS layers")
        print("  tcs_layers  - List TCS layers")
        print("  tcs_analyze - Analyze legacy layers")

    except Exception as e:
        print("Could not register aliases: %s" % str(e))
        print("")
        print("Manual setup - add these aliases in Rhino Options > Aliases:")
        print('  tcs_setup   = _-RunPythonScript "%s"' % __file__)
        print('  tcs_layers  = _-RunPythonScript "%s" list' % __file__)
        print('  tcs_analyze = _-RunPythonScript "%s" analyze' % __file__)


# ============================================================
# MAIN
# ============================================================

if __name__ == "__main__":
    import sys

    # Check command line args
    args = sys.argv[1:] if len(sys.argv) > 1 else []

    if 'list' in args:
        list_tcs_layers()
    elif 'analyze' in args:
        result = analyze_legacy_layers()
        print("=" * 50)
        print("LEGACY LAYER ANALYSIS")
        print("=" * 50)
        print("TCS layers found: %d" % result['tcs_count'])
        print("Legacy layers to migrate: %d" % result['legacy_count'])
        print("Unmapped material layers: %d" % result['unmapped_count'])
        if result['legacy_layers']:
            print("")
            print("Legacy layers:")
            for l in result['legacy_layers']:
                print("  %s -> %s" % (l['current'], l['target']))
        if result['unmapped_layers']:
            print("")
            print("Unmapped (manual review needed):")
            for l in result['unmapped_layers']:
                print("  " + l)
    elif 'register' in args:
        register_tcs_commands()
    else:
        setup_all_layers()
        print("")
        list_tcs_layers()
        print("")
        print("Tip: Run with 'register' arg to create Rhino aliases:")
        print("  tcs_setup, tcs_layers, tcs_analyze")
