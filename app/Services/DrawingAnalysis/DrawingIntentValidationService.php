<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Validates whether a drawing is suitable for downstream processing.
 * This is the FOURTH step (GATE) in the drawing analysis pipeline.
 *
 * Purpose: Determine if drawing has sufficient information for:
 * - Production modeling
 * - CNC file generation
 * - Material takeoff
 * - Verification only
 *
 * Will BLOCK downstream extraction if requirements are not met.
 */
class DrawingIntentValidationService
{
    protected ?string $geminiKey;
    protected string $model = 'gemini-2.0-flash';

    // Processing purposes and their requirements
    public const PROCESSING_PURPOSES = [
        'production_modeling' => [
            'description' => 'Full 3D model for shop drawings and visualization',
            'requires' => [
                'reference_planes' => true,
                'all_dimensions' => true,
                'material_specs' => true,
                'construction_notes' => true,
                'orientation_clarity' => true,
            ],
            'minimum_confidence' => 0.85,
        ],
        'cnc_generation' => [
            'description' => 'CNC cut files for panel processing',
            'requires' => [
                'reference_planes' => true,
                'all_dimensions' => true,
                'material_specs' => true,
                'construction_notes' => false,
                'orientation_clarity' => true,
            ],
            'minimum_confidence' => 0.90,
        ],
        'material_takeoff' => [
            'description' => 'Bill of materials and material estimation',
            'requires' => [
                'reference_planes' => false,
                'all_dimensions' => true,
                'material_specs' => true,
                'construction_notes' => false,
                'orientation_clarity' => false,
            ],
            'minimum_confidence' => 0.75,
        ],
        'verification_only' => [
            'description' => 'Field verification and measurement checking',
            'requires' => [
                'reference_planes' => false,
                'all_dimensions' => false,
                'material_specs' => false,
                'construction_notes' => false,
                'orientation_clarity' => false,
            ],
            'minimum_confidence' => 0.50,
        ],
    ];

    // Requirement categories
    public const REQUIREMENT_CATEGORIES = [
        'reference_planes' => 'Clear reference planes for all dimensions',
        'all_dimensions' => 'Complete dimensional information',
        'material_specs' => 'Material specifications for all components',
        'construction_notes' => 'Construction method notes',
        'orientation_clarity' => 'Clear view orientation',
        'hardware_specs' => 'Hardware specifications',
        'finish_specs' => 'Finish specifications',
        'assembly_notes' => 'Assembly instructions',
    ];

    // Blocker severity levels
    public const BLOCKER_SEVERITY = [
        'critical' => 'Prevents all downstream processing',
        'major' => 'Prevents production use, allows verification',
        'minor' => 'May cause issues, proceed with caution',
        'warning' => 'Informational only',
    ];

    public function __construct()
    {
        $this->geminiKey = config('services.google.api_key') ?? env('GOOGLE_API_KEY') ?? env('GEMINI_API_KEY');
    }

    /**
     * Validate drawing suitability based on prior analysis results.
     *
     * @param array $drawingContext Result from DrawingContextAnalyzerService
     * @param array $dimensionReferences Result from DimensionReferenceAnalyzerService
     * @param array $notesExtraction Result from DrawingNotesExtractorService
     * @param string|null $imageBase64 Optional image for additional AI validation
     * @param string|null $mimeType Image MIME type if image provided
     * @return array Validation result with suitability determination
     */
    public function validateDrawingIntent(
        array $drawingContext,
        array $dimensionReferences,
        array $notesExtraction,
        ?string $imageBase64 = null,
        ?string $mimeType = null
    ): array {
        // Aggregate data from prior analyses
        $aggregatedData = $this->aggregateAnalysisData(
            $drawingContext,
            $dimensionReferences,
            $notesExtraction
        );

        // Check requirements for each purpose
        $suitability = $this->assessSuitability($aggregatedData);

        // Identify blockers
        $blockers = $this->identifyBlockers($aggregatedData);

        // Generate recommendations
        $recommendations = $this->generateRecommendations($blockers, $suitability);

        // If image provided, do AI validation for additional context
        $aiValidation = null;
        if ($imageBase64 && $mimeType && !empty($this->geminiKey)) {
            $aiValidation = $this->performAiValidation(
                $imageBase64,
                $mimeType,
                $aggregatedData,
                $blockers
            );
        }

        // Determine if extraction should proceed
        $canProceed = $this->determineCanProceed($suitability, $blockers);

        return [
            'success' => true,
            'suitability' => $suitability,
            'blockers' => $blockers,
            'recommendations' => $recommendations,
            'ai_validation' => $aiValidation,
            'can_proceed' => $canProceed,
            'aggregated_data' => $aggregatedData,
        ];
    }

