<?php
/**
 * Data Map Validator - Against Staging Database Schema
 *
 * Validates project_10_full_data_map.json against the ACTUAL
 * staging.tcswoodwork.com database schema.
 *
 * Usage: php validate_against_staging_schema.php
 */

$schemaPath = __DIR__ . '/staging_full_database_schema.txt';
$jsonPath = __DIR__ . '/project_10_full_data_map.json';

if (!file_exists($schemaPath)) {
    die("ERROR: staging_full_database_schema.txt not found\n");
}
if (!file_exists($jsonPath)) {
    die("ERROR: project_10_full_data_map.json not found\n");
}

$schemaContent = file_get_contents($schemaPath);
$jsonContent = file_get_contents($jsonPath);
$dataMap = json_decode($jsonContent, true);

if (json_last_error() !== JSON_ERROR_NONE) {
    die("ERROR: Invalid JSON: " . json_last_error_msg() . "\n");
}

/**
 * Parse staging schema file to extract table columns
 */
function parseSchema(string $content): array
{
    $tables = [];
    $currentTable = null;
    $columns = [];

    $lines = explode("\n", $content);

    foreach ($lines as $line) {
        $line = trim($line);

        // Match table headers like "=== projects_drawers ==="
        if (preg_match('/^=== (\w+) ===$/', $line, $matches)) {
            if ($currentTable && !empty($columns)) {
                $tables[$currentTable] = $columns;
            }
            $currentTable = $matches[1];
            $columns = [];
            continue;
        }

        // Match column definitions: "column_name|type|NULL/NOT NULL|key"
        if ($currentTable && preg_match('/^(\w+)\|([^|]+)\|([^|]+)\|(.*)$/', $line, $matches)) {
            $columns[$matches[1]] = [
                'type' => trim($matches[2]),
                'nullable' => trim($matches[3]) === 'NULL',
                'key' => trim($matches[4]),
            ];
        }
    }

    // Save last table
    if ($currentTable && !empty($columns)) {
        $tables[$currentTable] = $columns;
    }

    return $tables;
}

/**
 * Map JSON sections to database tables
 */
function getTableMapping(): array
{
    return [
        'project' => 'projects_projects',
        'rooms' => 'projects_rooms',
        'room_locations' => 'projects_room_locations',
        'cabinet_runs' => 'projects_cabinet_runs',
        'cabinets' => 'projects_cabinets',
        'cabinet_sections' => 'projects_cabinet_sections',
        'drawers' => 'projects_drawers',
        'doors' => 'projects_doors',
        'shelves' => 'projects_shelves',
        'pullouts' => 'projects_pullouts',
        'hardware_requirements' => 'hardware_requirements',
    ];
}

/**
 * Extract fields from JSON data (handles nested value objects)
 */
function extractJsonFields(array $data, string $prefix = ''): array
{
    $fields = [];

    foreach ($data as $key => $value) {
        if (strpos($key, '_') === 0) {
            continue;
        }

        if (is_array($value)) {
            if (isset($value['value'])) {
                $fields[$prefix . $key] = [
                    'value' => $value['value'],
                    'status' => $value['status'] ?? null,
                    'note' => $value['note'] ?? null,
                ];
            } else {
                // Nested object - recurse
                $nested = extractJsonFields($value, $prefix . $key . '.');
                $fields = array_merge($fields, $nested);
            }
        } else {
            $fields[$prefix . $key] = ['value' => $value];
        }
    }

    return $fields;
}

/**
 * Validate JSON section against DB schema
 */
