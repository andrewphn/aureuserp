<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Extracts entities hierarchically from construction drawings WITHOUT making assumptions.
 * This is the FIFTH step in the drawing analysis pipeline.
 *
 * Purpose: Extract entity relationships and raw dimensions only.
 * Does NOT calculate gaps, place stretchers, size drawer boxes, or infer hardware.
 */
class HierarchicalEntityExtractorService
{
    protected ?string $geminiKey;
    protected string $model = 'gemini-2.0-flash';

    // Entity hierarchy levels (in order)
    public const ENTITY_HIERARCHY = [
        'project' => 1,
        'room' => 2,
        'location' => 3,
        'cabinet_run' => 4,
        'cabinet' => 5,
        'section' => 6,
        'component' => 7,
    ];

    // Entity types at each level
    public const ENTITY_TYPES = [
        'project' => ['project'],
        'room' => ['kitchen', 'bathroom', 'laundry', 'garage', 'closet', 'office', 'other'],
        'location' => ['wall', 'island', 'peninsula', 'corner', 'alcove'],
        'cabinet_run' => ['base_run', 'upper_run', 'tall_run', 'vanity_run'],
        'cabinet' => [
            'base', 'sink_base', 'drawer_base', 'blind_corner_base', 'lazy_susan',
            'wall', 'wall_corner', 'wall_diagonal',
            'tall', 'pantry', 'oven_cabinet',
            'vanity', 'vanity_sink', 'vanity_drawer',
        ],
        'section' => ['drawer_stack', 'door_section', 'open_section', 'appliance_opening'],
        'component' => ['drawer', 'door', 'shelf', 'false_front', 'panel', 'stretcher_placeholder'],
    ];

    // Component placeholder types (no sizing yet)
    public const COMPONENT_PLACEHOLDERS = [
        'drawer' => 'Drawer opening (size TBD)',
        'door' => 'Door opening (size TBD)',
        'shelf' => 'Shelf position (size TBD)',
        'false_front' => 'False front (size TBD)',
        'panel' => 'Panel (side, back, bottom)',
        'stretcher_placeholder' => 'Stretcher location (size TBD)',
        'appliance_opening' => 'Appliance rough opening',
        'sink_cutout' => 'Sink cutout area',
    ];

    public function __construct()
    {
        $this->geminiKey = config('services.google.api_key') ?? env('GOOGLE_API_KEY') ?? env('GEMINI_API_KEY');
    }

    /**
     * Extract entities hierarchically from a drawing.
     *
     * @param string $imageBase64 Base64-encoded image data
     * @param string $mimeType Image MIME type
     * @param array $priorAnalysis Combined results from steps 1-4
     * @return array Extraction result with hierarchical entities
     */
    public function extractEntities(
        string $imageBase64,
        string $mimeType,
        array $priorAnalysis
    ): array {
        if (empty($this->geminiKey)) {
            return $this->errorResponse('Gemini API key not configured');
        }

        // Verify we have validation gate approval
        $canProceed = $priorAnalysis['validation']['can_proceed']['extraction_allowed'] ?? false;
        if (!$canProceed) {
            return $this->errorResponse('Extraction blocked by validation gate');
        }

        $systemPrompt = $this->buildExtractionPrompt($priorAnalysis);

        try {
            $response = $this->callGeminiApi($systemPrompt, [
                'mimeType' => $mimeType,
                'data' => $imageBase64,
            ]);

            if (isset($response['error'])) {
                return $response;
            }

            return $this->parseExtractionResponse($response, $priorAnalysis);

        } catch (\Exception $e) {
            Log::error('HierarchicalEntityExtractor error: ' . $e->getMessage());
            return $this->errorResponse($e->getMessage());
        }
    }

