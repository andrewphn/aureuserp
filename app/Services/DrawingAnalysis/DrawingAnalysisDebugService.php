<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * DrawingAnalysisDebugService
 *
 * Provides comprehensive debugging, viewing, and inspection capabilities
 * for the drawing analysis pipeline. Useful for:
 * - Viewing step-by-step processing results
 * - Comparing extracted vs expected data
 * - Generating human-readable reports
 * - Tracking data transformations through the pipeline
 * - Viewing persistence mapping before/after database writes
 */
class DrawingAnalysisDebugService
{
    // Output formats
    public const FORMAT_JSON = 'json';
    public const FORMAT_HTML = 'html';
    public const FORMAT_TEXT = 'text';
    public const FORMAT_MARKDOWN = 'markdown';

    // View modes
    public const VIEW_SUMMARY = 'summary';
    public const VIEW_DETAILED = 'detailed';
    public const VIEW_RAW = 'raw';
    public const VIEW_DIFF = 'diff';

    protected ?string $sessionId = null;
    protected array $pipelineData = [];
    protected array $persistenceData = [];
    protected array $comparisonData = [];

    /**
     * Load pipeline session for debugging
     */
    public function loadSession(string $sessionId): self
    {
        $this->sessionId = $sessionId;

        // Load pipeline state
        $state = Cache::get("pipeline_state:{$sessionId}");
        if ($state) {
            $this->pipelineData = $state;
        }

        // Load step results
        for ($i = 1; $i <= 10; $i++) {
            $stepData = Cache::get("pipeline:{$sessionId}:step_{$i}");
            if ($stepData) {
                $this->pipelineData["cached_step_{$i}"] = $stepData;
            }
        }

        return $this;
    }

    /**
     * Load data from orchestrator export
     */
    public function loadFromExport(array $exportedData): self
    {
        $this->sessionId = $exportedData['session_id'] ?? 'imported';
        $this->pipelineData = $exportedData;

        return $this;
    }

    /**
     * Load persistence result for comparison
     */
    public function loadPersistenceResult(array $persistenceResult): self
    {
        $this->persistenceData = $persistenceResult;

        return $this;
    }

    // =========================================================================
    // STEP VIEWING METHODS
    // =========================================================================

    /**
     * Get overview of all steps
     */
    public function getStepsOverview(): array
    {
        $steps = [];
        $stepResults = $this->pipelineData['step_results'] ?? [];

        for ($i = 1; $i <= 10; $i++) {
            $stepKey = "step_{$i}";
            $stepData = $stepResults[$stepKey] ?? null;

            $steps[] = [
                'number' => $i,
                'name' => $this->getStepName($i),
                'status' => $stepData['status'] ?? 'not_executed',
                'duration_ms' => $stepData['duration_ms'] ?? null,
                'has_data' => !empty($stepData['data']),
                'was_edited' => $stepData['edited'] ?? false,
                'has_warnings' => $this->stepHasWarnings($stepData),
                'has_errors' => $stepData['status'] === 'failed',
                'data_keys' => $stepData['data'] ? array_keys($stepData['data']) : [],
            ];
        }

        return [
            'session_id' => $this->sessionId,
            'status' => $this->pipelineData['status'] ?? 'unknown',
            'steps' => $steps,
            'total_duration_ms' => array_sum(array_column($steps, 'duration_ms')),
            'completed_count' => count(array_filter($steps, fn($s) => $s['status'] === 'passed')),
            'failed_count' => count(array_filter($steps, fn($s) => $s['status'] === 'failed')),
        ];
    }

