<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Collection;

/**
 * RhinoDataExtractor - Extract cabinet data from Rhino 3DM files
 *
 * TCS Rhino drawings are organized as multiple 2D views arranged spatially:
 * - Plan Views: Top-down (Width × Depth)
 * - Elevations: Front-facing (Width × Height)
 * - Details: Close-up components
 *
 * This service identifies views, extracts dimensions, and combines data
 * from multiple views to build complete 3D cabinet specifications.
 *
 * @author TCS Woodwork
 * @since January 2026
 */
class RhinoDataExtractor
{
    /**
     * View type patterns for detection
     */
    protected const VIEW_PATTERNS = [
        'plan' => ['Plan View', 'Plan', 'Layout'],
        'elevation' => ['Elevation', 'Wall Elevation', 'Front View', 'Elev'],
        'detail' => ['Detail', 'Section', 'Close-up'],
    ];

    /**
     * Cabinet group name patterns
     */
    protected const CABINET_GROUP_PATTERNS = [
        '/^[A-Z][a-z]+-[A-Z][A-Za-z\/]+$/',  // Austin-Van, Austin-W/D
        '/^[A-Z][a-z]+-\d+$/',                // Austin-1, Austin-2
        '/^Cabinet[-_]?\d+$/i',                // Cabinet-1, Cabinet_2
        '/^Cab[-_]?\d+$/i',                    // Cab-1, Cab_2
    ];

    /**
     * Component labels to detect
     */
    protected const COMPONENT_LABELS = [
        'drawer' => ['Drawer', 'DWR', 'Drw'],
        'u_shaped_drawer' => ['U-Shaped Drawer', 'U-Drawer', 'Trash'],
        'door' => ['Door', 'DR'],
        'shelf' => ['Shelf', 'Open Shelf', 'SH'],
        'pullout' => ['Pullout', 'Pull-out', 'Pull Out'],
        'lazy_susan' => ['Lazy Susan', 'LS', 'Spinning'],
    ];

    /**
     * Status labels for tracking
     */
    protected const STATUS_LABELS = [
        'progress' => ['Progress', 'In Progress'],
        'job_card_made' => ['Job Card Made', 'Job Card'],
        'sent_to_cnc' => ['Sent to CNC', 'CNC'],
        'needs_adjustment' => ['Needs Adjustment', 'Adjust'],
    ];

    protected RhinoMCPService $rhinoMcp;
    protected array $views = [];
    protected array $groups = [];
    protected array $dimensions = [];
    protected array $textLabels = [];
    protected array $fixtures = [];

    public function __construct(RhinoMCPService $rhinoMcp)
    {
        $this->rhinoMcp = $rhinoMcp;
    }

    /**
     * Extract all cabinet data from the currently open Rhino document
     *
     * @return array Extracted cabinet data
     */
    public function extractCabinets(): array
    {
        Log::info('RhinoDataExtractor: Starting cabinet extraction');

        // Step 1: Discover all views in the drawing
        $this->discoverViews();
        Log::debug('RhinoDataExtractor: Discovered views', ['count' => count($this->views)]);

        // Step 2: Get cabinet groups
        $this->groups = $this->getGroups();
        Log::debug('RhinoDataExtractor: Found groups', ['groups' => $this->groups]);

        // Step 3: Extract all text labels
        $this->textLabels = $this->rhinoMcp->getTextObjects();
        Log::debug('RhinoDataExtractor: Found text labels', ['count' => count($this->textLabels)]);

        // Step 4: Extract all dimensions
        $this->dimensions = $this->rhinoMcp->getDimensions();
        Log::debug('RhinoDataExtractor: Found dimensions', ['count' => count($this->dimensions)]);

        // Step 5: Extract fixtures (sinks, faucets)
        $this->fixtures = $this->extractFixtures();
        Log::debug('RhinoDataExtractor: Found fixtures', ['count' => count($this->fixtures)]);

        // Step 6: Build cabinet data by combining views
        $cabinets = [];
        foreach ($this->groups as $groupName) {
            if ($this->isCabinetGroup($groupName)) {
                $cabinet = $this->buildCabinetFromViews($groupName);
                if ($cabinet) {
                    $cabinets[] = $cabinet;
                }
            }
        }

        // Step 7: If no groups found, try to detect cabinets from text labels
        if (empty($cabinets)) {
            $cabinets = $this->detectCabinetsFromLabels();
        }

        Log::info('RhinoDataExtractor: Extraction complete', ['cabinet_count' => count($cabinets)]);

        return [
            'cabinets' => $cabinets,
            'views' => $this->views,
            'fixtures' => $this->fixtures,
            'raw_data' => [
                'groups' => $this->groups,
                'text_labels' => $this->textLabels,
                'dimensions' => $this->dimensions,
            ],
        ];
    }