    /**
     * Build the system prompt for entity extraction.
     */
    protected function buildExtractionPrompt(array $priorAnalysis): string
    {
        // Extract relevant context from prior analysis
        $context = $priorAnalysis['context']['context'] ?? [];
        $dimensions = $priorAnalysis['dimensions']['references']['dimensions'] ?? [];
        $notes = $priorAnalysis['notes']['extraction']['notes'] ?? [];

        $viewType = $context['view_type']['primary'] ?? 'unknown';
        $orientation = $context['orientation']['primary'] ?? 'unknown';
        $units = $context['unit_system']['primary'] ?? 'inches';

        // Build dimension reference list
        $dimList = '';
        foreach (array_slice($dimensions, 0, 20) as $dim) {
            $value = $dim['value']['as_written'] ?? 'unknown';
            $orient = $dim['orientation'] ?? 'unknown';
            $dimList .= "- {$value} ({$orient})\n";
        }

        // Build notes reference list
        $noteList = '';
        $productionNotes = array_filter($notes, fn($n) => ($n['actionability'] ?? '') === 'production_required');
        foreach (array_slice($productionNotes, 0, 10) as $note) {
            $text = $note['text']['exact'] ?? '';
            $scope = $note['scope'] ?? 'unknown';
            $noteList .= "- [{$scope}] {$text}\n";
        }

        return <<<PROMPT
You are extracting entities from a cabinet construction drawing in SAFE MODE.

CRITICAL RULES - DO NOT VIOLATE:
1. Do NOT calculate gaps or reveals
2. Do NOT place stretchers (only mark placeholder locations)
3. Do NOT size drawer boxes
4. Do NOT infer hardware
5. Do NOT assume standard dimensions
6. ONLY extract what is explicitly shown or labeled

DRAWING CONTEXT:
- View Type: {$viewType}
- Orientation: {$orientation}
- Units: {$units}

AVAILABLE DIMENSIONS (from prior analysis):
{$dimList}

PRODUCTION NOTES (from prior analysis):
{$noteList}

## EXTRACTION HIERARCHY (follow this order strictly)

### Level 1: PROJECT
- Extract project name from title block if present
- Note drawing number, revision

### Level 2: ROOM(S)
- Identify room(s) shown
- Types: kitchen, bathroom, laundry, garage, closet, office, other

### Level 3: LOCATION(S)
- Identify specific locations within room
- Types: wall, island, peninsula, corner, alcove
- Include wall identifier if shown (e.g., "North Wall")

### Level 4: CABINET RUN(S)
- Identify runs of cabinets
- Types: base_run, upper_run, tall_run, vanity_run
- Note overall run length if dimensioned

### Level 5: CABINET(S)
- Identify individual cabinets
- Extract NAME/CODE if labeled (e.g., "SB36", "B24")
- Note cabinet TYPE (base, sink_base, wall, etc.)
- Extract overall WIDTH, HEIGHT, DEPTH if dimensioned
- DO NOT calculate - only report shown dimensions

### Level 6: SECTION(S)
- Identify sections within cabinets
- Types: drawer_stack, door_section, open_section, appliance_opening
- Note vertical position (top, middle, bottom)

### Level 7: COMPONENT PLACEHOLDERS
- Mark component LOCATIONS only
- Types: drawer, door, shelf, false_front, panel, stretcher_placeholder
- Do NOT size components - just note their presence and position
- For drawers: note position (1st, 2nd, etc.) not size
- For doors: note handing if shown (LH, RH)

## ENTITY DATA STRUCTURE

For each entity, provide:
- **id**: Unique identifier (e.g., "CAB-001", "DRW-001-01")
- **type**: Entity type from allowed list
- **name**: Name/label if shown, null if not
- **parent_id**: ID of parent entity
- **bounding_geometry**: Position/size information if visible
- **associated_notes**: IDs of notes that apply (from prior extraction)
- **associated_dimensions**: Raw dimension values that apply (unreconciled)
- **confidence**: 0.0-1.0

## RESPONSE FORMAT

Respond ONLY with valid JSON:

{
  "entities": {
    "project": {
      "id": "PRJ-001",
      "name": "Smith Kitchen",
      "drawing_number": "A-101",
      "revision": "2"
    },
    "rooms": [
      {
        "id": "ROOM-001",
        "type": "kitchen",
        "name": "Kitchen",
        "parent_id": "PRJ-001",
        "confidence": 0.95
      }
    ],
    "locations": [
      {
        "id": "LOC-001",
        "type": "wall",
        "name": "Sink Wall",
        "parent_id": "ROOM-001",
        "associated_notes": ["NOTE-001", "NOTE-003"],
        "confidence": 0.9
      }
    ],
    "cabinet_runs": [
      {
        "id": "RUN-001",
        "type": "base_run",
        "name": "Base Run",
        "parent_id": "LOC-001",
        "associated_dimensions": [
          {"value": "120\"", "type": "overall_length"}
        ],
        "confidence": 0.85
      }
    ],
    "cabinets": [
      {
        "id": "CAB-001",
        "type": "sink_base",
        "name": "SB36",
        "parent_id": "RUN-001",
        "bounding_geometry": {
          "width": {"value": "36\"", "source": "labeled"},
          "height": {"value": "34-1/2\"", "source": "inferred_from_run"},
          "depth": {"value": null, "source": "not_shown"}
        },
        "position_in_run": 2,
        "associated_notes": ["NOTE-002"],
        "associated_dimensions": [
          {"id": "DIM-003", "value": "36\"", "applies_to": "width"}
        ],
        "confidence": 0.9
      }
    ],
    "sections": [
      {
        "id": "SEC-001",
        "type": "drawer_stack",
        "parent_id": "CAB-001",
        "position": "top",
        "associated_dimensions": [],
        "confidence": 0.85
      }
    ],
    "components": [
      {
        "id": "COMP-001",
        "type": "false_front",
        "parent_id": "SEC-001",
        "placeholder": true,
        "position": "top",
        "notes": "U-shaped false front around sink",
        "confidence": 0.8
      },
      {
        "id": "COMP-002",
        "type": "drawer",
        "parent_id": "SEC-001",
        "placeholder": true,
        "position": "2nd_from_top",
        "notes": "Full-width drawer",
        "confidence": 0.85
      }
    ]
  },
  "relationships": [
    {
      "from": "CAB-001",
      "to": "CAB-002",
      "type": "adjacent_left"
    }
  ],
  "unassigned_dimensions": [
    {
      "id": "DIM-007",
      "value": "4\"",
      "possible_assignment": "toe_kick_height",
      "confidence": 0.6
    }
  ],
  "unassigned_notes": [
    {
      "id": "NOTE-008",
      "text": "VERIFY IN FIELD",
      "reason": "General note, not cabinet-specific"
    }
  ],
  "extraction_summary": {
    "total_entities": 12,
    "by_level": {
      "project": 1,
      "rooms": 1,
      "locations": 1,
      "cabinet_runs": 1,
      "cabinets": 3,
      "sections": 4,
      "components": 8
    },
    "placeholder_components": 8,
    "dimensions_assigned": 5,
    "dimensions_unassigned": 2,
    "overall_confidence": 0.85
  }
}
PROMPT;
    }