    /**
     * Get detailed view of a specific step
     */
    public function getStepDetail(int $stepNumber, string $viewMode = self::VIEW_DETAILED): array
    {
        $stepKey = "step_{$stepNumber}";
        $stepResults = $this->pipelineData['step_results'] ?? [];
        $stepData = $stepResults[$stepKey] ?? null;

        if (!$stepData) {
            return ['error' => "Step {$stepNumber} not found in session"];
        }

        $detail = [
            'step_number' => $stepNumber,
            'step_name' => $this->getStepName($stepNumber),
            'status' => $stepData['status'] ?? 'unknown',
            'duration_ms' => $stepData['duration_ms'] ?? null,
            'was_edited' => $stepData['edited'] ?? false,
            'edit_timestamp' => $stepData['edit_timestamp'] ?? null,
        ];

        switch ($viewMode) {
            case self::VIEW_RAW:
                $detail['data'] = $stepData['data'] ?? null;
                break;

            case self::VIEW_SUMMARY:
                $detail['data_summary'] = $this->summarizeStepData($stepNumber, $stepData['data'] ?? []);
                break;

            case self::VIEW_DETAILED:
            default:
                $detail['data'] = $stepData['data'] ?? null;
                $detail['data_summary'] = $this->summarizeStepData($stepNumber, $stepData['data'] ?? []);
                $detail['key_findings'] = $this->extractKeyFindings($stepNumber, $stepData['data'] ?? []);
                break;
        }

        return $detail;
    }

    /**
     * Get data flow between steps (what each step passed to the next)
     */
    public function getDataFlow(): array
    {
        $flow = [];
        $stepResults = $this->pipelineData['step_results'] ?? [];

        $dependencies = [
            1 => [],                    // No dependencies
            2 => [1],                   // Needs context from step 1
            3 => [1],                   // Needs context from step 1
            4 => [1, 2, 3],             // Validates steps 1-3
            5 => [1, 2, 3],             // Uses context, dimensions, notes
            6 => [2, 5],                // Verifies dimensions against entities
            7 => [5, 6],                // Checks entities against standards
            8 => [3, 6, 7],             // Derives constraints from notes and verification
            9 => [5, 6, 8],             // Extracts components using entities, verification, constraints
            10 => ['all'],              // Audits all steps
        ];

        foreach ($dependencies as $step => $deps) {
            $stepKey = "step_{$step}";
            $stepData = $stepResults[$stepKey] ?? null;

            $inputSummary = [];
            foreach ($deps as $dep) {
                if ($dep === 'all') {
                    $inputSummary['all_steps'] = 'Full pipeline results';
                } else {
                    $depKey = "step_{$dep}";
                    $depData = $stepResults[$depKey]['data'] ?? null;
                    $inputSummary["step_{$dep}"] = $depData ? array_keys($depData) : 'missing';
                }
            }

            $flow[] = [
                'step' => $step,
                'name' => $this->getStepName($step),
                'receives_from' => $deps,
                'input_summary' => $inputSummary,
                'output_keys' => $stepData['data'] ? array_keys($stepData['data']) : [],
                'status' => $stepData['status'] ?? 'not_executed',
            ];
        }

        return $flow;
    }

    // =========================================================================
    // ENTITY VIEWING METHODS
    // =========================================================================