    /**
     * Discover views by finding "Plan View" and "Elevation" text labels
     */
    protected function discoverViews(): void
    {
        // Always search all text objects to find view labels
        // View labels could be on any layer (commonly on layer '0' or 'Labels')
        $textLabels = $this->rhinoMcp->getTextObjects();

        foreach ($textLabels as $label) {
            $text = $label['text'] ?? '';
            $viewType = $this->detectViewType($text);

            if ($viewType) {
                $x = $label['x'] ?? 0;
                $y = $label['y'] ?? 0;

                $this->views[] = [
                    'label' => $text,
                    'type' => $viewType,
                    'center' => ['x' => $x, 'y' => $y],
                    'bounds' => [
                        // Expand bounds for elevation views (typically taller)
                        'x_min' => $x - 250,
                        'x_max' => $x + 250,
                        'y_min' => $y - 100,  // Label is usually at top of view
                        'y_max' => $y + 300,  // View content is below label
                    ],
                ];
            }
        }
    }

    /**
     * Detect view type from text label
     *
     * @param string $text Label text
     * @return string|null View type or null
     */
    protected function detectViewType(string $text): ?string
    {
        $text = strtolower($text);

        foreach (self::VIEW_PATTERNS as $type => $patterns) {
            foreach ($patterns as $pattern) {
                if (stripos($text, strtolower($pattern)) !== false) {
                    return $type;
                }
            }
        }

        return null;
    }

    /**
     * Get all groups from the Rhino document
     *
     * @return array List of group names
     */
    protected function getGroups(): array
    {
        return $this->rhinoMcp->getGroups();
    }

    /**
     * Check if a group name represents a cabinet
     *
     * @param string $groupName Group name to check
     * @return bool
     */
    protected function isCabinetGroup(string $groupName): bool
    {
        // Exclude small/helper groups
        if (strlen($groupName) <= 3 && !preg_match('/^[A-Z]\d{1,2}$/i', $groupName)) {
            return false;
        }

        // Check against cabinet patterns
        foreach (self::CABINET_GROUP_PATTERNS as $pattern) {
            if (preg_match($pattern, $groupName)) {
                return true;
            }
        }

        // Check if name suggests a cabinet
        $cabinetKeywords = ['Van', 'Vanity', 'W/D', 'Base', 'Wall', 'Tall', 'Pantry', 'Sink'];
        foreach ($cabinetKeywords as $keyword) {
            if (stripos($groupName, $keyword) !== false) {
                return true;
            }
        }

        return false;
    }