    /**
     * Call the Gemini API for entity extraction.
     */
    protected function callGeminiApi(string $systemPrompt, array $image): array
    {
        $contents = [
            [
                'role' => 'user',
                'parts' => [
                    ['text' => 'Extract all entities hierarchically from this drawing. Follow the safe mode rules - do not calculate gaps or size components.'],
                    ['inlineData' => $image],
                ],
            ],
        ];

        $payload = [
            'systemInstruction' => [
                'parts' => [['text' => $systemPrompt]],
            ],
            'contents' => $contents,
            'generationConfig' => [
                'temperature' => 0.2,
                'topP' => 0.8,
                'maxOutputTokens' => 8192,
            ],
        ];

        $maxRetries = 3;
        for ($attempt = 1; $attempt <= $maxRetries; $attempt++) {
            try {
                $response = Http::timeout(120)->withHeaders([
                    'Content-Type' => 'application/json',
                ])->post(
                    "https://generativelanguage.googleapis.com/v1beta/models/{$this->model}:generateContent?key={$this->geminiKey}",
                    $payload
                );

                if ($response->successful()) {
                    $data = $response->json();
                    $candidate = $data['candidates'][0] ?? null;

                    if (!$candidate) {
                        return $this->errorResponse('No response generated');
                    }

                    $content = $candidate['content'] ?? [];
                    $parts = $content['parts'] ?? [];
                    $textResponse = '';

                    foreach ($parts as $part) {
                        if (isset($part['text'])) {
                            $textResponse .= $part['text'];
                        }
                    }

                    return ['text' => $textResponse, 'success' => true];
                }

                $statusCode = $response->status();
                if ($statusCode === 429 && $attempt < $maxRetries) {
                    sleep(pow(2, $attempt));
                    continue;
                }

                return $this->errorResponse('API error: ' . ($response->json('error.message') ?? 'Unknown error'));

            } catch (\Exception $e) {
                if ($attempt < $maxRetries) {
                    sleep(pow(2, $attempt));
                    continue;
                }
                throw $e;
            }
        }

        return $this->errorResponse('All API attempts failed');
    }

