<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Webkul\Project\Models\ConstructionTemplate;

/**
 * AIDimensionInterpreter - Prepare data for AI interpretation via Claude Code
 *
 * This service prepares cabinet extraction data for AI interpretation.
 * The actual AI processing happens via Claude Code MCP tools, not direct API calls.
 *
 * Flow:
 * 1. Extraction runs and creates review items with raw data
 * 2. Claude Code (via tcs-erp MCP) calls interpret_cabinet_dimensions tool
 * 3. This service provides the context and construction standards
 * 4. Claude Code returns the interpretation directly
 *
 * For background processing without Claude Code, use the fallback rule-based logic.
 *
 * @author TCS Woodwork
 * @since January 2026
 */
class AIDimensionInterpreter
{
    /**
     * Standard cabinet specifications for reference
     */
    protected array $standardSpecs = [
        'base_cabinet' => [
            'height' => 34.5,
            'depths' => [21, 24],
            'widths' => [9, 12, 15, 18, 21, 24, 27, 30, 33, 36, 42, 48],
        ],
        'wall_cabinet' => [
            'heights' => [12, 15, 18, 24, 30, 36, 42],
            'depths' => [12, 15],
            'widths' => [9, 12, 15, 18, 21, 24, 27, 30, 33, 36, 42, 48],
        ],
        'tall_cabinet' => [
            'heights' => [84, 90, 96],
            'depths' => [21, 24],
            'widths' => [18, 24, 30, 36],
        ],
        'vanity' => [
            'heights' => [30, 32, 34, 36],
            'depths' => [18, 21],
            'widths' => [24, 30, 36, 42, 48, 60, 72],
        ],
    ];

    /**
     * Prepare interpretation context for Claude Code MCP
     *
     * This generates the full context that Claude Code needs to interpret
     * the cabinet dimensions. The actual AI processing happens in Claude Code.
     *
     * @param array $extractedCabinet Cabinet data from RhinoDataExtractor
     * @param array $confidenceScore Score from ExtractionConfidenceScorer
     * @param int|null $templateId Construction template ID for standards
     * @return array Context for AI interpretation
     */
    public function prepareContext(array $extractedCabinet, array $confidenceScore, ?int $templateId = null): array
    {
        $constructionStandards = $this->getConstructionStandards($templateId);

        return [
            'cabinet' => $extractedCabinet,
            'confidence_score' => $confidenceScore,
            'construction_standards' => $constructionStandards,
            'standard_specs' => $this->standardSpecs,
            'prompt' => $this->buildPrompt($extractedCabinet, $confidenceScore, $templateId),
        ];
    }

    /**
     * Build the AI prompt for dimension interpretation
     *
     * This prompt is used by Claude Code when the interpret_cabinet_dimensions
     * MCP tool is called. It provides all context needed for interpretation.
     */
    public function buildPrompt(array $cabinet, array $score, ?int $templateId = null): string
    {
        $constructionStandards = $this->getConstructionStandards($templateId);

        $cabinetJson = json_encode($cabinet, JSON_PRETTY_PRINT);
        $scoreJson = json_encode($score, JSON_PRETTY_PRINT);
        $standardsJson = json_encode($constructionStandards, JSON_PRETTY_PRINT);
        $specJson = json_encode($this->standardSpecs, JSON_PRETTY_PRINT);

        return <<<PROMPT
You are an expert cabinet shop technician at TCS Woodwork. Analyze the following extracted cabinet data from a Rhino 3D drawing and provide corrections and interpretations.

## Extracted Cabinet Data
```json
{$cabinetJson}
```

## Confidence Score Analysis
```json
{$scoreJson}
```

## TCS Construction Standards
```json
{$standardsJson}
```

## Standard Cabinet Specifications
```json
{$specJson}
```

## Your Task
1. Analyze the extracted dimensions and components
2. Identify any likely errors or misinterpretations
3. Suggest corrections based on:
   - Standard cabinet sizes
   - TCS construction standards
   - The cabinet type/name (e.g., "Vanity" suggests specific depth/height)
   - Component counts (drawer/door ratios)
4. Estimate missing dimensions if possible

## Response Format
Respond with a JSON object containing:
```json
{
  "interpretation": {
    "cabinet_type": "base|wall|tall|vanity|corner|special",
    "corrected_dimensions": {
      "width": <number or null>,
      "height": <number or null>,
      "depth": <number or null>
    },
    "dimension_sources": {
      "width": "extracted|inferred|standard",
      "height": "extracted|inferred|standard",
      "depth": "extracted|inferred|standard"
    },
    "corrections_applied": [
      {"field": "field_name", "original": <value>, "corrected": <value>, "reason": "explanation"}
    ],
    "inferences": [
      {"field": "field_name", "value": <value>, "reasoning": "explanation", "confidence": 0-100}
    ]
  },
  "component_analysis": {
    "drawer_count": <number>,
    "door_count": <number>,
    "has_false_front": <boolean>,
    "component_reasoning": "explanation of component detection"
  },
  "improved_confidence": {
    "score": <0-100>,
    "improvement_reason": "explanation of score change"
  },
  "warnings": ["list of any concerns or issues"],
  "recommendation": "approve|review|reject"
}
```
PROMPT;
    }

