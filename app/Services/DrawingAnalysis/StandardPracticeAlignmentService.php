<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Compares extracted geometry and math against woodworking best practices.
 * This is the SEVENTH step in the drawing analysis pipeline.
 *
 * Purpose: Identify what aligns with common shop practice vs. custom/non-standard.
 * Does NOT modify data or "fix" anything.
 */
class StandardPracticeAlignmentService
{
    protected ?string $geminiKey;
    protected string $model = 'gemini-2.0-flash';

    // Standard practice categories
    public const PRACTICE_CATEGORIES = [
        'drawer_spacing' => 'Drawer opening spacing and reveals',
        'stretcher_placement' => 'Stretcher/nailer positioning',
        'face_frame_overlap' => 'Face frame overlap on cabinet box',
        'slide_clearance' => 'Drawer slide clearance zones',
        'hinge_placement' => 'Hinge boring and placement',
        'shelf_spacing' => 'Adjustable shelf hole patterns',
        'toe_kick' => 'Toe kick dimensions',
        'rail_stile_width' => 'Face frame member widths',
    ];

    // Standard values (TCS/Industry)
    public const STANDARD_VALUES = [
        'drawer_reveal' => ['value' => 0.125, 'unit' => 'inches', 'note' => '1/8" standard reveal'],
        'face_frame_overlap' => ['value' => 0.375, 'unit' => 'inches', 'note' => '3/8" overlap on box'],
        'toe_kick_height' => ['value' => 4.0, 'unit' => 'inches', 'note' => 'Standard toe kick'],
        'toe_kick_depth' => ['value' => 3.0, 'unit' => 'inches', 'note' => 'Standard setback'],
        'stile_width' => ['min' => 1.5, 'max' => 1.75, 'unit' => 'inches', 'note' => 'Standard stile'],
        'rail_width' => ['min' => 1.5, 'max' => 2.0, 'unit' => 'inches', 'note' => 'Standard rail'],
        'slide_clearance' => ['value' => 0.5, 'unit' => 'inches', 'note' => 'Per side for slides'],
        'shelf_hole_spacing' => ['value' => 32, 'unit' => 'mm', 'note' => '32mm system standard'],
        'countertop_height' => ['value' => 36, 'unit' => 'inches', 'note' => 'Standard counter height'],
        'vanity_height' => ['value' => 32, 'unit' => 'inches', 'note' => 'Standard vanity height'],
        'upper_cabinet_height' => ['value' => 18, 'unit' => 'inches', 'note' => 'Above counter clearance'],
    ];

    // Alignment status
    public const ALIGNMENT_STATUS = [
        'standard' => 'Aligns with common shop practice',
        'acceptable_variation' => 'Within acceptable tolerance of standard',
        'custom' => 'Non-standard but intentional design choice',
        'non_standard' => 'Deviates from standard - verify intentional',
        'unknown' => 'Cannot determine alignment',
    ];

    public function __construct()
    {
        $this->geminiKey = config('services.google.api_key') ?? env('GOOGLE_API_KEY') ?? env('GEMINI_API_KEY');
    }

    /**
     * Check alignment with standard practices.
     *
     * @param string $imageBase64 Base64-encoded image data
     * @param string $mimeType Image MIME type
     * @param array $verificationResult Result from DimensionConsistencyVerifierService
     * @param array $entityExtraction Result from HierarchicalEntityExtractorService
     * @return array Alignment check result
     */
    public function checkStandardAlignment(
        string $imageBase64,
        string $mimeType,
        array $verificationResult,
        array $entityExtraction
    ): array {
        if (empty($this->geminiKey)) {
            return $this->errorResponse('Gemini API key not configured');
        }

        $systemPrompt = $this->buildAlignmentPrompt($verificationResult, $entityExtraction);

        try {
            $response = $this->callGeminiApi($systemPrompt, [
                'mimeType' => $mimeType,
                'data' => $imageBase64,
            ]);

            if (isset($response['error'])) {
                return $response;
            }

            return $this->parseAlignmentResponse($response);

        } catch (\Exception $e) {
            Log::error('StandardPracticeAlignment error: ' . $e->getMessage());
            return $this->errorResponse($e->getMessage());
        }
    }

