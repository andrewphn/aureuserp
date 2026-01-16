<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Extracts component definitions after all prior steps pass.
 * This is the NINTH step in the drawing analysis pipeline.
 *
 * Purpose: Define actual component specifications with full traceability.
 * Only runs if all validation gates pass.
 */
class ComponentExtractionService
{
    protected ?string $geminiKey;
    protected string $model = 'gemini-2.0-flash';

    // Component types
    public const COMPONENT_TYPES = [
        'drawer' => 'Drawer box',
        'drawer_front' => 'Drawer face/front',
        'door' => 'Cabinet door',
        'shelf' => 'Fixed or adjustable shelf',
        'false_front' => 'False front / drawer header',
        'stretcher' => 'Stretcher / nailer',
        'panel' => 'Panel (side, back, bottom)',
        'face_frame' => 'Face frame assembly',
        'toe_kick' => 'Toe kick component',
    ];

    // Stretcher purposes
    public const STRETCHER_PURPOSES = [
        'drawer_support' => 'Supports drawer slide mounting',
        'structural' => 'Structural support / nailer',
        'face_frame_backing' => 'Backing for face frame attachment',
        'shelf_support' => 'Fixed shelf support',
    ];

    // Stretcher vertical references
    public const STRETCHER_REFERENCES = [
        'above_drawer' => 'Above drawer opening',
        'below_drawer' => 'Below drawer opening',
        'aligned_box_bottom' => 'Aligned with box bottom',
        'aligned_box_top' => 'Aligned with box top',
        'custom_position' => 'Custom vertical position',
    ];

    // Component derivation methods
    public const DERIVATION_METHODS = [
        'explicit_dimension' => 'Directly from drawing dimension',
        'calculated' => 'Calculated from stack-up',
        'constraint_applied' => 'Derived from production constraint',
        'standard_applied' => 'Standard practice applied',
    ];

    public function __construct()
    {
        $this->geminiKey = config('services.google.api_key') ?? env('GOOGLE_API_KEY') ?? env('GEMINI_API_KEY');
    }

    /**
     * Extract component definitions.
     *
     * @param string $imageBase64 Base64-encoded image data
     * @param string $mimeType Image MIME type
     * @param array $entityExtraction Result from HierarchicalEntityExtractorService
     * @param array $constraintDerivation Result from ProductionConstraintDerivationService
     * @param array $verificationResult Result from DimensionConsistencyVerifierService
     * @param array $notesExtraction Result from DrawingNotesExtractorService
     * @return array Extracted components with full traceability
     */
    public function extractComponents(
        string $imageBase64,
        string $mimeType,
        array $entityExtraction,
        array $constraintDerivation,
        array $verificationResult,
        array $notesExtraction
    ): array {
        if (empty($this->geminiKey)) {
            return $this->errorResponse('Gemini API key not configured');
        }

        // Verify all prerequisites pass
        $prerequisiteCheck = $this->checkPrerequisites(
            $entityExtraction,
            $constraintDerivation,
            $verificationResult
        );

        if (!$prerequisiteCheck['passed']) {
            return $this->errorResponse(
                'Prerequisites not met: ' . implode(', ', $prerequisiteCheck['failures'])
            );
        }

        $systemPrompt = $this->buildExtractionPrompt(
            $entityExtraction,
            $constraintDerivation,
            $verificationResult,
            $notesExtraction
        );

        try {
            $response = $this->callGeminiApi($systemPrompt, [
                'mimeType' => $mimeType,
                'data' => $imageBase64,
            ]);

            if (isset($response['error'])) {
                return $response;
            }

            return $this->parseExtractionResponse($response);

        } catch (\Exception $e) {
            Log::error('ComponentExtraction error: ' . $e->getMessage());
            return $this->errorResponse($e->getMessage());
        }
    }

    /**
     * Check that all prerequisites are met.
     */
    protected function checkPrerequisites(
        array $entityExtraction,
        array $constraintDerivation,
        array $verificationResult
    ): array {
        $failures = [];

        // Entity extraction must succeed
        if (!($entityExtraction['success'] ?? false)) {
            $failures[] = 'Entity extraction failed';
        }

        // Constraint derivation must succeed
        if (!($constraintDerivation['success'] ?? false)) {
            $failures[] = 'Constraint derivation failed';
        }

        // Dimension verification must pass
        if (!($verificationResult['success'] ?? false)) {
            $failures[] = 'Dimension verification failed';
        }

        // Must be able to proceed from verification
        if (!($verificationResult['can_proceed']['can_proceed'] ?? false)) {
            $failures[] = 'Dimension verification blocked further processing';
        }

        return [
            'passed' => empty($failures),
            'failures' => $failures,
        ];
    }

