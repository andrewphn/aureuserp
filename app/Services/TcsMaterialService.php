<?php

namespace App\Services;

/**
 * TCS Material Service - Material layer configuration for V-Carve CNC nesting
 *
 * Provides bidirectional material mapping between:
 * - CabinetXYZService part_type + thickness -> Material layers
 * - Rhino layer names -> Material specifications
 *
 * Layer naming convention: {thickness-fraction}_{material}
 * Examples: 3-4_PreFin, 1-2_Baltic, 5-4_Hardwood
 *
 * This service bridges cabinet parts to V-Carve compatible nesting sheets.
 *
 * @author TCS Woodwork
 * @since January 2026
 */
class TcsMaterialService
{
    /**
     * Material layers with V-Carve compatible names
     * Format: thickness-fraction_material
     *
     * These layers organize parts by the CNC sheet they'll be nested on.
     */
    public const MATERIAL_LAYERS = [
        '3-4_PreFin' => [
            'thickness' => CabinetXYZService::PLYWOOD_3_4,
            'color' => [139, 90, 43],
            'description' => '3/4" Prefinished Plywood',
            'vcarve_sheet' => 'PLY_3-4_PREFIN',
        ],
        '3-4_Medex' => [
            'thickness' => CabinetXYZService::PLYWOOD_3_4,
            'color' => [65, 105, 225],
            'description' => '3/4" Medex MDF (paint grade)',
            'vcarve_sheet' => 'MDF_3-4_MEDEX',
        ],
        '3-4_RiftWO' => [
            'thickness' => CabinetXYZService::PLYWOOD_3_4,
            'color' => [210, 180, 140],
            'description' => '3/4" Rift White Oak',
            'vcarve_sheet' => 'PLY_3-4_RIFTWO',
        ],
        '1-2_Baltic' => [
            'thickness' => CabinetXYZService::PLYWOOD_1_2,
            'color' => [255, 228, 181],
            'description' => '1/2" Baltic Birch',
            'vcarve_sheet' => 'PLY_1-2_BALTIC',
        ],
        '1-4_Plywood' => [
            'thickness' => CabinetXYZService::PLYWOOD_1_4,
            'color' => [240, 230, 200],
            'description' => '1/4" Plywood (backs, bottoms)',
            'vcarve_sheet' => 'PLY_1-4_STANDARD',
        ],
        '5-4_Hardwood' => [
            'thickness' => CabinetXYZService::DRAWER_FACE_THICKNESS,
            'color' => [205, 133, 63],
            'description' => '5/4" Hardwood (face frames)',
            'vcarve_sheet' => 'HARDWOOD_5-4',
        ],
    ];

    /**
     * Map part_type -> default material layer
     * Can be overridden by project/cabinet settings
     */
    public const PART_TYPE_TO_MATERIAL = [
        // Box parts (cabinet carcase)
        'cabinet_box' => '3-4_PreFin',
        'toe_kick' => '3-4_Medex',

        // External parts (visible)
        'face_frame' => '5-4_Hardwood',
        'drawer_face' => '3-4_RiftWO',
        'finished_end' => '3-4_RiftWO',
        'false_front' => '3-4_RiftWO',

        // Internal parts
        'stretcher' => '3-4_PreFin',
        'false_front_backing' => '3-4_PreFin',
        'drawer_box' => '1-2_Baltic',
        'drawer_box_side' => '1-2_Baltic',
        'drawer_box_front' => '1-2_Baltic',
        'drawer_box_back' => '1-2_Baltic',
        'drawer_box_bottom' => '1-4_Plywood',
        'shelf' => '3-4_PreFin',
        'divider' => '3-4_PreFin',
    ];