    /**
     * Get construction standards from template
     */
    public function getConstructionStandards(?int $templateId = null): array
    {
        if ($templateId) {
            $template = ConstructionTemplate::find($templateId);
            if ($template) {
                return [
                    'face_frame_stile_width' => $template->face_frame_stile_width_inches,
                    'face_frame_rail_width' => $template->face_frame_rail_width_inches,
                    'door_gap' => $template->face_frame_door_gap_inches,
                    'material_thickness' => $template->material_thickness_inches ?? 0.75,
                    'back_panel_thickness' => $template->back_panel_thickness_inches ?? 0.25,
                ];
            }
        }

        // Default TCS standards
        return [
            'face_frame_stile_width' => 1.75,
            'face_frame_rail_width' => 1.5,
            'door_gap' => 0.125,
            'material_thickness' => 0.75,
            'back_panel_thickness' => 0.25,
        ];
    }

    /**
     * Interpret using rule-based fallback (no AI required)
     *
     * Use this when:
     * - Processing in background queue without Claude Code
     * - AI interpretation is not needed (high confidence extractions)
     * - Quick validation is sufficient
     *
     * @param array $cabinet Extracted cabinet data
     * @param array $score Confidence score
     * @return array Interpretation results
     */
    public function interpretWithFallback(array $cabinet, array $score): array
    {
        $cabinetType = $this->inferCabinetType($cabinet);
        $inferredDimensions = $this->inferMissingDimensions($cabinet, $cabinetType);

        $corrections = [];
        $inferences = [];

        // Check if we inferred any dimensions
        foreach (['width', 'height', 'depth'] as $dim) {
            if (empty($cabinet[$dim]) && !empty($inferredDimensions[$dim])) {
                $inferences[] = [
                    'field' => $dim,
                    'value' => $inferredDimensions[$dim],
                    'reasoning' => "Inferred from {$cabinetType} standards",
                    'confidence' => 40,
                ];
            }
        }

        // Merge original with inferred
        $correctedDimensions = [
            'width' => $cabinet['width'] ?? $inferredDimensions['width'] ?? null,
            'height' => $cabinet['height'] ?? $inferredDimensions['height'] ?? null,
            'depth' => $cabinet['depth'] ?? $inferredDimensions['depth'] ?? null,
        ];

        return [
            'success' => true,
            'method' => 'fallback',
            'cabinet_type' => $cabinetType,
            'corrected_dimensions' => $correctedDimensions,
            'dimension_sources' => [
                'width' => isset($cabinet['width']) ? 'extracted' : 'inferred',
                'height' => isset($cabinet['height']) ? 'extracted' : 'inferred',
                'depth' => isset($cabinet['depth']) ? 'extracted' : 'inferred',
            ],
            'corrections' => $corrections,
            'inferences' => $inferences,
            'component_analysis' => [
                'drawer_count' => $cabinet['components']['drawer_count'] ?? 0,
                'door_count' => $cabinet['components']['door_count'] ?? 0,
                'has_false_front' => false,
                'reasoning' => 'Rule-based analysis',
            ],
            'improved_confidence' => [
                'score' => $score['total'] ?? 50,
                'reason' => 'Rule-based interpretation applied',
            ],
            'warnings' => [],
            'recommendation' => ($score['total'] ?? 50) >= 80 ? 'approve' : 'review',
        ];
    }

