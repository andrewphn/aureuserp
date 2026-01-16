<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Verifies dimension consistency and performs math stack-ups.
 * This is the SIXTH step in the drawing analysis pipeline.
 *
 * Purpose: Validate that dimensions reconcile mathematically.
 * Does NOT automatically resolve discrepancies - flags them for review.
 */
class DimensionConsistencyVerifierService
{
    protected ?string $geminiKey;
    protected string $model = 'gemini-2.0-flash';

    // Fixed element types for stack-up
    public const FIXED_ELEMENTS = [
        'toe_kick' => 'Toe kick (typically 4")',
        'false_front' => 'False front / drawer header',
        'drawer_opening' => 'Drawer opening (face frame)',
        'door_opening' => 'Door opening (face frame)',
        'top_rail' => 'Top face frame rail',
        'bottom_rail' => 'Bottom face frame rail',
        'intermediate_rail' => 'Intermediate rail between openings',
        'stile' => 'Face frame stile',
        'countertop' => 'Countertop thickness',
        'stretcher' => 'Stretcher/nailer',
    ];

    // Gap types
    public const GAP_TYPES = [
        'reveal' => 'Reveal gap (drawer/door to frame)',
        'clearance' => 'Clearance gap (operational)',
        'expansion' => 'Expansion gap (material movement)',
        'construction' => 'Construction tolerance',
    ];

    // Stack-up directions
    public const STACK_DIRECTIONS = [
        'vertical' => 'Bottom to top (height)',
        'horizontal' => 'Left to right (width)',
        'depth' => 'Front to back (depth)',
    ];

    // Reconciliation status
    public const RECONCILIATION_STATUS = [
        'reconciled' => 'All dimensions add up correctly',
        'reconciled_with_gaps' => 'Dimensions reconcile when gaps are included',
        'discrepancy' => 'Dimensions do not reconcile',
        'insufficient_data' => 'Not enough dimensions to verify',
    ];

    public function __construct()
    {
        $this->geminiKey = config('services.google.api_key') ?? env('GOOGLE_API_KEY') ?? env('GEMINI_API_KEY');
    }

    /**
     * Verify dimension consistency for extracted entities.
     *
     * @param string $imageBase64 Base64-encoded image data
     * @param string $mimeType Image MIME type
     * @param array $entityExtraction Result from HierarchicalEntityExtractorService
     * @param array $priorAnalysis Combined results from steps 1-4
     * @return array Verification result with stack-ups and discrepancies
     */
    public function verifyDimensionConsistency(
        string $imageBase64,
        string $mimeType,
        array $entityExtraction,
        array $priorAnalysis
    ): array {
        if (empty($this->geminiKey)) {
            return $this->errorResponse('Gemini API key not configured');
        }

        if (!$entityExtraction['success']) {
            return $this->errorResponse('Entity extraction failed - cannot verify dimensions');
        }

        $systemPrompt = $this->buildVerificationPrompt($entityExtraction, $priorAnalysis);

        try {
            $response = $this->callGeminiApi($systemPrompt, [
                'mimeType' => $mimeType,
                'data' => $imageBase64,
            ]);

            if (isset($response['error'])) {
                return $response;
            }

            return $this->parseVerificationResponse($response);

        } catch (\Exception $e) {
            Log::error('DimensionConsistencyVerifier error: ' . $e->getMessage());
            return $this->errorResponse($e->getMessage());
        }
    }

