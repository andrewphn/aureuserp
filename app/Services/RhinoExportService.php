<?php

namespace App\Services;

/**
 * RhinoExportService - Export cabinet data for Rhino 3D modeling
 *
 * Transforms CabinetMathAuditService output into Rhino MCP-compatible format.
 *
 * COORDINATE SYSTEM TRANSFORMATION:
 * - Our system: Origin at Front-Top-Left, Y-down (matches SVG/CAD)
 * - Rhino: Origin at Front-Bottom-Left, Z-up (standard 3D)
 *
 * Transformation:
 *   Rhino X = Our X (left to right)
 *   Rhino Y = Our Z (front to back)
 *   Rhino Z = Cabinet Height - Our Y (flip vertical)
 *
 * @author TCS Woodwork
 * @since January 2026
 */
class RhinoExportService
{
    /**
     * Layer colors by part type (RGB 0-255)
     */
    protected const LAYER_COLORS = [
        'cabinet_box' => [139, 90, 43],      // Saddle brown - plywood
        'face_frame' => [210, 180, 140],     // Tan - hardwood
        'stretcher' => [160, 82, 45],        // Sienna - structural
        'false_front' => [222, 184, 135],    // Burlywood - decorative
        'false_front_backing' => [188, 143, 143], // Rosy brown
        'drawer_face' => [245, 222, 179],    // Wheat - drawer fronts
        'drawer_box' => [210, 105, 30],      // Chocolate - drawer boxes
        'toe_kick' => [105, 105, 105],       // Dim gray - hidden
        'hardware' => [192, 192, 192],       // Silver - metal
    ];

    /**
     * Layer hierarchy for Rhino organization
     */
    protected const LAYER_HIERARCHY = [
        'Cabinet' => [
            'Box' => ['Sides', 'Bottom', 'Back', 'Stretchers'],
            'FaceFrame' => ['Stiles', 'Rails'],
            'Drawers' => ['Faces', 'Boxes'],
            'FalseFronts' => ['Faces', 'Backings'],
            'ToeKick' => [],
        ],
    ];

    protected float $cabinetHeight = 0;
    protected float $boxHeight = 0;
    protected float $toeKickHeight = 0;

    /**
     * Export cabinet audit data to Rhino MCP format
     *
     * @param array $auditData Output from CabinetMathAuditService::generateFullAudit()
     * @return array Rhino-compatible export with layers and objects
     */
    public function exportToRhino(array $auditData): array
    {
        $this->cabinetHeight = $auditData['input_specs']['height'] ?? 32.75;
        $this->toeKickHeight = $auditData['input_specs']['toe_kick_height'] ?? 4.0;
        $this->boxHeight = $this->cabinetHeight - $this->toeKickHeight;

        $layers = $this->generateLayers();
        $objects = $this->generateObjects($auditData);

        return [
            'metadata' => [
                'export_type' => 'rhino_mcp',
                'version' => '1.0',
                'source' => 'CabinetMathAuditService',
                'units' => 'inches',
                'coordinate_system' => [
                    'origin' => 'Front-Bottom-Left',
                    'x_axis' => 'Left → Right',
                    'y_axis' => 'Front → Back',
                    'z_axis' => 'Bottom → Top',
                ],
                'cabinet_envelope' => $auditData['positions_3d']['cabinet_envelope'] ?? null,
                'part_count' => count($objects),
                'exported_at' => now()->toIso8601String(),
            ],
            'layers' => $layers,
            'objects' => $objects,
            'mcp_commands' => $this->generateMcpCommands($layers, $objects),
        ];
    }

    /**
     * Generate layer definitions for Rhino
     */
    protected function generateLayers(): array
    {
        $layers = [];

        // Root layer
        $layers[] = [
            'name' => 'Cabinet',
            'color' => [128, 128, 128],
            'parent' => null,
        ];

        // Sub-layers with colors
        $layerMap = [
            'Box' => 'cabinet_box',
            'FaceFrame' => 'face_frame',
            'Drawers' => 'drawer_face',
            'FalseFronts' => 'false_front',
            'ToeKick' => 'toe_kick',
            'Stretchers' => 'stretcher',
        ];

        foreach ($layerMap as $layerName => $colorKey) {
            $layers[] = [
                'name' => $layerName,
                'color' => self::LAYER_COLORS[$colorKey] ?? [128, 128, 128],
                'parent' => 'Cabinet',
            ];
        }

        return $layers;
    }

