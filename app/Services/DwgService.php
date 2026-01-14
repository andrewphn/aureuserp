<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Log;
use Exception;

/**
 * DWG/DXF Parsing Service
 * 
 * Provides server-side parsing of DWG and DXF CAD files.
 * Uses LibreDWG command-line tools when available, with fallback
 * to native PHP DXF parsing for DXF files.
 */
class DwgService
{
    /**
     * Path to LibreDWG binaries (if installed)
     */
    protected ?string $libreDwgPath;

    /**
     * Temporary directory for file processing
     */
    protected string $tempDir;

    /**
     * Supported entity types
     */
    public const ENTITY_TYPES = [
        'LINE', 'POLYLINE', 'LWPOLYLINE', 'CIRCLE', 'ARC',
        'ELLIPSE', 'SPLINE', 'TEXT', 'MTEXT', 'DIMENSION',
        'INSERT', 'BLOCK', 'HATCH', 'SOLID', 'POINT', '3DFACE',
    ];

    public function __construct()
    {
        $this->libreDwgPath = config('services.libredwg.path');
        $this->tempDir = storage_path('app/temp/dwg');
        
        if (!is_dir($this->tempDir)) {
            mkdir($this->tempDir, 0755, true);
        }
    }

    /**
     * Parse a DWG or DXF file
     *
     * @param string $filePath Path to the file
     * @param array $options Parsing options
     * @return array Parsed data
     */
    public function parse(string $filePath, array $options = []): array
    {
        if (!file_exists($filePath)) {
            throw new Exception("File not found: {$filePath}");
        }

        $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));

        return match ($extension) {
            'dwg' => $this->parseDwg($filePath, $options),
            'dxf' => $this->parseDxf($filePath, $options),
            default => throw new Exception("Unsupported file format: {$extension}"),
        };
    }

    /**
     * Parse a DXF file (native PHP implementation)
     *
     * @param string $filePath Path to DXF file
     * @param array $options Parsing options
     * @return array Parsed data
     */
    public function parseDxf(string $filePath, array $options = []): array
    {
        $content = file_get_contents($filePath);
        
        if ($content === false) {
            throw new Exception("Could not read file: {$filePath}");
        }

        $parser = new DxfFileParser();
        $data = $parser->parse($content);

        return array_merge($data, [
            'format' => 'DXF',
            'filename' => basename($filePath),
            'filesize' => filesize($filePath),
        ]);
    }

    /**
     * Parse a DWG file
     *
     * @param string $filePath Path to DWG file
     * @param array $options Parsing options
     * @return array Parsed data
     */
    public function parseDwg(string $filePath, array $options = []): array
    {
        // Try LibreDWG first if available
        if ($this->isLibreDwgAvailable()) {
            return $this->parseDwgWithLibreDwg($filePath, $options);
        }

        // Fallback: Try to convert using ODAFileConverter if available
        if ($this->isOdaConverterAvailable()) {
            return $this->parseDwgWithOdaConverter($filePath, $options);
        }

        // Last resort: Return partial info from binary header
        return $this->parseDwgHeader($filePath);
    }

    /**
     * Check if LibreDWG tools are available
     */
    public function isLibreDwgAvailable(): bool
    {
        if ($this->libreDwgPath) {
            return file_exists($this->libreDwgPath . '/dwgread');
        }

        // Try to find in PATH
        $result = Process::timeout(5)->run('which dwgread 2>/dev/null');
        return $result->successful() && !empty(trim($result->output()));
    }

    /**
     * Check if ODA File Converter is available
     */
    public function isOdaConverterAvailable(): bool
    {
        $result = Process::timeout(5)->run('which ODAFileConverter 2>/dev/null');
        return $result->successful() && !empty(trim($result->output()));
    }

    /**
     * Parse DWG using LibreDWG command-line tools
     */
    protected function parseDwgWithLibreDwg(string $filePath, array $options): array
    {
        $dwgread = $this->libreDwgPath ? $this->libreDwgPath . '/dwgread' : 'dwgread';
        $outputFile = $this->tempDir . '/' . uniqid('dwg_') . '.json';

        try {
            // Convert DWG to JSON using dwgread
            $result = Process::timeout(120)
                ->run("{$dwgread} -O json -o {$outputFile} {$filePath} 2>&1");

            if (!$result->successful()) {
                Log::warning("LibreDWG dwgread failed: " . $result->errorOutput());
                
                // Try converting to DXF instead
                return $this->convertDwgToDxf($filePath, $options);
            }

            if (file_exists($outputFile)) {
                $jsonContent = file_get_contents($outputFile);
                $data = json_decode($jsonContent, true);
                
                unlink($outputFile);
                
                return $this->normalizeLibreDwgOutput($data, $filePath);
            }

            throw new Exception('LibreDWG did not produce output file');
        } finally {
            if (file_exists($outputFile)) {
                @unlink($outputFile);
            }
        }
    }

    /**
     * Convert DWG to DXF using LibreDWG
     */
    protected function convertDwgToDxf(string $filePath, array $options): array
    {
        $dwg2dxf = $this->libreDwgPath ? $this->libreDwgPath . '/dwg2dxf' : 'dwg2dxf';
        $outputFile = $this->tempDir . '/' . uniqid('dwg_') . '.dxf';

        try {
            $result = Process::timeout(120)
                ->run("{$dwg2dxf} -o {$outputFile} {$filePath} 2>&1");

            if ($result->successful() && file_exists($outputFile)) {
                $data = $this->parseDxf($outputFile, $options);
                $data['originalFormat'] = 'DWG';
                $data['conversionMethod'] = 'libredwg';
                
                unlink($outputFile);
                return $data;
            }

            throw new Exception('DWG to DXF conversion failed');
        } finally {
            if (file_exists($outputFile)) {
                @unlink($outputFile);
            }
        }
    }

    /**
     * Parse DWG using ODA File Converter
     */
    protected function parseDwgWithOdaConverter(string $filePath, array $options): array
    {
        $inputDir = dirname($filePath);
        $outputDir = $this->tempDir . '/' . uniqid('oda_');
        mkdir($outputDir, 0755, true);

        try {
            // ODAFileConverter [input_folder] [output_folder] [output_version] [output_type] [recurse] [audit]
            $result = Process::timeout(120)
                ->run("ODAFileConverter '{$inputDir}' '{$outputDir}' 'ACAD2018' 'DXF' '0' '1' '*." . 
                      basename($filePath) . "' 2>&1");

            // Find the converted file
            $convertedFiles = glob($outputDir . '/*.dxf');
            
            if (!empty($convertedFiles)) {
                $data = $this->parseDxf($convertedFiles[0], $options);
                $data['originalFormat'] = 'DWG';
                $data['conversionMethod'] = 'oda';
                
                return $data;
            }

            throw new Exception('ODA conversion did not produce output');
        } finally {
            // Clean up
            if (is_dir($outputDir)) {
                array_map('unlink', glob($outputDir . '/*'));
                rmdir($outputDir);
            }
        }
    }

    /**
     * Parse basic DWG header information
     */
    protected function parseDwgHeader(string $filePath): array
    {
        $handle = fopen($filePath, 'rb');
        
        if (!$handle) {
            throw new Exception("Could not open file: {$filePath}");
        }

        try {
            // Read DWG version signature (first 6 bytes)
            $signature = fread($handle, 6);
            $version = $this->detectDwgVersion($signature);

            return [
                'format' => 'DWG',
                'filename' => basename($filePath),
                'filesize' => filesize($filePath),
                'version' => $version,
                'warning' => 'Full parsing unavailable. Install LibreDWG or ODA File Converter for complete parsing.',
                'entities' => [],
                'layers' => [],
                'bounds' => null,
                'stats' => [
                    'entityCount' => 0,
                    'layerCount' => 0,
                ],
            ];
        } finally {
            fclose($handle);
        }
    }

    /**
     * Detect DWG version from signature
     */
    protected function detectDwgVersion(string $signature): string
    {
        $versions = [
            'AC1015' => 'AutoCAD 2000',
            'AC1018' => 'AutoCAD 2004',
            'AC1021' => 'AutoCAD 2007',
            'AC1024' => 'AutoCAD 2010',
            'AC1027' => 'AutoCAD 2013',
            'AC1032' => 'AutoCAD 2018',
            'AC1035' => 'AutoCAD 2021',
        ];

        return $versions[$signature] ?? "Unknown ({$signature})";
    }

    /**
     * Normalize LibreDWG JSON output to standard format
     */
    protected function normalizeLibreDwgOutput(array $data, string $filePath): array
    {
        $entities = [];
        $layers = [];
        $bounds = [
            'minX' => PHP_FLOAT_MAX,
            'minY' => PHP_FLOAT_MAX,
            'maxX' => PHP_FLOAT_MIN,
            'maxY' => PHP_FLOAT_MIN,
        ];

        // Process objects from LibreDWG output
        if (isset($data['OBJECTS'])) {
            foreach ($data['OBJECTS'] as $object) {
                if (isset($object['type']) && in_array(strtoupper($object['type']), self::ENTITY_TYPES)) {
                    $entity = $this->normalizeEntity($object);
                    if ($entity) {
                        $entities[] = $entity;
                        $this->updateBounds($bounds, $entity);
                    }
                }
            }
        }

        // Process layers
        if (isset($data['TABLES']['LAYER'])) {
            foreach ($data['TABLES']['LAYER'] as $layer) {
                $layers[$layer['name'] ?? '0'] = [
                    'name' => $layer['name'] ?? '0',
                    'color' => $layer['color'] ?? 7,
                    'frozen' => $layer['frozen'] ?? false,
                ];
            }
        }

        return [
            'format' => 'DWG',
            'filename' => basename($filePath),
            'filesize' => filesize($filePath),
            'version' => $data['header']['$ACADVER'] ?? 'Unknown',
            'header' => $data['header'] ?? [],
            'entities' => $entities,
            'layers' => $layers,
            'bounds' => $bounds,
            'stats' => [
                'entityCount' => count($entities),
                'layerCount' => count($layers),
            ],
        ];
    }

    /**
     * Normalize an entity from LibreDWG format
     */
    protected function normalizeEntity(array $object): ?array
    {
        $type = strtoupper($object['type'] ?? '');
        
        $entity = [
            'type' => $type,
            'layer' => $object['layer'] ?? '0',
            'color' => $object['color'] ?? null,
            'handle' => $object['handle'] ?? null,
        ];

        // Type-specific normalization
        switch ($type) {
            case 'LINE':
                $entity['x1'] = $object['start']['x'] ?? $object['start_point'][0] ?? 0;
                $entity['y1'] = $object['start']['y'] ?? $object['start_point'][1] ?? 0;
                $entity['x2'] = $object['end']['x'] ?? $object['end_point'][0] ?? 0;
                $entity['y2'] = $object['end']['y'] ?? $object['end_point'][1] ?? 0;
                break;

            case 'CIRCLE':
                $entity['x'] = $object['center']['x'] ?? $object['center'][0] ?? 0;
                $entity['y'] = $object['center']['y'] ?? $object['center'][1] ?? 0;
                $entity['radius'] = $object['radius'] ?? 0;
                break;

            case 'ARC':
                $entity['x'] = $object['center']['x'] ?? $object['center'][0] ?? 0;
                $entity['y'] = $object['center']['y'] ?? $object['center'][1] ?? 0;
                $entity['radius'] = $object['radius'] ?? 0;
                $entity['startAngle'] = $object['start_angle'] ?? 0;
                $entity['endAngle'] = $object['end_angle'] ?? 360;
                break;

            case 'LWPOLYLINE':
            case 'POLYLINE':
                $vertices = [];
                if (isset($object['points'])) {
                    foreach ($object['points'] as $point) {
                        $vertices[] = [
                            'x' => $point['x'] ?? $point[0] ?? 0,
                            'y' => $point['y'] ?? $point[1] ?? 0,
                            'bulge' => $point['bulge'] ?? 0,
                        ];
                    }
                }
                $entity['vertices'] = $vertices;
                $entity['closed'] = $object['flag'] ?? false;
                break;

            case 'TEXT':
            case 'MTEXT':
                $entity['x'] = $object['insertion_point']['x'] ?? $object['insertion'][0] ?? 0;
                $entity['y'] = $object['insertion_point']['y'] ?? $object['insertion'][1] ?? 0;
                $entity['text'] = $object['text_value'] ?? $object['text'] ?? '';
                $entity['height'] = $object['height'] ?? 1;
                $entity['rotation'] = $object['rotation'] ?? 0;
                break;

            default:
                // Generic point extraction
                if (isset($object['x']) && isset($object['y'])) {
                    $entity['x'] = $object['x'];
                    $entity['y'] = $object['y'];
                }
        }

        return $entity;
    }

    /**
     * Update bounding box from entity
     */
    protected function updateBounds(array &$bounds, array $entity): void
    {
        $points = $this->getEntityPoints($entity);
        
        foreach ($points as $point) {
            if (isset($point['x'])) {
                $bounds['minX'] = min($bounds['minX'], $point['x']);
                $bounds['maxX'] = max($bounds['maxX'], $point['x']);
            }
            if (isset($point['y'])) {
                $bounds['minY'] = min($bounds['minY'], $point['y']);
                $bounds['maxY'] = max($bounds['maxY'], $point['y']);
            }
        }
    }

    /**
     * Extract coordinate points from entity
     */
    protected function getEntityPoints(array $entity): array
    {
        $points = [];

        switch ($entity['type']) {
            case 'LINE':
                $points[] = ['x' => $entity['x1'] ?? 0, 'y' => $entity['y1'] ?? 0];
                $points[] = ['x' => $entity['x2'] ?? 0, 'y' => $entity['y2'] ?? 0];
                break;

            case 'CIRCLE':
            case 'ARC':
                $r = $entity['radius'] ?? 0;
                $x = $entity['x'] ?? 0;
                $y = $entity['y'] ?? 0;
                $points[] = ['x' => $x - $r, 'y' => $y - $r];
                $points[] = ['x' => $x + $r, 'y' => $y + $r];
                break;

            case 'LWPOLYLINE':
            case 'POLYLINE':
                if (isset($entity['vertices'])) {
                    foreach ($entity['vertices'] as $vertex) {
                        $points[] = ['x' => $vertex['x'] ?? 0, 'y' => $vertex['y'] ?? 0];
                    }
                }
                break;

            default:
                if (isset($entity['x']) && isset($entity['y'])) {
                    $points[] = ['x' => $entity['x'], 'y' => $entity['y']];
                }
        }

        return $points;
    }

    /**
     * Convert parsed data to SVG
     */
    public function toSvg(array $data, array $options = []): string
    {
        $width = $options['width'] ?? 800;
        $height = $options['height'] ?? 600;
        $padding = $options['padding'] ?? 20;
        $strokeColor = $options['strokeColor'] ?? '#000000';
        $strokeWidth = $options['strokeWidth'] ?? 1;
        $backgroundColor = $options['backgroundColor'] ?? '#ffffff';

        $bounds = $data['bounds'];
        
        if (!$bounds || $bounds['minX'] >= $bounds['maxX']) {
            return $this->emptySvg($width, $height, $backgroundColor);
        }

        $dataWidth = $bounds['maxX'] - $bounds['minX'];
        $dataHeight = $bounds['maxY'] - $bounds['minY'];
        $availableWidth = $width - $padding * 2;
        $availableHeight = $height - $padding * 2;
        $scale = min($availableWidth / $dataWidth, $availableHeight / $dataHeight);

        $elements = [];
        
        foreach ($data['entities'] as $entity) {
            $element = $this->entityToSvg($entity, $bounds, $scale, $padding, $height, $strokeColor);
            if ($element) {
                $elements[] = $element;
            }
        }

        $elementsStr = implode("\n    ", $elements);

        return <<<SVG
<?xml version="1.0" encoding="UTF-8"?>
<svg xmlns="http://www.w3.org/2000/svg" 
     width="{$width}" height="{$height}" 
     viewBox="0 0 {$width} {$height}"
     style="background-color: {$backgroundColor}">
  <g stroke="{$strokeColor}" stroke-width="{$strokeWidth}" fill="none">
    {$elementsStr}
  </g>
</svg>
SVG;
    }

    /**
     * Convert a single entity to SVG element
     */
    protected function entityToSvg(
        array $entity, 
        array $bounds, 
        float $scale, 
        float $padding, 
        float $height,
        string $color
    ): ?string {
        $transform = function($x, $y) use ($bounds, $scale, $padding, $height) {
            return [
                'x' => $padding + ($x - $bounds['minX']) * $scale,
                'y' => $height - $padding - ($y - $bounds['minY']) * $scale,
            ];
        };

        switch ($entity['type']) {
            case 'LINE':
                $p1 = $transform($entity['x1'], $entity['y1']);
                $p2 = $transform($entity['x2'], $entity['y2']);
                return "<line x1=\"{$p1['x']}\" y1=\"{$p1['y']}\" x2=\"{$p2['x']}\" y2=\"{$p2['y']}\" stroke=\"{$color}\"/>";

            case 'CIRCLE':
                $center = $transform($entity['x'], $entity['y']);
                $radius = $entity['radius'] * $scale;
                return "<circle cx=\"{$center['x']}\" cy=\"{$center['y']}\" r=\"{$radius}\" stroke=\"{$color}\"/>";

            case 'LWPOLYLINE':
            case 'POLYLINE':
                if (empty($entity['vertices'])) return null;
                $points = array_map(function($v) use ($transform) {
                    $p = $transform($v['x'], $v['y']);
                    return "{$p['x']},{$p['y']}";
                }, $entity['vertices']);
                $pointsStr = implode(' ', $points);
                $tag = ($entity['closed'] ?? false) ? 'polygon' : 'polyline';
                return "<{$tag} points=\"{$pointsStr}\" stroke=\"{$color}\"/>";

            case 'TEXT':
            case 'MTEXT':
                $pos = $transform($entity['x'], $entity['y']);
                $text = htmlspecialchars($entity['text'] ?? '');
                $fontSize = ($entity['height'] ?? 1) * $scale;
                return "<text x=\"{$pos['x']}\" y=\"{$pos['y']}\" font-size=\"{$fontSize}\" fill=\"{$color}\" stroke=\"none\">{$text}</text>";

            default:
                return null;
        }
    }

    /**
     * Generate empty SVG
     */
    protected function emptySvg(int $width, int $height, string $backgroundColor): string
    {
        return <<<SVG
<?xml version="1.0" encoding="UTF-8"?>
<svg xmlns="http://www.w3.org/2000/svg" 
     width="{$width}" height="{$height}" 
     viewBox="0 0 {$width} {$height}"
     style="background-color: {$backgroundColor}">
</svg>
SVG;
    }

    /**
     * Convert parsed data to GeoJSON
     */
    public function toGeoJson(array $data): array
    {
        $features = [];

        foreach ($data['entities'] as $entity) {
            $geometry = $this->entityToGeoJsonGeometry($entity);
            if (!$geometry) continue;

            $features[] = [
                'type' => 'Feature',
                'properties' => [
                    'layer' => $entity['layer'],
                    'color' => $entity['color'],
                    'type' => $entity['type'],
                    'handle' => $entity['handle'] ?? null,
                ],
                'geometry' => $geometry,
            ];
        }

        return [
            'type' => 'FeatureCollection',
            'features' => $features,
        ];
    }

    /**
     * Convert entity to GeoJSON geometry
     */
    protected function entityToGeoJsonGeometry(array $entity): ?array
    {
        switch ($entity['type']) {
            case 'POINT':
                return [
                    'type' => 'Point',
                    'coordinates' => [$entity['x'], $entity['y']],
                ];

            case 'LINE':
                return [
                    'type' => 'LineString',
                    'coordinates' => [
                        [$entity['x1'], $entity['y1']],
                        [$entity['x2'], $entity['y2']],
                    ],
                ];

            case 'LWPOLYLINE':
            case 'POLYLINE':
                if (empty($entity['vertices'])) return null;
                $coords = array_map(fn($v) => [$v['x'], $v['y']], $entity['vertices']);
                
                if ($entity['closed'] ?? false) {
                    $coords[] = $coords[0];
                    return [
                        'type' => 'Polygon',
                        'coordinates' => [$coords],
                    ];
                }
                
                return [
                    'type' => 'LineString',
                    'coordinates' => $coords,
                ];

            case 'CIRCLE':
                // Approximate as polygon
                $coords = [];
                for ($i = 0; $i <= 36; $i++) {
                    $angle = ($i * 10) * M_PI / 180;
                    $coords[] = [
                        $entity['x'] + $entity['radius'] * cos($angle),
                        $entity['y'] + $entity['radius'] * sin($angle),
                    ];
                }
                return [
                    'type' => 'Polygon',
                    'coordinates' => [$coords],
                ];

            default:
                return null;
        }
    }

    /**
     * Get layer statistics from parsed data
     */
    public function getLayerStats(array $data): array
    {
        $stats = [];

        foreach ($data['entities'] as $entity) {
            $layer = $entity['layer'] ?? '0';
            
            if (!isset($stats[$layer])) {
                $stats[$layer] = [
                    'name' => $layer,
                    'entityCount' => 0,
                    'types' => [],
                    'color' => $data['layers'][$layer]['color'] ?? null,
                ];
            }

            $stats[$layer]['entityCount']++;
            $type = $entity['type'];
            $stats[$layer]['types'][$type] = ($stats[$layer]['types'][$type] ?? 0) + 1;
        }

        return $stats;
    }

    /**
     * Filter entities by layer
     */
    public function filterByLayers(array $data, array $layers): array
    {
        $layerSet = array_flip(array_map('strtolower', $layers));
        
        $data['entities'] = array_filter($data['entities'], function($entity) use ($layerSet) {
            return isset($layerSet[strtolower($entity['layer'] ?? '0')]);
        });

        $data['entities'] = array_values($data['entities']);
        $data['stats']['entityCount'] = count($data['entities']);

        return $data;
    }

    /**
     * Filter entities by type
     */
    public function filterByTypes(array $data, array $types): array
    {
        $typeSet = array_flip(array_map('strtoupper', $types));
        
        $data['entities'] = array_filter($data['entities'], function($entity) use ($typeSet) {
            return isset($typeSet[$entity['type']]);
        });

        $data['entities'] = array_values($data['entities']);
        $data['stats']['entityCount'] = count($data['entities']);

        return $data;
    }
}