    /**
     * Edgebanding configuration by part type
     * Codes: F=Front, T=Top, B=Bottom, L=Left, R=Right
     */
    public const EDGEBAND_BY_PART_TYPE = [
        'cabinet_box' => 'F',        // Front edge of sides
        'finished_end' => 'F,T',     // Front and top edges
        'stretcher' => 'F',          // Front edge
        'drawer_face' => null,       // Full face, no banding needed
        'false_front' => null,       // Full face
        'shelf' => 'F',              // Front edge exposed
        'divider' => null,           // Hidden
        'toe_kick' => null,          // Hidden
        'drawer_box' => null,        // Baltic birch - no banding
        'drawer_box_side' => null,
        'drawer_box_front' => null,
        'drawer_box_back' => null,
        'drawer_box_bottom' => null,
        'face_frame' => null,        // Hardwood - no banding
    ];

    /**
     * Machining operations by part type
     */
    public const MACHINING_BY_PART_TYPE = [
        'cabinet_box' => ['shelf_pins', 'dado_back'],
        'finished_end' => ['shelf_pins'],
        'drawer_box' => ['dado_bottom'],
        'drawer_box_side' => ['dado_bottom'],
        'drawer_box_front' => ['dado_bottom'],
        'drawer_box_back' => ['dado_bottom'],
        'face_frame' => ['pocket_screws'],
        'stretcher' => [],
        'false_front' => [],
        'false_front_backing' => [],
        'toe_kick' => [],
        'drawer_face' => [],
        'drawer_box_bottom' => [],
        'shelf' => [],
        'divider' => [],
    ];

    /**
     * Grain direction by part type
     */
    public const GRAIN_BY_PART_TYPE = [
        'cabinet_box' => 'vertical',     // Sides have vertical grain
        'face_frame' => 'vertical',      // Stiles vertical
        'drawer_face' => 'horizontal',   // Grain runs horizontally
        'finished_end' => 'vertical',    // Match cabinet sides
        'stretcher' => 'horizontal',     // Grain runs horizontally
        'toe_kick' => 'none',            // Medex - no grain
        'drawer_box' => 'horizontal',    // Baltic birch
        'drawer_box_side' => 'horizontal',
        'drawer_box_front' => 'horizontal',
        'drawer_box_back' => 'horizontal',
        'drawer_box_bottom' => 'none',   // Plywood bottom
        'shelf' => 'horizontal',
        'divider' => 'vertical',
        'false_front' => 'horizontal',
        'false_front_backing' => 'none',
    ];

    /**
     * Get material layer for a part based on its type and optional overrides
     *
     * @param array $part Part data with part_type
     * @param array|null $projectOverrides Optional project-level material overrides
     * @return string Material layer name (e.g., '3-4_PreFin')
     */
    public function getMaterialForPart(array $part, ?array $projectOverrides = null): string
    {
        $partType = $part['part_type'] ?? 'cabinet_box';

        // Check for project-level override first
        if ($projectOverrides && isset($projectOverrides[$partType])) {
            return $projectOverrides[$partType];
        }

        // Check for part-level material specification
        if (isset($part['material'])) {
            return $part['material'];
        }

        // Fall back to default mapping
        return self::PART_TYPE_TO_MATERIAL[$partType] ?? '3-4_PreFin';
    }

    /**
     * Get material layer configuration
     *
     * @param string $layerName Material layer name
     * @return array|null Layer configuration or null if not found
     */
    public function getMaterialConfig(string $layerName): ?array
    {
        return self::MATERIAL_LAYERS[$layerName] ?? null;
    }