    /**
     * Aggregate data from prior analysis steps.
     */
    protected function aggregateAnalysisData(
        array $drawingContext,
        array $dimensionReferences,
        array $notesExtraction
    ): array {
        $ctx = $drawingContext['context'] ?? [];
        $refs = $dimensionReferences['references'] ?? [];
        $notes = $notesExtraction['extraction'] ?? [];

        return [
            // From context analysis
            'view_type' => $ctx['view_type']['primary'] ?? 'unknown',
            'orientation' => $ctx['orientation']['primary'] ?? 'unknown',
            'orientation_confidence' => $ctx['orientation']['confidence'] ?? 0,
            'drawing_intent' => $ctx['drawing_intent']['type'] ?? 'unknown',
            'unit_system' => $ctx['unit_system']['primary'] ?? 'unknown',
            'scale_indicated' => $ctx['scale']['indicated'] ?? false,
            'baselines_identified' => $ctx['baselines']['identified'] ?? [],
            'context_confidence' => $ctx['analysis_confidence'] ?? 0,

            // From dimension reference analysis
            'dimension_count' => $refs['summary']['total_dimensions'] ?? 0,
            'flagged_dimensions' => $refs['summary']['flagged_dimensions'] ?? 0,
            'reference_consistency' => $refs['summary']['reference_consistency'] ?? 'unknown',
            'primary_reference' => $refs['summary']['primary_reference_plane'] ?? 'unknown',
            'dimension_conflicts' => $refs['potential_conflicts'] ?? [],
            'dimension_risk_level' => $dimensionReferences['risk_assessment']['overall_risk_level'] ?? 'unknown',

            // From notes extraction
            'total_notes' => $notes['summary']['total_notes'] ?? 0,
            'production_notes' => $notes['summary']['production_required_count'] ?? 0,
            'has_material_specs' => ($notes['summary']['by_type']['material_spec'] ?? 0) > 0,
            'has_hardware_specs' => ($notes['summary']['by_type']['hardware_spec'] ?? 0) > 0,
            'has_finish_specs' => ($notes['summary']['by_type']['finish_spec'] ?? 0) > 0,
            'has_warnings' => !empty($notesExtraction['categorized']['warnings'] ?? []),
            'title_block' => $notes['title_block'] ?? null,

            // Validation status from prior steps
            'context_valid' => $drawingContext['validation']['is_valid'] ?? false,
            'references_valid' => $dimensionReferences['validation']['is_valid'] ?? false,
            'notes_valid' => $notesExtraction['validation']['is_valid'] ?? false,
        ];
    }

    /**
     * Assess suitability for each processing purpose.
     */
    protected function assessSuitability(array $data): array
    {
        $results = [];

        foreach (self::PROCESSING_PURPOSES as $purpose => $config) {
            $score = 0;
            $maxScore = 0;
            $missingRequirements = [];
            $partialRequirements = [];

            foreach ($config['requires'] as $requirement => $required) {
                if (!$required) {
                    continue;
                }

                $maxScore += 1;
                $status = $this->checkRequirement($requirement, $data);

                if ($status === 'met') {
                    $score += 1;
                } elseif ($status === 'partial') {
                    $score += 0.5;
                    $partialRequirements[] = $requirement;
                } else {
                    $missingRequirements[] = $requirement;
                }
            }

            $confidence = $maxScore > 0 ? $score / $maxScore : 1;
            $suitable = $confidence >= $config['minimum_confidence'] && empty($missingRequirements);

            $results[$purpose] = [
                'suitable' => $suitable,
                'confidence' => round($confidence, 2),
                'minimum_required' => $config['minimum_confidence'],
                'missing_requirements' => $missingRequirements,
                'partial_requirements' => $partialRequirements,
                'description' => $config['description'],
            ];
        }

        return $results;
    }