/**
 * Native PHP DXF File Parser
 */
class DxfFileParser
{
    protected array $entities = [];
    protected array $layers = [];
    protected array $blocks = [];
    protected array $header = [];
    protected array $bounds = [
        'minX' => PHP_FLOAT_MAX,
        'minY' => PHP_FLOAT_MAX,
        'maxX' => PHP_FLOAT_MIN,
        'maxY' => PHP_FLOAT_MIN,
    ];

    /**
     * Parse DXF content
     */
    public function parse(string $content): array
    {
        $lines = preg_split('/\r?\n/', $content);
        $pairs = $this->parsePairs($lines);
        
        $currentSection = null;
        $i = 0;

        while ($i < count($pairs)) {
            [$code, $value] = $pairs[$i];

            if ($code === 0 && $value === 'SECTION') {
                $i++;
                if (isset($pairs[$i]) && $pairs[$i][0] === 2) {
                    $currentSection = $pairs[$i][1];
                }
            } elseif ($code === 0 && $value === 'ENDSEC') {
                $currentSection = null;
            } elseif ($currentSection === 'HEADER') {
                $i = $this->parseHeader($pairs, $i);
                continue;
            } elseif ($currentSection === 'TABLES') {
                $i = $this->parseTables($pairs, $i);
                continue;
            } elseif ($currentSection === 'ENTITIES') {
                $i = $this->parseEntities($pairs, $i);
                continue;
            }

            $i++;
        }

        return [
            'header' => $this->header,
            'layers' => $this->layers,
            'blocks' => $this->blocks,
            'entities' => $this->entities,
            'bounds' => $this->bounds,
            'stats' => [
                'entityCount' => count($this->entities),
                'layerCount' => count($this->layers),
                'blockCount' => count($this->blocks),
            ],
        ];
    }