    /**
     * Generate TCS metadata for a part
     *
     * Creates the full set of User Text attributes for a Rhino object.
     *
     * Supports two cabinet ID formats:
     * 1. ERP format: full_code like "TCS-001-9AustinFarmRoad-BTH1-SW-B1" with cabinet_number "BTH1-B1-C1"
     * 2. Simple format: "SANK-B36-001" (for manual Rhino work)
     *
     * @param array $part Part data from CabinetXYZService
     * @param string $cabinetId Cabinet identifier - either full_code or simple ID
     * @param string|null $partKey Optional part key for naming
     * @param string|null $projectNumber Optional project_number for ERP format
     * @param string|null $cabinetNumber Optional cabinet_number for ERP format
     * @return array TCS metadata key-value pairs
     */
    public function generateTcsMetadata(
        array $part,
        string $cabinetId,
        ?string $partKey = null,
        ?string $projectNumber = null,
        ?string $cabinetNumber = null
    ): array {
        $partType = $part['part_type'] ?? 'cabinet_box';
        $partName = $part['part_name'] ?? $partKey ?? 'unknown';
        $material = $this->getMaterialForPart($part);
        $materialConfig = $this->getMaterialConfig($material);

        // Determine project code and build IDs based on available data
        if ($projectNumber && $cabinetNumber) {
            // ERP format: use project_number and cabinet_number
            $projectCode = $this->getShortProjectCode($projectNumber);
            $rhinoCabinetId = $this->buildCabinetId($projectNumber, $cabinetNumber);
            $partId = $this->buildPartId($projectNumber, $cabinetNumber, $partName);
        } else {
            // Simple format: extract from cabinetId
            $projectCode = $this->extractProjectCode($cabinetId);
            $rhinoCabinetId = $cabinetId;
            $sanitizedPartName = preg_replace('/[^a-zA-Z0-9_-]/', '', str_replace(' ', '_', $partName));
            $partId = $cabinetId . '-' . $sanitizedPartName;
        }

        // Get dimensions (prefer cut_dimensions, fall back to dimensions)
        $cutDims = $part['cut_dimensions'] ?? [];
        $dims = $part['dimensions'] ?? [];

        $metadata = [
            // Identity
            'TCS_PART_ID' => $partId,
            'TCS_CABINET_ID' => $rhinoCabinetId,
            'TCS_PROJECT_CODE' => $projectCode,
            'TCS_PART_TYPE' => $partType,
            'TCS_PART_NAME' => $partName,
            // ERP linkage (if available)
            'TCS_ERP_FULL_CODE' => ($projectNumber && $cabinetNumber) ? $cabinetId : null,
            'TCS_ERP_CABINET_NUMBER' => $cabinetNumber,

            // Material
            'TCS_MATERIAL' => $material,
            'TCS_THICKNESS' => $cutDims['thickness'] ?? $dims['h'] ?? $materialConfig['thickness'] ?? 0.75,

            // CNC Data
            'TCS_CUT_WIDTH' => $cutDims['width'] ?? null,
            'TCS_CUT_LENGTH' => $cutDims['length'] ?? null,
            'TCS_GRAIN' => self::GRAIN_BY_PART_TYPE[$partType] ?? 'none',

            // Processing Instructions
            'TCS_EDGEBAND' => self::EDGEBAND_BY_PART_TYPE[$partType] ?? null,
            'TCS_MACHINING' => $this->getMachiningString($part),
        ];

        // Add dado info if present
        if (isset($part['dado'])) {
            $dado = $part['dado'];
            $metadata['TCS_DADO'] = sprintf(
                '%.4g x %.4g @ %.4g',
                $dado['depth'] ?? 0,
                $dado['width'] ?? 0,
                $dado['height_from_bottom'] ?? 0
            );
        }

        // Remove null values
        return array_filter($metadata, fn($v) => $v !== null);
    }

    /**
     * Get machining operations string for a part
     *
     * @param array $part Part data
     * @return string|null Comma-separated machining operations
     */
    protected function getMachiningString(array $part): ?string
    {
        $operations = [];
        $partType = $part['part_type'] ?? 'cabinet_box';

        // Get default operations for part type
        $defaultOps = self::MACHINING_BY_PART_TYPE[$partType] ?? [];
        $operations = array_merge($operations, $defaultOps);

        // Add dado_bottom if part has dado specification
        if (isset($part['dado']) && !in_array('dado_bottom', $operations)) {
            $operations[] = 'dado_bottom';
        }

        return $operations ? implode(',', array_unique($operations)) : null;
    }

