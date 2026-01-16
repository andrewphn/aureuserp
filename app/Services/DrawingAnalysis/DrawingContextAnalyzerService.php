<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Analyzes construction drawing context without extracting cabinet dimensions.
 * This is the first step in a multi-stage drawing analysis pipeline.
 *
 * Purpose: Understand WHAT the drawing shows before trying to extract data from it.
 */
class DrawingContextAnalyzerService
{
    protected ?string $geminiKey;
    protected string $model = 'gemini-2.0-flash';

    // Valid view types
    public const VIEW_TYPES = [
        'elevation' => 'Elevation view (front, side, or rear face of cabinets)',
        'plan' => 'Plan/top-down view showing layout',
        'section' => 'Cross-section cut through cabinet construction',
        'detail' => 'Enlarged detail of specific construction element',
        'composite' => 'Multiple views combined on one sheet',
        'isometric' => 'Three-dimensional isometric projection',
        'perspective' => 'Perspective rendering or 3D view',
    ];

    // Valid orientations
    public const ORIENTATIONS = [
        'front' => 'Front elevation (face frame visible)',
        'rear' => 'Rear view (back panel visible)',
        'left' => 'Left side view',
        'right' => 'Right side view',
        'top' => 'Top/plan view',
        'bottom' => 'Bottom view (underside)',
        'unknown' => 'Orientation cannot be determined',
    ];

    // Drawing intent categories
    public const DRAWING_INTENTS = [
        'conceptual' => 'Early design/concept sketch',
        'presentation' => 'Client presentation drawing',
        'production' => 'Shop/production drawing with full detail',
        'verification' => 'As-built or verification drawing',
        'unknown' => 'Intent cannot be determined',
    ];

    // Unit systems
    public const UNIT_SYSTEMS = [
        'inches' => 'Imperial inches',
        'feet_inches' => 'Feet and inches (e.g., 2\'-6")',
        'millimeters' => 'Metric millimeters',
        'centimeters' => 'Metric centimeters',
        'mixed' => 'Multiple unit systems present',
        'not_shown' => 'No dimensions visible',
    ];

    // Baseline references (critical for TCS construction)
    public const BASELINES = [
        'finished_floor' => 'Finished Floor Line (FFL)',
        'bottom_of_cabinet' => 'Bottom of cabinet box',
        'top_of_cabinet' => 'Top of cabinet box',
        'top_of_countertop' => 'Top of countertop surface',
        'face_frame_front' => 'Front plane of face frame',
        'back_of_cabinet' => 'Back panel plane',
        'centerline' => 'Cabinet centerline',
        'unknown' => 'Baseline not indicated',
    ];

    public function __construct()
    {
        $this->geminiKey = config('services.google.api_key') ?? env('GOOGLE_API_KEY') ?? env('GEMINI_API_KEY');
    }

    /**
     * Analyze a drawing to understand its context before extracting dimensions.
     *
     * @param string $imageBase64 Base64-encoded image data
     * @param string $mimeType Image MIME type (image/png, image/jpeg, application/pdf)
     * @return array Analysis result with structured context data
     */
    public function analyzeDrawingContext(string $imageBase64, string $mimeType): array
    {
        if (empty($this->geminiKey)) {
            return $this->errorResponse('Gemini API key not configured');
        }

        $systemPrompt = $this->buildContextAnalysisPrompt();

        try {
            $response = $this->callGeminiApi($systemPrompt, [
                'mimeType' => $mimeType,
                'data' => $imageBase64,
            ]);

            if (isset($response['error'])) {
                return $response;
            }

            return $this->parseContextResponse($response);

        } catch (\Exception $e) {
            Log::error('DrawingContextAnalyzer error: ' . $e->getMessage());
            return $this->errorResponse($e->getMessage());
        }
    }