    /**
     * Parse lines into code-value pairs
     */
    protected function parsePairs(array $lines): array
    {
        $pairs = [];
        
        for ($i = 0; $i < count($lines) - 1; $i += 2) {
            $code = intval(trim($lines[$i]));
            $value = trim($lines[$i + 1] ?? '');
            $pairs[] = [$code, $value];
        }

        return $pairs;
    }

    /**
     * Parse HEADER section
     */
    protected function parseHeader(array $pairs, int $startIndex): int
    {
        $i = $startIndex;
        
        while ($i < count($pairs)) {
            [$code, $value] = $pairs[$i];
            
            if ($code === 0 && ($value === 'ENDSEC' || $value === 'SECTION')) {
                return $i;
            }
            
            if ($code === 9) {
                $varName = $value;
                $values = [];
                $i++;
                
                while ($i < count($pairs) && $pairs[$i][0] !== 9 && $pairs[$i][0] !== 0) {
                    $values[] = ['code' => $pairs[$i][0], 'value' => $pairs[$i][1]];
                    $i++;
                }
                
                $this->header[$varName] = count($values) === 1 ? $values[0]['value'] : $values;
                continue;
            }
            
            $i++;
        }

        return $i;
    }

    /**
     * Parse TABLES section
     */
    protected function parseTables(array $pairs, int $startIndex): int
    {
        $i = $startIndex;
        $currentTable = null;

        while ($i < count($pairs)) {
            [$code, $value] = $pairs[$i];
            
            if ($code === 0 && $value === 'ENDSEC') {
                return $i;
            }
            
            if ($code === 0 && $value === 'TABLE') {
                $i++;
                if (isset($pairs[$i]) && $pairs[$i][0] === 2) {
                    $currentTable = $pairs[$i][1];
                }
            } elseif ($code === 0 && $value === 'LAYER' && $currentTable === 'LAYER') {
                $layer = $this->parseLayer($pairs, $i);
                if ($layer['name']) {
                    $this->layers[$layer['name']] = $layer;
                }
                $i = $layer['endIndex'];
                continue;
            }
            
            $i++;
        }

        return $i;
    }