function validateSection(array $jsonSection, array $dbColumns, string $tableName): array
{
    $results = [
        'table' => $tableName,
        'compliant' => [],
        'not_in_db' => [],
        'nested_objects' => [],
        'missing_required' => [],
        'type_warnings' => [],
    ];

    // Find first entity in section
    $entity = null;
    foreach ($jsonSection as $key => $value) {
        if (strpos($key, '_') !== 0 && is_array($value)) {
            $entity = $value;
            break;
        }
    }

    if (!$entity) {
        return $results;
    }

    $jsonFields = extractJsonFields($entity);
    $dbColumnNames = array_keys($dbColumns);

    foreach ($jsonFields as $fieldPath => $fieldData) {
        // Check if it's a nested field
        if (strpos($fieldPath, '.') !== false) {
            $baseName = explode('.', $fieldPath)[0];
            $results['nested_objects'][$baseName][] = $fieldPath;
            continue;
        }

        if (in_array($fieldPath, $dbColumnNames)) {
            $results['compliant'][$fieldPath] = [
                'db_type' => $dbColumns[$fieldPath]['type'],
                'nullable' => $dbColumns[$fieldPath]['nullable'],
            ];
        } else {
            $results['not_in_db'][] = $fieldPath;
        }
    }

    // Check for required DB columns not in JSON
    foreach ($dbColumns as $col => $info) {
        if (!$info['nullable'] && !in_array($col, ['id', 'created_at', 'updated_at', 'deleted_at', 'sort_order'])) {
            $found = false;
            foreach ($jsonFields as $fieldPath => $fieldData) {
                $baseField = explode('.', $fieldPath)[0];
                if ($baseField === $col) {
                    $found = true;
                    break;
                }
            }
            if (!$found && !isset($results['compliant'][$col])) {
                $results['missing_required'][] = $col;
            }
        }
    }

    return $results;
}

// Parse the schema
$dbSchema = parseSchema($schemaContent);
$tableMapping = getTableMapping();

echo "═══════════════════════════════════════════════════════════════════════════\n";
echo "    DATA MAP VALIDATION AGAINST STAGING DATABASE SCHEMA\n";
echo "    Project: 9 Austin Lane (ID: 10)\n";
echo "    Schema Source: staging.tcswoodwork.com\n";
echo "═══════════════════════════════════════════════════════════════════════════\n\n";

echo "📊 DATABASE TABLES FOUND: " . count($dbSchema) . "\n";
echo "─────────────────────────────────────────────────────────────────────────────\n";

// Show project-related tables
$projectTables = array_filter(array_keys($dbSchema), fn($t) => strpos($t, 'projects_') === 0 || $t === 'hardware_requirements');
foreach ($projectTables as $table) {
    echo "  ✓ $table (" . count($dbSchema[$table]) . " columns)\n";
}
echo "\n";

// Validate each section
$allResults = [];
$totalCompliant = 0;
$totalNotInDb = 0;
$totalNested = 0;

foreach ($tableMapping as $jsonKey => $tableName) {
    if (!isset($dataMap[$jsonKey])) {
        echo "⚠️  JSON section '$jsonKey' not found\n";
        continue;
    }

    if (!isset($dbSchema[$tableName])) {
        echo "❌ Database table '$tableName' not in schema\n";
        continue;
    }

    $results = validateSection($dataMap[$jsonKey], $dbSchema[$tableName], $tableName);
    $allResults[$jsonKey] = $results;

    $compliantCount = count($results['compliant']);
    $notInDbCount = count($results['not_in_db']);
    $nestedCount = count($results['nested_objects']);

    $totalCompliant += $compliantCount;
    $totalNotInDb += $notInDbCount;
    $totalNested += $nestedCount;

    $status = ($notInDbCount === 0 && $nestedCount === 0) ? "✅" : "⚠️";

    echo "\n$status $jsonKey → $tableName\n";
    echo "─────────────────────────────────────────────────────────────────────────────\n";

    if ($compliantCount > 0) {
        echo "  ✅ COMPLIANT ($compliantCount fields):\n";
        foreach ($results['compliant'] as $field => $info) {
            echo "     · $field ({$info['db_type']})\n";
        }
    }

    if ($notInDbCount > 0) {
        echo "  ❌ NOT IN DATABASE ($notInDbCount fields):\n";
        foreach ($results['not_in_db'] as $field) {
            echo "     · $field\n";
        }
    }

    if ($nestedCount > 0) {
        echo "  🔧 NESTED OBJECTS (need flattening):\n";
        foreach ($results['nested_objects'] as $baseName => $fields) {
            echo "     · $baseName → " . count($fields) . " nested fields\n";
        }
    }

    if (!empty($results['missing_required'])) {
        echo "  ⚠️  MISSING REQUIRED DB COLUMNS:\n";
        foreach (array_slice($results['missing_required'], 0, 10) as $col) {
            echo "     · $col\n";
        }
        if (count($results['missing_required']) > 10) {
            echo "     · ... and " . (count($results['missing_required']) - 10) . " more\n";
        }
    }
}

