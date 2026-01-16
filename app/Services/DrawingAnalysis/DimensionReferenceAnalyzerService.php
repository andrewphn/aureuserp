<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Analyzes how dimensions are referenced in construction drawings.
 * This is the SECOND step in the drawing analysis pipeline.
 *
 * Purpose: Understand WHERE dimensions are measured from before extracting values.
 * Does NOT reconcile values or assume standard gaps.
 */
class DimensionReferenceAnalyzerService
{
    protected ?string $geminiKey;
    protected string $model = 'gemini-2.0-flash';

    // Reference types
    public const REFERENCE_TYPES = [
        'physical' => 'Physical surface (cabinet edge, floor, panel)',
        'visual' => 'Visual plane (face frame front, door face)',
        'implied' => 'Implied surface (countertop, finish surface)',
        'centerline' => 'Centerline reference',
        'unknown' => 'Reference point cannot be determined',
    ];

    // Physical reference points
    public const PHYSICAL_REFERENCES = [
        'finished_floor' => 'Finished floor line (FFL)',
        'subfloor' => 'Subfloor/rough floor',
        'cabinet_box_bottom' => 'Bottom of cabinet box',
        'cabinet_box_top' => 'Top of cabinet box',
        'cabinet_box_side' => 'Side panel of cabinet box',
        'cabinet_box_back' => 'Back panel of cabinet box',
        'toe_kick_bottom' => 'Bottom of toe kick',
        'toe_kick_top' => 'Top of toe kick / bottom of cabinet',
        'shelf_surface' => 'Shelf surface',
        'drawer_bottom' => 'Drawer box bottom',
        'wall_surface' => 'Wall surface',
        'ceiling' => 'Ceiling',
    ];

    // Visual reference points (front-facing)
    public const VISUAL_REFERENCES = [
        'face_frame_front' => 'Front surface of face frame',
        'face_frame_edge' => 'Edge of face frame member',
        'door_face' => 'Front surface of door',
        'drawer_face' => 'Front surface of drawer front',
        'stile_edge' => 'Edge of face frame stile',
        'rail_edge' => 'Edge of face frame rail',
        'reveal_line' => 'Reveal/inset line',
    ];

    // Implied reference points (often not explicitly labeled)
    public const IMPLIED_REFERENCES = [
        'countertop_surface' => 'Top of countertop (may include thickness)',
        'backsplash_top' => 'Top of backsplash',
        'appliance_opening' => 'Appliance rough opening edge',
        'finish_surface' => 'Finish material surface',
        'hardware_centerline' => 'Hardware mounting centerline',
        'hinge_centerline' => 'Hinge center point',
    ];

    // Dimension orientations
    public const ORIENTATIONS = [
        'horizontal' => 'Left-right measurement',
        'vertical' => 'Top-bottom measurement',
        'diagonal' => 'Angled measurement',
        'depth' => 'Front-back measurement',
    ];

    // Flag categories for problematic dimensions
    public const FLAG_CATEGORIES = [
        'mixed_references' => 'Dimension mixes front and rear reference planes',
        'implicit_thickness' => 'Includes material thickness implicitly',
        'unlabeled_surface' => 'References implied surface without label',
        'conflict' => 'Conflicts with another dimension',
        'ambiguous_start' => 'Start reference point is ambiguous',
        'ambiguous_end' => 'End reference point is ambiguous',
        'crossing_planes' => 'Dimension crosses multiple reference planes',
        'assumed_gap' => 'Appears to assume a standard gap',
    ];

    public function __construct()
    {
        $this->geminiKey = config('services.google.api_key') ?? env('GOOGLE_API_KEY') ?? env('GEMINI_API_KEY');
    }