    /**
     * Parse a material layer name and return its components
     *
     * TCS format: 3-4_Medex -> ['thickness' => 0.75, 'material' => 'Medex']
     *
     * @param string $layerName Layer name to parse
     * @return array|null Parsed data or null if invalid format
     */
    public function parseMaterialLayer(string $layerName): ?array
    {
        // TCS format: 3-4_Medex
        if (preg_match('/^(\d+)-(\d+)_(\w+)$/', $layerName, $m)) {
            $numerator = (int) $m[1];
            $denominator = (int) $m[2];
            $thickness = $numerator / $denominator;

            return [
                'thickness' => $thickness,
                'material' => $m[3],
                'layer' => $layerName,
                'format' => 'tcs',
            ];
        }

        // Try legacy format
        return $this->parseLegacyLayerName($layerName);
    }

    /**
     * Normalize a legacy layer name to TCS format
     *
     * Handles various legacy formats found in existing drawings:
     * - "3/4 Medex", "3/4\" Rift WO", "RiftWO 3/4Ply_Cab1"
     *
     * @param string $layerName Legacy layer name
     * @return array|null Normalized data or null if unrecognized
     */
    public function parseLegacyLayerName(string $layerName): ?array
    {
        $patterns = [
            '/^3\/4[\s"\']*Medex/i' => [
                'thickness' => 0.75,
                'material' => 'Medex',
                'layer' => '3-4_Medex',
            ],
            '/^3\/4[\s"\']*PreFin/i' => [
                'thickness' => 0.75,
                'material' => 'PreFin',
                'layer' => '3-4_PreFin',
            ],
            '/^3\/4[\s"\']*Rift/i' => [
                'thickness' => 0.75,
                'material' => 'RiftWO',
                'layer' => '3-4_RiftWO',
            ],
            '/^1\/2[\s"\']*Baltic/i' => [
                'thickness' => 0.5,
                'material' => 'Baltic',
                'layer' => '1-2_Baltic',
            ],
            '/^1\/4[\s"\']*Ply/i' => [
                'thickness' => 0.25,
                'material' => 'Plywood',
                'layer' => '1-4_Plywood',
            ],
            '/^5\/4[\s"\']*Hard/i' => [
                'thickness' => 1.0,
                'material' => 'Hardwood',
                'layer' => '5-4_Hardwood',
            ],
            // Patterns that match material name first
            '/RiftWO/i' => [
                'thickness' => 0.75,
                'material' => 'RiftWO',
                'layer' => '3-4_RiftWO',
            ],
            '/Medex/i' => [
                'thickness' => 0.75,
                'material' => 'Medex',
                'layer' => '3-4_Medex',
            ],
            '/Baltic/i' => [
                'thickness' => 0.5,
                'material' => 'Baltic',
                'layer' => '1-2_Baltic',
            ],
        ];

        foreach ($patterns as $pattern => $result) {
            if (preg_match($pattern, $layerName)) {
                $result['format'] = 'legacy';
                $result['original'] = $layerName;
                return $result;
            }
        }

        return null;
    }

    /**
     * Extract project code from full_code or project_number
     *
     * ERP full_code format: TCS-001-9AustinFarmRoad-BTH1-SW-B1
     * ERP project_number format: TCS-001-9AustinFarmRoad
     *
     * For Rhino/V-Carve, we use a shortened format for readability:
     * - Project: TCS-001-9AustinFarmRoad -> "AUST" (derived from address)
     * - Or the full project_number if no short code available
     *
     * @param string $cabinetId Full cabinet ID (full_code or project_number)
     * @return string Project code for Rhino metadata
     */
    public function extractProjectCode(string $cabinetId): string
    {
        $parts = explode('-', $cabinetId);

        // ERP format: TCS-001-ProjectName-Room-Location-Run
        // Return first meaningful segment (TCS-001 or first part)
        if (count($parts) >= 2 && $parts[0] === 'TCS') {
            // Return TCS-001 format for project identification
            return $parts[0] . '-' . $parts[1];
        }

        // Fallback: first segment
        if (count($parts) >= 1) {
            return $parts[0];
        }

        return 'UNKNOWN';
    }