    /**
     * Build the system prompt for component extraction.
     */
    protected function buildExtractionPrompt(
        array $entityExtraction,
        array $constraintDerivation,
        array $verificationResult,
        array $notesExtraction
    ): string {
        // Build entity summary
        $entities = $entityExtraction['extraction']['entities'] ?? [];
        $cabinets = $entities['cabinets'] ?? [];
        $placeholders = $entities['components'] ?? [];

        $entitySummary = "CABINETS:\n";
        foreach ($cabinets as $cab) {
            $id = $cab['id'] ?? 'unknown';
            $name = $cab['name'] ?? 'unnamed';
            $type = $cab['type'] ?? 'unknown';
            $geom = $cab['bounding_geometry'] ?? [];
            $width = $geom['width']['value'] ?? 'N/A';
            $height = $geom['height']['value'] ?? 'N/A';
            $entitySummary .= "- {$id}: {$name} ({$type}) W:{$width} H:{$height}\n";
        }

        $entitySummary .= "\nCOMPONENT PLACEHOLDERS:\n";
        foreach ($placeholders as $ph) {
            $id = $ph['id'] ?? 'unknown';
            $type = $ph['type'] ?? 'unknown';
            $parent = $ph['parent_id'] ?? 'unknown';
            $pos = $ph['position'] ?? 'unknown';
            $entitySummary .= "- {$id}: {$type} in {$parent}, position: {$pos}\n";
        }

        // Build constraint summary
        $constraints = $constraintDerivation['constraints'] ?? [];
        $constraintSummary = "";
        foreach ($constraints as $c) {
            $id = $c['id'] ?? 'unknown';
            $type = $c['type'] ?? 'unknown';
            $value = $c['value'] ?? 'N/A';
            $unit = $c['unit'] ?? '';
            $source = $c['source'] ?? 'unknown';
            $constraintSummary .= "- {$id}: {$type} = {$value}{$unit} (source: {$source})\n";
        }

        // Build verification summary
        $verifications = $verificationResult['verification']['cabinet_verifications'] ?? [];
        $verificationSummary = "";
        foreach ($verifications as $cv) {
            $cabId = $cv['cabinet_id'] ?? 'unknown';
            $status = $cv['overall_status'] ?? 'unknown';
            $verificationSummary .= "- {$cabId}: {$status}\n";
        }

        return <<<PROMPT
You are extracting final component definitions from a cabinet drawing.

ALL PRIOR VALIDATION STEPS HAVE PASSED. You may now define actual components.

## EXTRACTED ENTITIES
{$entitySummary}

## PRODUCTION CONSTRAINTS
{$constraintSummary}

## VERIFICATION STATUS
{$verificationSummary}

## EXTRACTION RULES

For EACH component placeholder, create a full component definition including:

### 1. BASIC IDENTIFICATION
- Component ID (from placeholder)
- Component type (drawer, door, shelf, stretcher, etc.)
- Parent entity ID

### 2. REFERENCE PLANE
What is the machining/position reference for this component?
- face_frame_front
- cabinet_box_front
- cabinet_box_bottom
- etc.

### 3. GOVERNING CONSTRAINTS
Which constraints from the derivation step apply to this component?
- List constraint IDs
- Show how each constraint affects the component

### 4. DIMENSIONS (with derivation method)
For each dimension, explicitly state HOW it was derived:
- **explicit_dimension**: Direct from drawing
- **calculated**: From stack-up math
- **constraint_applied**: From production constraint
- **standard_applied**: From standard practice

### 5. ASSOCIATED NOTES
Which drawing notes apply to this component?

### SPECIAL HANDLING FOR STRETCHERS

For each stretcher, define:
- **purpose**: drawer_support, structural, face_frame_backing, shelf_support
- **vertical_reference**: above_drawer, below_drawer, aligned_box_bottom, etc.
- **vertical_position**: Exact position from reference
- **depth_offset**: Distance from drawer box front (if drawer support)
- **width_type**: full_width or partial_width
- **width_value**: Actual width if partial

Explicitly state the derivation logic:
"This stretcher is positioned [X]\" above the drawer opening because [constraint/note/calculation]"

## RESPONSE FORMAT

Respond ONLY with valid JSON:

{
  "components": [
    {
      "id": "COMP-001",
      "type": "drawer",
      "parent_id": "CAB-001",
      "parent_name": "SB36",
      "position": "2nd_from_top",

      "reference_plane": "face_frame_front",

      "dimensions": {
        "width": {
          "value": 32.25,
          "unit": "inches",
          "derivation_method": "calculated",
          "derivation_detail": "Opening width (33\") minus 2x slide clearance (0.375\" each) = 32.25\""
        },
        "height": {
          "value": 6.75,
          "unit": "inches",
          "derivation_method": "calculated",
          "derivation_detail": "Face frame opening (7\") minus 2x reveal (0.125\" each) = 6.75\""
        },
        "depth": {
          "value": 21,
          "unit": "inches",
          "derivation_method": "constraint_applied",
          "derivation_detail": "Cabinet depth (24\") minus face frame (0.75\") minus back clearance (2.25\") = 21\""
        }
      },

      "governing_constraints": [
        {
          "constraint_id": "GAP-0125",
          "applies_as": "drawer_reveal",
          "effect": "Reduces drawer front size by 0.25\" total"
        },
        {
          "constraint_id": "INF-SLI-001",
          "applies_as": "slide_clearance",
          "effect": "Reduces drawer box width by 0.75\" total"
        }
      ],

      "associated_notes": ["NOTE-002"],

      "confidence": 0.9,
      "derivation_summary": "Drawer sized from face frame opening minus reveals and clearances"
    },
    {
      "id": "STR-001",
      "type": "stretcher",
      "parent_id": "CAB-001",
      "parent_name": "SB36",

      "reference_plane": "cabinet_box_bottom",

      "stretcher_details": {
        "purpose": "drawer_support",
        "vertical_reference": "above_drawer",
        "vertical_position": {
          "value": 8.5,
          "unit": "inches",
          "from": "cabinet_box_bottom",
          "derivation": "Drawer opening bottom (7.5\") + 1\" support offset = 8.5\" from box bottom"
        },
        "depth_offset": {
          "value": 0.5,
          "unit": "inches",
          "from": "drawer_box_front",
          "derivation": "Standard 0.5\" setback to clear drawer box"
        },
        "width_type": "full_width",
        "width_value": null
      },

      "dimensions": {
        "width": {
          "value": 34.5,
          "unit": "inches",
          "derivation_method": "calculated",
          "derivation_detail": "Cabinet box interior width"
        },
        "height": {
          "value": 0.75,
          "unit": "inches",
          "derivation_method": "standard_applied",
          "derivation_detail": "Standard 3/4\" stretcher material"
        },
        "depth": {
          "value": 3.5,
          "unit": "inches",
          "derivation_method": "standard_applied",
          "derivation_detail": "Standard stretcher depth for drawer support"
        }
      },

      "governing_constraints": [
        {
          "constraint_id": "MAT-001",
          "applies_as": "material_thickness",
          "effect": "Stretcher height = 3/4\" plywood"
        }
      ],

      "associated_notes": [],

      "confidence": 0.85,
      "derivation_summary": "Full-width stretcher positioned to support drawer slide mounting, set back 0.5\" from drawer box path"
    }
  ],

  "extraction_summary": {
    "total_components": 12,
    "by_type": {
      "drawer": 4,
      "door": 2,
      "stretcher": 3,
      "shelf": 2,
      "false_front": 1
    },
    "derivation_breakdown": {
      "explicit_dimension": 5,
      "calculated": 20,
      "constraint_applied": 8,
      "standard_applied": 3
    },
    "average_confidence": 0.87
  },

  "unresolved_items": [
    {
      "placeholder_id": "COMP-008",
      "reason": "Insufficient dimension data to size shelf",
      "recommendation": "Verify shelf position with field measurement"
    }
  ]
}
PROMPT;
    }