    /**
     * Build cabinet data by combining Plan View (depth) + Elevation (height)
     *
     * @param string $groupName Cabinet group name
     * @return array|null Cabinet data or null if insufficient data
     */
    protected function buildCabinetFromViews(string $groupName): ?array
    {
        // Get objects in this group
        $objectIds = $this->rhinoMcp->getObjectsByGroup($groupName);

        if (empty($objectIds)) {
            return null;
        }

        // Get bounding box of all group objects
        $bbox = $this->rhinoMcp->getBoundingBox($objectIds);

        // Find labels that reference this cabinet
        $cabinetLabels = $this->findCabinetLabels($groupName);

        // Find views containing this cabinet's labels
        $elevationView = $this->findViewContaining($cabinetLabels, 'elevation');
        $planView = $this->findViewContaining($cabinetLabels, 'plan');

        // Extract dimensions from each view
        $width = null;
        $height = null;
        $depth = null;
        $elevationDims = [];
        $planDims = [];

        if ($elevationView) {
            $elevationDims = $this->getDimensionsInView($elevationView);
            $height = $this->findLikelyHeight($elevationDims);
            $width = $this->findLikelyWidth($elevationDims);
        }

        if ($planView) {
            $planDims = $this->getDimensionsInView($planView);
            $depth = $this->findLikelyDepth($planDims);

            // Width from plan view can override elevation if not found
            if (!$width) {
                $width = $this->findLikelyWidth($planDims);
            }
        }

        // Fallback to bounding box if dimensions not found
        if ($bbox) {
            if (!$width) {
                $width = round($bbox['max'][0] - $bbox['min'][0], 4);
            }
            if (!$depth) {
                $depth = round($bbox['max'][1] - $bbox['min'][1], 4);
            }
        }

        // Extract components (doors, drawers, etc.)
        $components = $this->extractComponents($groupName, $cabinetLabels);

        // Extract face frame details
        $faceFrame = $this->extractFaceFrameDetails($elevationDims);

        // Find nearby fixtures
        $nearbyFixtures = $this->getFixturesNear($bbox ?? ['min' => [0, 0, 0], 'max' => [100, 100, 100]]);

        return [
            'name' => $groupName,
            'identifier' => $this->parseIdentifierFromGroup($groupName),
            'width' => $width,
            'height' => $height,
            'depth' => $depth,
            'bounding_box' => $bbox,
            'elevation_view' => $elevationView,
            'plan_view' => $planView,
            'elevation_dims' => $elevationDims,
            'plan_dims' => $planDims,
            'face_frame' => $faceFrame,
            'components' => $components,
            'fixtures' => $nearbyFixtures,
            'labels' => $cabinetLabels,
            'confidence' => $this->calculateConfidence($width, $height, $depth, $components),
        ];
    }

    /**
     * Find text labels that reference a cabinet
     *
     * @param string $groupName Cabinet group name
     * @return array Matching labels
     */
    protected function findCabinetLabels(string $groupName): array
    {
        $matches = [];

        // Extract identifier from group name (e.g., "Austin-Van" -> "Van", "Austin-Vanity")
        $identifier = $this->parseIdentifierFromGroup($groupName);

        foreach ($this->textLabels as $label) {
            $text = $label['text'] ?? '';

            // Check if label contains the group name or identifier
            if (stripos($text, $groupName) !== false ||
                stripos($text, $identifier) !== false ||
                stripos($text, str_replace('-', ' ', $groupName)) !== false) {
                $matches[] = $label;
            }
        }

        return $matches;
    }

    /**
     * Parse identifier from group name
     *
     * @param string $groupName e.g., "Austin-Vanity"
     * @return string e.g., "Vanity"
     */
    protected function parseIdentifierFromGroup(string $groupName): string
    {
        $parts = explode('-', $groupName);
        return count($parts) > 1 ? end($parts) : $groupName;
    }

    /**
     * Find a view containing the given labels
     *
     * @param array $labels Labels to search for
     * @param string $viewType Target view type
     * @return array|null View data or null
     */
    protected function findViewContaining(array $labels, string $viewType): ?array
    {
        foreach ($this->views as $view) {
            if ($view['type'] !== $viewType) {
                continue;
            }

            $bounds = $view['bounds'];

            foreach ($labels as $label) {
                $x = $label['x'] ?? 0;
                $y = $label['y'] ?? 0;

                if ($x >= $bounds['x_min'] && $x <= $bounds['x_max'] &&
                    $y >= $bounds['y_min'] && $y <= $bounds['y_max']) {
                    return $view;
                }
            }
        }

        return null;
    }