    /**
     * Extract short project code for V-Carve labels
     *
     * Converts long project names to 4-character codes for CNC labeling.
     * Example: "TCS-001-9AustinFarmRoad" -> "AUST"
     *          "TCS-0554-15WSankaty" -> "SANK"
     *
     * @param string $projectNumber Full project number
     * @return string 4-character short code
     */
    public function getShortProjectCode(string $projectNumber): string
    {
        $parts = explode('-', $projectNumber);

        // Look for the address/name part (usually the 3rd segment)
        if (count($parts) >= 3) {
            $namePart = $parts[2];

            // Handle patterns like "15WSankaty" (street number + direction + name)
            // or "9AustinFarmRoad" (street number + name)
            // Pattern: digits + optional single direction letter (N/S/E/W) that's NOT followed by lowercase
            if (preg_match('/^\d+([NSEW])(?=[A-Z])/', $namePart, $matches)) {
                // Has direction letter like "15W" in "15WSankaty" - remove the number+direction
                $namePart = preg_replace('/^\d+[NSEW]/', '', $namePart);
            } else {
                // Just remove leading digits like "9" in "9AustinFarmRoad"
                $namePart = preg_replace('/^\d+/', '', $namePart);
            }

            // Take first 4 characters, uppercase
            return strtoupper(substr($namePart, 0, 4));
        }

        // Fallback: use first 4 chars of entire string (letters only)
        return strtoupper(substr(preg_replace('/[^A-Za-z]/', '', $projectNumber), 0, 4));
    }

    /**
     * Build a TCS part ID for Rhino User Text
     *
     * Format: {SHORT_PROJECT}-{CABINET_NUMBER}-{PART_NAME}
     * Example: AUST-BTH1-B1-C1-LeftSide
     *
     * @param string $projectNumber Full project number (e.g., 'TCS-001-9AustinFarmRoad')
     * @param string $cabinetNumber Cabinet number (e.g., 'BTH1-B1-C1')
     * @param string $partName Part name (e.g., 'LeftSide')
     * @return string Full part ID for Rhino
     */
    public function buildPartId(string $projectNumber, string $cabinetNumber, string $partName): string
    {
        $shortCode = $this->getShortProjectCode($projectNumber);
        $safeName = str_replace([' ', '-'], '_', $partName);
        return sprintf('%s-%s-%s', $shortCode, $cabinetNumber, $safeName);
    }

    /**
     * Build a cabinet ID for Rhino metadata
     *
     * Format: {SHORT_PROJECT}-{CABINET_NUMBER}
     * Example: AUST-BTH1-B1-C1
     *
     * @param string $projectNumber Full project number
     * @param string $cabinetNumber Cabinet number from ERP
     * @return string Cabinet ID for Rhino
     */
    public function buildCabinetId(string $projectNumber, string $cabinetNumber): string
    {
        $shortCode = $this->getShortProjectCode($projectNumber);
        return sprintf('%s-%s', $shortCode, $cabinetNumber);
    }

    /**
     * Build a cabinet ID from legacy format components
     *
     * @deprecated Use buildCabinetId(projectNumber, cabinetNumber) instead
     * @param string $projectCode Project identifier (e.g., 'SANK')
     * @param string $typeCode Cabinet type code (e.g., 'B36')
     * @param int $sequence Sequence number within project
     * @return string Full cabinet ID (e.g., 'SANK-B36-001')
     */
    public function buildCabinetIdLegacy(string $projectCode, string $typeCode, int $sequence): string
    {
        return sprintf('%s-%s-%03d', strtoupper($projectCode), strtoupper($typeCode), $sequence);
    }

