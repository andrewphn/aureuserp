<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\RhinoMCPService;
use App\Services\RhinoDataExtractor;

/**
 * Analyze views and structure in Rhino documents
 *
 * Provides detailed analysis of TCS Rhino drawing structure:
 * - View detection (Plan View, Elevation, Detail)
 * - Layer organization
 * - Group identification
 * - Dimension summary
 *
 * Usage:
 *   php artisan rhino:analyze-views
 *   php artisan rhino:analyze-views --detailed
 *   php artisan rhino:analyze-views --json
 *
 * @author TCS Woodwork
 * @since January 2026
 */
class AnalyzeRhinoViews extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'rhino:analyze-views
                            {--detailed : Show detailed view and dimension data}
                            {--json : Output results as JSON}
                            {--layers : Show layer analysis only}
                            {--groups : Show group analysis only}
                            {--dimensions : Show dimension analysis only}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Analyze views and structure in the currently open Rhino document';

    protected RhinoMCPService $rhinoMcp;
    protected RhinoDataExtractor $extractor;

    public function __construct(RhinoMCPService $rhinoMcp, RhinoDataExtractor $extractor)
    {
        parent::__construct();
        $this->rhinoMcp = $rhinoMcp;
        $this->extractor = $extractor;
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Rhino Document Analysis');
        $this->line('=======================');
        $this->newLine();

        // Check connection
        try {
            $docInfo = $this->rhinoMcp->getDocumentInfo();
            $this->info('Connected to Rhino');
        } catch (\Exception $e) {
            $this->error('Failed to connect to Rhino: ' . $e->getMessage());
            return 1;
        }

        // Gather all data
        $analysis = $this->gatherAnalysis();

        // Output based on options
        if ($this->option('json')) {
            $this->line(json_encode($analysis, JSON_PRETTY_PRINT));
            return 0;
        }

        // Display specific sections or all
        if ($this->option('layers')) {
            $this->displayLayerAnalysis($analysis);
        } elseif ($this->option('groups')) {
            $this->displayGroupAnalysis($analysis);
        } elseif ($this->option('dimensions')) {
            $this->displayDimensionAnalysis($analysis);
        } else {
            // Display all sections
            $this->displayDocumentSummary($analysis);
            $this->displayViewAnalysis($analysis);
            $this->displayLayerAnalysis($analysis);
            $this->displayGroupAnalysis($analysis);

            if ($this->option('detailed')) {
                $this->displayDimensionAnalysis($analysis);
                $this->displayTextAnalysis($analysis);
            }
        }

        return 0;
    }

    /**
     * Gather all analysis data
     *
     * @return array
     */
    protected function gatherAnalysis(): array
    {
        $this->info('Gathering document data...');

        $summary = $this->extractor->getDocumentSummary();
        $viewAnalysis = $this->extractor->analyzeViews();
        $dimensions = $this->rhinoMcp->getDimensions();
        $textObjects = $this->rhinoMcp->getTextObjects();
        $blocks = $this->rhinoMcp->getBlockInstances();

        return [
            'document' => $summary['document_info'] ?? [],
            'layers' => $summary['layers'] ?? [],
            'groups' => $summary['groups'] ?? [],
            'potential_cabinet_groups' => $summary['potential_cabinet_groups'] ?? [],
            'views' => $viewAnalysis,
            'dimensions' => [
                'count' => count($dimensions),
                'items' => $dimensions,
            ],
            'text' => [
                'count' => count($textObjects),
                'items' => $textObjects,
            ],
            'blocks' => [
                'count' => count($blocks),
                'items' => $blocks,
            ],
        ];
    }

    /**
     * Display document summary
     *
     * @param array $analysis
     */
    protected function displayDocumentSummary(array $analysis): void
    {
        $this->newLine();
        $this->info('Document Summary');
        $this->line('----------------');

        $doc = $analysis['document'] ?? [];

        $this->table(
            ['Property', 'Value'],
            [
                ['Name', $doc['name'] ?? 'Unknown'],
                ['Path', $doc['path'] ?? 'Unknown'],
                ['Total Objects', $doc['object_count'] ?? count($doc['objects'] ?? [])],
                ['Layers', count($analysis['layers'] ?? [])],
                ['Groups', count($analysis['groups'] ?? [])],
                ['Text Labels', $analysis['text']['count'] ?? 0],
                ['Dimensions', $analysis['dimensions']['count'] ?? 0],
                ['Block Instances', $analysis['blocks']['count'] ?? 0],
            ]
        );
    }

    /**
     * Display view analysis
     *
     * @param array $analysis
     */
    protected function displayViewAnalysis(array $analysis): void
    {
        $this->newLine();
        $this->info('View Analysis');
        $this->line('-------------');

        $views = $analysis['views'] ?? [];

        $this->line("Total views detected: {$views['view_count']}");
        $this->newLine();

        // Views by type
        $byType = $views['views_by_type'] ?? [];

        $planViews = $byType['plan'] ?? [];
        $elevationViews = $byType['elevation'] ?? [];
        $detailViews = $byType['detail'] ?? [];

        if (!empty($planViews)) {
            $this->line('<fg=cyan>Plan Views:</>');
            foreach ($planViews as $view) {
                $this->line("  - {$view}");
            }
        }

        if (!empty($elevationViews)) {
            $this->line('<fg=green>Elevation Views:</>');
            foreach ($elevationViews as $view) {
                $this->line("  - {$view}");
            }
        }

        if (!empty($detailViews)) {
            $this->line('<fg=yellow>Detail Views:</>');
            foreach ($detailViews as $view) {
                $this->line("  - {$view}");
            }
        }

        if ($this->option('detailed') && !empty($views['all_views'])) {
            $this->newLine();
            $this->line('View Coordinates:');

            $tableData = [];
            foreach ($views['all_views'] as $view) {
                $bounds = $view['bounds'] ?? [];
                $tableData[] = [
                    $view['label'] ?? 'Unknown',
                    strtoupper($view['type'] ?? '?'),
                    round($view['center']['x'] ?? 0, 1),
                    round($view['center']['y'] ?? 0, 1),
                    isset($bounds['x_min']) ? round($bounds['x_min'], 0) . ' to ' . round($bounds['x_max'], 0) : '-',
                ];
            }

            $this->table(
                ['Label', 'Type', 'Center X', 'Center Y', 'X Range'],
                $tableData
            );
        }
    }

    /**
     * Display layer analysis
     *
     * @param array $analysis
     */
    protected function displayLayerAnalysis(array $analysis): void
    {
        $this->newLine();
        $this->info('Layer Analysis');
        $this->line('--------------');

        $layers = $analysis['layers'] ?? [];

        if (empty($layers)) {
            $this->line('No layers found');
            return;
        }

        $tableData = [];
        foreach ($layers as $layer) {
            $name = $layer['name'] ?? 'Unknown';
            $visible = ($layer['visible'] ?? false) ? 'Yes' : 'No';
            $color = $layer['color'] ?? [128, 128, 128];
            $colorStr = "RGB({$color[0]},{$color[1]},{$color[2]})";

            $tableData[] = [$name, $visible, $colorStr];
        }

        // Sort by name
        usort($tableData, fn($a, $b) => strcmp($a[0], $b[0]));

        $this->table(
            ['Layer Name', 'Visible', 'Color'],
            $tableData
        );

        // Highlight important TCS layers
        $tcsLayers = ['Labels', 'Dimensions', 'Dado', 'Default', '0'];
        $this->newLine();
        $this->line('Key TCS Layers:');
        foreach ($layers as $layer) {
            $name = $layer['name'] ?? '';
            if (in_array($name, $tcsLayers) || stripos($name, 'Dimension') !== false) {
                $this->line("  <fg=green>{$name}</>");
            }
        }
    }

    /**
     * Display group analysis
     *
     * @param array $analysis
     */
    protected function displayGroupAnalysis(array $analysis): void
    {
        $this->newLine();
        $this->info('Group Analysis');
        $this->line('--------------');

        $groups = $analysis['groups'] ?? [];
        $cabinetGroups = $analysis['potential_cabinet_groups'] ?? [];

        if (empty($groups)) {
            $this->line('No groups found');
            return;
        }

        $this->line("Total groups: " . count($groups));
        $this->line("Potential cabinet groups: " . count($cabinetGroups));
        $this->newLine();

        // Show cabinet groups
        if (!empty($cabinetGroups)) {
            $this->line('<fg=green>Cabinet Groups (auto-detected):</>');
            foreach ($cabinetGroups as $group) {
                $objectCount = count($this->rhinoMcp->getObjectsByGroup($group));
                $this->line("  - {$group} ({$objectCount} objects)");
            }
        }

        // Show other groups
        $otherGroups = array_diff($groups, $cabinetGroups);
        if (!empty($otherGroups) && $this->option('detailed')) {
            $this->newLine();
            $this->line('<fg=gray>Other Groups:</>');
            foreach ($otherGroups as $group) {
                $objectCount = count($this->rhinoMcp->getObjectsByGroup($group));
                $this->line("  - {$group} ({$objectCount} objects)");
            }
        }
    }

    /**
     * Display dimension analysis
     *
     * @param array $analysis
     */
    protected function displayDimensionAnalysis(array $analysis): void
    {
        $this->newLine();
        $this->info('Dimension Analysis');
        $this->line('------------------');

        $dims = $analysis['dimensions']['items'] ?? [];

        if (empty($dims)) {
            $this->line('No dimensions found');
            return;
        }

        $this->line("Total dimensions: " . count($dims));

        // Group by layer
        $byLayer = [];
        foreach ($dims as $dim) {
            $layer = $dim['layer'] ?? 'Unknown';
            if (!isset($byLayer[$layer])) {
                $byLayer[$layer] = [];
            }
            $byLayer[$layer][] = $dim;
        }

        $this->newLine();
        $this->line('Dimensions by Layer:');
        foreach ($byLayer as $layer => $layerDims) {
            $this->line("  {$layer}: " . count($layerDims) . " dimensions");
        }

        // Parse and show dimension values
        $this->newLine();
        $this->line('Parsed Dimension Values:');

        $parsedValues = [];
        foreach ($dims as $dim) {
            $text = $dim['text'] ?? '';
            $value = $dim['value'] ?? null;

            if ($value === null) {
                $value = $this->rhinoMcp->parseDimensionText($text);
            }

            if ($value !== null) {
                $parsedValues[] = [
                    'text' => $text,
                    'value' => $value,
                    'layer' => $dim['layer'] ?? 'Unknown',
                ];
            }
        }

        // Sort by value
        usort($parsedValues, fn($a, $b) => $a['value'] <=> $b['value']);

        // Show unique values
        $uniqueValues = array_unique(array_column($parsedValues, 'value'));
        sort($uniqueValues);

        $tableData = [];
        foreach ($uniqueValues as $value) {
            $fraction = $this->decimalToFraction($value);
            $count = count(array_filter($parsedValues, fn($p) => $p['value'] === $value));
            $tableData[] = [
                $value,
                $fraction,
                $count,
            ];
        }

        $this->table(
            ['Decimal', 'Fractional', 'Count'],
            array_slice($tableData, 0, 20) // Show first 20
        );

        if (count($tableData) > 20) {
            $this->line('... and ' . (count($tableData) - 20) . ' more unique values');
        }
    }

    /**
     * Display text analysis
     *
     * @param array $analysis
     */
    protected function displayTextAnalysis(array $analysis): void
    {
        $this->newLine();
        $this->info('Text Label Analysis');
        $this->line('-------------------');

        $texts = $analysis['text']['items'] ?? [];

        if (empty($texts)) {
            $this->line('No text labels found');
            return;
        }

        $this->line("Total text labels: " . count($texts));

        // Categorize text
        $categories = [
            'view_labels' => [],
            'cabinet_ids' => [],
            'component_labels' => [],
            'status_labels' => [],
            'other' => [],
        ];

        $viewPatterns = ['Plan View', 'Elevation', 'Detail', 'Section'];
        $componentPatterns = ['Drawer', 'Door', 'Shelf', 'Pullout'];
        $statusPatterns = ['Progress', 'Job Card', 'CNC', 'Adjustment'];

        foreach ($texts as $text) {
            $content = $text['text'] ?? '';

            // Check view labels
            $isView = false;
            foreach ($viewPatterns as $pattern) {
                if (stripos($content, $pattern) !== false) {
                    $categories['view_labels'][] = $content;
                    $isView = true;
                    break;
                }
            }
            if ($isView) {
                continue;
            }

            // Check cabinet identifiers (Austin-Van pattern)
            if (preg_match('/^[A-Z][a-z]+-[A-Z]/i', $content)) {
                $categories['cabinet_ids'][] = $content;
                continue;
            }

            // Check component labels
            $isComponent = false;
            foreach ($componentPatterns as $pattern) {
                if (stripos($content, $pattern) !== false) {
                    $categories['component_labels'][] = $content;
                    $isComponent = true;
                    break;
                }
            }
            if ($isComponent) {
                continue;
            }

            // Check status labels
            $isStatus = false;
            foreach ($statusPatterns as $pattern) {
                if (stripos($content, $pattern) !== false) {
                    $categories['status_labels'][] = $content;
                    $isStatus = true;
                    break;
                }
            }
            if ($isStatus) {
                continue;
            }

            // Other
            $categories['other'][] = $content;
        }

        // Display categories
        $this->newLine();
        foreach ($categories as $category => $items) {
            if (empty($items)) {
                continue;
            }

            $categoryName = str_replace('_', ' ', ucfirst($category));
            $this->line("<fg=cyan>{$categoryName}:</> (" . count($items) . ")");

            $uniqueItems = array_unique($items);
            foreach (array_slice($uniqueItems, 0, 10) as $item) {
                $this->line("  - {$item}");
            }

            if (count($uniqueItems) > 10) {
                $this->line("  ... and " . (count($uniqueItems) - 10) . " more");
            }

            $this->newLine();
        }
    }

    /**
     * Convert decimal inches to fractional string
     *
     * @param float $decimal
     * @return string
     */
    protected function decimalToFraction(float $decimal): string
    {
        $whole = (int) $decimal;
        $frac = $decimal - $whole;

        $sixteenths = (int) round($frac * 16);

        if ($sixteenths === 0) {
            return $whole . '"';
        }
        if ($sixteenths === 16) {
            return ($whole + 1) . '"';
        }

        // Simplify fraction
        $num = $sixteenths;
        $denom = 16;

        foreach ([8, 4, 2] as $divisor) {
            if ($num % $divisor === 0) {
                $num = $num / $divisor;
                $denom = $denom / $divisor;
                break;
            }
        }

        if ($whole > 0) {
            return "{$whole}-{$num}/{$denom}\"";
        }
        return "{$num}/{$denom}\"";
    }
}