    /**
     * Check if a specific requirement is met.
     */
    protected function checkRequirement(string $requirement, array $data): string
    {
        switch ($requirement) {
            case 'reference_planes':
                $baselines = $data['baselines_identified'] ?? [];
                $consistency = $data['reference_consistency'] ?? 'unknown';
                if (count($baselines) >= 2 && $consistency !== 'inconsistent') {
                    return 'met';
                } elseif (count($baselines) >= 1) {
                    return 'partial';
                }
                return 'not_met';

            case 'all_dimensions':
                $dimCount = $data['dimension_count'] ?? 0;
                $flaggedCount = $data['flagged_dimensions'] ?? 0;
                $riskLevel = $data['dimension_risk_level'] ?? 'high';

                if ($dimCount >= 3 && $flaggedCount === 0 && $riskLevel === 'low') {
                    return 'met';
                } elseif ($dimCount >= 2 && $riskLevel !== 'high') {
                    return 'partial';
                }
                return 'not_met';

            case 'material_specs':
                return $data['has_material_specs'] ? 'met' : 'not_met';

            case 'construction_notes':
                $prodNotes = $data['production_notes'] ?? 0;
                if ($prodNotes >= 3) {
                    return 'met';
                } elseif ($prodNotes >= 1) {
                    return 'partial';
                }
                return 'not_met';

            case 'orientation_clarity':
                $orientation = $data['orientation'] ?? 'unknown';
                $confidence = $data['orientation_confidence'] ?? 0;

                if ($orientation !== 'unknown' && $confidence >= 0.8) {
                    return 'met';
                } elseif ($orientation !== 'unknown' && $confidence >= 0.6) {
                    return 'partial';
                }
                return 'not_met';

            case 'hardware_specs':
                return $data['has_hardware_specs'] ? 'met' : 'not_met';

            case 'finish_specs':
                return $data['has_finish_specs'] ? 'met' : 'not_met';

            default:
                return 'not_met';
        }
    }

    /**
     * Identify blockers that prevent processing.
     */
    protected function identifyBlockers(array $data): array
    {
        $blockers = [];

        // Critical blockers
        if (!$data['context_valid']) {
            $blockers[] = [
                'severity' => 'critical',
                'category' => 'context_analysis',
                'description' => 'Drawing context analysis failed validation',
                'impact' => 'Cannot determine drawing type or orientation',
            ];
        }

        if ($data['orientation'] === 'unknown') {
            $blockers[] = [
                'severity' => 'critical',
                'category' => 'orientation',
                'description' => 'Drawing orientation could not be determined',
                'impact' => 'Cannot establish reference planes for dimensions',
            ];
        }

        if ($data['dimension_count'] === 0) {
            $blockers[] = [
                'severity' => 'critical',
                'category' => 'dimensions',
                'description' => 'No dimensions found in drawing',
                'impact' => 'Cannot extract any measurements',
            ];
        }

        // Major blockers
        if (!empty($data['dimension_conflicts'])) {
            $conflictCount = count($data['dimension_conflicts']);
            $blockers[] = [
                'severity' => 'major',
                'category' => 'dimension_conflicts',
                'description' => "{$conflictCount} dimension conflict(s) detected",
                'impact' => 'Measurements may be incorrect - manual resolution required',
            ];
        }

        if ($data['dimension_risk_level'] === 'high') {
            $blockers[] = [
                'severity' => 'major',
                'category' => 'dimension_risk',
                'description' => 'High risk in dimension reference analysis',
                'impact' => 'Extracted dimensions may have incorrect reference points',
            ];
        }

        if (empty($data['baselines_identified'])) {
            $blockers[] = [
                'severity' => 'major',
                'category' => 'baselines',
                'description' => 'No reference baselines identified',
                'impact' => 'Cannot establish measurement origins',
            ];
        }

        // Minor blockers
        if (!$data['has_material_specs']) {
            $blockers[] = [
                'severity' => 'minor',
                'category' => 'material_specs',
                'description' => 'No material specifications found',
                'impact' => 'Material takeoff will be incomplete',
            ];
        }

        if ($data['unit_system'] === 'mixed') {
            $blockers[] = [
                'severity' => 'minor',
                'category' => 'units',
                'description' => 'Mixed unit systems detected',
                'impact' => 'Dimension conversion may be needed',
            ];
        }

        // Warnings
        if ($data['has_warnings']) {
            $blockers[] = [
                'severity' => 'warning',
                'category' => 'drawing_warnings',
                'description' => 'Drawing contains warning notes',
                'impact' => 'Review warnings before production',
            ];
        }

        if ($data['drawing_intent'] === 'conceptual') {
            $blockers[] = [
                'severity' => 'warning',
                'category' => 'drawing_intent',
                'description' => 'Drawing appears to be conceptual/preliminary',
                'impact' => 'Dimensions may not be final',
            ];
        }

        return $blockers;
    }

