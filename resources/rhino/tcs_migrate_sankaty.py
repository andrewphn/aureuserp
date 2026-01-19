"""
TCS Sankaty Migration Script

Migrate existing Sankaty.3dm drawing to TCS layer standards.
This script:
1. Creates TCS material layer hierarchy
2. Renames legacy layers to TCS format
3. Adds TCS metadata (User Text) to objects based on groups
4. Preserves existing object colors and properties

Run in Rhino: RunPythonScript tcs_migrate_sankaty.py

IMPORTANT: Make a backup copy of Sankaty.3dm before running!

@author TCS Woodwork
@since January 2026
"""

import rhinoscriptsyntax as rs
import json


# ============================================================
# CONFIGURATION
# ============================================================

# Legacy layer name -> TCS format mapping
LAYER_MIGRATION_MAP = {
    # Standard material names
    '3/4 Medex': '3-4_Medex',
    '3/4 PreFin': '3-4_PreFin',
    '3/4" Rift WO': '3-4_RiftWO',
    '3/4 Rift WO': '3-4_RiftWO',
    '1/2 Baltic': '1-2_Baltic',
    '1/4 Plywood': '1-4_Plywood',
    '5/4 Hardwood': '5-4_Hardwood',

    # With inch marks
    '3/4" Medex': '3-4_Medex',
    '3/4" PreFin': '3-4_PreFin',
    '1/2" Baltic': '1-2_Baltic',
    '1/4" Plywood': '1-4_Plywood',
    '5/4" Hardwood': '5-4_Hardwood',

    # Partial/variant names
    'RiftWO': '3-4_RiftWO',
    'Rift WO': '3-4_RiftWO',
    'Rift White Oak': '3-4_RiftWO',
    'Medex': '3-4_Medex',
    'Baltic Birch': '1-2_Baltic',
    'Baltic': '1-2_Baltic',
}

# Material layer colors (RGB)
MATERIAL_COLORS = {
    '3-4_PreFin': (139, 90, 43),
    '3-4_Medex': (65, 105, 225),
    '3-4_RiftWO': (210, 180, 140),
    '1-2_Baltic': (255, 228, 181),
    '1-4_Plywood': (240, 230, 200),
    '5-4_Hardwood': (205, 133, 63),
}

# Default cabinet ID prefix when group name doesn't provide one
# Format matches TCS database: {TYPE_CODE}-{SEQUENCE}
DEFAULT_CABINET_PREFIX = "CAB"


# ============================================================
# LAYER MIGRATION FUNCTIONS
# ============================================================

def create_tcs_parent_layer():
    """Create the TCS_Materials parent layer."""
    parent = "TCS_Materials"
    if not rs.IsLayer(parent):
        rs.AddLayer(parent, (128, 128, 128))
        print(f"Created parent layer: {parent}")
        return True
    return False