    /**
     * Build the system prompt for drawing context analysis.
     */
    protected function buildContextAnalysisPrompt(): string
    {
        return <<<'PROMPT'
You are analyzing a construction drawing for custom cabinetry.

IMPORTANT:
Do NOT extract cabinet dimensions or components yet.
Your task is to understand the DRAWING CONTEXT only.

For the provided drawing (PDF, image, or rendered DWG):

## 1. VIEW TYPE
Identify the primary view type:
- **elevation**: Front, side, or rear face view
- **plan**: Top-down/bird's eye view
- **section**: Cut-through showing internal construction
- **detail**: Enlarged view of specific element
- **composite**: Multiple views on one sheet
- **isometric**: 3D isometric projection
- **perspective**: Rendered perspective view

If composite, list all view types present.

## 2. ORIENTATION
For elevation views, identify the orientation:
- **front**: Face frame/doors visible
- **rear**: Back panel visible
- **left**: Left side panel visible
- **right**: Right side panel visible
- **top**: Looking down at cabinet top
- **bottom**: Looking up at cabinet bottom
- **unknown**: Cannot determine (flag this clearly)

## 3. DRAWING INTENT
What is this drawing for?
- **conceptual**: Early design exploration
- **presentation**: Client approval drawing
- **production**: Shop drawing for fabrication (most detailed)
- **verification**: As-built or measurement verification
- **unknown**: Cannot determine

## 4. UNIT SYSTEM
Identify the units used for dimensions:
- **inches**: Decimal or fractional inches (e.g., 24.5", 24-1/2")
- **feet_inches**: Feet and inches (e.g., 2'-6")
- **millimeters**: Metric mm (e.g., 600mm)
- **centimeters**: Metric cm
- **mixed**: Multiple systems present (flag which ones)
- **not_shown**: No dimensions visible

## 5. SCALE
If a scale is indicated:
- Extract the exact scale (e.g., "1/2\" = 1'-0\"", "1:10", "FULL SIZE")
- If "NTS" or "Not to Scale" is shown, report that
- If no scale indicated, report "not_shown"

## 6. BASELINES (Critical for Construction)
Identify any reference baselines shown or implied:
- **finished_floor**: FFL or floor line marked
- **bottom_of_cabinet**: Dimension starts from cabinet bottom
- **top_of_cabinet**: Dimension starts from cabinet top
- **top_of_countertop**: Counter surface as reference
- **face_frame_front**: Front plane of face frame
- **back_of_cabinet**: Back panel as reference
- **centerline**: Cabinet centerline marked
- **unknown**: No clear baseline indicated

List ALL baselines that appear to be used.

## 7. ADDITIONAL CONTEXT
Note any other relevant context:
- Drawing number or title block info
- Revision indicators
- Material callouts visible (don't extract, just note presence)
- Hardware callouts visible
- Notes or specifications present
- Quality of drawing (clean CAD vs. sketch vs. photo)

## Response Format

Respond ONLY with valid JSON (no markdown code blocks, no explanation):

{
  "view_type": {
    "primary": "elevation",
    "secondary": null,
    "all_views": ["elevation"],
    "confidence": 0.95
  },
  "orientation": {
    "primary": "front",
    "confidence": 0.9,
    "reasoning": "Face frame members and door openings are visible"
  },
  "drawing_intent": {
    "type": "production",
    "confidence": 0.85,
    "indicators": ["Detailed dimensions", "Material callouts", "Construction notes"]
  },
  "unit_system": {
    "primary": "inches",
    "secondary": null,
    "format": "fractional",
    "confidence": 0.95
  },
  "scale": {
    "indicated": true,
    "value": "1/2\" = 1'-0\"",
    "is_to_scale": true
  },
  "baselines": {
    "identified": ["finished_floor", "face_frame_front"],
    "primary": "finished_floor",
    "confidence": 0.8,
    "notes": "FFL marked at bottom of drawing"
  },
  "additional_context": {
    "has_title_block": true,
    "has_revision_info": false,
    "has_material_callouts": true,
    "has_hardware_callouts": true,
    "has_notes": true,
    "drawing_quality": "clean_cad",
    "notes": "Production-quality CAD drawing with full annotations"
  },
  "analysis_confidence": 0.88,
  "warnings": [],
  "recommendations": [
    "Proceed with dimension extraction",
    "Use finished floor as height reference"
  ]
}
PROMPT;
    }

    /**
     * Call the Gemini API for context analysis.
     */
    protected function callGeminiApi(string $systemPrompt, array $image): array
    {
        $contents = [
            [
                'role' => 'user',
                'parts' => [
                    ['text' => 'Analyze this construction drawing and identify its context. Do not extract dimensions yet.'],
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
                'temperature' => 0.2, // Low temperature for consistent structured output
                'topP' => 0.8,
                'maxOutputTokens' => 2048,
            ],
        ];

        $maxRetries = 3;
        for ($attempt = 1; $attempt <= $maxRetries; $attempt++) {
            try {
                $response = Http::timeout(60)->withHeaders([
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
     * Parse and validate the context analysis response.
     */
    protected function parseContextResponse(array $response): array
    {
        $text = $response['text'] ?? '';

        // Clean up response - remove markdown code blocks if present
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

        // Validate the response structure
        $validation = $this->validateContextAnalysis($decoded);

        return [
            'success' => true,
            'context' => $decoded,
            'validation' => $validation,
            'is_ready_for_extraction' => $this->isReadyForExtraction($decoded, $validation),
        ];
    }

    /**
     * Validate the context analysis against expected structure.
     */
    protected function validateContextAnalysis(array $analysis): array
    {
        $errors = [];
        $warnings = [];

        // Check required fields
        $requiredFields = ['view_type', 'orientation', 'drawing_intent', 'unit_system'];
        foreach ($requiredFields as $field) {
            if (!isset($analysis[$field])) {
                $errors[] = "Missing required field: {$field}";
            }
        }

        // Validate view type
        if (isset($analysis['view_type']['primary'])) {
            $viewType = $analysis['view_type']['primary'];
            if (!array_key_exists($viewType, self::VIEW_TYPES)) {
                $warnings[] = "Unknown view type: {$viewType}";
            }
        }

        // Validate orientation
        if (isset($analysis['orientation']['primary'])) {
            $orientation = $analysis['orientation']['primary'];
            if (!array_key_exists($orientation, self::ORIENTATIONS)) {
                $warnings[] = "Unknown orientation: {$orientation}";
            }
            if ($orientation === 'unknown') {
                $warnings[] = "Orientation could not be determined - manual review recommended";
            }
        }

        // Validate unit system
        if (isset($analysis['unit_system']['primary'])) {
            $units = $analysis['unit_system']['primary'];
            if (!array_key_exists($units, self::UNIT_SYSTEMS)) {
                $warnings[] = "Unknown unit system: {$units}";
            }
            if ($units === 'mixed') {
                $warnings[] = "Mixed units detected - verify dimension consistency";
            }
            if ($units === 'not_shown') {
                $warnings[] = "No dimensions visible - cannot extract measurements";
            }
        }

        // Check confidence levels
        $confidenceFields = [
            'view_type.confidence',
            'orientation.confidence',
            'drawing_intent.confidence',
            'unit_system.confidence',
        ];

        foreach ($confidenceFields as $field) {
            $parts = explode('.', $field);
            $value = $analysis;
            foreach ($parts as $part) {
                $value = $value[$part] ?? null;
                if ($value === null) break;
            }
            if ($value !== null && $value < 0.7) {
                $warnings[] = "Low confidence ({$value}) for {$field}";
            }
        }

        // Check baselines
        if (!isset($analysis['baselines']['identified']) || empty($analysis['baselines']['identified'])) {
            $warnings[] = "No baselines identified - dimension references may be ambiguous";
        }

        return [
            'is_valid' => empty($errors),
            'errors' => $errors,
            'warnings' => $warnings,
        ];
    }

    /**
     * Determine if the drawing is ready for dimension extraction.
     */
    protected function isReadyForExtraction(array $analysis, array $validation): array
    {
        $ready = true;
        $blockers = [];
        $recommendations = [];

        // Check for critical issues
        if (!$validation['is_valid']) {
            $ready = false;
            $blockers[] = 'Context analysis failed validation';
        }

        // Check unit system
        $units = $analysis['unit_system']['primary'] ?? 'not_shown';
        if ($units === 'not_shown') {
            $ready = false;
            $blockers[] = 'No dimensions visible in drawing';
        }

        // Check view type for dimension extraction suitability
        $viewType = $analysis['view_type']['primary'] ?? 'unknown';
        $extractableViews = ['elevation', 'section', 'detail'];
        if (!in_array($viewType, $extractableViews) && $viewType !== 'composite') {
            $recommendations[] = "View type '{$viewType}' may have limited extractable dimensions";
        }

        // Check drawing intent
        $intent = $analysis['drawing_intent']['type'] ?? 'unknown';
        if ($intent === 'conceptual') {
            $recommendations[] = "Conceptual drawing - dimensions may not be final";
        }

        // Check overall confidence
        $confidence = $analysis['analysis_confidence'] ?? 0;
        if ($confidence < 0.6) {
            $recommendations[] = "Low overall confidence ({$confidence}) - manual review recommended";
        }

        return [
            'ready' => $ready,
            'blockers' => $blockers,
            'recommendations' => array_merge(
                $recommendations,
                $analysis['recommendations'] ?? []
            ),
        ];
    }

    /**
     * Create a summary suitable for logging or display.
     */
    public function summarizeContext(array $analysisResult): string
    {
        if (!$analysisResult['success']) {
            return "Analysis failed: " . ($analysisResult['error'] ?? 'Unknown error');
        }

        $ctx = $analysisResult['context'];
        $lines = [];

        $lines[] = "Drawing Context Analysis";
        $lines[] = str_repeat('-', 40);

        // View and orientation
        $viewType = $ctx['view_type']['primary'] ?? 'unknown';
        $orientation = $ctx['orientation']['primary'] ?? 'unknown';
        $lines[] = "View: {$viewType} ({$orientation})";

        // Intent
        $intent = $ctx['drawing_intent']['type'] ?? 'unknown';
        $lines[] = "Intent: {$intent}";

        // Units
        $units = $ctx['unit_system']['primary'] ?? 'unknown';
        $format = $ctx['unit_system']['format'] ?? '';
        $lines[] = "Units: {$units}" . ($format ? " ({$format})" : '');

        // Scale
        if (isset($ctx['scale'])) {
            $scale = $ctx['scale']['indicated'] ? ($ctx['scale']['value'] ?? 'shown') : 'not shown';
            $lines[] = "Scale: {$scale}";
        }

        // Baselines
        if (isset($ctx['baselines']['identified'])) {
            $baselines = implode(', ', $ctx['baselines']['identified']);
            $lines[] = "Baselines: {$baselines}";
        }

        // Confidence
        $confidence = $ctx['analysis_confidence'] ?? 0;
        $lines[] = "Confidence: " . round($confidence * 100) . "%";

        // Ready for extraction
        if (isset($analysisResult['is_ready_for_extraction'])) {
            $ready = $analysisResult['is_ready_for_extraction']['ready'];
            $lines[] = "Ready for extraction: " . ($ready ? 'Yes' : 'No');

            if (!$ready && !empty($analysisResult['is_ready_for_extraction']['blockers'])) {
                foreach ($analysisResult['is_ready_for_extraction']['blockers'] as $blocker) {
                    $lines[] = "  ⚠ {$blocker}";
                }
            }
        }

        // Warnings
        if (!empty($analysisResult['validation']['warnings'])) {
            $lines[] = "";
            $lines[] = "Warnings:";
            foreach ($analysisResult['validation']['warnings'] as $warning) {
                $lines[] = "  • {$warning}";
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
            'context' => null,
            'validation' => ['is_valid' => false, 'errors' => [$message], 'warnings' => []],
            'is_ready_for_extraction' => ['ready' => false, 'blockers' => [$message], 'recommendations' => []],
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
     * Get valid values for a given category (useful for UI dropdowns).
     */
    public static function getValidValues(string $category): array
    {
        return match ($category) {
            'view_types' => self::VIEW_TYPES,
            'orientations' => self::ORIENTATIONS,
            'drawing_intents' => self::DRAWING_INTENTS,
            'unit_systems' => self::UNIT_SYSTEMS,
            'baselines' => self::BASELINES,
            default => [],
        };
    }
}