    /**
     * Get hierarchical entity tree from Step 5
     */
    public function getEntityTree(): array
    {
        $stepResults = $this->pipelineData['step_results'] ?? [];
        $entityData = $stepResults['step_5']['data']['entities'] ?? [];

        if (empty($entityData)) {
            return ['error' => 'No entity data found'];
        }

        $tree = [];

        // Project level
        $project = $entityData['project'] ?? null;
        if ($project) {
            $tree['project'] = [
                'id' => $project['id'] ?? 'unknown',
                'name' => $project['name'] ?? 'Unknown Project',
                'rooms' => [],
            ];

            // Rooms
            foreach ($entityData['rooms'] ?? [] as $room) {
                $roomNode = [
                    'id' => $room['id'],
                    'name' => $room['name'] ?? 'Unknown Room',
                    'type' => $room['type'] ?? null,
                    'locations' => [],
                ];

                // Locations for this room
                foreach ($entityData['locations'] ?? [] as $location) {
                    if (($location['parent_id'] ?? '') === $room['id']) {
                        $locationNode = [
                            'id' => $location['id'],
                            'name' => $location['name'] ?? 'Unknown Location',
                            'type' => $location['type'] ?? null,
                            'cabinet_runs' => [],
                        ];

                        // Runs for this location
                        foreach ($entityData['cabinet_runs'] ?? [] as $run) {
                            if (($run['parent_id'] ?? '') === $location['id']) {
                                $runNode = [
                                    'id' => $run['id'],
                                    'name' => $run['name'] ?? 'Unknown Run',
                                    'type' => $run['type'] ?? null,
                                    'cabinets' => [],
                                ];

                                // Cabinets for this run
                                foreach ($entityData['cabinets'] ?? [] as $cabinet) {
                                    if (($cabinet['parent_id'] ?? '') === $run['id']) {
                                        $cabinetNode = [
                                            'id' => $cabinet['id'],
                                            'name' => $cabinet['name'] ?? 'Unknown Cabinet',
                                            'position' => $cabinet['position_in_run'] ?? null,
                                            'dimensions' => $this->formatDimensions($cabinet['bounding_geometry'] ?? []),
                                            'sections' => [],
                                        ];

                                        // Sections for this cabinet
                                        foreach ($entityData['sections'] ?? [] as $section) {
                                            if (($section['parent_id'] ?? '') === $cabinet['id']) {
                                                $cabinetNode['sections'][] = [
                                                    'id' => $section['id'],
                                                    'name' => $section['name'] ?? 'Unknown Section',
                                                    'type' => $section['type'] ?? null,
                                                ];
                                            }
                                        }

                                        $runNode['cabinets'][] = $cabinetNode;
                                    }
                                }

                                $locationNode['cabinet_runs'][] = $runNode;
                            }
                        }

                        $roomNode['locations'][] = $locationNode;
                    }
                }

                $tree['project']['rooms'][] = $roomNode;
            }
        }

        return $tree;
    }

    /**
     * Get components summary from Step 9
     */
    public function getComponentsSummary(): array
    {
        $stepResults = $this->pipelineData['step_results'] ?? [];
        $componentData = $stepResults['step_9']['data']['components'] ?? [];

        if (empty($componentData)) {
            return ['error' => 'No component data found'];
        }

        $byType = [];
        $byParent = [];

        foreach ($componentData as $comp) {
            $type = $comp['type'] ?? 'unknown';
            $parentId = $comp['parent_id'] ?? 'unknown';

            // Group by type
            if (!isset($byType[$type])) {
                $byType[$type] = [];
            }
            $byType[$type][] = [
                'id' => $comp['id'],
                'name' => $comp['name'] ?? null,
                'dimensions' => $this->formatComponentDimensions($comp),
                'confidence' => $comp['confidence'] ?? null,
            ];

            // Group by parent
            if (!isset($byParent[$parentId])) {
                $byParent[$parentId] = [];
            }
            $byParent[$parentId][] = $comp['id'];
        }

        return [
            'total_count' => count($componentData),
            'by_type' => array_map(fn($items) => [
                'count' => count($items),
                'items' => $items,
            ], $byType),
            'by_parent' => $byParent,
            'type_counts' => array_map('count', $byType),
        ];
    }

    // =========================================================================
    // PERSISTENCE VIEWING METHODS
    // =========================================================================

    /**
     * Get database mapping preview (what will be written where)
     */
    public function getDatabaseMappingPreview(): array
    {
        $extractedData = $this->pipelineData['extracted_data'] ?? [];

        $mapping = [
            'projects_projects' => $this->previewProjectMapping($extractedData),
            'projects_rooms' => $this->previewRoomsMapping($extractedData),
            'projects_room_locations' => $this->previewLocationsMapping($extractedData),
            'projects_cabinet_runs' => $this->previewRunsMapping($extractedData),
            'projects_cabinets' => $this->previewCabinetsMapping($extractedData),
            'projects_cabinet_sections' => $this->previewSectionsMapping($extractedData),
            'projects_drawers' => $this->previewDrawersMapping($extractedData),
            'projects_doors' => $this->previewDoorsMapping($extractedData),
            'projects_shelves' => $this->previewShelvesMapping($extractedData),
            'projects_stretchers' => $this->previewStretchersMapping($extractedData),
        ];

        return [
            'session_id' => $this->sessionId,
            'tables' => $mapping,
            'record_counts' => array_map(fn($rows) => count($rows['records'] ?? []), $mapping),
            'total_records' => array_sum(array_map(fn($rows) => count($rows['records'] ?? []), $mapping)),
        ];
    }