    /**
     * Infer cabinet type from name and components
     */
    protected function inferCabinetType(array $cabinet): string
    {
        $name = strtolower($cabinet['name'] ?? '');
        $identifier = strtolower($cabinet['identifier'] ?? '');

        // Check name/identifier for type hints
        if (str_contains($name, 'van') || str_contains($identifier, 'van')) {
            return 'vanity';
        }
        if (str_contains($name, 'tall') || str_contains($name, 'pantry')) {
            return 'tall_cabinet';
        }
        if (str_contains($name, 'wall') || str_contains($name, 'upper')) {
            return 'wall_cabinet';
        }

        // Check height for type hints
        $height = $cabinet['height'] ?? 0;
        if ($height >= 80) {
            return 'tall_cabinet';
        }
        if ($height > 0 && $height <= 42) {
            return 'wall_cabinet';
        }

        return 'base_cabinet';
    }

    /**
     * Infer missing dimensions based on cabinet type
     */
    protected function inferMissingDimensions(array $cabinet, string $type): array
    {
        $specs = $this->standardSpecs[$type] ?? $this->standardSpecs['base_cabinet'];
        $inferred = [];

        // Infer height
        if (empty($cabinet['height'])) {
            if (isset($specs['height'])) {
                $inferred['height'] = $specs['height'];
            } elseif (isset($specs['heights'])) {
                // Use most common height for type
                $inferred['height'] = $specs['heights'][count($specs['heights']) - 1];
            }
        }

        // Infer depth
        if (empty($cabinet['depth'])) {
            if (isset($specs['depths'])) {
                // Use deepest standard for type
                $inferred['depth'] = max($specs['depths']);
            }
        }

        // Width is harder to infer - use bounding box if available
        if (empty($cabinet['width']) && !empty($cabinet['bounding_box'])) {
            $bbox = $cabinet['bounding_box'];
            $bboxWidth = round(($bbox['max'][0] ?? 0) - ($bbox['min'][0] ?? 0), 2);
            if ($bboxWidth >= 6 && $bboxWidth <= 96) {
                // Round to nearest standard width
                $nearestWidth = $this->findNearestStandard($bboxWidth, $specs['widths'] ?? [36]);
                $inferred['width'] = $nearestWidth;
            }
        }

        return $inferred;
    }

    /**
     * Find nearest standard dimension
     */
    protected function findNearestStandard(float $value, array $standards): float
    {
        if (empty($standards)) {
            return $value;
        }

        $nearest = $standards[0];
        $minDiff = abs($value - $standards[0]);

        foreach ($standards as $standard) {
            $diff = abs($value - $standard);
            if ($diff < $minDiff) {
                $minDiff = $diff;
                $nearest = $standard;
            }
        }

        return $nearest;
    }

    /**
     * Get standard specifications
     */
    public function getStandardSpecs(): array
    {
        return $this->standardSpecs;
    }

    /**
     * Batch prepare contexts for multiple cabinets
     *
     * @param array $cabinets Array of extracted cabinet data
     * @param array $scores Array of confidence scores
     * @param int|null $templateId Construction template ID
     * @return array Contexts for each cabinet
     */
    public function batchPrepareContexts(array $cabinets, array $scores, ?int $templateId = null): array
    {
        $contexts = [];

        foreach ($cabinets as $index => $cabinet) {
            $score = $scores[$index] ?? ['total' => 50];
            $contexts[$index] = [
                'cabinet_name' => $cabinet['name'] ?? "Cabinet {$index}",
                'original_confidence' => $score['total'] ?? 50,
                'context' => $this->prepareContext($cabinet, $score, $templateId),
            ];
        }

        return $contexts;
    }
}