    /**
     * Parse a single LAYER
     */
    protected function parseLayer(array $pairs, int $startIndex): array
    {
        $layer = ['name' => '', 'color' => 7, 'frozen' => false, 'locked' => false];
        $i = $startIndex + 1;

        while ($i < count($pairs)) {
            [$code, $value] = $pairs[$i];
            
            if ($code === 0) break;
            
            switch ($code) {
                case 2: $layer['name'] = $value; break;
                case 62: $layer['color'] = intval($value); break;
                case 70:
                    $flags = intval($value);
                    $layer['frozen'] = ($flags & 1) !== 0;
                    $layer['locked'] = ($flags & 4) !== 0;
                    break;
            }
            
            $i++;
        }

        $layer['endIndex'] = $i;
        return $layer;
    }

    /**
     * Parse ENTITIES section
     */
    protected function parseEntities(array $pairs, int $startIndex): int
    {
        $i = $startIndex;
        $entityTypes = array_flip(DwgService::ENTITY_TYPES);

        while ($i < count($pairs)) {
            [$code, $value] = $pairs[$i];
            
            if ($code === 0 && $value === 'ENDSEC') {
                return $i;
            }
            
            if ($code === 0 && isset($entityTypes[$value])) {
                $entity = $this->parseEntity($pairs, $i, $value);
                if ($entity) {
                    $this->entities[] = $entity;
                    $this->updateBounds($entity);
                }
                $i = $entity['endIndex'] ?? $i + 1;
                continue;
            }
            
            $i++;
        }

        return $i;
    }

