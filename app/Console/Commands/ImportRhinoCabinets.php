<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\RhinoMCPService;
use App\Services\RhinoDataExtractor;
use App\Services\RhinoToCabinetMapper;
use Webkul\Project\Models\Project;
use Webkul\Project\Models\Room;

/**
 * Import cabinet data from Rhino 3DM files
 *
 * Extracts cabinet specifications, dimensions, and metadata from
 * TCS Rhino drawings and imports them into the ERP cabinet system.
 *
 * Usage:
 *   php artisan rhino:import-cabinets --project=123
 *   php artisan rhino:import-cabinets --project=123 --room=456
 *   php artisan rhino:import-cabinets --dry-run
 *   php artisan rhino:import-cabinets --update
 *
 * @author TCS Woodwork
 * @since January 2026
 */
class ImportRhinoCabinets extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'rhino:import-cabinets
                            {--project= : Project ID to associate cabinets with}
                            {--room= : Room ID to associate cabinets with}
                            {--cabinet-run= : Cabinet run ID to associate cabinets with}
                            {--dry-run : Preview extraction without saving to database}
                            {--update : Update existing cabinets by name match}
                            {--detailed : Show detailed extraction data}
                            {--json : Output results as JSON}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import cabinet data from currently open Rhino document';

    protected RhinoMCPService $rhinoMcp;
    protected RhinoDataExtractor $extractor;
    protected RhinoToCabinetMapper $mapper;

    public function __construct(
        RhinoMCPService $rhinoMcp,
        RhinoDataExtractor $extractor,
        RhinoToCabinetMapper $mapper
    ) {
        parent::__construct();
        $this->rhinoMcp = $rhinoMcp;
        $this->extractor = $extractor;
        $this->mapper = $mapper;
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Rhino Cabinet Import');
        $this->line('====================');
        $this->newLine();

        // Step 1: Check Rhino connection
        $this->info('Checking Rhino connection...');

        try {
            $docInfo = $this->rhinoMcp->getDocumentInfo();
            $this->info('Connected to Rhino document');

            if ($this->option('verbose')) {
                $this->table(
                    ['Property', 'Value'],
                    [
                        ['Document Name', $docInfo['name'] ?? 'Unknown'],
                        ['Path', $docInfo['path'] ?? 'Unknown'],
                        ['Object Count', $docInfo['object_count'] ?? 'Unknown'],
                    ]
                );
            }
        } catch (\Exception $e) {
            $this->error('Failed to connect to Rhino: ' . $e->getMessage());
            $this->line('Make sure Rhino is running and the MCP server is active.');
            return 1;
        }

        $this->newLine();

        // Step 2: Validate context options
        $context = $this->buildContext();

        if (!$context['project_id'] && !$this->option('dry-run')) {
            $this->warn('No project specified. Use --project=ID to associate cabinets with a project.');
            if (!$this->confirm('Continue without project association?', false)) {
                return 1;
            }
        }

        // Step 3: Extract cabinet data
        $this->info('Extracting cabinet data from Rhino...');

        try {
            $extractedData = $this->extractor->extractCabinets();
        } catch (\Exception $e) {
            $this->error('Extraction failed: ' . $e->getMessage());
            return 1;
        }

        $cabinetCount = count($extractedData['cabinets'] ?? []);
        $this->info("Found {$cabinetCount} cabinet(s)");

        if ($cabinetCount === 0) {
            $this->warn('No cabinets detected in the document.');
            $this->line('Make sure cabinet groups are properly named (e.g., "Austin-Vanity")');
            return 0;
        }

        $this->newLine();

        // Step 4: Map to Cabinet model data
        $this->info('Mapping to cabinet model...');
        $mappedData = $this->mapper->mapAllCabinets($extractedData, $context);

        // Step 5: Display preview report
        $this->displayPreviewReport($mappedData);

        // Step 6: Handle JSON output
        if ($this->option('json')) {
            $this->line(json_encode($mappedData, JSON_PRETTY_PRINT));
            return 0;
        }

        // Step 7: Handle dry run
        if ($this->option('dry-run')) {
            $this->newLine();
            $this->info('DRY RUN - No changes made to database');
            return 0;
        }

        // Step 8: Confirm import
        $this->newLine();
        if (!$this->confirm("Import {$cabinetCount} cabinet(s)?", true)) {
            $this->line('Import cancelled.');
            return 0;
        }

        // Step 9: Create/update cabinets
        $this->newLine();

        if ($this->option('update')) {
            $this->info('Updating existing cabinets...');
            $results = $this->updateExistingCabinets($extractedData, $context);
        } else {
            $this->info('Creating new cabinets...');
            $results = $this->createNewCabinets($mappedData);
        }

        $this->newLine();
        $this->displayResults($results);

        return 0;
    }

    /**
     * Build context array from command options
     *
     * @return array
     */
    protected function buildContext(): array
    {
        $context = [
            'project_id' => $this->option('project') ? (int) $this->option('project') : null,
            'room_id' => $this->option('room') ? (int) $this->option('room') : null,
            'cabinet_run_id' => $this->option('cabinet-run') ? (int) $this->option('cabinet-run') : null,
        ];

        // Validate project exists
        if ($context['project_id']) {
            $project = Project::find($context['project_id']);
            if (!$project) {
                $this->warn("Project ID {$context['project_id']} not found");
                $context['project_id'] = null;
            } else {
                $this->line("Project: {$project->name}");
            }
        }

        // Validate room exists
        if ($context['room_id']) {
            $room = Room::find($context['room_id']);
            if (!$room) {
                $this->warn("Room ID {$context['room_id']} not found");
                $context['room_id'] = null;
            } else {
                $this->line("Room: {$room->name}");
            }
        }

        return $context;
    }

    /**
     * Display preview report
     *
     * @param array $mappedData
     */
    protected function displayPreviewReport(array $mappedData): void
    {
        $report = $this->mapper->generatePreviewReport($mappedData);

        // Summary
        $this->line('');
        $this->info('Summary:');
        $this->table(
            ['Metric', 'Value'],
            [
                ['Total Cabinets', $report['summary']['total_cabinets'] ?? 0],
                ['High Confidence', $report['summary']['high_confidence'] ?? 0],
                ['Requires Review', $report['summary']['requires_review'] ?? 0],
            ]
        );

        // Cabinet details
        $this->newLine();
        $this->info('Cabinets:');

        $tableData = [];
        foreach ($report['cabinets'] as $cabinet) {
            $tableData[] = [
                $cabinet['name'],
                $cabinet['dimensions']['width'],
                $cabinet['dimensions']['height'],
                $cabinet['dimensions']['depth'],
                $cabinet['components']['drawers'],
                $cabinet['components']['doors'],
                $this->formatConfidence($cabinet['confidence']),
            ];
        }

        $this->table(
            ['Name', 'Width', 'Height', 'Depth', 'Drawers', 'Doors', 'Confidence'],
            $tableData
        );

        // Warnings
        if (!empty($report['warnings'])) {
            $this->newLine();
            $this->warn('Warnings:');
            foreach ($report['warnings'] as $warning) {
                $this->line("  - {$warning}");
            }
        }

        // Fixtures
        if (!empty($report['fixtures'])) {
            $this->newLine();
            $this->info('Detected Fixtures:');
            foreach ($report['fixtures'] as $fixture) {
                $this->line("  - {$fixture['product']} {$fixture['model']}");
            }
        }

        // Detailed mode: show raw dimensions
        if ($this->option('detailed')) {
            $this->newLine();
            $this->info('Raw Dimension Data:');
            foreach ($mappedData['cabinets'] as $cabinet) {
                $this->line("  {$cabinet['cabinet_number']}:");
                $rhino = $cabinet['_rhino_source'] ?? [];
                if (!empty($rhino['elevation_dims'])) {
                    $this->line("    Elevation dims: " . count($rhino['elevation_dims']));
                    foreach ($rhino['elevation_dims'] as $dim) {
                        $value = $dim['parsed_value'] ?? $dim['value'] ?? '?';
                        $orient = $dim['orientation'] ?? '?';
                        $this->line("      - {$value}\" ({$orient})");
                    }
                }
            }
        }
    }

    /**
     * Format confidence level with color
     *
     * @param string $confidence
     * @return string
     */
    protected function formatConfidence(string $confidence): string
    {
        return match ($confidence) {
            'high' => '<fg=green>HIGH</>',
            'medium' => '<fg=yellow>MEDIUM</>',
            'low' => '<fg=red>LOW</>',
            default => $confidence,
        };
    }

    /**
     * Create new cabinet records
     *
     * @param array $mappedData
     * @return array
     */
    protected function createNewCabinets(array $mappedData): array
    {
        $created = 0;
        $failed = 0;
        $errors = [];

        foreach ($mappedData['cabinets'] as $cabinetData) {
            try {
                $this->mapper->createCabinets(['cabinets' => [$cabinetData]], false);
                $created++;
                $this->line("  Created: {$cabinetData['cabinet_number']}");
            } catch (\Exception $e) {
                $failed++;
                $errors[] = [
                    'cabinet' => $cabinetData['cabinet_number'] ?? 'Unknown',
                    'error' => $e->getMessage(),
                ];
                $this->error("  Failed: {$cabinetData['cabinet_number']} - {$e->getMessage()}");
            }
        }

        return [
            'created' => $created,
            'updated' => 0,
            'failed' => $failed,
            'errors' => $errors,
        ];
    }

    /**
     * Update existing cabinets
     *
     * @param array $extractedData
     * @param array $context
     * @return array
     */
    protected function updateExistingCabinets(array $extractedData, array $context): array
    {
        $updated = 0;
        $created = 0;
        $failed = 0;
        $errors = [];

        foreach ($extractedData['cabinets'] as $extracted) {
            $name = $extracted['name'] ?? '';

            try {
                // Try to find existing cabinet
                $existing = $this->mapper->findMatchingCabinet($name, $context['project_id'] ?? null);

                if ($existing) {
                    $this->mapper->updateCabinet($existing, $extracted);
                    $updated++;
                    $this->line("  Updated: {$name} (ID: {$existing->id})");
                } else {
                    // Create new if not found
                    $mapped = $this->mapper->mapToCabinetData($extracted, $context);
                    $this->mapper->createCabinets(['cabinets' => [$mapped]], false);
                    $created++;
                    $this->line("  Created: {$name} (no existing match)");
                }
            } catch (\Exception $e) {
                $failed++;
                $errors[] = [
                    'cabinet' => $name,
                    'error' => $e->getMessage(),
                ];
                $this->error("  Failed: {$name} - {$e->getMessage()}");
            }
        }

        return [
            'created' => $created,
            'updated' => $updated,
            'failed' => $failed,
            'errors' => $errors,
        ];
    }

    /**
     * Display final results
     *
     * @param array $results
     */
    protected function displayResults(array $results): void
    {
        $this->info('Import Complete');
        $this->table(
            ['Action', 'Count'],
            [
                ['Created', $results['created']],
                ['Updated', $results['updated']],
                ['Failed', $results['failed']],
            ]
        );

        if (!empty($results['errors'])) {
            $this->newLine();
            $this->error('Errors:');
            foreach ($results['errors'] as $error) {
                $this->line("  {$error['cabinet']}: {$error['error']}");
            }
        }

        if ($results['created'] > 0 || $results['updated'] > 0) {
            $this->newLine();
            $this->info('Imported cabinets require review. Navigate to:');
            $this->line('  Admin → Projects → Cabinets');
        }
    }
}