    /**
     * Get dimensions within a view's bounds
     *
     * @param array $view View data with bounds
     * @return array Dimensions in the view
     */
    protected function getDimensionsInView(array $view): array
    {
        $bounds = $view['bounds'];
        $viewDims = [];

        foreach ($this->dimensions as $dim) {
            // Check using center position (from BoundingBox calculation)
            $center = $dim['center'] ?? null;

            if ($center === null) {
                continue;
            }

            $x = $center[0] ?? 0;
            $y = $center[1] ?? 0;

            // Check if dimension is within view bounds
            if ($x >= $bounds['x_min'] && $x <= $bounds['x_max'] &&
                $y >= $bounds['y_min'] && $y <= $bounds['y_max']) {

                // Parse dimension value
                $value = $dim['value'] ?? null;
                $text = $dim['text'] ?? '';

                if ($value === null && $text) {
                    $value = $this->rhinoMcp->parseDimensionText($text);
                }

                $dim['parsed_value'] = $value;

                // Determine orientation from text annotation or default based on value
                // Common cabinet heights are 30-42", widths are 12-48"
                // This is a heuristic - we'll also use the layer name for clues
                $dim['orientation'] = $this->inferDimensionOrientation($dim);
                $viewDims[] = $dim;
            }
        }

        return $viewDims;
    }

    /**
     * Infer dimension orientation from context
     *
     * @param array $dim Dimension data
     * @return string 'horizontal', 'vertical', or 'unknown'
     */
    protected function inferDimensionOrientation(array $dim): string
    {
        $layer = strtolower($dim['layer'] ?? '');
        $text = strtolower($dim['text'] ?? '');
        $value = $dim['parsed_value'] ?? $dim['value'] ?? null;

        // Check layer name for clues
        if (strpos($layer, 'height') !== false || strpos($layer, 'vert') !== false) {
            return 'vertical';
        }
        if (strpos($layer, 'width') !== false || strpos($layer, 'horiz') !== false) {
            return 'horizontal';
        }

        // Use value ranges as heuristic
        // Values 30-96" are likely heights
        // Values 1.5-3" are likely face frame (stile=horizontal, rail=vertical)
        // This is imperfect but helps with extraction
        if ($value !== null) {
            if ($value >= 1.4 && $value <= 2.1) {
                // Face frame dimensions - assume stile (horizontal) by default
                return 'horizontal';
            }
            if ($value >= 30 && $value <= 96) {
                // Likely a height dimension
                return 'vertical';
            }
            if ($value >= 9 && $value <= 48) {
                // Could be width - default to horizontal
                return 'horizontal';
            }
        }

        return 'unknown';
    }

    /**
     * Determine dimension orientation (horizontal or vertical)
     *
     * @param array $points Dimension points
     * @return string 'horizontal' or 'vertical'
     */
    protected function getDimensionOrientation(array $points): string
    {
        if (count($points) < 2) {
            return 'unknown';
        }

        $dx = abs(($points[1][0] ?? 0) - ($points[0][0] ?? 0));
        $dy = abs(($points[1][1] ?? 0) - ($points[0][1] ?? 0));

        return $dx > $dy ? 'horizontal' : 'vertical';
    }

    /**
     * Find the likely cabinet height from elevation dimensions
     *
     * @param array $dims Dimensions from elevation view
     * @return float|null Height in inches
     */
    protected function findLikelyHeight(array $dims): ?float
    {
        // Look for vertical dimensions in typical cabinet height range
        $candidates = [];

        foreach ($dims as $dim) {
            if ($dim['orientation'] !== 'vertical') {
                continue;
            }

            $value = $dim['parsed_value'] ?? null;

            if ($value !== null && $value >= 20 && $value <= 96) {
                $candidates[] = $value;
            }
        }

        if (empty($candidates)) {
            return null;
        }

        // Return the largest vertical dimension as likely overall height
        return max($candidates);
    }

    /**
     * Find the likely cabinet width from dimensions
     *
     * @param array $dims Dimensions from view
     * @return float|null Width in inches
     */
    protected function findLikelyWidth(array $dims): ?float
    {
        // Look for horizontal dimensions in typical cabinet width range
        $candidates = [];

        foreach ($dims as $dim) {
            if ($dim['orientation'] !== 'horizontal') {
                continue;
            }

            $value = $dim['parsed_value'] ?? null;

            if ($value !== null && $value >= 9 && $value <= 84) {
                $candidates[] = $value;
            }
        }

        if (empty($candidates)) {
            return null;
        }

        // Return the largest horizontal dimension as likely overall width
        return max($candidates);
    }