    /**
     * Build the system prompt for alignment checking.
     */
    protected function buildAlignmentPrompt(array $verificationResult, array $entityExtraction): string
    {
        // Build standards reference
        $standardsRef = '';
        foreach (self::STANDARD_VALUES as $key => $std) {
            if (isset($std['value'])) {
                $standardsRef .= "- {$key}: {$std['value']}{$std['unit']} ({$std['note']})\n";
            } else {
                $standardsRef .= "- {$key}: {$std['min']}-{$std['max']}{$std['unit']} ({$std['note']})\n";
            }
        }

        // Extract gap data from verification
        $gaps = $verificationResult['verification']['cabinet_verifications'] ?? [];
        $gapSummary = '';
        foreach ($gaps as $cv) {
            $cabId = $cv['cabinet_id'] ?? 'unknown';
            $impliedGaps = $cv['implied_gaps'] ?? [];
            foreach ($impliedGaps as $gap) {
                $gapSummary .= "- {$cabId}: {$gap['location']} = {$gap['calculated_value']}\"\n";
            }
        }

        return <<<PROMPT
You are comparing cabinet construction data against standard woodworking practices.

CRITICAL RULES:
- Do NOT modify any data
- Do NOT "fix" anything
- Only COMPARE and FLAG differences
- Note whether deviations appear intentional or accidental

## INDUSTRY STANDARDS REFERENCE
{$standardsRef}

## CALCULATED GAPS FROM PRIOR ANALYSIS
{$gapSummary}

## EVALUATE THE FOLLOWING PRACTICES

### 1. DRAWER SPACING LOGIC
Compare drawer reveals and spacing to standard 1/8" reveal:
- Are reveals consistent across all drawers?
- Do reveals match standard or are they custom?
- Is spacing logic clear (equal distribution, progressive, custom)?

### 2. STRETCHER PLACEMENT INTENT
If stretchers are indicated:
- Are they positioned for drawer support (behind drawer box)?
- Are they structural (nailer for face frame)?
- Is depth offset from front consistent?

### 3. FACE FRAME OVERLAP ASSUMPTIONS
Check face frame to cabinet box relationship:
- Does frame overlap box by standard 3/8"?
- Is overlap consistent all around?
- Are stile/rail widths standard (1.5" - 1.75")?

### 4. SLIDE CLEARANCE ZONES
For drawer cabinets:
- Is there adequate clearance for side-mount slides (0.5" per side)?
- Does drawer opening width minus clearances equal drawer box width?
- Are clearances consistent?

## RESPONSE FORMAT

Respond ONLY with valid JSON:

{
  "practice_evaluations": [
    {
      "category": "drawer_spacing",
      "status": "standard",
      "details": {
        "observed_value": 0.125,
        "standard_value": 0.125,
        "deviation": 0,
        "deviation_percent": 0
      },
      "assessment": "Drawer reveals match standard 1/8\" reveal",
      "is_consistent": true,
      "affected_cabinets": ["CAB-001", "CAB-002"],
      "confidence": 0.9
    },
    {
      "category": "face_frame_overlap",
      "status": "acceptable_variation",
      "details": {
        "observed_value": 0.5,
        "standard_value": 0.375,
        "deviation": 0.125,
        "deviation_percent": 33
      },
      "assessment": "Frame overlap is 1/2\" instead of standard 3/8\" - common variation",
      "is_consistent": true,
      "affected_cabinets": ["CAB-001", "CAB-002", "CAB-003"],
      "confidence": 0.85
    },
    {
      "category": "stretcher_placement",
      "status": "custom",
      "details": {
        "observed_pattern": "Full-width stretchers at 4\" below box top",
        "standard_pattern": "Stretchers behind drawer boxes",
        "deviation_description": "Non-standard position"
      },
      "assessment": "Stretcher placement appears intentional for specific drawer configuration",
      "is_consistent": true,
      "affected_cabinets": ["CAB-001"],
      "confidence": 0.75
    },
    {
      "category": "slide_clearance",
      "status": "non_standard",
      "details": {
        "observed_value": 0.375,
        "standard_value": 0.5,
        "deviation": -0.125,
        "deviation_percent": -25
      },
      "assessment": "Slide clearance appears tight - verify slide specification",
      "is_consistent": true,
      "affected_cabinets": ["CAB-002"],
      "confidence": 0.8,
      "flag": {
        "severity": "warning",
        "message": "Verify drawer slides will fit with 3/8\" clearance per side"
      }
    }
  ],

  "overall_alignment": {
    "status": "mostly_standard",
    "standard_count": 2,
    "acceptable_variation_count": 1,
    "custom_count": 1,
    "non_standard_count": 1,
    "summary": "Construction mostly follows standard practices with some custom elements"
  },

  "flags": [
    {
      "category": "slide_clearance",
      "severity": "warning",
      "message": "Slide clearance (0.375\") below standard (0.5\") - verify slide specs",
      "cabinet_id": "CAB-002"
    }
  ],

  "custom_elements": [
    {
      "element": "stretcher_placement",
      "description": "Full-width stretchers at non-standard height",
      "appears_intentional": true,
      "recommendation": "Document in production notes"
    }
  ],

  "recommendations": [
    "Verify drawer slide specifications for CAB-002",
    "Document custom stretcher placement for shop reference",
    "Face frame overlap variation is acceptable - no action needed"
  ]
}
PROMPT;
    }

    /**
     * Call the Gemini API for alignment checking.
     */
    protected function callGeminiApi(string $systemPrompt, array $image): array
    {
        $contents = [
            [
                'role' => 'user',
                'parts' => [
                    ['text' => 'Compare the cabinet construction against standard woodworking practices. Flag non-standard elements but do not modify anything.'],
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
                'maxOutputTokens' => 4096,
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
     * Parse and validate the alignment response.
     */
    protected function parseAlignmentResponse(array $response): array
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

        // Validate the response
        $validation = $this->validateAlignment($decoded);

        return [
            'success' => true,
            'alignment' => $decoded,
            'validation' => $validation,
        ];
    }

    /**
     * Validate the alignment results.
     */
    protected function validateAlignment(array $alignment): array
    {
        $errors = [];
        $warnings = [];

        $evaluations = $alignment['practice_evaluations'] ?? [];

        if (empty($evaluations)) {
            $warnings[] = 'No practice evaluations performed';
        }

        foreach ($evaluations as $eval) {
            $category = $eval['category'] ?? 'unknown';

            if (!array_key_exists($category, self::PRACTICE_CATEGORIES)) {
                $warnings[] = "Unknown practice category: {$category}";
            }

            $status = $eval['status'] ?? 'unknown';
            if (!array_key_exists($status, self::ALIGNMENT_STATUS)) {
                $warnings[] = "Unknown alignment status for {$category}: {$status}";
            }

            if ($status === 'non_standard' && !isset($eval['flag'])) {
                $warnings[] = "Non-standard {$category} should have a flag";
            }
        }

        // Check for flags that need attention
        $flags = $alignment['flags'] ?? [];
        $errorFlags = array_filter($flags, fn($f) => ($f['severity'] ?? '') === 'error');
        if (!empty($errorFlags)) {
            foreach ($errorFlags as $f) {
                $errors[] = "Practice error: {$f['message']}";
            }
        }

        return [
            'is_valid' => empty($errors),
            'errors' => $errors,
            'warnings' => $warnings,
        ];
    }

    /**
     * Get non-standard elements that need documentation.
     */
    public function getNonStandardElements(array $alignmentResult): array
    {
        if (!$alignmentResult['success']) {
            return [];
        }

        $evaluations = $alignmentResult['alignment']['practice_evaluations'] ?? [];

        return array_filter($evaluations, function ($eval) {
            $status = $eval['status'] ?? '';
            return in_array($status, ['custom', 'non_standard']);
        });
    }

    /**
     * Get flags that need attention.
     */
    public function getFlags(array $alignmentResult): array
    {
        if (!$alignmentResult['success']) {
            return [];
        }

        return $alignmentResult['alignment']['flags'] ?? [];
    }

    /**
     * Create a summary suitable for logging or display.
     */
    public function summarizeAlignment(array $alignmentResult): string
    {
        if (!$alignmentResult['success']) {
            return "Alignment check failed: " . ($alignmentResult['error'] ?? 'Unknown error');
        }

        $align = $alignmentResult['alignment'];
        $overall = $align['overall_alignment'] ?? [];
        $lines = [];

        $lines[] = "Standard Practice Alignment Check";
        $lines[] = str_repeat('-', 40);

        // Overall status
        $status = strtoupper($overall['status'] ?? 'unknown');
        $lines[] = "Overall: {$status}";
        $lines[] = "";

        // Counts
        $lines[] = "Standard: " . ($overall['standard_count'] ?? 0);
        $lines[] = "Acceptable variation: " . ($overall['acceptable_variation_count'] ?? 0);
        $lines[] = "Custom: " . ($overall['custom_count'] ?? 0);
        $lines[] = "Non-standard: " . ($overall['non_standard_count'] ?? 0);

        // Summary
        if (!empty($overall['summary'])) {
            $lines[] = "";
            $lines[] = $overall['summary'];
        }

        // Flags
        $flags = $align['flags'] ?? [];
        if (!empty($flags)) {
            $lines[] = "";
            $lines[] = "⚠ FLAGS:";
            foreach ($flags as $f) {
                $severity = strtoupper($f['severity'] ?? 'info');
                $lines[] = "  [{$severity}] {$f['message']}";
            }
        }

        // Custom elements
        $custom = $align['custom_elements'] ?? [];
        if (!empty($custom)) {
            $lines[] = "";
            $lines[] = "Custom Elements:";
            foreach ($custom as $c) {
                $intentional = ($c['appears_intentional'] ?? false) ? '(intentional)' : '(verify)';
                $lines[] = "  • {$c['element']}: {$c['description']} {$intentional}";
            }
        }

        // Recommendations
        $recommendations = $align['recommendations'] ?? [];
        if (!empty($recommendations)) {
            $lines[] = "";
            $lines[] = "Recommendations:";
            foreach ($recommendations as $rec) {
                $lines[] = "  • {$rec}";
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
            'alignment' => null,
            'validation' => ['is_valid' => false, 'errors' => [$message], 'warnings' => []],
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
            'practice_categories' => self::PRACTICE_CATEGORIES,
            'standard_values' => self::STANDARD_VALUES,
            'alignment_status' => self::ALIGNMENT_STATUS,
            default => [],
        };
    }
}