    /**
     * Get all material layers as an array suitable for Rhino layer creation
     *
     * @param string $parentLayer Parent layer name (default: 'TCS_Materials')
     * @return array Layer definitions with full paths
     */
    public function getTcsLayerHierarchy(string $parentLayer = 'TCS_Materials'): array
    {
        $layers = [];

        foreach (self::MATERIAL_LAYERS as $name => $config) {
            $layers[] = [
                'name' => $name,
                'full_path' => $parentLayer . '::' . $name,
                'color' => $config['color'],
                'description' => $config['description'],
                'thickness' => $config['thickness'],
            ];
        }

        return [
            'parent' => $parentLayer,
            'layers' => $layers,
        ];
    }

    /**
     * Generate Rhino Python script snippet to create TCS layer hierarchy
     *
     * @return string Python code to create layers
     */
    public function generateLayerSetupScript(): string
    {
        $layers = $this->getTcsLayerHierarchy();
        $parentLayer = $layers['parent'];

        $script = <<<PYTHON
def create_tcs_layers():
    """Create TCS material layer hierarchy for V-Carve nesting."""
    import rhinoscriptsyntax as rs

    # Create parent layer
    parent = "{$parentLayer}"
    if not rs.IsLayer(parent):
        rs.AddLayer(parent)

    # Material layers
    materials = {

PYTHON;

        foreach ($layers['layers'] as $layer) {
            $colorStr = implode(', ', $layer['color']);
            $script .= "        '{$layer['name']}': ([{$colorStr}], \"{$layer['description']}\"),\n";
        }

        $script .= <<<PYTHON
    }

    for name, (color, desc) in materials.items():
        full_name = parent + "::" + name
        if not rs.IsLayer(full_name):
            rs.AddLayer(full_name, color)
            print(f"Created layer: {full_name}")
        else:
            print(f"Layer exists: {full_name}")

    return True
PYTHON;

        return $script;
    }

    /**
     * Validate that a material layer exists in the configuration
     *
     * @param string $layerName Layer name to validate
     * @return bool True if valid
     */
    public function isValidMaterialLayer(string $layerName): bool
    {
        return isset(self::MATERIAL_LAYERS[$layerName]);
    }

    /**
     * Get all available material layer names
     *
     * @return array List of material layer names
     */
    public function getAvailableMaterials(): array
    {
        return array_keys(self::MATERIAL_LAYERS);
    }

    /**
     * Load unified TCS construction configuration
     *
     * Loads the shared config from config/tcs_construction.json
     * for use by both PHP services and external systems (Grasshopper).
     *
     * @return array Configuration data
     */
    public function loadUnifiedConfig(): array
    {
        $configPath = base_path('config/tcs_construction.json');

        if (!file_exists($configPath)) {
            return [];
        }

        $json = file_get_contents($configPath);
        return json_decode($json, true) ?? [];
    }