    /**
     * Find the likely cabinet depth from plan view dimensions
     *
     * @param array $dims Dimensions from plan view
     * @return float|null Depth in inches
     */
    protected function findLikelyDepth(array $dims): ?float
    {
        // In plan view, "vertical" dimension (Y direction) represents depth
        $candidates = [];

        foreach ($dims as $dim) {
            // For plan views, we look at the smaller dimension for depth
            $value = $dim['parsed_value'] ?? null;

            if ($value !== null && $value >= 10 && $value <= 36) {
                $candidates[] = $value;
            }
        }

        if (empty($candidates)) {
            return null;
        }

        // Return the dimension closest to common cabinet depths (19", 21", 24")
        $commonDepths = [19, 21, 24, 12, 15, 18];
        $bestMatch = null;
        $bestDistance = PHP_FLOAT_MAX;

        foreach ($candidates as $candidate) {
            foreach ($commonDepths as $common) {
                $distance = abs($candidate - $common);
                if ($distance < $bestDistance) {
                    $bestDistance = $distance;
                    $bestMatch = $candidate;
                }
            }
        }

        return $bestMatch;
    }

    /**
     * Extract face frame details from elevation dimensions
     *
     * @param array $dims Elevation dimensions
     * @return array Face frame details
     */
    protected function extractFaceFrameDetails(array $dims): array
    {
        $stileWidth = null;
        $railWidth = null;

        foreach ($dims as $dim) {
            $value = $dim['parsed_value'] ?? null;

            if ($value === null) {
                continue;
            }

            // Common face frame stile/rail widths: 1.5", 1.75", 2"
            if ($value >= 1.4 && $value <= 2.1) {
                if ($dim['orientation'] === 'horizontal' && !$stileWidth) {
                    $stileWidth = $value;
                } elseif ($dim['orientation'] === 'vertical' && !$railWidth) {
                    $railWidth = $value;
                }
            }
        }

        return [
            'detected' => $stileWidth !== null || $railWidth !== null,
            'stile_width' => $stileWidth,
            'rail_width' => $railWidth,
            'construction_type' => ($stileWidth !== null || $railWidth !== null) ? 'face_frame' : 'unknown',
        ];
    }

    /**
     * Extract component counts from labels near the cabinet
     *
     * @param string $groupName Cabinet group name
     * @param array $cabinetLabels Labels associated with cabinet
     * @return array Component information
     */
    protected function extractComponents(string $groupName, array $cabinetLabels): array
    {
        $components = [
            'drawer_count' => 0,
            'door_count' => 0,
            'shelf_count' => 0,
            'pullout_count' => 0,
            'has_u_shaped_drawer' => false,
            'has_lazy_susan' => false,
            'detected_components' => [],
        ];

        foreach ($this->textLabels as $label) {
            $text = $label['text'] ?? '';

            foreach (self::COMPONENT_LABELS as $type => $patterns) {
                foreach ($patterns as $pattern) {
                    if (stripos($text, $pattern) !== false) {
                        $components['detected_components'][] = [
                            'type' => $type,
                            'label' => $text,
                            'position' => ['x' => $label['x'] ?? 0, 'y' => $label['y'] ?? 0],
                        ];

                        switch ($type) {
                            case 'drawer':
                                $components['drawer_count']++;
                                break;
                            case 'u_shaped_drawer':
                                $components['has_u_shaped_drawer'] = true;
                                $components['drawer_count']++;
                                break;
                            case 'door':
                                $components['door_count']++;
                                break;
                            case 'shelf':
                                $components['shelf_count']++;
                                break;
                            case 'pullout':
                                $components['pullout_count']++;
                                break;
                            case 'lazy_susan':
                                $components['has_lazy_susan'] = true;
                                break;
                        }
                        break 2;
                    }
                }
            }
        }

        return $components;
    }