    /**
     * Parse a single entity
     */
    protected function parseEntity(array $pairs, int $startIndex, string $type): array
    {
        $entity = [
            'type' => $type,
            'layer' => '0',
            'color' => null,
            'handle' => null,
        ];

        $i = $startIndex + 1;

        while ($i < count($pairs)) {
            [$code, $value] = $pairs[$i];
            
            if ($code === 0) break;

            // Common properties
            switch ($code) {
                case 5: $entity['handle'] = $value; break;
                case 8: $entity['layer'] = $value; break;
                case 62: $entity['color'] = intval($value); break;
                case 6: $entity['lineType'] = $value; break;
            }

            // Type-specific
            $this->parseEntityProperty($entity, $type, $code, $value);
            $i++;
        }

        $entity['endIndex'] = $i;
        return $entity;
    }

    /**
     * Parse entity-specific properties
     */
    protected function parseEntityProperty(array &$entity, string $type, int $code, string $value): void
    {
        switch ($type) {
            case 'LINE':
                match ($code) {
                    10 => $entity['x1'] = floatval($value),
                    20 => $entity['y1'] = floatval($value),
                    11 => $entity['x2'] = floatval($value),
                    21 => $entity['y2'] = floatval($value),
                    default => null,
                };
                break;

            case 'CIRCLE':
                match ($code) {
                    10 => $entity['x'] = floatval($value),
                    20 => $entity['y'] = floatval($value),
                    40 => $entity['radius'] = floatval($value),
                    default => null,
                };
                break;

            case 'ARC':
                match ($code) {
                    10 => $entity['x'] = floatval($value),
                    20 => $entity['y'] = floatval($value),
                    40 => $entity['radius'] = floatval($value),
                    50 => $entity['startAngle'] = floatval($value),
                    51 => $entity['endAngle'] = floatval($value),
                    default => null,
                };
                break;

            case 'LWPOLYLINE':
            case 'POLYLINE':
                if (!isset($entity['vertices'])) {
                    $entity['vertices'] = [];
                }
                
                match ($code) {
                    10 => $entity['vertices'][] = ['x' => floatval($value), 'y' => 0, 'bulge' => 0],
                    20 => count($entity['vertices']) > 0 
                        ? $entity['vertices'][count($entity['vertices']) - 1]['y'] = floatval($value) 
                        : null,
                    42 => count($entity['vertices']) > 0 
                        ? $entity['vertices'][count($entity['vertices']) - 1]['bulge'] = floatval($value) 
                        : null,
                    70 => $entity['closed'] = (intval($value) & 1) !== 0,
                    default => null,
                };
                break;

            case 'TEXT':
            case 'MTEXT':
                match ($code) {
                    1 => $entity['text'] = $value,
                    10 => $entity['x'] = floatval($value),
                    20 => $entity['y'] = floatval($value),
                    40 => $entity['height'] = floatval($value),
                    50 => $entity['rotation'] = floatval($value),
                    7 => $entity['style'] = $value,
                    default => null,
                };
                break;

            case 'INSERT':
                match ($code) {
                    2 => $entity['blockName'] = $value,
                    10 => $entity['x'] = floatval($value),
                    20 => $entity['y'] = floatval($value),
                    41 => $entity['scaleX'] = floatval($value),
                    42 => $entity['scaleY'] = floatval($value),
                    50 => $entity['rotation'] = floatval($value),
                    default => null,
                };
                break;

            case 'POINT':
                match ($code) {
                    10 => $entity['x'] = floatval($value),
                    20 => $entity['y'] = floatval($value),
                    default => null,
                };
                break;
        }
    }