    /**
     * Generate recommendations based on blockers and suitability.
     */
    protected function generateRecommendations(array $blockers, array $suitability): array
    {
        $recommendations = [];

        // Address critical blockers first
        $criticalBlockers = array_filter($blockers, fn($b) => $b['severity'] === 'critical');
        foreach ($criticalBlockers as $blocker) {
            $recommendations[] = [
                'priority' => 'critical',
                'action' => "Resolve: {$blocker['description']}",
                'reason' => $blocker['impact'],
            ];
        }

        // Address major blockers
        $majorBlockers = array_filter($blockers, fn($b) => $b['severity'] === 'major');
        foreach ($majorBlockers as $blocker) {
            $recommendations[] = [
                'priority' => 'high',
                'action' => "Address: {$blocker['description']}",
                'reason' => $blocker['impact'],
            ];
        }

        // Suitability-specific recommendations
        foreach ($suitability as $purpose => $result) {
            if (!$result['suitable'] && !empty($result['missing_requirements'])) {
                $missing = implode(', ', $result['missing_requirements']);
                $recommendations[] = [
                    'priority' => 'medium',
                    'action' => "For {$purpose}: Add {$missing}",
                    'reason' => "Drawing does not meet requirements for {$result['description']}",
                ];
            }
        }

        // General recommendations
        if (empty($criticalBlockers) && empty($majorBlockers)) {
            $recommendations[] = [
                'priority' => 'low',
                'action' => 'Proceed with entity extraction',
                'reason' => 'Drawing passes validation gate',
            ];
        }

        return $recommendations;
    }