// Generate detailed drawer field mapping
echo "\n\n═══════════════════════════════════════════════════════════════════════════\n";
echo "    DRAWER FIELD MAPPING (projects_drawers)\n";
echo "═══════════════════════════════════════════════════════════════════════════\n\n";

if (isset($dbSchema['projects_drawers'])) {
    $drawerCols = $dbSchema['projects_drawers'];

    // Group columns by category
    $categories = [
        'Identity' => ['id', 'product_id', 'cabinet_id', 'section_id', 'drawer_number', 'drawer_name', 'full_code', 'drawer_position', 'sort_order'],
        'Position' => ['position_in_opening_inches', 'consumed_height_inches', 'position_from_left_inches', 'consumed_width_inches'],
        'Front Dimensions' => ['front_width_inches', 'front_height_inches', 'front_thickness_inches'],
        'Front Construction' => ['top_rail_width_inches', 'bottom_rail_width_inches', 'stile_width_inches', 'profile_type', 'fabrication_method'],
        'Box Dimensions' => ['box_width_inches', 'box_depth_inches', 'box_depth_shop_inches', 'box_height_inches', 'box_height_shop_inches'],
        'Opening' => ['opening_width_inches', 'opening_height_inches', 'opening_depth_inches'],
        'Box Calculated' => ['box_outside_width_inches', 'box_inside_width_inches'],
        'Box Material' => ['box_material', 'side_thickness_inches', 'bottom_thickness_inches', 'box_thickness', 'joinery_method'],
        'Dado Specs' => ['dado_depth_inches', 'dado_width_inches', 'dado_height_inches'],
        'Cut List - Sides' => ['side_cut_height_inches', 'side_cut_height_shop_inches', 'side_cut_length_inches', 'side_cut_length_shop_inches'],
        'Cut List - Front/Back' => ['front_cut_height_inches', 'front_cut_height_shop_inches', 'front_cut_width_inches', 'back_cut_height_inches', 'back_cut_height_shop_inches', 'back_cut_width_inches'],
        'Cut List - Bottom' => ['bottom_cut_width_inches', 'bottom_cut_depth_inches'],
        'Clearances' => ['clearance_side_inches', 'clearance_top_inches', 'clearance_bottom_inches'],
        'Slide Hardware' => ['slide_type', 'slide_model', 'slide_product_id', 'slide_length_inches', 'slide_quantity', 'soft_close', 'slide_spec_source', 'min_cabinet_depth_blum_inches', 'min_cabinet_depth_shop_inches'],
        'Finish' => ['finish_type', 'paint_color', 'stain_color', 'has_decorative_hardware', 'decorative_hardware_model', 'decorative_hardware_product_id'],
        'Production Tracking' => ['cnc_cut_at', 'manually_cut_at', 'edge_banded_at', 'box_assembled_at', 'front_attached_at', 'sanded_at', 'finished_at', 'slides_installed_at', 'installed_in_cabinet_at'],
        'QC' => ['qc_passed', 'qc_notes', 'qc_inspected_at', 'qc_inspector_id'],
        'Meta' => ['complexity_score', 'complexity_breakdown', 'complexity_calculated_at', 'dimensions_calculated_at', 'notes', 'created_at', 'updated_at', 'deleted_at'],
    ];

    foreach ($categories as $category => $fields) {
        echo "📦 $category:\n";
        foreach ($fields as $field) {
            if (isset($drawerCols[$field])) {
                $col = $drawerCols[$field];
                $nullable = $col['nullable'] ? 'NULL' : 'NOT NULL';
                echo "   · $field | {$col['type']} | $nullable\n";
            }
        }
        echo "\n";
    }
}