    /**
     * Analyze dimension references in a drawing.
     *
     * @param string $imageBase64 Base64-encoded image data
     * @param string $mimeType Image MIME type
     * @param array|null $drawingContext Optional context from DrawingContextAnalyzerService
     * @return array Analysis result with dimension reference data
     */
    public function analyzeDimensionReferences(
        string $imageBase64,
        string $mimeType,
        ?array $drawingContext = null
    ): array {
        if (empty($this->geminiKey)) {
            return $this->errorResponse('Gemini API key not configured');
        }

        $systemPrompt = $this->buildReferenceAnalysisPrompt($drawingContext);

        try {
            $response = $this->callGeminiApi($systemPrompt, [
                'mimeType' => $mimeType,
                'data' => $imageBase64,
            ]);

            if (isset($response['error'])) {
                return $response;
            }

            return $this->parseReferenceResponse($response);

        } catch (\Exception $e) {
            Log::error('DimensionReferenceAnalyzer error: ' . $e->getMessage());
            return $this->errorResponse($e->getMessage());
        }
    }

    /**
     * Build the system prompt for dimension reference analysis.
     */
    protected function buildReferenceAnalysisPrompt(?array $drawingContext): string
    {
        $contextInfo = '';
        if ($drawingContext) {
            $viewType = $drawingContext['view_type']['primary'] ?? 'unknown';
            $orientation = $drawingContext['orientation']['primary'] ?? 'unknown';
            $units = $drawingContext['unit_system']['primary'] ?? 'unknown';
            $baselines = implode(', ', $drawingContext['baselines']['identified'] ?? ['unknown']);

            $contextInfo = <<<CONTEXT

## DRAWING CONTEXT (from prior analysis)
- View Type: {$viewType}
- Orientation: {$orientation}
- Unit System: {$units}
- Known Baselines: {$baselines}

Use this context to inform your reference analysis.
CONTEXT;
        }

        return <<<PROMPT
You are analyzing dimension references in a cabinet construction drawing.

IMPORTANT:
- Do NOT reconcile dimension values yet
- Do NOT assume standard gaps or offsets
- Do NOT convert or calculate - report exactly what is shown
- Your task is to understand WHERE each dimension is measured FROM and TO
{$contextInfo}

## ANALYSIS INSTRUCTIONS

For EACH visible dimension in the drawing, identify:

### 1. DIMENSION VALUE
- Report the value exactly as written (e.g., "24-1/2\"", "600mm", "2'-6\"")
- Include any tolerance if shown (e.g., "±1/16\"")
- Note if value is unclear or partially obscured

### 2. ORIENTATION
- **horizontal**: Left-to-right measurement (width)
- **vertical**: Top-to-bottom measurement (height)
- **diagonal**: Angled measurement
- **depth**: Front-to-back measurement (if visible in section)

### 3. START REFERENCE
Where does this dimension BEGIN?
Classify as:
- **physical**: Actual material edge (cabinet box, floor, panel)
- **visual**: Front-facing surface (face frame, door face)
- **implied**: Assumed surface (countertop, finish)
- **centerline**: Center point reference
- **unknown**: Cannot determine

Identify the SPECIFIC reference point from these options:

Physical: finished_floor, subfloor, cabinet_box_bottom, cabinet_box_top,
         cabinet_box_side, cabinet_box_back, toe_kick_bottom, toe_kick_top,
         shelf_surface, drawer_bottom, wall_surface, ceiling

Visual: face_frame_front, face_frame_edge, door_face, drawer_face,
        stile_edge, rail_edge, reveal_line

Implied: countertop_surface, backsplash_top, appliance_opening,
         finish_surface, hardware_centerline, hinge_centerline

### 4. END REFERENCE
Where does this dimension END?
Use the same classification and identification as start reference.

### 5. FLAGS
Flag the dimension if ANY of these conditions apply:

- **mixed_references**: Dimension starts at front plane, ends at rear plane (or vice versa)
- **implicit_thickness**: Value likely includes countertop or material thickness without showing it
- **unlabeled_surface**: References an implied surface that isn't explicitly labeled
- **conflict**: This dimension contradicts another dimension in the drawing
- **ambiguous_start**: Start point could be interpreted multiple ways
- **ambiguous_end**: End point could be interpreted multiple ways
- **crossing_planes**: Dimension line crosses from one reference plane to another
- **assumed_gap**: Value appears to assume a standard gap (1/8", reveal, etc.)

### 6. CONFIDENCE
Rate your confidence in this dimension's interpretation (0.0 to 1.0)

## RESPONSE FORMAT

Respond ONLY with valid JSON (no markdown, no explanation):

{
  "dimensions": [
    {
      "id": "DIM-001",
      "value": {
        "as_written": "32-3/4\"",
        "numeric": 32.75,
        "unit": "inches",
        "tolerance": null,
        "is_clear": true
      },
      "orientation": "vertical",
      "start_reference": {
        "type": "physical",
        "point": "finished_floor",
        "explicit": true,
        "notes": "FFL line clearly marked"
      },
      "end_reference": {
        "type": "physical",
        "point": "cabinet_box_top",
        "explicit": false,
        "notes": "Appears to end at top of cabinet box, not countertop"
      },
      "flags": [],
      "confidence": 0.9,
      "location_in_drawing": "left side, main elevation",
      "notes": null
    },
    {
      "id": "DIM-002",
      "value": {
        "as_written": "36\"",
        "numeric": 36.0,
        "unit": "inches",
        "tolerance": null,
        "is_clear": true
      },
      "orientation": "vertical",
      "start_reference": {
        "type": "physical",
        "point": "finished_floor",
        "explicit": true,
        "notes": "FFL marked"
      },
      "end_reference": {
        "type": "implied",
        "point": "countertop_surface",
        "explicit": false,
        "notes": "Dimension line ends at countertop but doesn't specify if surface or substrate"
      },
      "flags": [
        {
          "category": "implicit_thickness",
          "description": "36\" likely includes countertop thickness but this is not explicit",
          "severity": "warning"
        },
        {
          "category": "unlabeled_surface",
          "description": "Countertop surface referenced but not labeled",
          "severity": "warning"
        }
      ],
      "confidence": 0.7,
      "location_in_drawing": "right side, main elevation",
      "notes": "Common countertop height but reference is ambiguous"
    }
  ],
  "reference_planes_identified": [
    {
      "name": "finished_floor",
      "type": "physical",
      "clearly_marked": true,
      "used_by_dimensions": ["DIM-001", "DIM-002"]
    },
    {
      "name": "face_frame_front",
      "type": "visual",
      "clearly_marked": false,
      "used_by_dimensions": []
    }
  ],
  "potential_conflicts": [
    {
      "dimensions": ["DIM-001", "DIM-002"],
      "description": "32-3/4\" to cabinet top vs 36\" to counter - difference should be countertop thickness",
      "resolution_needed": true
    }
  ],
  "summary": {
    "total_dimensions": 2,
    "flagged_dimensions": 1,
    "high_confidence_count": 1,
    "low_confidence_count": 1,
    "primary_reference_plane": "finished_floor",
    "reference_consistency": "mixed"
  },
  "analysis_notes": "Drawing shows two height dimensions with different end references. Verify countertop thickness before proceeding.",
  "recommendations": [
    "Clarify if 36\" includes countertop thickness",
    "Verify cabinet box height is 32-3/4\" from FFL"
  ]
}
PROMPT;
    }

    /**
     * Call the Gemini API for reference analysis.
     */
    protected function callGeminiApi(string $systemPrompt, array $image): array
    {
        $contents = [
            [
                'role' => 'user',
                'parts' => [
                    ['text' => 'Analyze how dimensions are referenced in this drawing. Identify start and end references for each dimension. Do not reconcile values or assume standard gaps.'],
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
                'maxOutputTokens' => 4096, // Larger for detailed dimension analysis
            ],
        ];

        $maxRetries = 3;
        for ($attempt = 1; $attempt <= $maxRetries; $attempt++) {
            try {
                $response = Http::timeout(90)->withHeaders([
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
     * Parse and validate the reference analysis response.
     */
    protected function parseReferenceResponse(array $response): array
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

        // Validate and enhance the response
        $validation = $this->validateReferenceAnalysis($decoded);
        $riskAssessment = $this->assessDimensionRisks($decoded);

        return [
            'success' => true,
            'references' => $decoded,
            'validation' => $validation,
            'risk_assessment' => $riskAssessment,
        ];
    }

    /**
     * Validate the reference analysis against expected structure.
     */
    protected function validateReferenceAnalysis(array $analysis): array
    {
        $errors = [];
        $warnings = [];

        // Check for dimensions array
        if (!isset($analysis['dimensions']) || !is_array($analysis['dimensions'])) {
            $errors[] = "Missing or invalid 'dimensions' array";
            return ['is_valid' => false, 'errors' => $errors, 'warnings' => $warnings];
        }

        // Validate each dimension
        foreach ($analysis['dimensions'] as $index => $dim) {
            $dimId = $dim['id'] ?? "DIM-{$index}";

            // Check required fields
            if (!isset($dim['value']['as_written'])) {
                $warnings[] = "{$dimId}: Missing dimension value";
            }

            if (!isset($dim['orientation'])) {
                $warnings[] = "{$dimId}: Missing orientation";
            } elseif (!array_key_exists($dim['orientation'], self::ORIENTATIONS)) {
                $warnings[] = "{$dimId}: Unknown orientation '{$dim['orientation']}'";
            }

            // Validate start reference
            if (!isset($dim['start_reference']['type'])) {
                $warnings[] = "{$dimId}: Missing start reference type";
            } elseif (!array_key_exists($dim['start_reference']['type'], self::REFERENCE_TYPES)) {
                $warnings[] = "{$dimId}: Unknown start reference type '{$dim['start_reference']['type']}'";
            }

            // Validate end reference
            if (!isset($dim['end_reference']['type'])) {
                $warnings[] = "{$dimId}: Missing end reference type";
            } elseif (!array_key_exists($dim['end_reference']['type'], self::REFERENCE_TYPES)) {
                $warnings[] = "{$dimId}: Unknown end reference type '{$dim['end_reference']['type']}'";
            }

            // Check for unknown references
            if (($dim['start_reference']['type'] ?? '') === 'unknown') {
                $warnings[] = "{$dimId}: Start reference could not be determined";
            }
            if (($dim['end_reference']['type'] ?? '') === 'unknown') {
                $warnings[] = "{$dimId}: End reference could not be determined";
            }

            // Validate flags
            foreach ($dim['flags'] ?? [] as $flag) {
                $category = $flag['category'] ?? '';
                if (!empty($category) && !array_key_exists($category, self::FLAG_CATEGORIES)) {
                    $warnings[] = "{$dimId}: Unknown flag category '{$category}'";
                }
            }

            // Check confidence
            $confidence = $dim['confidence'] ?? 0;
            if ($confidence < 0.5) {
                $warnings[] = "{$dimId}: Low confidence ({$confidence}) - manual review recommended";
            }
        }

        return [
            'is_valid' => empty($errors),
            'errors' => $errors,
            'warnings' => $warnings,
        ];
    }

    /**
     * Assess risks in the dimension references for construction accuracy.
     */
    protected function assessDimensionRisks(array $analysis): array
    {
        $risks = [];
        $dimensions = $analysis['dimensions'] ?? [];

        // Count dimensions by risk category
        $mixedReferenceCount = 0;
        $implicitThicknessCount = 0;
        $ambiguousCount = 0;
        $conflictCount = 0;

        foreach ($dimensions as $dim) {
            $flags = $dim['flags'] ?? [];
            foreach ($flags as $flag) {
                $category = $flag['category'] ?? '';
                switch ($category) {
                    case 'mixed_references':
                    case 'crossing_planes':
                        $mixedReferenceCount++;
                        break;
                    case 'implicit_thickness':
                    case 'assumed_gap':
                        $implicitThicknessCount++;
                        break;
                    case 'ambiguous_start':
                    case 'ambiguous_end':
                    case 'unlabeled_surface':
                        $ambiguousCount++;
                        break;
                    case 'conflict':
                        $conflictCount++;
                        break;
                }
            }
        }

        // Assess overall risk level
        $totalFlags = $mixedReferenceCount + $implicitThicknessCount + $ambiguousCount + $conflictCount;
        $dimensionCount = count($dimensions);

        if ($dimensionCount === 0) {
            $riskLevel = 'unknown';
            $risks[] = [
                'level' => 'high',
                'category' => 'no_dimensions',
                'description' => 'No dimensions found in drawing',
            ];
        } elseif ($conflictCount > 0) {
            $riskLevel = 'high';
            $risks[] = [
                'level' => 'high',
                'category' => 'conflicts',
                'description' => "{$conflictCount} dimension conflict(s) detected - manual resolution required",
            ];
        } elseif ($mixedReferenceCount > 2 || ($totalFlags / $dimensionCount) > 0.5) {
            $riskLevel = 'high';
        } elseif ($implicitThicknessCount > 0 || $ambiguousCount > 2) {
            $riskLevel = 'medium';
        } else {
            $riskLevel = 'low';
        }

        // Add specific risks
        if ($mixedReferenceCount > 0) {
            $risks[] = [
                'level' => $mixedReferenceCount > 2 ? 'high' : 'medium',
                'category' => 'mixed_references',
                'description' => "{$mixedReferenceCount} dimension(s) mix front and rear reference planes",
                'recommendation' => 'Verify which plane each dimension references before extraction',
            ];
        }

        if ($implicitThicknessCount > 0) {
            $risks[] = [
                'level' => 'medium',
                'category' => 'implicit_values',
                'description' => "{$implicitThicknessCount} dimension(s) may include implicit thicknesses or gaps",
                'recommendation' => 'Confirm countertop thickness and material allowances',
            ];
        }

        if ($ambiguousCount > 0) {
            $risks[] = [
                'level' => $ambiguousCount > 3 ? 'high' : 'medium',
                'category' => 'ambiguous_references',
                'description' => "{$ambiguousCount} dimension(s) have ambiguous reference points",
                'recommendation' => 'Request clarification or review original drawing source',
            ];
        }

        // Check reference plane consistency
        $referencePlanes = $analysis['reference_planes_identified'] ?? [];
        $unmarkedPlanes = array_filter($referencePlanes, fn($p) => !($p['clearly_marked'] ?? false));
        if (count($unmarkedPlanes) > 0) {
            $unmarkedNames = array_column($unmarkedPlanes, 'name');
            $risks[] = [
                'level' => 'low',
                'category' => 'unmarked_planes',
                'description' => 'Some reference planes are not clearly marked: ' . implode(', ', $unmarkedNames),
                'recommendation' => 'Verify assumed reference planes before construction',
            ];
        }

        return [
            'overall_risk_level' => $riskLevel,
            'risks' => $risks,
            'statistics' => [
                'total_dimensions' => $dimensionCount,
                'flagged_dimensions' => $totalFlags,
                'mixed_reference_count' => $mixedReferenceCount,
                'implicit_thickness_count' => $implicitThicknessCount,
                'ambiguous_count' => $ambiguousCount,
                'conflict_count' => $conflictCount,
            ],
            'proceed_with_extraction' => $riskLevel !== 'high' || $conflictCount === 0,
        ];
    }

    /**
     * Get dimensions grouped by their reference type.
     */
    public function groupDimensionsByReference(array $analysisResult): array
    {
        if (!$analysisResult['success']) {
            return [];
        }

        $dimensions = $analysisResult['references']['dimensions'] ?? [];
        $grouped = [
            'physical_to_physical' => [],
            'physical_to_visual' => [],
            'physical_to_implied' => [],
            'visual_to_visual' => [],
            'other' => [],
        ];

        foreach ($dimensions as $dim) {
            $startType = $dim['start_reference']['type'] ?? 'unknown';
            $endType = $dim['end_reference']['type'] ?? 'unknown';

            $key = "{$startType}_to_{$endType}";
            if (isset($grouped[$key])) {
                $grouped[$key][] = $dim;
            } else {
                $grouped['other'][] = $dim;
            }
        }

        return $grouped;
    }

    /**
     * Create a summary suitable for logging or display.
     */
    public function summarizeReferences(array $analysisResult): string
    {
        if (!$analysisResult['success']) {
            return "Analysis failed: " . ($analysisResult['error'] ?? 'Unknown error');
        }

        $refs = $analysisResult['references'];
        $risk = $analysisResult['risk_assessment'];
        $lines = [];

        $lines[] = "Dimension Reference Analysis";
        $lines[] = str_repeat('-', 40);

        // Summary stats
        $summary = $refs['summary'] ?? [];
        $total = $summary['total_dimensions'] ?? 0;
        $flagged = $summary['flagged_dimensions'] ?? 0;
        $lines[] = "Dimensions found: {$total}";
        $lines[] = "Flagged dimensions: {$flagged}";
        $lines[] = "Primary reference: " . ($summary['primary_reference_plane'] ?? 'unknown');
        $lines[] = "Reference consistency: " . ($summary['reference_consistency'] ?? 'unknown');

        // Risk assessment
        $lines[] = "";
        $lines[] = "Risk Level: " . strtoupper($risk['overall_risk_level'] ?? 'unknown');

        if (!empty($risk['risks'])) {
            $lines[] = "";
            $lines[] = "Identified Risks:";
            foreach ($risk['risks'] as $r) {
                $level = strtoupper($r['level'] ?? 'unknown');
                $lines[] = "  [{$level}] {$r['description']}";
                if (!empty($r['recommendation'])) {
                    $lines[] = "    → {$r['recommendation']}";
                }
            }
        }

        // Potential conflicts
        $conflicts = $refs['potential_conflicts'] ?? [];
        if (!empty($conflicts)) {
            $lines[] = "";
            $lines[] = "Potential Conflicts:";
            foreach ($conflicts as $c) {
                $dims = implode(' vs ', $c['dimensions'] ?? []);
                $lines[] = "  • {$dims}: {$c['description']}";
            }
        }

        // Recommendations
        $recommendations = $refs['recommendations'] ?? [];
        if (!empty($recommendations)) {
            $lines[] = "";
            $lines[] = "Recommendations:";
            foreach ($recommendations as $rec) {
                $lines[] = "  • {$rec}";
            }
        }

        // Extraction readiness
        $lines[] = "";
        $canProceed = $risk['proceed_with_extraction'] ?? false;
        $lines[] = "Ready for extraction: " . ($canProceed ? 'Yes' : 'No - resolve issues first');

        return implode("\n", $lines);
    }

    /**
     * Get flagged dimensions only.
     */
    public function getFlaggedDimensions(array $analysisResult): array
    {
        if (!$analysisResult['success']) {
            return [];
        }

        $dimensions = $analysisResult['references']['dimensions'] ?? [];

        return array_filter($dimensions, function ($dim) {
            return !empty($dim['flags']);
        });
    }

    /**
     * Create error response.
     */
    protected function errorResponse(string $message): array
    {
        return [
            'success' => false,
            'error' => $message,
            'references' => null,
            'validation' => ['is_valid' => false, 'errors' => [$message], 'warnings' => []],
            'risk_assessment' => [
                'overall_risk_level' => 'unknown',
                'risks' => [['level' => 'high', 'category' => 'error', 'description' => $message]],
                'proceed_with_extraction' => false,
            ],
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
     * Get valid values for a given category.
     */
    public static function getValidValues(string $category): array
    {
        return match ($category) {
            'reference_types' => self::REFERENCE_TYPES,
            'physical_references' => self::PHYSICAL_REFERENCES,
            'visual_references' => self::VISUAL_REFERENCES,
            'implied_references' => self::IMPLIED_REFERENCES,
            'orientations' => self::ORIENTATIONS,
            'flag_categories' => self::FLAG_CATEGORIES,
            default => [],
        };
    }
}