    /**
     * Perform AI validation for additional context.
     */
    protected function performAiValidation(
        string $imageBase64,
        string $mimeType,
        array $aggregatedData,
        array $blockers
    ): ?array {
        $systemPrompt = $this->buildValidationPrompt($aggregatedData, $blockers);

        try {
            $response = $this->callGeminiApi($systemPrompt, [
                'mimeType' => $mimeType,
                'data' => $imageBase64,
            ]);

            if (isset($response['error'])) {
                return null;
            }

            $text = $response['text'] ?? '';
            $text = preg_replace('/```json\s*/i', '', $text);
            $text = preg_replace('/```\s*/i', '', $text);

            return json_decode(trim($text), true);

        } catch (\Exception $e) {
            Log::error('DrawingIntentValidation AI error: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Build prompt for AI validation.
     */
    protected function buildValidationPrompt(array $data, array $blockers): string
    {
        $blockerList = '';
        if (!empty($blockers)) {
            $blockerList = "\n\nIDENTIFIED BLOCKERS:\n";
            foreach ($blockers as $b) {
                $blockerList .= "- [{$b['severity']}] {$b['description']}\n";
            }
        }

        return <<<PROMPT
You are validating a cabinet construction drawing for production suitability.

PRIOR ANALYSIS SUMMARY:
- View Type: {$data['view_type']}
- Orientation: {$data['orientation']} (confidence: {$data['orientation_confidence']})
- Drawing Intent: {$data['drawing_intent']}
- Dimensions Found: {$data['dimension_count']}
- Flagged Dimensions: {$data['flagged_dimensions']}
- Reference Consistency: {$data['reference_consistency']}
- Material Specs Present: {$data['has_material_specs']}
{$blockerList}

TASK:
Review the drawing and validate the prior analysis. Specifically:
1. Confirm or correct the orientation assessment
2. Identify any additional blockers not caught by automated analysis
3. Assess overall production readiness
4. Note any critical information that may have been missed

Respond ONLY with valid JSON:

{
  "orientation_confirmed": true,
  "orientation_correction": null,
  "additional_blockers": [],
  "missed_information": [],
  "production_readiness": {
    "score": 0.85,
    "assessment": "Ready for production with minor clarifications needed"
  },
  "human_review_needed": false,
  "notes": "Additional observations if any"
}
PROMPT;
    }

    /**
     * Call Gemini API for validation.
     */
    protected function callGeminiApi(string $systemPrompt, array $image): array
    {
        $contents = [
            [
                'role' => 'user',
                'parts' => [
                    ['text' => 'Validate this drawing for production suitability based on the prior analysis.'],
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
                'maxOutputTokens' => 2048,
            ],
        ];

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
                    return ['error' => 'No response generated'];
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

            return ['error' => 'API error: ' . ($response->json('error.message') ?? 'Unknown error')];

        } catch (\Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }

    /**
     * Determine if entity extraction should proceed.
     */
    protected function determineCanProceed(array $suitability, array $blockers): array
    {
        $criticalBlockers = array_filter($blockers, fn($b) => $b['severity'] === 'critical');
        $majorBlockers = array_filter($blockers, fn($b) => $b['severity'] === 'major');

        // Block extraction if critical issues
        if (!empty($criticalBlockers)) {
            return [
                'extraction_allowed' => false,
                'reason' => 'Critical blockers prevent extraction',
                'blocker_count' => count($criticalBlockers),
                'allowed_purposes' => [],
            ];
        }

        // Determine which purposes are allowed
        $allowedPurposes = [];
        foreach ($suitability as $purpose => $result) {
            if ($result['suitable']) {
                $allowedPurposes[] = $purpose;
            }
        }

        // Always allow verification if no critical blockers
        if (!in_array('verification_only', $allowedPurposes)) {
            $allowedPurposes[] = 'verification_only';
        }

        return [
            'extraction_allowed' => true,
            'reason' => empty($majorBlockers)
                ? 'All validation checks passed'
                : 'Extraction allowed with caution - major blockers present',
            'blocker_count' => count($majorBlockers),
            'allowed_purposes' => $allowedPurposes,
            'warnings' => array_map(fn($b) => $b['description'], $majorBlockers),
        ];
    }

    /**
     * Create a summary suitable for logging or display.
     */
    public function summarizeValidation(array $validationResult): string
    {
        $lines = [];

        $lines[] = "Drawing Intent Validation";
        $lines[] = str_repeat('=', 40);

        // Can proceed status
        $proceed = $validationResult['can_proceed'] ?? [];
        $allowed = $proceed['extraction_allowed'] ?? false;
        $lines[] = "";
        $lines[] = "EXTRACTION: " . ($allowed ? '✓ ALLOWED' : '✗ BLOCKED');
        $lines[] = "Reason: " . ($proceed['reason'] ?? 'Unknown');

        // Allowed purposes
        if (!empty($proceed['allowed_purposes'])) {
            $lines[] = "";
            $lines[] = "Allowed Purposes:";
            foreach ($proceed['allowed_purposes'] as $purpose) {
                $lines[] = "  ✓ {$purpose}";
            }
        }

        // Suitability summary
        $lines[] = "";
        $lines[] = "Suitability Assessment:";
        foreach ($validationResult['suitability'] ?? [] as $purpose => $result) {
            $status = $result['suitable'] ? '✓' : '✗';
            $conf = round($result['confidence'] * 100);
            $lines[] = "  {$status} {$purpose}: {$conf}%";
            if (!empty($result['missing_requirements'])) {
                $missing = implode(', ', $result['missing_requirements']);
                $lines[] = "    Missing: {$missing}";
            }
        }

        // Blockers
        $blockers = $validationResult['blockers'] ?? [];
        if (!empty($blockers)) {
            $lines[] = "";
            $lines[] = "Blockers:";
            foreach ($blockers as $blocker) {
                $severity = strtoupper($blocker['severity']);
                $lines[] = "  [{$severity}] {$blocker['description']}";
            }
        }

        // Recommendations
        $recommendations = $validationResult['recommendations'] ?? [];
        if (!empty($recommendations)) {
            $lines[] = "";
            $lines[] = "Recommendations:";
            foreach (array_slice($recommendations, 0, 5) as $rec) {
                $priority = strtoupper($rec['priority']);
                $lines[] = "  [{$priority}] {$rec['action']}";
            }
        }

        return implode("\n", $lines);
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
            'processing_purposes' => array_keys(self::PROCESSING_PURPOSES),
            'requirement_categories' => self::REQUIREMENT_CATEGORIES,
            'blocker_severity' => self::BLOCKER_SEVERITY,
            default => [],
        };
    }
}