def migrate_layers(dry_run=True):
    """
    Migrate legacy layer names to TCS format.

    Args:
        dry_run: If True, only report what would change without making changes.

    Returns:
        Dictionary with migration results
    """
    all_layers = rs.LayerNames()
    migrated = []
    skipped = []
    errors = []

    print("")
    print("=" * 60)
    print("LAYER MIGRATION" + (" (DRY RUN)" if dry_run else ""))
    print("=" * 60)

    # Ensure parent exists
    if not dry_run:
        create_tcs_parent_layer()

    for layer in all_layers:
        # Skip if already TCS format
        if layer.startswith('TCS_Materials::'):
            skipped.append(layer)
            continue

        # Check if layer matches any migration pattern
        matched = False
        for legacy_pattern, tcs_name in LAYER_MIGRATION_MAP.items():
            if legacy_pattern.lower() in layer.lower() or layer.lower() == legacy_pattern.lower():
                new_layer = f"TCS_Materials::{tcs_name}"

                if dry_run:
                    print(f"  WOULD RENAME: '{layer}' -> '{new_layer}'")
                    migrated.append({
                        'old': layer,
                        'new': new_layer,
                        'tcs_name': tcs_name,
                    })
                else:
                    try:
                        # Create new layer if needed
                        if not rs.IsLayer(new_layer):
                            color = MATERIAL_COLORS.get(tcs_name, (128, 128, 128))
                            rs.AddLayer(new_layer, color)

                        # Move objects from old to new layer
                        objects = rs.ObjectsByLayer(layer)
                        if objects:
                            for obj in objects:
                                rs.ObjectLayer(obj, new_layer)
                            print(f"  MIGRATED: '{layer}' -> '{new_layer}' ({len(objects)} objects)")
                        else:
                            print(f"  RENAMED: '{layer}' -> '{new_layer}' (empty layer)")

                        # Optionally delete old layer if empty
                        remaining = rs.ObjectsByLayer(layer)
                        if not remaining:
                            rs.DeleteLayer(layer)

                        migrated.append({
                            'old': layer,
                            'new': new_layer,
                            'tcs_name': tcs_name,
                            'object_count': len(objects) if objects else 0,
                        })
                    except Exception as e:
                        errors.append({
                            'layer': layer,
                            'error': str(e),
                        })
                        print(f"  ERROR migrating '{layer}': {e}")

                matched = True
                break

        if not matched and not layer.startswith('TCS_'):
            # Check if it looks like a material layer we don't recognize
            material_keywords = ['ply', 'medex', 'baltic', 'hardwood', 'rift', 'mdf', 'oak']
            if any(kw in layer.lower() for kw in material_keywords):
                print(f"  UNMAPPED MATERIAL LAYER: '{layer}'")

    return {
        'migrated': migrated,
        'skipped': skipped,
        'errors': errors,
        'dry_run': dry_run,
    }


# ============================================================
# METADATA FUNCTIONS
# ============================================================

def is_cabinet_id_format(name):
    """
    Check if a name follows TCS cabinet ID format.

    Valid formats: B36-001, W3030-002, T2484-001, VAN36-001
    Pattern: {TYPE_CODE}-{SEQUENCE}

    Args:
        name: String to check

    Returns:
        True if matches cabinet ID format
    """
    import re
    # Cabinet type codes: B (base), W (wall), T (tall), VAN (vanity)
    # Followed by dimensions and sequence number
    pattern = r'^(B|W|T|VAN)\d+(-\d+)?$'
    return bool(re.match(pattern, name, re.IGNORECASE))


def infer_part_type_from_name(name):
    """
    Infer part type from object or group name.

    Args:
        name: Object or group name

    Returns:
        Part type string
    """
    name_lower = name.lower() if name else ""

    if 'side' in name_lower:
        if 'drawer' in name_lower:
            return 'drawer_box'
        return 'cabinet_box'
    if 'bottom' in name_lower:
        if 'drawer' in name_lower:
            return 'drawer_box_bottom'
        return 'cabinet_box'
    if 'back' in name_lower:
        if 'drawer' in name_lower:
            return 'drawer_box'
        return 'cabinet_box'
    if 'front' in name_lower:
        if 'drawer' in name_lower:
            return 'drawer_box'
        return 'cabinet_box'
    if 'face frame' in name_lower or 'stile' in name_lower or 'rail' in name_lower:
        return 'face_frame'
    if 'stretcher' in name_lower:
        return 'stretcher'
    if 'toe kick' in name_lower or 'toekick' in name_lower:
        return 'toe_kick'
    if 'drawer face' in name_lower or 'drawer front' in name_lower:
        return 'drawer_face'
    if 'end panel' in name_lower or 'finished end' in name_lower:
        return 'finished_end'
    if 'shelf' in name_lower:
        return 'shelf'
    if 'divider' in name_lower:
        return 'divider'

    return 'cabinet_box'  # Default


def infer_material_from_layer(layer):
    """
    Infer material from layer name.

    Args:
        layer: Layer name

    Returns:
        TCS material layer name or None
    """
    if layer.startswith('TCS_Materials::'):
        return layer.replace('TCS_Materials::', '')

    layer_lower = layer.lower()

    if 'medex' in layer_lower:
        return '3-4_Medex'
    if 'rift' in layer_lower:
        return '3-4_RiftWO'
    if 'baltic' in layer_lower:
        return '1-2_Baltic'
    if '1/4' in layer_lower or '1-4' in layer_lower:
        return '1-4_Plywood'
    if '5/4' in layer_lower or '5-4' in layer_lower or 'hardwood' in layer_lower:
        return '5-4_Hardwood'
    if 'prefin' in layer_lower or 'prefinish' in layer_lower:
        return '3-4_PreFin'

    return '3-4_PreFin'  # Default