    /**
     * Extract fixtures (sinks, faucets, appliances) from block instances
     *
     * @return array Fixture data
     */
    protected function extractFixtures(): array
    {
        $blockInstances = $this->rhinoMcp->getBlockInstances();
        $fixtures = [];

        foreach ($blockInstances as $block) {
            $attrs = $block['attributes'] ?? [];

            // Check if it's a fixture (has product/model attributes)
            if (isset($attrs['PRODUCT']) || isset($attrs['MODELNUMBER']) || isset($attrs['MODEL'])) {
                $fixtures[] = [
                    'block_name' => $block['block_name'] ?? 'Unknown',
                    'product' => $attrs['PRODUCT'] ?? null,
                    'model' => $attrs['MODELNUMBER'] ?? $attrs['MODEL'] ?? null,
                    'manufacturer' => $attrs['MANUFACTURER'] ?? null,
                    'material' => $attrs['MATERIAL'] ?? null,
                    'position' => $block['insertion_point'] ?? [0, 0, 0],
                    'attributes' => $attrs,
                ];
            }
        }

        return $fixtures;
    }

    /**
     * Get fixtures near a bounding box
     *
     * @param array $bbox Bounding box with min/max
     * @return array Nearby fixtures
     */
    protected function getFixturesNear(array $bbox): array
    {
        $nearby = [];
        $tolerance = 50; // inches

        $min = $bbox['min'] ?? [0, 0, 0];
        $max = $bbox['max'] ?? [100, 100, 100];

        foreach ($this->fixtures as $fixture) {
            $pos = $fixture['position'] ?? [0, 0, 0];

            if ($pos[0] >= ($min[0] - $tolerance) && $pos[0] <= ($max[0] + $tolerance) &&
                $pos[1] >= ($min[1] - $tolerance) && $pos[1] <= ($max[1] + $tolerance)) {
                $nearby[] = $fixture;
            }
        }

        return $nearby;
    }

    /**
     * Detect cabinets from text labels when no groups exist
     *
     * @return array Detected cabinets
     */
    protected function detectCabinetsFromLabels(): array
    {
        $cabinets = [];
        $processed = [];

        foreach ($this->textLabels as $label) {
            $text = $label['text'] ?? '';

            // Look for cabinet identifier patterns
            if (preg_match('/([A-Z][a-z]*[-]?(?:Van(?:ity)?|W\/D|Base|Wall|Tall|Sink))/i', $text, $matches)) {
                $identifier = $matches[1];

                if (in_array($identifier, $processed)) {
                    continue;
                }
                $processed[] = $identifier;

                // Find associated dimensions
                $nearbyDims = $this->findDimensionsNear($label['x'] ?? 0, $label['y'] ?? 0);

                // Build cabinet from nearby data
                $cabinet = [
                    'name' => $identifier,
                    'identifier' => $identifier,
                    'width' => $this->findLikelyWidth($nearbyDims),
                    'height' => $this->findLikelyHeight($nearbyDims),
                    'depth' => $this->findLikelyDepth($nearbyDims),
                    'bounding_box' => null,
                    'elevation_dims' => $nearbyDims,
                    'plan_dims' => [],
                    'face_frame' => $this->extractFaceFrameDetails($nearbyDims),
                    'components' => $this->extractComponentsNear($label['x'] ?? 0, $label['y'] ?? 0),
                    'fixtures' => [],
                    'labels' => [$label],
                    'detected_from' => 'label_search',
                    'confidence' => 'low',
                ];

                if ($cabinet['width'] || $cabinet['height']) {
                    $cabinets[] = $cabinet;
                }
            }
        }

        return $cabinets;
    }

    /**
     * Find dimensions near a point
     *
     * @param float $x X coordinate
     * @param float $y Y coordinate
     * @param float $radius Search radius
     * @return array Nearby dimensions
     */
    protected function findDimensionsNear(float $x, float $y, float $radius = 100): array
    {
        $nearby = [];

        foreach ($this->dimensions as $dim) {
            $center = $dim['center'] ?? null;

            if ($center === null) {
                continue;
            }

            $dx = abs(($center[0] ?? 0) - $x);
            $dy = abs(($center[1] ?? 0) - $y);

            if ($dx <= $radius && $dy <= $radius) {
                $dim['parsed_value'] = $dim['value'] ?? $this->rhinoMcp->parseDimensionText($dim['text'] ?? '');
                $dim['orientation'] = $this->inferDimensionOrientation($dim);
                $nearby[] = $dim;
            }
        }

        return $nearby;
    }