    /**
     * Generate Rhino objects from 3D positions
     */
    protected function generateObjects(array $auditData): array
    {
        $objects = [];
        $parts = $auditData['positions_3d']['parts'] ?? [];

        foreach ($parts as $partKey => $part) {
            $rhinoObject = $this->transformPartToRhinoObject($partKey, $part);
            if ($rhinoObject) {
                $objects[] = $rhinoObject;
            }
        }

        return $objects;
    }

    /**
     * Transform a single part to Rhino BOX object
     *
     * @param string $partKey Part identifier
     * @param array $part Part data from positions_3d
     * @return array|null Rhino object definition
     */
    protected function transformPartToRhinoObject(string $partKey, array $part): ?array
    {
        $position = $part['position'] ?? ['x' => 0, 'y' => 0, 'z' => 0];
        $dimensions = $part['dimensions'] ?? ['w' => 0, 'h' => 0, 'd' => 0];
        $partType = $part['part_type'] ?? 'cabinet_box';

        // Skip parts with zero dimensions
        if ($dimensions['w'] <= 0 || $dimensions['h'] <= 0 || $dimensions['d'] <= 0) {
            // Drawer boxes have special handling - use box_parts
            if ($partType === 'drawer_box' && isset($part['box_parts'])) {
                return $this->transformDrawerBoxToRhinoObject($partKey, $part);
            }
            return null;
        }

        // Transform coordinates: Our Y-down → Rhino Z-up
        $rhinoTranslation = $this->transformCoordinates(
            $position['x'],
            $position['y'],
            $position['z'],
            $dimensions['h'] // Need height to calculate bottom position
        );

        // Map part type to layer
        $layer = $this->getLayerForPartType($partType);

        // Get color for part type
        $color = self::LAYER_COLORS[$partType] ?? [128, 128, 128];

        return [
            'type' => 'BOX',
            'name' => $part['part_name'] ?? $partKey,
            'color' => $color,
            'params' => [
                'width' => $dimensions['w'],  // X dimension
                'length' => $dimensions['d'], // Y dimension (depth)
                'height' => $dimensions['h'], // Z dimension (height)
            ],
            'translation' => $rhinoTranslation,
            'rotation' => $this->getRotationForPart($part),
            'metadata' => [
                'part_key' => $partKey,
                'part_type' => $partType,
                'layer' => $layer,
                'material' => $part['material'] ?? 'Unknown',
                'cut_dimensions' => $part['cut_dimensions'] ?? null,
                'orientation' => $part['orientation'] ?? null,
                'cnc' => $part['cnc'] ?? null,
                'notes' => $part['notes'] ?? null,
            ],
        ];
    }

    /**
     * Transform drawer box with individual pieces
     */
    protected function transformDrawerBoxToRhinoObject(string $partKey, array $part): ?array
    {
        $position = $part['position'] ?? ['x' => 0, 'y' => 0, 'z' => 0];
        $dimensions = $part['dimensions'] ?? ['w' => 0, 'h' => 0, 'd' => 0];
        $boxParts = $part['box_parts'] ?? [];

        // Use the overall drawer box dimensions
        $boxHeight = $boxParts['sides']['width'] ?? 6; // Side width is the box height
        $boxWidth = $dimensions['w'] ?? 37;
        $boxDepth = $dimensions['d'] ?? 18;

        if ($boxHeight <= 0) {
            return null;
        }

        $rhinoTranslation = $this->transformCoordinates(
            $position['x'],
            $position['y'],
            $position['z'],
            $boxHeight
        );

        return [
            'type' => 'BOX',
            'name' => $part['part_name'] ?? $partKey,
            'color' => self::LAYER_COLORS['drawer_box'],
            'params' => [
                'width' => $boxWidth,
                'length' => $boxDepth,
                'height' => $boxHeight,
            ],
            'translation' => $rhinoTranslation,
            'rotation' => [0, 0, 0],
            'metadata' => [
                'part_key' => $partKey,
                'part_type' => 'drawer_box',
                'layer' => 'Drawers',
                'material' => $part['material'] ?? '1/2" Plywood',
                'box_parts' => $boxParts,
                'cnc' => $part['cnc'] ?? null,
            ],
        ];
    }