def add_tcs_metadata_to_objects(dry_run=True):
    """
    Add TCS metadata to objects based on groups and names.

    Args:
        dry_run: If True, only report what would change.

    Returns:
        Dictionary with results
    """
    print("")
    print("=" * 60)
    print("METADATA ASSIGNMENT" + (" (DRY RUN)" if dry_run else ""))
    print("=" * 60)

    all_objects = rs.AllObjects()
    updated = []
    skipped = []

    if not all_objects:
        print("No objects in document")
        return {'updated': updated, 'skipped': skipped, 'dry_run': dry_run}

    for obj in all_objects:
        name = rs.ObjectName(obj) or ""
        layer = rs.ObjectLayer(obj) or ""
        groups = rs.ObjectGroups(obj) or []

        # Get existing TCS metadata
        existing_keys = rs.GetUserText(obj) or []
        has_tcs_metadata = any(k.startswith('TCS_') for k in existing_keys)

        if has_tcs_metadata:
            skipped.append({
                'guid': str(obj),
                'name': name,
                'reason': 'already has TCS metadata',
            })
            continue

        # Build cabinet ID from group name
        # TCS convention: {TYPE_CODE}-{SEQUENCE} like B36-001, W3030-002
        cabinet_id = None
        if groups:
            for group in groups:
                # Check if group looks like a cabinet ID (e.g., B36-001, W3030-002)
                if is_cabinet_id_format(group):
                    cabinet_id = group
                    break
            if not cabinet_id:
                # Use first group as cabinet ID
                cabinet_id = groups[0]

        if not cabinet_id:
            cabinet_id = f"{DEFAULT_CABINET_PREFIX}-UNKNOWN"

        # Infer part type and material
        part_type = infer_part_type_from_name(name)
        material = infer_material_from_layer(layer)

        # Build part ID
        sanitized_name = name.replace(' ', '_').replace('-', '_') if name else 'part'
        part_id = f"{cabinet_id}-{sanitized_name}"

        # Build metadata
        metadata = {
            'TCS_PART_ID': part_id,
            'TCS_CABINET_ID': cabinet_id,
            'TCS_PART_TYPE': part_type,
            'TCS_PART_NAME': name or 'Unknown',
            'TCS_MATERIAL': material,
        }

        if dry_run:
            print(f"  WOULD ADD METADATA to '{name or str(obj)[:8]}...':")
            print(f"    Cabinet: {cabinet_id}, Type: {part_type}, Material: {material}")
            updated.append({
                'guid': str(obj),
                'name': name,
                'metadata': metadata,
            })
        else:
            try:
                for key, value in metadata.items():
                    rs.SetUserText(obj, key, str(value))
                print(f"  ADDED METADATA: '{name or str(obj)[:8]}...' ({part_type})")
                updated.append({
                    'guid': str(obj),
                    'name': name,
                    'metadata': metadata,
                })
            except Exception as e:
                print(f"  ERROR on '{name}': {e}")

    print("")
    print(f"Updated: {len(updated)} objects")
    print(f"Skipped: {len(skipped)} objects (already had metadata)")

    return {
        'updated': updated,
        'skipped': skipped,
        'dry_run': dry_run,
    }


# ============================================================
# VERIFICATION FUNCTIONS
# ============================================================