    /**
     * Compare extracted data with persistence result
     */
    public function compareToPersistenceResult(): array
    {
        if (empty($this->persistenceData)) {
            return ['error' => 'No persistence result loaded'];
        }

        $idMap = $this->persistenceData['id_map'] ?? [];
        $createdRecords = $this->persistenceData['created_records'] ?? [];

        $comparison = [];

        // Compare entity IDs
        $entities = $this->pipelineData['extracted_data']['entities']['entities'] ?? [];

        foreach (['rooms', 'locations', 'cabinet_runs', 'cabinets', 'sections'] as $entityType) {
            $entityList = $entities[$entityType] ?? [];
            foreach ($entityList as $entity) {
                $extractedId = $entity['id'] ?? null;
                $dbId = $idMap[$extractedId] ?? null;

                $comparison[] = [
                    'entity_type' => $entityType,
                    'extracted_id' => $extractedId,
                    'database_id' => $dbId,
                    'mapped' => $dbId !== null,
                    'name' => $entity['name'] ?? null,
                ];
            }
        }

        return [
            'session_id' => $this->sessionId,
            'persistence_success' => $this->persistenceData['success'] ?? false,
            'total_mapped' => count(array_filter($comparison, fn($c) => $c['mapped'])),
            'total_unmapped' => count(array_filter($comparison, fn($c) => !$c['mapped'])),
            'id_mappings' => $comparison,
            'created_summary' => $this->persistenceData['summary'] ?? [],
        ];
    }

    // =========================================================================
    // REPORT GENERATION METHODS
    // =========================================================================

    /**
     * Generate a complete debug report
     */
    public function generateReport(string $format = self::FORMAT_HTML): string
    {
        $data = [
            'session_id' => $this->sessionId,
            'generated_at' => now()->toIso8601String(),
            'overview' => $this->getStepsOverview(),
            'entity_tree' => $this->getEntityTree(),
            'components_summary' => $this->getComponentsSummary(),
            'data_flow' => $this->getDataFlow(),
            'db_mapping' => $this->getDatabaseMappingPreview(),
        ];

        switch ($format) {
            case self::FORMAT_JSON:
                return json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

            case self::FORMAT_MARKDOWN:
                return $this->renderMarkdownReport($data);

            case self::FORMAT_TEXT:
                return $this->renderTextReport($data);

            case self::FORMAT_HTML:
            default:
                return $this->renderHtmlReport($data);
        }
    }

    /**
     * Generate step-by-step comparison report
     */
    public function generateStepComparisonReport(array $expectedData): array
    {
        $comparison = [];
        $stepResults = $this->pipelineData['step_results'] ?? [];

        foreach ($expectedData as $stepKey => $expected) {
            $actual = $stepResults[$stepKey]['data'] ?? null;

            $comparison[$stepKey] = [
                'step' => $stepKey,
                'has_actual' => $actual !== null,
                'has_expected' => $expected !== null,
                'matches' => $this->deepCompare($actual, $expected),
                'differences' => $this->findDifferences($actual, $expected),
            ];
        }

        return $comparison;
    }

    // =========================================================================
    // HELPER METHODS
    // =========================================================================

    protected function getStepName(int $stepNumber): string
    {
        return match($stepNumber) {
            1 => 'Drawing Context Analysis',
            2 => 'Dimension Reference Analysis',
            3 => 'Notes & Callout Extraction',
            4 => 'Drawing Intent Validation',
            5 => 'Hierarchical Entity Extraction',
            6 => 'Dimension Consistency Verification',
            7 => 'Standard Practice Alignment',
            8 => 'Production Constraint Derivation',
            9 => 'Component Extraction',
            10 => 'Verification & Audit',
            default => "Unknown Step {$stepNumber}",
        };
    }