    /**
     * Transform coordinates from our system to Rhino
     *
     * OUR SYSTEM (CabinetMathAuditService):
     *   - Origin: Front-Top-Left corner of the BOX (not including toe kick)
     *   - X: Left → Right (positive)
     *   - Y: Top → Bottom (positive) - Y=0 is top of box, Y=28.75 is bottom
     *   - Z: Front → Back (positive)
     *   - The toe kick is BELOW the box (Y > boxHeight)
     *
     * RHINO SYSTEM (standard 3D):
     *   - Origin: Front-Bottom-Left of CABINET (floor level)
     *   - X: Left → Right (positive)
     *   - Y: Front → Back (positive)
     *   - Z: Bottom → Top (positive)
     *
     * TRANSFORMATION:
     *   Rhino X = Our X
     *   Rhino Y = Our Z
     *   Rhino Z = (boxHeight - Our Y) + toeKickHeight
     *           = toeKickHeight + boxHeight - Our Y
     *
     * The "+toeKickHeight" shifts everything up since Rhino origin is at floor,
     * and our origin is at top of box (above toe kick).
     *
     * @param float $x Our X coordinate
     * @param float $y Our Y coordinate (top-down from top of box)
     * @param float $z Our Z coordinate (front-back)
     * @param float $partHeight Height of the part (for bottom position)
     * @return array [x, y, z] in Rhino coordinates (position of part's bottom-front-left corner)
     */
    protected function transformCoordinates(float $x, float $y, float $z, float $partHeight): array
    {
        // Rhino X = Our X (no change)
        $rhinoX = $x;

        // Rhino Y = Our Z (depth becomes Y in Rhino)
        $rhinoY = $z;

        // Rhino Z calculation:
        // - Our Y=0 is at top of box (which is at toeKickHeight + boxHeight from floor)
        // - Part's TOP in our system is at Y
        // - Part's BOTTOM in our system is at Y + partHeight
        // - Rhino needs the BOTTOM position (Z up)
        //
        // If part top is at our Y, its bottom is at our Y + partHeight
        // In Rhino: Z = totalHeight - (our Y + partHeight)
        //         = (toeKickHeight + boxHeight) - ourY - partHeight
        //         = cabinetHeight - ourY - partHeight
        //
        // Special case: toe kick has Y > boxHeight (below the box)
        // For toe kick at Y=28.75 (bottom of box), Z should be 0 to toeKickHeight

        $rhinoZ = $this->cabinetHeight - $y - $partHeight;

        // Clamp to prevent negative values (shouldn't happen with correct data)
        if ($rhinoZ < 0) {
            // This happens when Y + partHeight > cabinetHeight
            // Likely the toe kick which extends below box
            $rhinoZ = max(0, $this->toeKickHeight - ($y - $this->boxHeight) - $partHeight);
        }

        return [$rhinoX, $rhinoY, $rhinoZ];
    }

    /**
     * Get rotation angles for a part based on orientation
     */
    protected function getRotationForPart(array $part): array
    {
        $orientation = $part['orientation'] ?? [];
        $rotation = $orientation['rotation'] ?? 0;

        // Convert our rotation to Rhino radians
        // Our rotation is typically around Z axis (vertical)
        if ($rotation === 90) {
            return [0, 0, M_PI / 2]; // 90 degrees in radians
        }

        return [0, 0, 0];
    }