    /**
     * Parse and validate the extraction response.
     */
    protected function parseExtractionResponse(array $response, array $priorAnalysis): array
    {
        $text = $response['text'] ?? '';

        // Clean up response
        $text = preg_replace('/```json\s*/i', '', $text);
        $text = preg_replace('/```\s*/i', '', $text);
        $text = trim($text);

        $decoded = json_decode($text, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return [
                'success' => false,
                'error' => 'Failed to parse JSON response: ' . json_last_error_msg(),
                'raw_response' => $text,
            ];
        }

        // Validate the extraction
        $validation = $this->validateExtraction($decoded);

        // Build entity index for easy lookup
        $entityIndex = $this->buildEntityIndex($decoded);

        return [
            'success' => true,
            'extraction' => $decoded,
            'validation' => $validation,
            'entity_index' => $entityIndex,
        ];
    }

    /**
     * Validate the extraction against expected structure.
     */
    protected function validateExtraction(array $extraction): array
    {
        $errors = [];
        $warnings = [];

        $entities = $extraction['entities'] ?? [];

        // Check hierarchy levels
        foreach (self::ENTITY_HIERARCHY as $level => $order) {
            $key = $level === 'project' ? 'project' : $level . 's';
            if ($level === 'cabinet_run') $key = 'cabinet_runs';
            if ($level === 'component') $key = 'components';

            if ($level === 'project') {
                if (!isset($entities['project'])) {
                    $warnings[] = "No project entity found";
                }
            } else {
                $items = $entities[$key] ?? [];
                if (empty($items) && in_array($level, ['cabinet', 'component'])) {
                    $warnings[] = "No {$level} entities extracted";
                }
            }
        }

        // Validate parent-child relationships
        $allIds = $this->collectAllIds($entities);
        foreach ($this->flattenEntities($entities) as $entity) {
            $parentId = $entity['parent_id'] ?? null;
            if ($parentId && !in_array($parentId, $allIds)) {
                $entityId = $entity['id'] ?? 'unknown';
                $errors[] = "Entity {$entityId} references non-existent parent {$parentId}";
            }
        }

        // Check for placeholder compliance (components should be placeholders)
        $components = $entities['components'] ?? [];
        foreach ($components as $comp) {
            if (!($comp['placeholder'] ?? false)) {
                $compId = $comp['id'] ?? 'unknown';
                $warnings[] = "Component {$compId} not marked as placeholder - should not have final dimensions";
            }
        }

        // Check confidence levels
        foreach ($this->flattenEntities($entities) as $entity) {
            $confidence = $entity['confidence'] ?? 1;
            if ($confidence < 0.6) {
                $entityId = $entity['id'] ?? 'unknown';
                $warnings[] = "Low confidence ({$confidence}) for entity {$entityId}";
            }
        }

        return [
            'is_valid' => empty($errors),
            'errors' => $errors,
            'warnings' => $warnings,
        ];
    }

    /**
     * Collect all entity IDs.
     */
    protected function collectAllIds(array $entities): array
    {
        $ids = [];

        if (isset($entities['project']['id'])) {
            $ids[] = $entities['project']['id'];
        }

        foreach (['rooms', 'locations', 'cabinet_runs', 'cabinets', 'sections', 'components'] as $key) {
            foreach ($entities[$key] ?? [] as $entity) {
                if (isset($entity['id'])) {
                    $ids[] = $entity['id'];
                }
            }
        }

        return $ids;
    }

    /**
     * Flatten all entities into a single array.
     */
    protected function flattenEntities(array $entities): array
    {
        $flat = [];

        foreach (['rooms', 'locations', 'cabinet_runs', 'cabinets', 'sections', 'components'] as $key) {
            foreach ($entities[$key] ?? [] as $entity) {
                $flat[] = $entity;
            }
        }

        return $flat;
    }