    protected function stepHasWarnings(?array $stepData): bool
    {
        if (!$stepData || !isset($stepData['data'])) {
            return false;
        }

        $data = $stepData['data'];
        return !empty($data['warnings'] ?? [])
            || !empty($data['flags'] ?? [])
            || !empty($data['discrepancies'] ?? [])
            || !empty($data['potential_conflicts'] ?? []);
    }

    protected function summarizeStepData(int $stepNumber, array $data): array
    {
        return match($stepNumber) {
            1 => [
                'view_type' => $data['view_type'] ?? 'unknown',
                'orientation' => $data['orientation'] ?? 'unknown',
                'drawing_intent' => $data['drawing_intent'] ?? 'unknown',
                'unit_system' => $data['unit_system'] ?? 'unknown',
            ],
            2 => [
                'dimension_count' => count($data['dimensions'] ?? []),
                'conflicts' => count($data['potential_conflicts'] ?? []),
            ],
            3 => [
                'note_count' => count($data['notes'] ?? []),
                'has_title_block' => !empty($data['title_block']),
            ],
            4 => [
                'can_proceed' => $data['can_proceed']['extraction_allowed'] ?? false,
                'blocker_count' => count($data['blockers'] ?? []),
            ],
            5 => [
                'room_count' => count($data['entities']['rooms'] ?? []),
                'cabinet_count' => count($data['entities']['cabinets'] ?? []),
                'section_count' => count($data['entities']['sections'] ?? []),
            ],
            6 => [
                'verifications' => count($data['cabinet_verifications'] ?? []),
                'discrepancies' => count($data['discrepancies'] ?? []),
            ],
            7 => [
                'evaluations' => count($data['practice_evaluations'] ?? []),
                'flags' => count($data['flags'] ?? []),
            ],
            8 => [
                'constraint_count' => count($data['constraints'] ?? []),
            ],
            9 => [
                'component_count' => count($data['components'] ?? []),
            ],
            10 => [
                'verification_level' => $data['verification_level']['level'] ?? 'unknown',
                'assumption_count' => count($data['assumptions'] ?? []),
            ],
            default => ['key_count' => count($data)],
        };
    }

    protected function extractKeyFindings(int $stepNumber, array $data): array
    {
        $findings = [];

        // Extract warnings/flags
        foreach (['warnings', 'flags', 'discrepancies', 'blockers'] as $key) {
            if (!empty($data[$key])) {
                $findings[$key] = $data[$key];
            }
        }

        // Step-specific findings
        if ($stepNumber === 4 && !($data['can_proceed']['extraction_allowed'] ?? true)) {
            $findings['gate_blocked'] = true;
            $findings['missing_requirements'] = $data['can_proceed']['missing'] ?? [];
        }

        if ($stepNumber === 10) {
            $findings['verification_level'] = $data['verification_level'] ?? null;
            $findings['assumptions'] = $data['assumptions'] ?? [];
            $findings['recommendations'] = $data['recommendations'] ?? [];
        }

        return $findings;
    }

    protected function formatDimensions(array $geometry): string
    {
        $w = $geometry['width']['numeric'] ?? '?';
        $h = $geometry['height']['numeric'] ?? '?';
        $d = $geometry['depth']['numeric'] ?? '?';

        return "{$w}\"W x {$h}\"H x {$d}\"D";
    }

    protected function formatComponentDimensions(array $component): string
    {
        $dims = $component['dimensions'] ?? [];
        $w = $dims['width']['value'] ?? '?';
        $h = $dims['height']['value'] ?? '?';

        return "{$w}\" x {$h}\"";
    }

    protected function deepCompare($a, $b): bool
    {
        return json_encode($a) === json_encode($b);
    }