def verify_migration():
    """
    Verify the migration was successful.

    Returns:
        Dictionary with verification results
    """
    print("")
    print("=" * 60)
    print("MIGRATION VERIFICATION")
    print("=" * 60)

    # Check layers
    all_layers = rs.LayerNames()
    tcs_layers = [l for l in all_layers if l.startswith('TCS_Materials::')]
    legacy_layers = []

    for layer in all_layers:
        if not layer.startswith('TCS_'):
            for pattern in LAYER_MIGRATION_MAP.keys():
                if pattern.lower() in layer.lower():
                    legacy_layers.append(layer)
                    break

    print(f"TCS Material Layers: {len(tcs_layers)}")
    for layer in tcs_layers:
        obj_count = len(rs.ObjectsByLayer(layer) or [])
        print(f"  {layer}: {obj_count} objects")

    if legacy_layers:
        print(f"\nLegacy Layers Still Present: {len(legacy_layers)}")
        for layer in legacy_layers:
            print(f"  {layer}")

    # Check metadata
    all_objects = rs.AllObjects() or []
    with_metadata = 0
    without_metadata = 0

    for obj in all_objects:
        keys = rs.GetUserText(obj) or []
        if any(k.startswith('TCS_') for k in keys):
            with_metadata += 1
        else:
            without_metadata += 1

    print(f"\nObjects with TCS metadata: {with_metadata}")
    print(f"Objects without TCS metadata: {without_metadata}")

    return {
        'tcs_layer_count': len(tcs_layers),
        'legacy_layer_count': len(legacy_layers),
        'objects_with_metadata': with_metadata,
        'objects_without_metadata': without_metadata,
        'success': len(legacy_layers) == 0 and without_metadata == 0,
    }


def generate_migration_report():
    """Generate a JSON report of the migration status."""
    report = {
        'project': 'Sankaty',
        'layers': [],
        'metadata_coverage': {},
    }

    all_layers = rs.LayerNames()
    for layer in all_layers:
        if layer.startswith('TCS_Materials::'):
            objects = rs.ObjectsByLayer(layer) or []
            report['layers'].append({
                'name': layer,
                'object_count': len(objects),
                'format': 'tcs',
            })
        elif any(p.lower() in layer.lower() for p in LAYER_MIGRATION_MAP.keys()):
            objects = rs.ObjectsByLayer(layer) or []
            report['layers'].append({
                'name': layer,
                'object_count': len(objects),
                'format': 'legacy',
                'needs_migration': True,
            })

    all_objects = rs.AllObjects() or []
    for obj in all_objects:
        keys = rs.GetUserText(obj) or []
        cabinet_id = None
        for key in keys:
            if key == 'TCS_CABINET_ID':
                cabinet_id = rs.GetUserText(obj, key)
                break

        if cabinet_id:
            if cabinet_id not in report['metadata_coverage']:
                report['metadata_coverage'][cabinet_id] = 0
            report['metadata_coverage'][cabinet_id] += 1

    print("")
    print("=" * 60)
    print("MIGRATION REPORT (JSON)")
    print("=" * 60)
    print(json.dumps(report, indent=2))

    return report


# ============================================================
# MAIN ENTRY POINTS
# ============================================================

def dry_run():
    """
    Perform a dry run of the migration without making changes.

    Call this first to see what would be changed.
    """
    print("=" * 60)
    print("TCS SANKATY MIGRATION - DRY RUN")
    print("=" * 60)
    print("This will show what changes would be made without applying them.")
    print("")

    migrate_layers(dry_run=True)
    add_tcs_metadata_to_objects(dry_run=True)

    print("")
    print("Dry run complete. Review the above and run migrate() to apply changes.")


def migrate():
    """
    Perform the actual migration.

    WARNING: This modifies the document! Make a backup first!
    """
    print("=" * 60)
    print("TCS SANKATY MIGRATION")
    print("=" * 60)
    print("WARNING: This will modify the document!")
    print("Make sure you have a backup of Sankaty.3dm!")
    print("")

    # Ask for confirmation
    result = rs.MessageBox(
        "This will migrate layers and add TCS metadata to all objects.\n\n"
        "Make sure you have a BACKUP before proceeding!\n\n"
        "Continue with migration?",
        1 + 48,  # Yes/No + Warning icon
        "TCS Migration"
    )

    if result != 6:  # 6 = Yes
        print("Migration cancelled.")
        return

    migrate_layers(dry_run=False)
    add_tcs_metadata_to_objects(dry_run=False)
    verify_migration()

    print("")
    print("=" * 60)
    print("MIGRATION COMPLETE")
    print("=" * 60)
    print("Don't forget to save the file!")


def quick_migrate():
    """
    Perform migration without confirmation prompt.

    Use with caution - no backup check!
    """
    migrate_layers(dry_run=False)
    add_tcs_metadata_to_objects(dry_run=False)
    verify_migration()


# ============================================================
# SCRIPT EXECUTION
# ============================================================

if __name__ == "__main__":
    # Default to dry run for safety
    dry_run()
    print("")
    print("To apply changes, run: migrate()")