    /**
     * Update bounds from entity
     */
    protected function updateBounds(array $entity): void
    {
        switch ($entity['type']) {
            case 'LINE':
                $this->expandBounds($entity['x1'] ?? 0, $entity['y1'] ?? 0);
                $this->expandBounds($entity['x2'] ?? 0, $entity['y2'] ?? 0);
                break;

            case 'CIRCLE':
            case 'ARC':
                $r = $entity['radius'] ?? 0;
                $x = $entity['x'] ?? 0;
                $y = $entity['y'] ?? 0;
                $this->expandBounds($x - $r, $y - $r);
                $this->expandBounds($x + $r, $y + $r);
                break;

            case 'LWPOLYLINE':
            case 'POLYLINE':
                foreach ($entity['vertices'] ?? [] as $vertex) {
                    $this->expandBounds($vertex['x'], $vertex['y']);
                }
                break;

            default:
                if (isset($entity['x'], $entity['y'])) {
                    $this->expandBounds($entity['x'], $entity['y']);
                }
        }
    }

    /**
     * Expand bounds to include point
     */
    protected function expandBounds(float $x, float $y): void
    {
        $this->bounds['minX'] = min($this->bounds['minX'], $x);
        $this->bounds['minY'] = min($this->bounds['minY'], $y);
        $this->bounds['maxX'] = max($this->bounds['maxX'], $x);
        $this->bounds['maxY'] = max($this->bounds['maxY'], $y);
    }
}