    protected function findDifferences($actual, $expected, string $path = ''): array
    {
        $diffs = [];

        if (!is_array($actual) || !is_array($expected)) {
            if ($actual !== $expected) {
                $diffs[] = [
                    'path' => $path,
                    'actual' => $actual,
                    'expected' => $expected,
                ];
            }
            return $diffs;
        }

        $allKeys = array_unique(array_merge(array_keys($actual), array_keys($expected)));

        foreach ($allKeys as $key) {
            $newPath = $path ? "{$path}.{$key}" : $key;
            $actualValue = $actual[$key] ?? null;
            $expectedValue = $expected[$key] ?? null;

            if (!array_key_exists($key, $actual)) {
                $diffs[] = ['path' => $newPath, 'type' => 'missing_in_actual', 'expected' => $expectedValue];
            } elseif (!array_key_exists($key, $expected)) {
                $diffs[] = ['path' => $newPath, 'type' => 'extra_in_actual', 'actual' => $actualValue];
            } else {
                $diffs = array_merge($diffs, $this->findDifferences($actualValue, $expectedValue, $newPath));
            }
        }

        return $diffs;
    }

    // Preview mapping methods (simplified)
    protected function previewProjectMapping(array $data): array
    {
        $project = $data['entities']['entities']['project'] ?? null;
        if (!$project) return ['records' => []];

        return [
            'records' => [[
                'name' => $project['name'] ?? 'Imported Project',
                'source' => 'step_5.entities.project',
            ]],
        ];
    }

    protected function previewRoomsMapping(array $data): array
    {
        $rooms = $data['entities']['entities']['rooms'] ?? [];
        return [
            'records' => array_map(fn($r) => [
                'extracted_id' => $r['id'],
                'name' => $r['name'] ?? 'Room',
                'type' => $r['type'] ?? null,
            ], $rooms),
        ];
    }

    protected function previewLocationsMapping(array $data): array
    {
        $locations = $data['entities']['entities']['locations'] ?? [];
        return [
            'records' => array_map(fn($l) => [
                'extracted_id' => $l['id'],
                'name' => $l['name'] ?? 'Location',
                'parent_id' => $l['parent_id'] ?? null,
            ], $locations),
        ];
    }

    protected function previewRunsMapping(array $data): array
    {
        $runs = $data['entities']['entities']['cabinet_runs'] ?? [];
        return [
            'records' => array_map(fn($r) => [
                'extracted_id' => $r['id'],
                'name' => $r['name'] ?? 'Run',
                'type' => $r['type'] ?? null,
            ], $runs),
        ];
    }

    protected function previewCabinetsMapping(array $data): array
    {
        $cabinets = $data['entities']['entities']['cabinets'] ?? [];
        return [
            'records' => array_map(fn($c) => [
                'extracted_id' => $c['id'],
                'name' => $c['name'] ?? 'Cabinet',
                'dimensions' => $this->formatDimensions($c['bounding_geometry'] ?? []),
            ], $cabinets),
        ];
    }

    protected function previewSectionsMapping(array $data): array
    {
        $sections = $data['entities']['entities']['sections'] ?? [];
        return [
            'records' => array_map(fn($s) => [
                'extracted_id' => $s['id'],
                'name' => $s['name'] ?? 'Section',
                'type' => $s['type'] ?? null,
            ], $sections),
        ];
    }

    protected function previewDrawersMapping(array $data): array
    {
        $components = $data['components']['components'] ?? [];
        $drawers = array_filter($components, fn($c) => ($c['type'] ?? '') === 'drawer');
        return [
            'records' => array_map(fn($d) => [
                'extracted_id' => $d['id'],
                'name' => $d['name'] ?? 'Drawer',
                'dimensions' => $this->formatComponentDimensions($d),
            ], array_values($drawers)),
        ];
    }