    /**
     * Extract components near a point
     *
     * @param float $x X coordinate
     * @param float $y Y coordinate
     * @param float $radius Search radius
     * @return array Component data
     */
    protected function extractComponentsNear(float $x, float $y, float $radius = 150): array
    {
        $components = [
            'drawer_count' => 0,
            'door_count' => 0,
            'shelf_count' => 0,
            'pullout_count' => 0,
            'has_u_shaped_drawer' => false,
            'has_lazy_susan' => false,
            'detected_components' => [],
        ];

        foreach ($this->textLabels as $label) {
            $lx = $label['x'] ?? 0;
            $ly = $label['y'] ?? 0;

            if (abs($lx - $x) > $radius || abs($ly - $y) > $radius) {
                continue;
            }

            $text = $label['text'] ?? '';

            foreach (self::COMPONENT_LABELS as $type => $patterns) {
                foreach ($patterns as $pattern) {
                    if (stripos($text, $pattern) !== false) {
                        switch ($type) {
                            case 'drawer':
                                $components['drawer_count']++;
                                break;
                            case 'u_shaped_drawer':
                                $components['has_u_shaped_drawer'] = true;
                                break;
                            case 'door':
                                $components['door_count']++;
                                break;
                            case 'shelf':
                                $components['shelf_count']++;
                                break;
                            case 'pullout':
                                $components['pullout_count']++;
                                break;
                            case 'lazy_susan':
                                $components['has_lazy_susan'] = true;
                                break;
                        }
                        break 2;
                    }
                }
            }
        }

        return $components;
    }

    /**
     * Calculate confidence score for extracted cabinet data
     *
     * @param float|null $width
     * @param float|null $height
     * @param float|null $depth
     * @param array $components
     * @return string 'high', 'medium', or 'low'
     */
    protected function calculateConfidence(?float $width, ?float $height, ?float $depth, array $components): string
    {
        $score = 0;

        // Dimensions found
        if ($width !== null) {
            $score += 25;
        }
        if ($height !== null) {
            $score += 25;
        }
        if ($depth !== null) {
            $score += 20;
        }

        // Components detected
        if (!empty($components['detected_components'])) {
            $score += 15;
        }

        // Component counts
        if (($components['drawer_count'] ?? 0) > 0 || ($components['door_count'] ?? 0) > 0) {
            $score += 15;
        }

        if ($score >= 70) {
            return 'high';
        }
        if ($score >= 40) {
            return 'medium';
        }
        return 'low';
    }

    /**
     * Get document summary for quick analysis
     *
     * @return array Summary information
     */
    public function getDocumentSummary(): array
    {
        $docInfo = $this->rhinoMcp->getDocumentInfo();
        $layers = $this->rhinoMcp->getLayers();
        $groups = $this->rhinoMcp->getGroups();
        $textObjects = $this->rhinoMcp->getTextObjects();

        return [
            'document_info' => $docInfo,
            'layer_count' => count($layers),
            'layers' => $layers,
            'group_count' => count($groups),
            'groups' => $groups,
            'text_label_count' => count($textObjects),
            'potential_cabinet_groups' => array_filter($groups, fn($g) => $this->isCabinetGroup($g)),
        ];
    }

    /**
     * Analyze views in the document
     *
     * @return array View analysis
     */
    public function analyzeViews(): array
    {
        $this->discoverViews();

        $analysis = [
            'view_count' => count($this->views),
            'views_by_type' => [
                'plan' => [],
                'elevation' => [],
                'detail' => [],
            ],
            'all_views' => $this->views,
        ];

        foreach ($this->views as $view) {
            $type = $view['type'] ?? 'unknown';
            if (isset($analysis['views_by_type'][$type])) {
                $analysis['views_by_type'][$type][] = $view['label'];
            }
        }

        return $analysis;
    }
}
