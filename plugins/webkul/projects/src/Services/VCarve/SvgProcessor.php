<?php

namespace Webkul\Project\Services\VCarve;

/**
 * VCarve SVG Processor
 *
 * Processes VCarve-generated SVGs to improve visualization:
 * - Increases stroke widths for visibility
 * - Applies distinct colors per layer/operation type
 * - Scales viewBox for proper rendering
 * - Extracts layer information for legends
 * - Interprets tool strings and operations for shop floor use
 */
class SvgProcessor
{
    /**
     * Color palette for different operation types
     */
    protected array $layerColors = [
        'Toolpath Previews' => '#1e40af', // Blue
        'Labels' => '#374151',             // Gray
        'Shelf Pins' => '#059669',         // Green
        'slide' => '#7c3aed',              // Purple (drawer slides)
        'Drill' => '#dc2626',              // Red
        'Pocket' => '#ea580c',             // Orange
        'Profile' => '#0891b2',            // Cyan
        'Pin Pocket' => '#65a30d',         // Lime
        'PreFin' => '#6366f1',             // Indigo
        'Medex' => '#f59e0b',              // Amber
        'RiftWO' => '#84cc16',             // Lime
        'default' => '#374151',            // Gray
    ];

    /**
     * Operation type descriptions for shop floor
     */
    protected array $operationDescriptions = [
        'shelf_pins' => [
            'icon' => 'grid-dots',
            'label' => 'Shelf Pin Holes',
            'description' => 'Adjustable shelf support holes',
            'hardware' => 'Shelf pins (5mm)',
        ],
        'slide' => [
            'icon' => 'arrows-horizontal',
            'label' => 'Drawer Slide Holes',
            'description' => 'Mounting holes for drawer slides',
            'hardware' => 'Drawer slides',
        ],
        'hinge' => [
            'icon' => 'door-open',
            'label' => 'Hinge Boring',
            'description' => '35mm cup holes for European hinges',
            'hardware' => 'Blum hinges',
        ],
        'pocket' => [
            'icon' => 'square-minus',
            'label' => 'Pocket Cut',
            'description' => 'Recessed area for joinery or inlay',
            'hardware' => null,
        ],
        'profile' => [
            'icon' => 'cut',
            'label' => 'Profile Cut',
            'description' => 'Perimeter cut to separate part from sheet',
            'hardware' => null,
        ],
        'drilling' => [
            'icon' => 'drill',
            'label' => 'Drilling',
            'description' => 'Through or blind holes',
            'hardware' => null,
        ],
        'dado' => [
            'icon' => 'minus',
            'label' => 'Dado/Groove',
            'description' => 'Channel for fixed shelves or backs',
            'hardware' => null,
        ],
    ];

    /**
     * Minimum stroke width for visibility
     */
    protected float $minStrokeWidth = 0.5;

    /**
     * Process an SVG string for better visualization
     */
    public function process(string $svg): array
    {
        // Extract layers for legend
        $layers = $this->extractLayers($svg);

        // Process the SVG content
        $processedSvg = $this->enhanceSvg($svg);

        return [
            'svg' => $processedSvg,
            'layers' => $layers,
        ];
    }

    /**
     * Extract layer information from SVG with interpreted metadata
     */
    public function extractLayers(string $svg): array
    {
        $layers = [];

        // Find all inkscape:label attributes
        preg_match_all('/inkscape:label="([^"]+)"/', $svg, $matches);

        foreach ($matches[1] as $label) {
            // Skip internal/setup layers
            if (str_starts_with($label, 'xx') || $label === 'Toolpath Previews') {
                continue;
            }

            $type = $this->classifyLayer($label);
            $interpretation = $this->interpretLayer($label, $type);

            $layers[$label] = [
                'id' => $this->slugify($label),
                'name' => $label,
                'color' => $this->getColorForLayer($label),
                'type' => $type,
                'icon' => $interpretation['icon'],
                'description' => $interpretation['description'],
                'hardware' => $interpretation['hardware'],
                'displayName' => $interpretation['displayName'],
            ];
        }

        return $layers;
    }