    /**
     * Build the system prompt for dimension verification.
     */
    protected function buildVerificationPrompt(array $entityExtraction, array $priorAnalysis): string
    {
        // Extract cabinet data for verification
        $cabinets = $entityExtraction['extraction']['entities']['cabinets'] ?? [];
        $dimensions = $priorAnalysis['dimensions']['references']['dimensions'] ?? [];

        // Build cabinet summary
        $cabinetSummary = '';
        foreach ($cabinets as $cab) {
            $id = $cab['id'] ?? 'unknown';
            $name = $cab['name'] ?? 'unnamed';
            $type = $cab['type'] ?? 'unknown';
            $geom = $cab['bounding_geometry'] ?? [];
            $width = $geom['width']['value'] ?? 'N/A';
            $height = $geom['height']['value'] ?? 'N/A';
            $cabinetSummary .= "- {$id}: {$name} ({$type}) - W:{$width} H:{$height}\n";
        }

        // Build dimension list
        $dimSummary = '';
        foreach (array_slice($dimensions, 0, 15) as $dim) {
            $value = $dim['value']['as_written'] ?? 'unknown';
            $orient = $dim['orientation'] ?? 'unknown';
            $dimSummary .= "- {$value} ({$orient})\n";
        }

        return <<<PROMPT
You are verifying dimension consistency for cabinet construction.

CRITICAL RULES:
- Perform mathematical stack-ups for each cabinet
- Flag discrepancies - do NOT automatically resolve them
- Calculate implied gaps ONLY if math reconciles
- Report EXACTLY what the numbers show

EXTRACTED CABINETS:
{$cabinetSummary}

AVAILABLE DIMENSIONS:
{$dimSummary}

## VERIFICATION PROCESS

### For EACH cabinet, perform:

#### 1. IDENTIFY TOTAL DIMENSIONS
Extract the overall cabinet box dimensions:
- **Height**: Total cabinet height (may or may not include toe kick)
- **Width**: Total cabinet width
- **Depth**: Total cabinet depth

Note the source of each dimension (labeled, calculated, or unknown).

#### 2. IDENTIFY FIXED ELEMENTS
List all fixed-height or fixed-width elements:
- **Toe kick**: Height (typically 4")
- **False fronts**: Height of each
- **Drawer openings**: Face frame opening heights
- **Rails**: Top, bottom, and intermediate rail widths
- **Stiles**: Left and right stile widths

For each, note if dimension is:
- Explicitly labeled
- Standard/assumed
- Unknown

#### 3. IDENTIFY IMPLIED GAPS
List spaces between elements that aren't dimensioned:
- Reveal gaps (drawer face to frame)
- Clearance gaps (operational clearance)
- Undimensioned spaces

#### 4. PERFORM VERTICAL STACK-UP
Starting from bottom, add up:
```
Toe kick + [Bottom rail] + [Opening 1] + [Rail] + [Opening 2] + ... + [Top rail] = Total Height
```

Show the calculation clearly.

#### 5. PERFORM HORIZONTAL STACK-UP
Starting from left, add up:
```
Left stile + [Opening 1 width] + [Center stile] + [Opening 2 width] + Right stile = Total Width
```

Show the calculation clearly.

#### 6. DETERMINE RECONCILIATION STATUS

If math DOES reconcile:
- Status: "reconciled" or "reconciled_with_gaps"
- Output calculated gap values
- Note if gaps are consistent across cabinet

If math does NOT reconcile:
- Status: "discrepancy"
- Calculate the discrepancy amount
- Identify which dimensions conflict
- Do NOT attempt to resolve

## RESPONSE FORMAT

Respond ONLY with valid JSON:

{
  "cabinet_verifications": [
    {
      "cabinet_id": "CAB-001",
      "cabinet_name": "SB36",
      "cabinet_type": "sink_base",

      "total_dimensions": {
        "height": {
          "value": 34.5,
          "unit": "inches",
          "source": "labeled",
          "includes_toe_kick": true
        },
        "width": {
          "value": 36,
          "unit": "inches",
          "source": "labeled"
        },
        "depth": {
          "value": 24,
          "unit": "inches",
          "source": "assumed_standard"
        }
      },

      "fixed_elements": {
        "vertical": [
          {"type": "toe_kick", "value": 4, "source": "labeled"},
          {"type": "bottom_rail", "value": null, "source": "not_present_sink_cabinet"},
          {"type": "drawer_opening_1", "value": 6, "source": "labeled"},
          {"type": "intermediate_rail", "value": 1.5, "source": "labeled"},
          {"type": "drawer_opening_2", "value": null, "source": "calculated_remainder"},
          {"type": "top_rail", "value": 1.5, "source": "assumed_standard"}
        ],
        "horizontal": [
          {"type": "left_stile", "value": 1.5, "source": "labeled"},
          {"type": "opening_width", "value": 33, "source": "calculated"},
          {"type": "right_stile", "value": 1.5, "source": "labeled"}
        ]
      },

      "vertical_stackup": {
        "calculation": "4 (toe) + 6 (drawer 1) + 1.5 (rail) + X (drawer 2) + 1.5 (top rail) = 34.5",
        "known_total": 34.5,
        "known_elements_sum": 13,
        "remainder": 21.5,
        "remainder_assigned_to": "drawer_opening_2",
        "status": "reconciled",
        "discrepancy": null
      },

      "horizontal_stackup": {
        "calculation": "1.5 (L stile) + 33 (opening) + 1.5 (R stile) = 36",
        "known_total": 36,
        "known_elements_sum": 36,
        "remainder": 0,
        "status": "reconciled",
        "discrepancy": null
      },

      "implied_gaps": [
        {
          "location": "drawer_1_reveal",
          "calculated_value": 0.125,
          "unit": "inches",
          "gap_type": "reveal",
          "notes": "Standard 1/8\" reveal"
        }
      ],

      "gap_consistency": {
        "are_gaps_consistent": true,
        "standard_gap_value": 0.125,
        "notes": "All reveals appear to be 1/8\""
      },

      "overall_status": "reconciled",
      "confidence": 0.9
    }
  ],

  "discrepancies": [
    {
      "cabinet_id": "CAB-002",
      "direction": "vertical",
      "expected_total": 34.5,
      "calculated_total": 35.25,
      "difference": 0.75,
      "conflicting_dimensions": [
        {"element": "drawer_opening_2", "labeled": 8, "calculated_remainder": 7.25}
      ],
      "possible_causes": [
        "Dimension may include reveal gaps",
        "Toe kick height may differ from assumed 4\""
      ],
      "resolution_needed": true
    }
  ],

  "summary": {
    "cabinets_verified": 3,
    "fully_reconciled": 2,
    "reconciled_with_gaps": 1,
    "with_discrepancies": 0,
    "insufficient_data": 0,
    "overall_consistency": "good"
  },

  "verification_notes": "All cabinets reconcile with standard 1/8\" reveals. Sink base confirmed to have no bottom rail.",

  "recommendations": [
    "Verify toe kick height is 4\" as assumed",
    "Confirm 1/8\" reveal is project standard"
  ]
}
PROMPT;
    }

    /**
     * Call the Gemini API for verification.
     */
    protected function callGeminiApi(string $systemPrompt, array $image): array
    {
        $contents = [
            [
                'role' => 'user',
                'parts' => [
                    ['text' => 'Verify dimension consistency for each cabinet. Perform vertical and horizontal stack-ups. Flag any discrepancies without resolving them.'],
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
                'temperature' => 0.1, // Very low for math accuracy
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
     * Parse and validate the verification response.
     */
    protected function parseVerificationResponse(array $response): array
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
        $validation = $this->validateVerification($decoded);

        // Check if we can proceed to next step
        $canProceed = $this->determineCanProceed($decoded);

        return [
            'success' => true,
            'verification' => $decoded,
            'validation' => $validation,
            'can_proceed' => $canProceed,
        ];
    }

    /**
     * Validate the verification results.
     */
    protected function validateVerification(array $verification): array
    {
        $errors = [];
        $warnings = [];

        $cabinetVerifications = $verification['cabinet_verifications'] ?? [];

        if (empty($cabinetVerifications)) {
            $warnings[] = 'No cabinet verifications performed';
        }

        foreach ($cabinetVerifications as $cv) {
            $cabId = $cv['cabinet_id'] ?? 'unknown';

            // Check for required fields
            if (!isset($cv['vertical_stackup'])) {
                $warnings[] = "{$cabId}: Missing vertical stack-up";
            }
            if (!isset($cv['horizontal_stackup'])) {
                $warnings[] = "{$cabId}: Missing horizontal stack-up";
            }

            // Check for discrepancies
            $vertStatus = $cv['vertical_stackup']['status'] ?? 'unknown';
            $horizStatus = $cv['horizontal_stackup']['status'] ?? 'unknown';

            if ($vertStatus === 'discrepancy') {
                $disc = $cv['vertical_stackup']['discrepancy'] ?? 'unknown';
                $warnings[] = "{$cabId}: Vertical discrepancy of {$disc}\"";
            }
            if ($horizStatus === 'discrepancy') {
                $disc = $cv['horizontal_stackup']['discrepancy'] ?? 'unknown';
                $warnings[] = "{$cabId}: Horizontal discrepancy of {$disc}\"";
            }

            // Check confidence
            $confidence = $cv['confidence'] ?? 0;
            if ($confidence < 0.7) {
                $warnings[] = "{$cabId}: Low verification confidence ({$confidence})";
            }
        }

        // Check overall discrepancies
        $discrepancies = $verification['discrepancies'] ?? [];
        if (!empty($discrepancies)) {
            foreach ($discrepancies as $d) {
                $cabId = $d['cabinet_id'] ?? 'unknown';
                $diff = $d['difference'] ?? 'unknown';
                $errors[] = "{$cabId}: Unresolved discrepancy of {$diff}\"";
            }
        }

        return [
            'is_valid' => empty($errors),
            'errors' => $errors,
            'warnings' => $warnings,
        ];
    }

    /**
     * Determine if we can proceed to next step.
     */
    protected function determineCanProceed(array $verification): array
    {
        $summary = $verification['summary'] ?? [];
        $discrepancies = $verification['discrepancies'] ?? [];

        $totalCabinets = $summary['cabinets_verified'] ?? 0;
        $withDiscrepancies = $summary['with_discrepancies'] ?? 0;
        $consistency = $summary['overall_consistency'] ?? 'unknown';

        // Block if there are unresolved discrepancies
        if (!empty($discrepancies)) {
            return [
                'can_proceed' => false,
                'reason' => 'Unresolved dimension discrepancies',
                'discrepancy_count' => count($discrepancies),
                'action_required' => 'Resolve discrepancies before proceeding',
            ];
        }

        // Warn but allow if some cabinets have issues
        if ($withDiscrepancies > 0) {
            return [
                'can_proceed' => true,
                'reason' => 'Some cabinets have discrepancies but were resolved',
                'discrepancy_count' => $withDiscrepancies,
                'action_required' => 'Review flagged cabinets',
            ];
        }

        return [
            'can_proceed' => true,
            'reason' => 'All dimensions reconcile',
            'discrepancy_count' => 0,
            'action_required' => null,
        ];
    }

    /**
     * Get cabinets with discrepancies.
     */
    public function getCabinetsWithDiscrepancies(array $verificationResult): array
    {
        if (!$verificationResult['success']) {
            return [];
        }

        return $verificationResult['verification']['discrepancies'] ?? [];
    }

    /**
     * Get calculated gap values.
     */
    public function getCalculatedGaps(array $verificationResult): array
    {
        if (!$verificationResult['success']) {
            return [];
        }

        $gaps = [];
        $cabinetVerifications = $verificationResult['verification']['cabinet_verifications'] ?? [];

        foreach ($cabinetVerifications as $cv) {
            $cabId = $cv['cabinet_id'] ?? 'unknown';
            $impliedGaps = $cv['implied_gaps'] ?? [];

            foreach ($impliedGaps as $gap) {
                $gaps[] = array_merge($gap, ['cabinet_id' => $cabId]);
            }
        }

        return $gaps;
    }

    /**
     * Create a summary suitable for logging or display.
     */
    public function summarizeVerification(array $verificationResult): string
    {
        if (!$verificationResult['success']) {
            return "Verification failed: " . ($verificationResult['error'] ?? 'Unknown error');
        }

        $ver = $verificationResult['verification'];
        $summary = $ver['summary'] ?? [];
        $lines = [];

        $lines[] = "Dimension Consistency Verification";
        $lines[] = str_repeat('-', 40);

        // Summary stats
        $lines[] = "Cabinets verified: " . ($summary['cabinets_verified'] ?? 0);
        $lines[] = "Fully reconciled: " . ($summary['fully_reconciled'] ?? 0);
        $lines[] = "With gaps calculated: " . ($summary['reconciled_with_gaps'] ?? 0);
        $lines[] = "With discrepancies: " . ($summary['with_discrepancies'] ?? 0);
        $lines[] = "Insufficient data: " . ($summary['insufficient_data'] ?? 0);

        $lines[] = "";
        $lines[] = "Overall consistency: " . strtoupper($summary['overall_consistency'] ?? 'unknown');

        // Discrepancies
        $discrepancies = $ver['discrepancies'] ?? [];
        if (!empty($discrepancies)) {
            $lines[] = "";
            $lines[] = "⚠ DISCREPANCIES:";
            foreach ($discrepancies as $d) {
                $lines[] = "  {$d['cabinet_id']} ({$d['direction']}): {$d['difference']}\" difference";
                if (!empty($d['possible_causes'])) {
                    foreach ($d['possible_causes'] as $cause) {
                        $lines[] = "    - {$cause}";
                    }
                }
            }
        }

        // Can proceed
        $proceed = $verificationResult['can_proceed'];
        $lines[] = "";
        $lines[] = "Can proceed: " . ($proceed['can_proceed'] ? 'Yes' : 'No');
        if (!empty($proceed['action_required'])) {
            $lines[] = "Action required: {$proceed['action_required']}";
        }

        // Recommendations
        $recommendations = $ver['recommendations'] ?? [];
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
            'verification' => null,
            'validation' => ['is_valid' => false, 'errors' => [$message], 'warnings' => []],
            'can_proceed' => ['can_proceed' => false, 'reason' => $message],
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
            'fixed_elements' => self::FIXED_ELEMENTS,
            'gap_types' => self::GAP_TYPES,
            'stack_directions' => self::STACK_DIRECTIONS,
            'reconciliation_status' => self::RECONCILIATION_STATUS,
            default => [],
        };
    }
}