    /**
     * Build an index of entities by ID and type.
     */
    protected function buildEntityIndex(array $extraction): array
    {
        $byId = [];
        $byType = [];
        $byParent = [];

        $entities = $extraction['entities'] ?? [];

        // Index project
        if (isset($entities['project'])) {
            $project = $entities['project'];
            $byId[$project['id']] = $project;
            $byType['project'][] = $project;
        }

        // Index all other entities
        foreach (['rooms', 'locations', 'cabinet_runs', 'cabinets', 'sections', 'components'] as $key) {
            $singularKey = rtrim($key, 's');
            if ($key === 'cabinet_runs') $singularKey = 'cabinet_run';

            foreach ($entities[$key] ?? [] as $entity) {
                $id = $entity['id'] ?? null;
                if ($id) {
                    $byId[$id] = $entity;
                    $byType[$singularKey][] = $entity;

                    $parentId = $entity['parent_id'] ?? null;
                    if ($parentId) {
                        $byParent[$parentId][] = $entity;
                    }
                }
            }
        }

        return [
            'by_id' => $byId,
            'by_type' => $byType,
            'by_parent' => $byParent,
        ];
    }

    /**
     * Get entity by ID.
     */
    public function getEntityById(array $extractionResult, string $id): ?array
    {
        return $extractionResult['entity_index']['by_id'][$id] ?? null;
    }

    /**
     * Get children of an entity.
     */
    public function getEntityChildren(array $extractionResult, string $parentId): array
    {
        return $extractionResult['entity_index']['by_parent'][$parentId] ?? [];
    }

    /**
     * Get entities by type.
     */
    public function getEntitiesByType(array $extractionResult, string $type): array
    {
        return $extractionResult['entity_index']['by_type'][$type] ?? [];
    }

    /**
     * Create a summary suitable for logging or display.
     */
    public function summarizeExtraction(array $extractionResult): string
    {
        if (!$extractionResult['success']) {
            return "Extraction failed: " . ($extractionResult['error'] ?? 'Unknown error');
        }

        $ext = $extractionResult['extraction'];
        $summary = $ext['extraction_summary'] ?? [];
        $lines = [];

        $lines[] = "Hierarchical Entity Extraction";
        $lines[] = str_repeat('-', 40);

        // Project info
        $project = $ext['entities']['project'] ?? [];
        if (!empty($project['name'])) {
            $lines[] = "Project: {$project['name']}";
        }
        if (!empty($project['drawing_number'])) {
            $lines[] = "Drawing: {$project['drawing_number']}";
        }

        // Entity counts
        $lines[] = "";
        $lines[] = "Entities Extracted:";
        $byLevel = $summary['by_level'] ?? [];
        foreach ($byLevel as $level => $count) {
            $lines[] = "  {$level}: {$count}";
        }

        $lines[] = "";
        $lines[] = "Total: " . ($summary['total_entities'] ?? 0);
        $lines[] = "Placeholder components: " . ($summary['placeholder_components'] ?? 0);

        // Dimensions
        $lines[] = "";
        $lines[] = "Dimensions assigned: " . ($summary['dimensions_assigned'] ?? 0);
        $lines[] = "Dimensions unassigned: " . ($summary['dimensions_unassigned'] ?? 0);

        // Unassigned items
        $unassignedDims = $ext['unassigned_dimensions'] ?? [];
        if (!empty($unassignedDims)) {
            $lines[] = "";
            $lines[] = "Unassigned Dimensions:";
            foreach (array_slice($unassignedDims, 0, 5) as $dim) {
                $lines[] = "  - {$dim['value']}: {$dim['possible_assignment']}";
            }
        }

        // Confidence
        $lines[] = "";
        $lines[] = "Overall confidence: " . round(($summary['overall_confidence'] ?? 0) * 100) . "%";

        // Validation
        if (!empty($extractionResult['validation']['warnings'])) {
            $lines[] = "";
            $lines[] = "Warnings:";
            foreach (array_slice($extractionResult['validation']['warnings'], 0, 5) as $w) {
                $lines[] = "  • {$w}";
            }
        }

        return implode("\n", $lines);
    }

    /**
     * Create error response.
     */
    protected function errorResponse(string $message): array
    {
        return [
            'success' => false,
            'error' => $message,
            'extraction' => null,
            'validation' => ['is_valid' => false, 'errors' => [$message], 'warnings' => []],
            'entity_index' => [],
        ];
    }

    /**
     * Check if service is configured.
     */
    public function isConfigured(): bool
    {
        return !empty($this->geminiKey);
    }

    /**
     * Get valid values for categories.
     */
    public static function getValidValues(string $category): array
    {
        return match ($category) {
            'entity_hierarchy' => self::ENTITY_HIERARCHY,
            'entity_types' => self::ENTITY_TYPES,
            'component_placeholders' => self::COMPONENT_PLACEHOLDERS,
            default => [],
        };
    }
}