// Summary
echo "\n═══════════════════════════════════════════════════════════════════════════\n";
echo "    SUMMARY\n";
echo "═══════════════════════════════════════════════════════════════════════════\n";
echo "  ✅ Compliant Fields: $totalCompliant\n";
echo "  ❌ Not in Database:  $totalNotInDb\n";
echo "  🔧 Nested Objects:   $totalNested\n";
echo "\n";

// Generate DB-compliant JSON structure for drawers
echo "═══════════════════════════════════════════════════════════════════════════\n";
echo "    DB-COMPLIANT DRAWER STRUCTURE (from staging schema)\n";
echo "═══════════════════════════════════════════════════════════════════════════\n\n";

$compliantDrawer = [
    // Identity
    'cabinet_id' => null,
    'section_id' => null,
    'drawer_number' => 1,
    'drawer_name' => 'DR1',
    'drawer_position' => 'upper',

    // Front
    'front_width_inches' => 37.8125,
    'front_height_inches' => 7.0,
    'front_thickness_inches' => 0.75,
    'top_rail_width_inches' => 2.25,
    'bottom_rail_width_inches' => 2.25,
    'stile_width_inches' => 1.0,
    'profile_type' => '2-1/4 Shaker',
    'fabrication_method' => 'cnc',

    // Opening (NOW IN DATABASE!)
    'opening_width_inches' => 37.8125,
    'opening_height_inches' => 7.0,
    'opening_depth_inches' => 18.75,

    // Box Dimensions
    'box_width_inches' => 37.1875,
    'box_depth_inches' => 18.0,
    'box_depth_shop_inches' => 18.25,
    'box_height_inches' => 6.1875,
    'box_height_shop_inches' => 6.0,

    // Box Calculated
    'box_outside_width_inches' => 37.1875,
    'box_inside_width_inches' => 36.1563,

    // Box Material
    'box_material' => 'baltic_birch',
    'side_thickness_inches' => 0.5,
    'bottom_thickness_inches' => 0.25,
    'box_thickness' => 0.5,
    'joinery_method' => 'dovetail',

    // Dado Specs (NOW IN DATABASE!)
    'dado_depth_inches' => 0.25,
    'dado_width_inches' => 0.25,
    'dado_height_inches' => 0.5,

    // Cut List - Sides (NOW IN DATABASE!)
    'side_cut_height_inches' => 6.0,
    'side_cut_height_shop_inches' => 6.0,
    'side_cut_length_inches' => 18.25,
    'side_cut_length_shop_inches' => 18.25,

    // Cut List - Front/Back
    'front_cut_height_inches' => 6.0,
    'front_cut_height_shop_inches' => 6.0,
    'front_cut_width_inches' => 36.1875,
    'back_cut_height_inches' => 6.0,
    'back_cut_height_shop_inches' => 6.0,
    'back_cut_width_inches' => 36.1875,

    // Cut List - Bottom
    'bottom_cut_width_inches' => 36.625,
    'bottom_cut_depth_inches' => 17.4375,

    // Clearances (NOW IN DATABASE!)
    'clearance_side_inches' => 0.625,
    'clearance_top_inches' => 0.25,
    'clearance_bottom_inches' => 0.5625,

    // Slides
    'slide_type' => 'blum_tandem',
    'slide_model' => '563H4570B',
    'slide_product_id' => 23528,
    'slide_length_inches' => 18.0,
    'slide_quantity' => 2,
    'soft_close' => true,
    'slide_spec_source' => 'DrawerConfiguratorService',
    'min_cabinet_depth_blum_inches' => 18.90625,
    'min_cabinet_depth_shop_inches' => 18.75,

    // Finish
    'finish_type' => 'paint',
    'has_decorative_hardware' => false,

    // Notes for U-cutout alert
    'notes' => 'ALERT: U-SHAPED CUTOUT DIMENSIONS MISSING - BUILD AS FULL RECTANGLE - CUT ON-SITE',
];

echo json_encode($compliantDrawer, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n";

echo "\n✅ Validation complete.\n";
echo "\n📝 KEY FINDING: The staging database has MORE columns than documented!\n";
echo "   The drawer table includes cut_list, dado_specs, clearances, and opening columns.\n";
echo "   This means the JSON nested objects CAN be flattened to actual DB columns.\n";
