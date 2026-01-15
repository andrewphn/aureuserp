<?php
/**
 * Data Map Validator
 *
 * Parses DATABASE_HIERARCHY.md to extract table schemas
 * then validates project_10_full_data_map.json against them.
 *
 * Usage: php validate_data_map.php
 */

// Load the files
$hierarchyPath = __DIR__ . '/../../docs/DATABASE_HIERARCHY.md';
$jsonPath = __DIR__ . '/project_10_full_data_map.json';

if (!file_exists($hierarchyPath)) {
    die("ERROR: DATABASE_HIERARCHY.md not found at: $hierarchyPath\n");
}

if (!file_exists($jsonPath)) {
    die("ERROR: project_10_full_data_map.json not found at: $jsonPath\n");
}

$hierarchyContent = file_get_contents($hierarchyPath);
$jsonContent = file_get_contents($jsonPath);
$dataMap = json_decode($jsonContent, true);

if (json_last_error() !== JSON_ERROR_NONE) {
    die("ERROR: Invalid JSON: " . json_last_error_msg() . "\n");
}

/**
 * Parse DATABASE_HIERARCHY.md to extract table schemas
 */
function parseHierarchy(string $content): array
{
    $tables = [];
    $currentTable = null;
    $columns = [];

    $lines = explode("\n", $content);

    foreach ($lines as $line) {
        // Match table headers like "### 8. `projects_drawers` (Component Level)"
        if (preg_match('/^###\s+\d+\.\s+`(\w+)`/', $line, $matches)) {
            // Save previous table
            if ($currentTable && !empty($columns)) {
                $tables[$currentTable] = $columns;
            }
            $currentTable = $matches[1];
            $columns = [];
            continue;
        }

        // Match column definitions like "| `column_name` | type | Description |"
        if ($currentTable && preg_match('/^\|\s*`(\w+)`\s*\|\s*(\w+(?:\([^)]+\))?)\s*\|\s*(.+?)\s*\|/', $line, $matches)) {
            $columns[$matches[1]] = [
                'type' => trim($matches[2]),
                'description' => trim($matches[3]),
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
    ];
}

/**
 * Extract actual field names from JSON (handles nested value objects)
 */
function extractJsonFields(array $data, string $prefix = ''): array
{
    $fields = [];

    foreach ($data as $key => $value) {
        // Skip metadata keys
        if (strpos($key, '_') === 0) {
            continue;
        }

        // If value is an array with 'value' key, it's a field definition
        if (is_array($value) && isset($value['value'])) {
            $fields[] = $prefix . $key;
        }
        // If value is an array without 'value', it's a nested object
        elseif (is_array($value) && !isset($value['value'])) {
            // Check if it's a nested structure (like box_construction)
            $hasNestedValues = false;
            foreach ($value as $v) {
                if (is_array($v) && isset($v['value'])) {
                    $hasNestedValues = true;
                    break;
                }
            }
            if ($hasNestedValues) {
                $nested = extractJsonFields($value, $prefix . $key . '.');
                $fields = array_merge($fields, $nested);
            } else {
                // It's a complex nested structure
                $fields[] = $prefix . $key . ' (nested_object)';
            }
        }
        else {
            $fields[] = $prefix . $key;
        }
    }

    return $fields;
}

/**
 * Validate a JSON section against database schema
 */
function validateSection(array $jsonSection, array $dbColumns, string $tableName): array
{
    $issues = [
        'compliant' => [],
        'missing_in_db' => [],
        'missing_in_json' => [],
        'nested_needs_flattening' => [],
        'type_mismatches' => [],
    ];

    // Get first entity from section (they're keyed by name like "bathroom", "laundry")
    $firstEntity = null;
    foreach ($jsonSection as $key => $value) {
        if (strpos($key, '_') !== 0 && is_array($value)) {
            $firstEntity = $value;
            break;
        }
    }

    if (!$firstEntity) {
        return $issues;
    }

    $jsonFields = extractJsonFields($firstEntity);
    $dbColumnNames = array_keys($dbColumns);

    foreach ($jsonFields as $field) {
        // Handle nested fields
        if (strpos($field, '.') !== false || strpos($field, '(nested_object)') !== false) {
            $baseName = explode('.', str_replace(' (nested_object)', '', $field))[0];
            $issues['nested_needs_flattening'][] = $field;
            continue;
        }

        if (in_array($field, $dbColumnNames)) {
            $issues['compliant'][] = $field;
        } else {
            $issues['missing_in_db'][] = $field;
        }
    }

    // Check for important DB columns missing from JSON
    $importantColumns = ['id', 'created_at', 'updated_at'];
    foreach ($dbColumnNames as $col) {
        if (!in_array($col, $importantColumns) && !in_array($col, $jsonFields)) {
            // Check if any JSON field contains this column name
            $found = false;
            foreach ($jsonFields as $jf) {
                if (strpos($jf, $col) !== false) {
                    $found = true;
                    break;
                }
            }
            if (!$found) {
                $issues['missing_in_json'][] = $col;
            }
        }
    }

    return $issues;
}

// Parse the hierarchy
echo "═══════════════════════════════════════════════════════════════\n";
echo "        DATA MAP VALIDATION REPORT\n";
echo "        Project: 9 Austin Lane (ID: 10)\n";
echo "═══════════════════════════════════════════════════════════════\n\n";

$dbSchemas = parseHierarchy($hierarchyContent);
$tableMapping = getTableMapping();

echo "📋 PARSED DATABASE TABLES FROM DATABASE_HIERARCHY.md:\n";
echo "─────────────────────────────────────────────────────\n";
foreach ($dbSchemas as $table => $columns) {
    echo "  ✓ $table (" . count($columns) . " columns)\n";
}
echo "\n";

// Validate each section
$totalIssues = 0;
$complianceReport = [];

foreach ($tableMapping as $jsonKey => $tableName) {
    if (!isset($dataMap[$jsonKey])) {
        echo "⚠️  SECTION '$jsonKey' not found in JSON\n";
        continue;
    }

    if (!isset($dbSchemas[$tableName])) {
        echo "❌ TABLE '$tableName' not found in DATABASE_HIERARCHY.md\n";
        continue;
    }

    $issues = validateSection($dataMap[$jsonKey], $dbSchemas[$tableName], $tableName);
    $complianceReport[$jsonKey] = $issues;

    $hasIssues = !empty($issues['missing_in_db']) || !empty($issues['nested_needs_flattening']);

    echo "\n" . ($hasIssues ? "⚠️" : "✅") . " SECTION: $jsonKey → $tableName\n";
    echo "─────────────────────────────────────────────────────\n";

    if (!empty($issues['compliant'])) {
        echo "  ✅ Compliant Fields (" . count($issues['compliant']) . "):\n";
        foreach ($issues['compliant'] as $field) {
            echo "     · $field\n";
        }
    }

    if (!empty($issues['missing_in_db'])) {
        echo "  ❌ Fields NOT in Database Schema:\n";
        foreach ($issues['missing_in_db'] as $field) {
            echo "     · $field (remove or move to metadata)\n";
            $totalIssues++;
        }
    }

    if (!empty($issues['nested_needs_flattening'])) {
        echo "  🔧 Nested Objects (need flattening for DB):\n";
        foreach ($issues['nested_needs_flattening'] as $field) {
            echo "     · $field\n";
            $totalIssues++;
        }
    }

    if (!empty($issues['missing_in_json'])) {
        $important = array_filter($issues['missing_in_json'], function($col) {
            // Filter out timestamps and auto-generated fields
            return !in_array($col, ['created_at', 'updated_at', 'deleted_at', 'sort_order', 'is_active']);
        });
        if (!empty($important)) {
            echo "  ℹ️  Optional DB Columns (not in JSON):\n";
            $shown = 0;
            foreach ($important as $field) {
                if ($shown < 10) {
                    echo "     · $field\n";
                    $shown++;
                } else {
                    echo "     · ... and " . (count($important) - 10) . " more\n";
                    break;
                }
            }
        }
    }
}

// Check for sections that don't map to any known table
echo "\n\n📊 SECTIONS WITHOUT DATABASE TABLE MAPPING:\n";
echo "─────────────────────────────────────────────────────\n";
$unmappedSections = ['countertop', 'sink', 'mirror_cabinet', 'hardware_requirements', '_legend', '_tcs_material_standards', '_summary'];
foreach ($unmappedSections as $section) {
    if (isset($dataMap[$section]) && strpos($section, '_') !== 0) {
        $status = in_array($section, ['countertop', 'sink', 'mirror_cabinet'])
            ? "❌ TABLE NOT IN DATABASE_HIERARCHY.md"
            : "⚠️ NEEDS VERIFICATION";
        echo "  · $section → $status\n";
    }
}

// Summary
echo "\n\n═══════════════════════════════════════════════════════════════\n";
echo "                    SUMMARY\n";
echo "═══════════════════════════════════════════════════════════════\n";
echo "  Total Compliance Issues: $totalIssues\n";
echo "\n";

// Generate recommendations
echo "📝 RECOMMENDED ACTIONS:\n";
echo "─────────────────────────────────────────────────────\n";

$recommendations = [
    "1. FLATTEN nested objects for DB insertion:",
    "   - drawer_box_calculated → box_width_inches, box_depth_inches, box_height_inches",
    "   - box_construction → box_material (string), box_thickness, joinery_method",
    "   - cut_list → Store in notes or separate cut_list JSON column",
    "   - toe_kick → toe_kick_height, toe_kick_depth",
    "",
    "2. RENAME fields to match DB schema:",
    "   - cabinets.width_inches → length_inches (DB uses length for width)",
    "   - cabinets.overlay_type → door_mounting",
    "",
    "3. MOVE fields to correct tables:",
    "   - slide_model, slide_product_id from cabinets → drawers",
    "   - face_frame_stile_width_inches from room_locations → cabinets",
    "",
    "4. METADATA SECTIONS (keep in JSON, don't insert to DB):",
    "   - _legend, _tcs_material_standards, _summary",
    "   - drawer_box_calculated.clearances_applied",
    "   - hardware_validation",
    "   - ACTIVE_ALERTS",
    "",
    "5. CREATE MISSING TABLES or use alternative storage:",
    "   - projects_countertops (or store in cabinet notes)",
    "   - projects_fixtures (or store in room notes)",
];

foreach ($recommendations as $rec) {
    echo "  $rec\n";
}

// Generate compliant JSON template
echo "\n\n═══════════════════════════════════════════════════════════════\n";
echo "            DB-COMPLIANT DRAWER STRUCTURE EXAMPLE\n";
echo "═══════════════════════════════════════════════════════════════\n";

$compliantDrawerExample = [
    '_note' => 'This structure matches projects_drawers table schema',
    'cabinet_id' => null,
    'section_id' => null,
    'drawer_number' => 1,
    'drawer_name' => 'DR1',
    'drawer_position' => 'upper',
    'front_width_inches' => 37.8125,
    'front_height_inches' => 7.0,
    'front_thickness_inches' => 0.75,
    'top_rail_width_inches' => 2.25,
    'bottom_rail_width_inches' => 2.25,
    'style_width_inches' => 1.0,
    'profile_type' => '2-1/4 Shaker',
    'fabrication_method' => 'cnc',
    'box_width_inches' => 37.1875,
    'box_depth_inches' => 18.25,
    'box_height_inches' => 6.0,
    'box_material' => 'baltic_birch',
    'box_thickness' => 0.5,
    'joinery_method' => 'dovetail',
    'slide_type' => 'blum_tandem',
    'slide_model' => '563H4570B',
    'slide_length_inches' => 18.0,
    'slide_quantity' => 2,
    'soft_close' => true,
    'slide_product_id' => 23528,
    'finish_type' => 'paint',
    'has_decorative_hardware' => false,
    '_metadata' => [
        '_note' => 'Additional calculated data (not stored in DB)',
        'opening' => ['width' => 37.8125, 'height' => 7.0, 'depth' => 18.75],
        'cut_list' => ['sides' => '2 @ 6\" x 18-1/4\"'],
        'alerts' => ['U-shaped cutout TBD'],
    ],
];

echo json_encode($compliantDrawerExample, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n";

echo "\n✅ Validation complete.\n";