    /**
     * Validate TCS metadata against the unified specification
     *
     * Checks that all required fields are present and valid.
     *
     * @param array $metadata Metadata to validate
     * @return array Validation result with 'valid', 'errors', 'warnings'
     */
    public function validateTcsMetadata(array $metadata): array
    {
        $errors = [];
        $warnings = [];

        // Required fields per unified spec
        $required = [
            'TCS_PART_ID',
            'TCS_CABINET_ID',
            'TCS_MATERIAL',
            'TCS_PART_TYPE',
            'TCS_THICKNESS',
        ];

        // Check required fields
        foreach ($required as $field) {
            if (!isset($metadata[$field]) || $metadata[$field] === null || $metadata[$field] === '') {
                $errors[] = "Missing required field: {$field}";
            }
        }

        // Validate TCS_MATERIAL is a known material
        if (isset($metadata['TCS_MATERIAL']) && !$this->isValidMaterialLayer($metadata['TCS_MATERIAL'])) {
            $errors[] = "Unknown material layer: {$metadata['TCS_MATERIAL']}";
        }

        // Validate TCS_CABINET_ID format (should be SHORT-CABINET_NUMBER)
        if (isset($metadata['TCS_CABINET_ID'])) {
            $cabinetId = $metadata['TCS_CABINET_ID'];
            if (!preg_match('/^[A-Z]{4}-/', $cabinetId)) {
                $warnings[] = "TCS_CABINET_ID should start with 4-letter project code: {$cabinetId}";
            }
        }

        // Validate TCS_PART_ID contains cabinet ID
        if (isset($metadata['TCS_PART_ID']) && isset($metadata['TCS_CABINET_ID'])) {
            if (strpos($metadata['TCS_PART_ID'], $metadata['TCS_CABINET_ID']) !== 0) {
                $warnings[] = "TCS_PART_ID should start with TCS_CABINET_ID";
            }
        }

        // Validate edgeband codes if present
        if (isset($metadata['TCS_EDGEBAND']) && $metadata['TCS_EDGEBAND'] !== '') {
            $validCodes = ['F', 'B', 'T', 'O', 'L', 'R'];
            $codes = explode(',', $metadata['TCS_EDGEBAND']);
            foreach ($codes as $code) {
                if (!in_array(trim($code), $validCodes)) {
                    $errors[] = "Invalid edgeband code: {$code}";
                }
            }
        }

        // Validate grain direction if present
        if (isset($metadata['TCS_GRAIN'])) {
            $validGrains = ['vertical', 'horizontal', 'none'];
            if (!in_array($metadata['TCS_GRAIN'], $validGrains)) {
                $errors[] = "Invalid grain direction: {$metadata['TCS_GRAIN']}";
            }
        }

        // Validate thickness is numeric and reasonable
        if (isset($metadata['TCS_THICKNESS'])) {
            $thickness = (float) $metadata['TCS_THICKNESS'];
            if ($thickness <= 0 || $thickness > 2) {
                $warnings[] = "Unusual thickness value: {$metadata['TCS_THICKNESS']}";
            }
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
            'warnings' => $warnings,
            'metadata' => $metadata,
        ];
    }

    /**
     * Generate metadata for Grasshopper compatibility
     *
     * Returns metadata in a format that Grasshopper components can read,
     * including the TCS_ERP_ID for API sync operations.
     *
     * @param array $part Part data
     * @param int $erpCabinetId ERP cabinet ID (numeric)
     * @param string $projectNumber Project number
     * @param string $cabinetNumber Cabinet number
     * @param string|null $partKey Part key
     * @return array Grasshopper-compatible metadata
     */
    public function generateGrasshopperMetadata(
        array $part,
        int $erpCabinetId,
        string $projectNumber,
        string $cabinetNumber,
        ?string $partKey = null
    ): array {
        // Start with standard TCS metadata
        $metadata = $this->generateTcsMetadata(
            $part,
            '', // Not used when projectNumber/cabinetNumber provided
            $partKey,
            $projectNumber,
            $cabinetNumber
        );

        // Add Grasshopper-specific fields
        $metadata['TCS_ERP_ID'] = (string) $erpCabinetId;
        $metadata['TCS_PROJECT_NUMBER'] = $projectNumber;
        $metadata['TCS_CABINET_NUMBER'] = $cabinetNumber;
        $metadata['TCS_HAS_OVERRIDES'] = 'false';
        $metadata['TCS_OVERRIDES'] = '{}';

        return $metadata;
    }

    /**
     * Convert User Text dictionary to JSON for Grasshopper override storage
     *
     * @param array $overrides Key-value pairs of overrides
     * @return string JSON string for TCS_OVERRIDES
     */
    public function encodeOverrides(array $overrides): string
    {
        return json_encode($overrides, JSON_UNESCAPED_SLASHES);
    }

    /**
     * Decode TCS_OVERRIDES JSON string
     *
     * @param string $json JSON override string
     * @return array Decoded overrides
     */
    public function decodeOverrides(string $json): array
    {
        return json_decode($json, true) ?? [];
    }

    /**
     * Get the unified spec version
     *
     * @return string Version string
     */
    public function getSpecVersion(): string
    {
        return '1.0.0';
    }
}