    /**
     * Interpret a layer name into useful shop floor information
     */
    protected function interpretLayer(string $layerName, string $type): array
    {
        $name = strtolower($layerName);

        // Check for known operation patterns
        if (str_contains($name, 'shelf') && str_contains($name, 'pin')) {
            return [
                'icon' => 'squares-2x2',
                'displayName' => 'Shelf Pin Holes',
                'description' => 'Adjustable shelf support holes (5mm)',
                'hardware' => ['type' => 'shelf_pins', 'size' => '5mm', 'note' => 'For adjustable shelves'],
            ];
        }

        if (str_contains($name, 'slide')) {
            return [
                'icon' => 'arrows-right-left',
                'displayName' => 'Drawer Slide Mounting',
                'description' => 'Holes for side-mount drawer slides',
                'hardware' => ['type' => 'drawer_slides', 'note' => 'Check drawer depth for slide length'],
            ];
        }

        if (str_contains($name, 'hinge') || str_contains($name, '35mm')) {
            return [
                'icon' => 'rectangle-group',
                'displayName' => 'Hinge Boring',
                'description' => '35mm cup holes for European hinges',
                'hardware' => ['type' => 'hinges', 'size' => '35mm', 'note' => 'Blum or similar Euro hinges'],
            ];
        }

        if (str_contains($name, 'pin pocket') || str_contains($name, 'pinpocket')) {
            return [
                'icon' => 'square-2-stack',
                'displayName' => 'Pin Pocket',
                'description' => 'Pocket for shelf pin support strip',
                'hardware' => ['type' => 'shelf_pins', 'note' => 'Standard 5mm pins'],
            ];
        }

        if (str_contains($name, 'pocket')) {
            return [
                'icon' => 'square-3-stack-3d',
                'displayName' => 'Pocket Cut',
                'description' => 'Recessed area for joinery',
                'hardware' => null,
            ];
        }

        if (str_contains($name, 'profile')) {
            return [
                'icon' => 'scissors',
                'displayName' => 'Profile Cut',
                'description' => 'Perimeter cut - separates part from sheet',
                'hardware' => null,
            ];
        }

        if (str_contains($name, 'drill')) {
            return [
                'icon' => 'wrench-screwdriver',
                'displayName' => 'Drilling',
                'description' => 'Through or blind holes',
                'hardware' => null,
            ];
        }

        if (str_contains($name, 'dado') || str_contains($name, 'groove')) {
            return [
                'icon' => 'minus',
                'displayName' => 'Dado/Groove',
                'description' => 'Channel for fixed shelves or panel backs',
                'hardware' => null,
            ];
        }

        if (str_contains($name, 'label')) {
            return [
                'icon' => 'tag',
                'displayName' => 'Part Labels',
                'description' => 'Engraved identification text',
                'hardware' => null,
            ];
        }

        // Material layers (PreFin, Medex, RiftWO, etc.)
        if (preg_match('/(prefin|medex|rift|mdf|ply|oak|maple|walnut)/i', $name)) {
            return [
                'icon' => 'square-3-stack-3d',
                'displayName' => ucfirst($layerName),
                'description' => 'Material-specific toolpath layer',
                'hardware' => null,
            ];
        }

        return [
            'icon' => 'cube',
            'displayName' => $layerName,
            'description' => 'Toolpath operation',
            'hardware' => null,
        ];
    }