    protected function previewDoorsMapping(array $data): array
    {
        $components = $data['components']['components'] ?? [];
        $doors = array_filter($components, fn($c) => ($c['type'] ?? '') === 'door');
        return [
            'records' => array_map(fn($d) => [
                'extracted_id' => $d['id'],
                'name' => $d['name'] ?? 'Door',
                'dimensions' => $this->formatComponentDimensions($d),
            ], array_values($doors)),
        ];
    }

    protected function previewShelvesMapping(array $data): array
    {
        $components = $data['components']['components'] ?? [];
        $shelves = array_filter($components, fn($c) => ($c['type'] ?? '') === 'shelf');
        return [
            'records' => array_map(fn($s) => [
                'extracted_id' => $s['id'],
                'name' => $s['name'] ?? 'Shelf',
            ], array_values($shelves)),
        ];
    }

    protected function previewStretchersMapping(array $data): array
    {
        $components = $data['components']['components'] ?? [];
        $stretchers = array_filter($components, fn($c) => ($c['type'] ?? '') === 'stretcher');
        return [
            'records' => array_map(fn($s) => [
                'extracted_id' => $s['id'],
                'type' => $s['stretcher_details']['purpose'] ?? 'support',
            ], array_values($stretchers)),
        ];
    }

    // =========================================================================
    // REPORT RENDERING
    // =========================================================================