    /**
     * Map part type to Rhino layer
     */
    protected function getLayerForPartType(string $partType): string
    {
        return match ($partType) {
            'cabinet_box' => 'Box',
            'face_frame' => 'FaceFrame',
            'stretcher' => 'Stretchers',
            'false_front' => 'FalseFronts',
            'false_front_backing' => 'FalseFronts',
            'drawer_face' => 'Drawers',
            'drawer_box' => 'Drawers',
            'toe_kick' => 'ToeKick',
            default => 'Box',
        };
    }

    /**
     * Generate MCP commands for direct Rhino execution
     */
    protected function generateMcpCommands(array $layers, array $objects): array
    {
        $commands = [];

        // Layer creation commands
        foreach ($layers as $layer) {
            $commands[] = [
                'tool' => 'rhino/create_layer',
                'params' => [
                    'name' => $layer['name'],
                    'color' => $layer['color'],
                    'parent' => $layer['parent'],
                ],
            ];
        }

        // Batch object creation command
        $rhinoObjects = array_map(function ($obj) {
            return [
                'type' => $obj['type'],
                'name' => $obj['name'],
                'color' => $obj['color'],
                'params' => $obj['params'],
                'translation' => $obj['translation'],
                'rotation' => $obj['rotation'] ?? [0, 0, 0],
            ];
        }, $objects);

        $commands[] = [
            'tool' => 'rhino/create_objects',
            'params' => [
                'objects' => $rhinoObjects,
            ],
        ];

        return $commands;
    }

    /**
     * Export to JSON file for external parsing
     *
     * @param array $auditData Output from CabinetMathAuditService
     * @param string $outputPath File path to save JSON
     * @return string Path to saved file
     */
    public function exportToJsonFile(array $auditData, string $outputPath): string
    {
        $rhinoData = $this->exportToRhino($auditData);

        file_put_contents(
            $outputPath,
            json_encode($rhinoData, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
        );

        return $outputPath;
    }

    /**
     * Execute export directly to Rhino via MCP
     *
     * @param array $auditData Output from CabinetMathAuditService
     * @return array Results from MCP commands
     */
    public function executeInRhino(array $auditData): array
    {
        $rhinoData = $this->exportToRhino($auditData);
        $results = [];

        // This would be called via MCP in practice
        // For now, return the commands that would be executed
        return [
            'status' => 'ready',
            'commands' => $rhinoData['mcp_commands'],
            'object_count' => count($rhinoData['objects']),
            'layer_count' => count($rhinoData['layers']),
        ];
    }

    /**
     * Generate a Grasshopper-compatible data structure
     * For parametric modeling workflows
     */
    public function exportForGrasshopper(array $auditData): array
    {
        $this->cabinetHeight = $auditData['input_specs']['height'] ?? 32.75;
        $parts = $auditData['positions_3d']['parts'] ?? [];

        $ghData = [
            'parameters' => $auditData['input_specs'],
            'points' => [],      // Corner points for each part
            'dimensions' => [],  // Width, Height, Depth for each part
            'part_names' => [],
            'part_types' => [],
            'materials' => [],
        ];

        foreach ($parts as $partKey => $part) {
            $position = $part['position'] ?? ['x' => 0, 'y' => 0, 'z' => 0];
            $dimensions = $part['dimensions'] ?? ['w' => 0, 'h' => 0, 'd' => 0];

            // Skip zero-dimension parts
            if ($dimensions['w'] <= 0 && $dimensions['h'] <= 0) {
                continue;
            }

            // Transform to Rhino coordinates
            $rhinoPos = $this->transformCoordinates(
                $position['x'],
                $position['y'],
                $position['z'],
                $dimensions['h'] ?: 1
            );

            $ghData['points'][] = $rhinoPos;
            $ghData['dimensions'][] = [
                $dimensions['w'],
                $dimensions['d'],
                $dimensions['h'],
            ];
            $ghData['part_names'][] = $part['part_name'] ?? $partKey;
            $ghData['part_types'][] = $part['part_type'] ?? 'unknown';
            $ghData['materials'][] = $part['material'] ?? 'Unknown';
        }

        return $ghData;
    }
}