    /**
     * Parse a VCarve tool string into readable information
     * Example: "#46367-K SC MORTISE 2 FL 3/8" DIAx7/8x3/8" Shank Spektra"
     */
    public function parseToolString(string $toolString): array
    {
        $result = [
            'raw' => $toolString,
            'toolNumber' => null,
            'type' => null,
            'diameter' => null,
            'flutes' => null,
            'manufacturer' => null,
            'description' => null,
        ];

        // Extract tool number (e.g., #46367-K)
        if (preg_match('/#?(\d+[-\w]*)/i', $toolString, $matches)) {
            $result['toolNumber'] = $matches[1];
        }

        // Extract diameter (e.g., 3/8" DIA or 1/2")
        if (preg_match('/([\d\/]+)[""]?\s*DIA/i', $toolString, $matches)) {
            $result['diameter'] = $matches[1] . '"';
        } elseif (preg_match('/([\d\/]+)[""](?=x|$|\s)/i', $toolString, $matches)) {
            $result['diameter'] = $matches[1] . '"';
        }

        // Extract flute count
        if (preg_match('/(\d+)\s*FL/i', $toolString, $matches)) {
            $result['flutes'] = (int)$matches[1];
        }

        // Determine tool type
        $typeMappings = [
            'MORTISE' => ['type' => 'Mortising Bit', 'use' => 'Square pockets and mortises'],
            'SPIRAL' => ['type' => 'Spiral Bit', 'use' => 'Clean cuts with chip evacuation'],
            'BALL' => ['type' => 'Ball Nose', 'use' => '3D carving and contours'],
            'V-BIT' => ['type' => 'V-Bit', 'use' => 'Engraving and chamfers'],
            'DRILL' => ['type' => 'Drill Bit', 'use' => 'Precision holes'],
            'STRAIGHT' => ['type' => 'Straight Bit', 'use' => 'General purpose cutting'],
            'COMPRESSION' => ['type' => 'Compression', 'use' => 'Clean top and bottom edges'],
            'DOWNCUT' => ['type' => 'Downcut', 'use' => 'Clean top surface'],
            'UPCUT' => ['type' => 'Upcut', 'use' => 'Good chip evacuation'],
        ];

        foreach ($typeMappings as $keyword => $info) {
            if (stripos($toolString, $keyword) !== false) {
                $result['type'] = $info['type'];
                $result['description'] = $info['use'];
                break;
            }
        }

        // Check for manufacturer keywords
        $manufacturers = ['Spektra', 'Amana', 'Whiteside', 'CMT', 'Freud', 'Vortex'];
        foreach ($manufacturers as $mfg) {
            if (stripos($toolString, $mfg) !== false) {
                $result['manufacturer'] = $mfg;
                break;
            }
        }

        // Generate human-readable summary
        $parts = [];
        if ($result['diameter']) $parts[] = $result['diameter'];
        if ($result['type']) $parts[] = $result['type'];
        if ($result['flutes']) $parts[] = $result['flutes'] . '-flute';

        $result['summary'] = !empty($parts) ? implode(' ', $parts) : $toolString;

        return $result;
    }

    /**
     * Create a CSS-safe ID from layer name
     */
    protected function slugify(string $text): string
    {
        return preg_replace('/[^a-z0-9]+/', '-', strtolower($text));
    }

    /**
     * Get color for a layer based on its name
     */
    protected function getColorForLayer(string $layerName): string
    {
        foreach ($this->layerColors as $keyword => $color) {
            if (stripos($layerName, $keyword) !== false) {
                return $color;
            }
        }

        return $this->layerColors['default'];
    }

    /**
     * Classify layer type for grouping
     */
    protected function classifyLayer(string $layerName): string
    {
        $name = strtolower($layerName);

        if (str_contains($name, 'shelf') || str_contains($name, 'pin')) {
            return 'hardware';
        }
        if (str_contains($name, 'slide') || str_contains($name, 'drawer')) {
            return 'hardware';
        }
        if (str_contains($name, 'drill')) {
            return 'drilling';
        }
        if (str_contains($name, 'pocket')) {
            return 'pocket';
        }
        if (str_contains($name, 'profile')) {
            return 'profile';
        }
        if (str_contains($name, 'label')) {
            return 'labels';
        }

        return 'material';
    }

    /**
     * Enhance SVG for better visualization
     */
    protected function enhanceSvg(string $svg): string
    {
        // Fix viewBox and dimensions for proper scaling
        $svg = $this->fixDimensions($svg);

        // Increase stroke widths
        $svg = $this->enhanceStrokes($svg);

        // Apply colors per layer
        $svg = $this->applyLayerColors($svg);

        // Add interactive data attributes to layer groups
        $svg = $this->addLayerInteractivity($svg);

        // Add background
        $svg = $this->addBackground($svg);

        return $svg;
    }