    protected function renderHtmlReport(array $data): string
    {
        $html = '<!DOCTYPE html><html><head>';
        $html .= '<title>Drawing Analysis Debug Report</title>';
        $html .= '<style>
            body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; margin: 20px; }
            h1, h2, h3 { color: #333; }
            .section { margin: 20px 0; padding: 15px; border: 1px solid #ddd; border-radius: 8px; }
            .step { display: inline-block; padding: 8px 16px; margin: 4px; border-radius: 4px; }
            .step.passed { background: #d4edda; color: #155724; }
            .step.failed { background: #f8d7da; color: #721c24; }
            .step.not_executed { background: #e2e3e5; color: #383d41; }
            table { border-collapse: collapse; width: 100%; margin: 10px 0; }
            th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
            th { background: #f4f4f4; }
            .tree { font-family: monospace; white-space: pre; }
            pre { background: #f8f9fa; padding: 10px; border-radius: 4px; overflow-x: auto; }
        </style></head><body>';

        $html .= "<h1>Drawing Analysis Debug Report</h1>";
        $html .= "<p>Session: {$data['session_id']}</p>";
        $html .= "<p>Generated: {$data['generated_at']}</p>";

        // Overview
        $html .= '<div class="section"><h2>Pipeline Overview</h2>';
        $overview = $data['overview'];
        $html .= "<p>Status: <strong>{$overview['status']}</strong></p>";
        $html .= "<p>Completed: {$overview['completed_count']}/10 steps</p>";
        $html .= '<div>';
        foreach ($overview['steps'] as $step) {
            $class = $step['status'];
            $html .= "<span class=\"step {$class}\">Step {$step['number']}: {$step['name']} ({$step['status']})</span>";
        }
        $html .= '</div></div>';

        // Entity Tree
        $html .= '<div class="section"><h2>Entity Hierarchy</h2>';
        $html .= '<pre class="tree">' . $this->renderTreeAsText($data['entity_tree']) . '</pre>';
        $html .= '</div>';

        // Components Summary
        $html .= '<div class="section"><h2>Components Summary</h2>';
        $components = $data['components_summary'];
        if (!isset($components['error'])) {
            $html .= "<p>Total Components: {$components['total_count']}</p>";
            $html .= '<table><tr><th>Type</th><th>Count</th></tr>';
            foreach ($components['type_counts'] as $type => $count) {
                $html .= "<tr><td>{$type}</td><td>{$count}</td></tr>";
            }
            $html .= '</table>';
        }
        $html .= '</div>';

        // Database Mapping Preview
        $html .= '<div class="section"><h2>Database Mapping Preview</h2>';
        $dbMapping = $data['db_mapping'];
        $html .= "<p>Total Records to Create: {$dbMapping['total_records']}</p>";
        $html .= '<table><tr><th>Table</th><th>Record Count</th></tr>';
        foreach ($dbMapping['record_counts'] as $table => $count) {
            $html .= "<tr><td>{$table}</td><td>{$count}</td></tr>";
        }
        $html .= '</table></div>';

        $html .= '</body></html>';

        return $html;
    }

    protected function renderMarkdownReport(array $data): string
    {
        $md = "# Drawing Analysis Debug Report\n\n";
        $md .= "**Session:** {$data['session_id']}\n";
        $md .= "**Generated:** {$data['generated_at']}\n\n";

        // Overview
        $md .= "## Pipeline Overview\n\n";
        $overview = $data['overview'];
        $md .= "- **Status:** {$overview['status']}\n";
        $md .= "- **Completed:** {$overview['completed_count']}/10 steps\n\n";

        $md .= "| Step | Name | Status | Duration |\n";
        $md .= "|------|------|--------|----------|\n";
        foreach ($overview['steps'] as $step) {
            $duration = $step['duration_ms'] ? "{$step['duration_ms']}ms" : '-';
            $md .= "| {$step['number']} | {$step['name']} | {$step['status']} | {$duration} |\n";
        }
        $md .= "\n";

        // Components
        $md .= "## Components Summary\n\n";
        $components = $data['components_summary'];
        if (!isset($components['error'])) {
            $md .= "| Type | Count |\n";
            $md .= "|------|-------|\n";
            foreach ($components['type_counts'] as $type => $count) {
                $md .= "| {$type} | {$count} |\n";
            }
        }

        return $md;
    }

    protected function renderTextReport(array $data): string
    {
        $text = "=== DRAWING ANALYSIS DEBUG REPORT ===\n\n";
        $text .= "Session: {$data['session_id']}\n";
        $text .= "Generated: {$data['generated_at']}\n\n";

        $overview = $data['overview'];
        $text .= "STATUS: {$overview['status']}\n";
        $text .= "COMPLETED: {$overview['completed_count']}/10 steps\n\n";

        $text .= "--- STEPS ---\n";
        foreach ($overview['steps'] as $step) {
            $status = strtoupper($step['status']);
            $text .= "  [{$status}] Step {$step['number']}: {$step['name']}\n";
        }

        return $text;
    }

    protected function renderTreeAsText(array $tree, int $indent = 0): string
    {
        $text = '';
        $prefix = str_repeat('  ', $indent);

        if (isset($tree['project'])) {
            $project = $tree['project'];
            $text .= "{$prefix}PROJECT: {$project['name']}\n";

            foreach ($project['rooms'] ?? [] as $room) {
                $text .= "{$prefix}  ROOM: {$room['name']} ({$room['type']})\n";

                foreach ($room['locations'] ?? [] as $location) {
                    $text .= "{$prefix}    LOCATION: {$location['name']}\n";

                    foreach ($location['cabinet_runs'] ?? [] as $run) {
                        $text .= "{$prefix}      RUN: {$run['name']} ({$run['type']})\n";

                        foreach ($run['cabinets'] ?? [] as $cabinet) {
                            $text .= "{$prefix}        CABINET: {$cabinet['name']} - {$cabinet['dimensions']}\n";

                            foreach ($cabinet['sections'] ?? [] as $section) {
                                $text .= "{$prefix}          SECTION: {$section['name']}\n";
                            }
                        }
                    }
                }
            }
        }

        return $text;
    }

    /**
     * Save report to storage
     */
    public function saveReport(string $format = self::FORMAT_HTML, ?string $filename = null): string
    {
        $content = $this->generateReport($format);
        $extension = match($format) {
            self::FORMAT_JSON => 'json',
            self::FORMAT_MARKDOWN => 'md',
            self::FORMAT_TEXT => 'txt',
            default => 'html',
        };

        $filename = $filename ?? "drawing-analysis-debug-{$this->sessionId}.{$extension}";
        $path = "debug-reports/{$filename}";

        Storage::put($path, $content);

        return $path;
    }
}