    /**
     * Call the Gemini API for component extraction.
     */
    protected function callGeminiApi(string $systemPrompt, array $image): array
    {
        $contents = [
            [
                'role' => 'user',
                'parts' => [
                    ['text' => 'Extract full component definitions from this drawing. All validation steps have passed. Provide complete dimensions with derivation traceability.'],
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
                'temperature' => 0.1, // Very low for accurate calculations
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
    protected function parseExtractionResponse(array $response): array
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

        // Index components for easy lookup
        $componentIndex = $this->buildComponentIndex($decoded);

        return [
            'success' => true,
            'extraction' => $decoded,
            'validation' => $validation,
            'component_index' => $componentIndex,
        ];
    }

    /**
     * Validate the component extraction.
     */
    protected function validateExtraction(array $extraction): array
    {
        $errors = [];
        $warnings = [];

        $components = $extraction['components'] ?? [];

        if (empty($components)) {
            $warnings[] = 'No components extracted';
        }

        foreach ($components as $comp) {
            $id = $comp['id'] ?? 'unknown';

            // Check required fields
            if (!isset($comp['type'])) {
                $errors[] = "{$id}: Missing component type";
            }
            if (!isset($comp['parent_id'])) {
                $errors[] = "{$id}: Missing parent ID";
            }
            if (!isset($comp['reference_plane'])) {
                $warnings[] = "{$id}: Missing reference plane";
            }

            // Check dimensions have derivation
            $dims = $comp['dimensions'] ?? [];
            foreach ($dims as $dimName => $dim) {
                if (!isset($dim['derivation_method'])) {
                    $warnings[] = "{$id}: {$dimName} missing derivation method";
                }
                if (!isset($dim['derivation_detail'])) {
                    $warnings[] = "{$id}: {$dimName} missing derivation detail";
                }
            }

            // For stretchers, check special fields
            if (($comp['type'] ?? '') === 'stretcher') {
                $details = $comp['stretcher_details'] ?? [];
                if (!isset($details['purpose'])) {
                    $warnings[] = "{$id}: Stretcher missing purpose";
                }
                if (!isset($details['vertical_reference'])) {
                    $warnings[] = "{$id}: Stretcher missing vertical reference";
                }
            }

            // Check confidence
            $confidence = $comp['confidence'] ?? 0;
            if ($confidence < 0.7) {
                $warnings[] = "{$id}: Low confidence ({$confidence})";
            }
        }

        // Check for unresolved items
        $unresolved = $extraction['unresolved_items'] ?? [];
        foreach ($unresolved as $item) {
            $warnings[] = "Unresolved: {$item['placeholder_id']} - {$item['reason']}";
        }

        return [
            'is_valid' => empty($errors),
            'errors' => $errors,
            'warnings' => $warnings,
        ];
    }

    /**
     * Build component index for easy lookup.
     */
    protected function buildComponentIndex(array $extraction): array
    {
        $byId = [];
        $byType = [];
        $byParent = [];

        foreach ($extraction['components'] ?? [] as $comp) {
            $id = $comp['id'] ?? null;
            $type = $comp['type'] ?? 'unknown';
            $parent = $comp['parent_id'] ?? null;

            if ($id) {
                $byId[$id] = $comp;
                $byType[$type][] = $comp;
                if ($parent) {
                    $byParent[$parent][] = $comp;
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
     * Get components by type.
     */
    public function getComponentsByType(array $extractionResult, string $type): array
    {
        if (!$extractionResult['success']) {
            return [];
        }

        return $extractionResult['component_index']['by_type'][$type] ?? [];
    }

    /**
     * Get components for a cabinet.
     */
    public function getComponentsForCabinet(array $extractionResult, string $cabinetId): array
    {
        if (!$extractionResult['success']) {
            return [];
        }

        return $extractionResult['component_index']['by_parent'][$cabinetId] ?? [];
    }

    /**
     * Get stretchers with full details.
     */
    public function getStretchers(array $extractionResult): array
    {
        return $this->getComponentsByType($extractionResult, 'stretcher');
    }

    /**
     * Create a summary suitable for logging or display.
     */
    public function summarizeExtraction(array $extractionResult): string
    {
        if (!$extractionResult['success']) {
            return "Component extraction failed: " . ($extractionResult['error'] ?? 'Unknown error');
        }

        $ext = $extractionResult['extraction'];
        $summary = $ext['extraction_summary'] ?? [];
        $lines = [];

        $lines[] = "Component Extraction Results";
        $lines[] = str_repeat('-', 40);

        // Summary stats
        $lines[] = "Total components: " . ($summary['total_components'] ?? 0);

        // By type
        $lines[] = "";
        $lines[] = "By Type:";
        foreach ($summary['by_type'] ?? [] as $type => $count) {
            $lines[] = "  {$type}: {$count}";
        }

        // Derivation breakdown
        $lines[] = "";
        $lines[] = "Derivation Methods:";
        foreach ($summary['derivation_breakdown'] ?? [] as $method => $count) {
            $lines[] = "  {$method}: {$count}";
        }

        // Average confidence
        $lines[] = "";
        $lines[] = "Average confidence: " . round(($summary['average_confidence'] ?? 0) * 100) . "%";

        // Unresolved items
        $unresolved = $ext['unresolved_items'] ?? [];
        if (!empty($unresolved)) {
            $lines[] = "";
            $lines[] = "⚠ UNRESOLVED ITEMS:";
            foreach ($unresolved as $item) {
                $lines[] = "  • {$item['placeholder_id']}: {$item['reason']}";
            }
        }

        // Validation warnings
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
            'component_index' => [],
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
            'component_types' => self::COMPONENT_TYPES,
            'stretcher_purposes' => self::STRETCHER_PURPOSES,
            'stretcher_references' => self::STRETCHER_REFERENCES,
            'derivation_methods' => self::DERIVATION_METHODS,
            default => [],
        };
    }
}