    /**
     * Add data attributes to layer groups for JavaScript interactivity
     */
    protected function addLayerInteractivity(string $svg): string
    {
        // Add data-layer attribute and CSS class to each inkscape layer group
        $svg = preg_replace_callback(
            '/(<g[^>]*inkscape:label="([^"]+)"[^>]*)>/i',
            function ($matches) {
                $layerId = $this->slugify($matches[2]);
                $existingTag = $matches[1];

                // Add data attribute and class for hover/click targeting
                if (!str_contains($existingTag, 'data-layer=')) {
                    return $existingTag . ' data-layer="' . $layerId . '" class="vcarve-layer vcarve-layer-' . $layerId . '">';
                }
                return $matches[0];
            },
            $svg
        );

        return $svg;
    }

    /**
     * Fix SVG dimensions for responsive rendering
     */
    protected function fixDimensions(string $svg): string
    {
        // Remove fixed width/height, keep viewBox
        $svg = preg_replace('/\s+width="[^"]*"/', '', $svg);
        $svg = preg_replace('/\s+height="[^"]*"/', '', $svg);

        // Add responsive attributes
        $svg = preg_replace(
            '/<svg([^>]*)>/',
            '<svg$1 width="100%" height="100%" preserveAspectRatio="xMidYMid meet">',
            $svg
        );

        return $svg;
    }

    /**
     * Increase stroke widths for visibility
     */
    protected function enhanceStrokes(string $svg): string
    {
        // Replace thin stroke widths with minimum
        $svg = preg_replace_callback(
            '/stroke-width:([\d.]+)/',
            function ($matches) {
                $width = (float) $matches[1];
                $newWidth = max($width, $this->minStrokeWidth);
                return "stroke-width:{$newWidth}";
            },
            $svg
        );

        // Add default stroke width to elements without one
        $svg = preg_replace(
            '/(<path[^>]*style="[^"]*)(")/',
            '$1;stroke-width:' . $this->minStrokeWidth . '$2',
            $svg
        );

        return $svg;
    }

    /**
     * Apply distinct colors to each layer
     */
    protected function applyLayerColors(string $svg): string
    {
        // Find each layer group and apply colors
        foreach ($this->layerColors as $keyword => $color) {
            if ($keyword === 'default') continue;

            // Match layer groups containing the keyword
            $pattern = '/(<g[^>]*inkscape:label="[^"]*' . preg_quote($keyword, '/') . '[^"]*"[^>]*)(style="[^"]*stroke:#[0-9a-fA-F]{6})/i';

            $svg = preg_replace_callback(
                $pattern,
                function ($matches) use ($color) {
                    // Replace the stroke color in the style
                    $newStyle = preg_replace('/stroke:#[0-9a-fA-F]{6}/', 'stroke:' . $color, $matches[2]);
                    return $matches[1] . $newStyle;
                },
                $svg
            );
        }

        return $svg;
    }

    /**
     * Add a white background rectangle
     */
    protected function addBackground(string $svg): string
    {
        // Extract viewBox to create matching background
        if (preg_match('/viewBox="([\d.]+)\s+([\d.]+)\s+([\d.]+)\s+([\d.]+)"/', $svg, $matches)) {
            $x = $matches[1];
            $y = $matches[2];
            $width = $matches[3];
            $height = $matches[4];

            $background = '<rect x="' . $x . '" y="' . $y . '" width="' . $width . '" height="' . $height . '" fill="#f8fafc" stroke="#e2e8f0" stroke-width="0.5"/>';

            // Insert after opening svg tag
            $svg = preg_replace(
                '/(<svg[^>]*>)/',
                '$1' . "\n" . $background,
                $svg
            );
        }

        return $svg;
    }

    /**
     * Generate HTML legend for layers
     */
    public function generateLegend(array $layers): string
    {
        if (empty($layers)) {
            return '';
        }

        $html = '<div class="flex flex-wrap gap-3 p-3 bg-gray-50 dark:bg-gray-800 rounded-lg">';

        foreach ($layers as $layer) {
            $html .= sprintf(
                '<div class="flex items-center gap-2">
                    <span class="w-4 h-4 rounded" style="background-color: %s"></span>
                    <span class="text-sm text-gray-700 dark:text-gray-300">%s</span>
                </div>',
                $layer['color'],
                htmlspecialchars($layer['name'])
            );
        }

        $html .= '</div>';

        return $html;
    }
}
