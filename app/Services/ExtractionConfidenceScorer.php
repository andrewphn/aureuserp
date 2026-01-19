<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

/**
 * ExtractionConfidenceScorer - Calculate confidence scores for Rhino extractions
 *
 * Implements a weighted scoring system based on:
 * - 40%: Dimension completeness (W/H/D)
 * - 20%: Dimension validity (standard cabinet sizes)
 * - 15%: Component detection (drawers, doors, etc.)
 * - 15%: Multi-view correlation
 * - 10%: Label matching
 *
 * Score thresholds:
 * - High (80%+): Auto-approve
 * - Medium (50-79%): AI interpretation + review
 * - Low (<50%): Require human review
 *
 * @author TCS Woodwork
 * @since January 2026
 */
class ExtractionConfidenceScorer
{
    /**
     * Weight constants for scoring factors
     */
    protected const WEIGHT_DIMENSIONS = 0.40;
    protected const WEIGHT_VALIDITY = 0.20;
    protected const WEIGHT_COMPONENTS = 0.15;
    protected const WEIGHT_MULTI_VIEW = 0.15;
    protected const WEIGHT_LABELS = 0.10;

    /**
     * Standard cabinet dimension ranges (in inches)
     */
    protected const STANDARD_WIDTHS = [9, 12, 15, 18, 21, 24, 27, 30, 33, 36, 42, 48, 60, 72, 84];
    protected const STANDARD_HEIGHTS = [30, 34.5, 36, 42, 48, 84, 90, 96];
    protected const STANDARD_DEPTHS = [12, 15, 18, 21, 24];

    /**
     * Tolerance for dimension matching (inches)
     */
    protected const DIMENSION_TOLERANCE = 1.0;

    /**
     * Calculate confidence score for extracted cabinet data
     *
     * @param array $extractedCabinet Cabinet data from RhinoDataExtractor
     * @return array Score details with total and breakdown
     */
    public function calculateScore(array $extractedCabinet): array
    {
        $scores = [
            'dimensions' => $this->scoreDimensions($extractedCabinet),
            'validity' => $this->scoreValidity($extractedCabinet),
            'components' => $this->scoreComponents($extractedCabinet),
            'multi_view' => $this->scoreMultiView($extractedCabinet),
            'labels' => $this->scoreLabels($extractedCabinet),
        ];

        // Calculate weighted total
        $total = ($scores['dimensions']['score'] * self::WEIGHT_DIMENSIONS) +
                 ($scores['validity']['score'] * self::WEIGHT_VALIDITY) +
                 ($scores['components']['score'] * self::WEIGHT_COMPONENTS) +
                 ($scores['multi_view']['score'] * self::WEIGHT_MULTI_VIEW) +
                 ($scores['labels']['score'] * self::WEIGHT_LABELS);

        $level = $this->getConfidenceLevel($total);

        $result = [
            'total' => round($total, 2),
            'level' => $level,
            'weights' => [
                'dimensions' => self::WEIGHT_DIMENSIONS,
                'validity' => self::WEIGHT_VALIDITY,
                'components' => self::WEIGHT_COMPONENTS,
                'multi_view' => self::WEIGHT_MULTI_VIEW,
                'labels' => self::WEIGHT_LABELS,
            ],
            'breakdown' => $scores,
            'missing_fields' => $this->identifyMissingFields($extractedCabinet),
            'issues' => $this->identifyIssues($extractedCabinet),
            'recommendations' => $this->generateRecommendations($scores, $level),
        ];

        Log::debug('ExtractionConfidenceScorer: Calculated score', [
            'cabinet' => $extractedCabinet['name'] ?? 'unknown',
            'total' => $result['total'],
            'level' => $result['level'],
        ]);

        return $result;
    }

    /**
     * Score dimension completeness (40% weight)
     */
    protected function scoreDimensions(array $cabinet): array
    {
        $score = 0;
        $details = [];

        // Width (most important)
        if (!empty($cabinet['width']) && $cabinet['width'] > 0) {
            $score += 40;
            $details['width'] = ['found' => true, 'value' => $cabinet['width']];
        } else {
            $details['width'] = ['found' => false, 'reason' => 'No width dimension found'];
        }

        // Height
        if (!empty($cabinet['height']) && $cabinet['height'] > 0) {
            $score += 35;
            $details['height'] = ['found' => true, 'value' => $cabinet['height']];
        } else {
            $details['height'] = ['found' => false, 'reason' => 'No height dimension found'];
        }

        // Depth
        if (!empty($cabinet['depth']) && $cabinet['depth'] > 0) {
            $score += 25;
            $details['depth'] = ['found' => true, 'value' => $cabinet['depth']];
        } else {
            $details['depth'] = ['found' => false, 'reason' => 'No depth dimension found in plan view'];
        }

        return [
            'score' => $score,
            'max' => 100,
            'details' => $details,
        ];
    }

    /**
     * Score dimension validity against standard sizes (20% weight)
     */
    protected function scoreValidity(array $cabinet): array
    {
        $score = 0;
        $details = [];
        $validChecks = 0;
        $passedChecks = 0;

        // Check width against standards
        if (!empty($cabinet['width'])) {
            $validChecks++;
            $isStandard = $this->isNearStandardDimension($cabinet['width'], self::STANDARD_WIDTHS);
            if ($isStandard) {
                $passedChecks++;
                $details['width'] = ['valid' => true, 'note' => 'Standard width'];
            } else {
                $details['width'] = [
                    'valid' => false,
                    'note' => "Non-standard width {$cabinet['width']}\" - verify",
                    'nearest_standard' => $this->findNearestStandard($cabinet['width'], self::STANDARD_WIDTHS),
                ];
            }
        }

        // Check height against standards
        if (!empty($cabinet['height'])) {
            $validChecks++;
            $isStandard = $this->isNearStandardDimension($cabinet['height'], self::STANDARD_HEIGHTS);
            if ($isStandard) {
                $passedChecks++;
                $details['height'] = ['valid' => true, 'note' => 'Standard height'];
            } else {
                $details['height'] = [
                    'valid' => false,
                    'note' => "Non-standard height {$cabinet['height']}\" - verify",
                    'nearest_standard' => $this->findNearestStandard($cabinet['height'], self::STANDARD_HEIGHTS),
                ];
            }
        }

        // Check depth against standards
        if (!empty($cabinet['depth'])) {
            $validChecks++;
            $isStandard = $this->isNearStandardDimension($cabinet['depth'], self::STANDARD_DEPTHS);
            if ($isStandard) {
                $passedChecks++;
                $details['depth'] = ['valid' => true, 'note' => 'Standard depth'];
            } else {
                $details['depth'] = [
                    'valid' => false,
                    'note' => "Non-standard depth {$cabinet['depth']}\" - verify",
                    'nearest_standard' => $this->findNearestStandard($cabinet['depth'], self::STANDARD_DEPTHS),
                ];
            }
        }

        // Check for impossible dimensions
        if (!empty($cabinet['width']) && !empty($cabinet['height'])) {
            $ratio = $cabinet['height'] / $cabinet['width'];
            if ($ratio > 10 || $ratio < 0.1) {
                $details['ratio'] = [
                    'valid' => false,
                    'note' => "Unusual height/width ratio ({$ratio}) - may indicate extraction error",
                ];
            }
        }

        // Calculate score
        if ($validChecks > 0) {
            $score = ($passedChecks / $validChecks) * 100;
        }

        return [
            'score' => round($score, 2),
            'max' => 100,
            'details' => $details,
        ];
    }

    /**
     * Score component detection (15% weight)
     */
    protected function scoreComponents(array $cabinet): array
    {
        $score = 0;
        $details = [];
        $components = $cabinet['components'] ?? [];

        // Has any components detected
        if (!empty($components['detected_components'])) {
            $score += 30;
            $details['detection'] = [
                'found' => true,
                'count' => count($components['detected_components']),
            ];
        } else {
            $details['detection'] = ['found' => false, 'reason' => 'No component labels detected'];
        }

        // Has drawer count
        if (($components['drawer_count'] ?? 0) > 0) {
            $score += 25;
            $details['drawers'] = ['count' => $components['drawer_count']];
        }

        // Has door count
        if (($components['door_count'] ?? 0) > 0) {
            $score += 25;
            $details['doors'] = ['count' => $components['door_count']];
        }

        // Has special components (U-shaped drawer, lazy susan)
        if ($components['has_u_shaped_drawer'] ?? false) {
            $score += 10;
            $details['special'][] = 'U-shaped drawer';
        }
        if ($components['has_lazy_susan'] ?? false) {
            $score += 10;
            $details['special'][] = 'Lazy Susan';
        }

        return [
            'score' => min($score, 100),
            'max' => 100,
            'details' => $details,
        ];
    }

    /**
     * Score multi-view correlation (15% weight)
     */
    protected function scoreMultiView(array $cabinet): array
    {
        $score = 0;
        $details = [];

        // Has elevation view
        if (!empty($cabinet['elevation_view'])) {
            $score += 40;
            $details['elevation'] = ['found' => true, 'label' => $cabinet['elevation_view']['label'] ?? null];
        } else {
            $details['elevation'] = ['found' => false, 'reason' => 'No elevation view matched'];
        }

        // Has plan view
        if (!empty($cabinet['plan_view'])) {
            $score += 40;
            $details['plan'] = ['found' => true, 'label' => $cabinet['plan_view']['label'] ?? null];
        } else {
            $details['plan'] = ['found' => false, 'reason' => 'No plan view matched'];
        }

        // Width consistency between views
        $elevationWidth = $this->getMaxHorizontalDimension($cabinet['elevation_dims'] ?? []);
        $planWidth = $this->getMaxHorizontalDimension($cabinet['plan_dims'] ?? []);

        if ($elevationWidth && $planWidth) {
            $diff = abs($elevationWidth - $planWidth);
            if ($diff <= self::DIMENSION_TOLERANCE) {
                $score += 20;
                $details['width_correlation'] = ['match' => true, 'difference' => $diff];
            } else {
                $details['width_correlation'] = [
                    'match' => false,
                    'elevation_width' => $elevationWidth,
                    'plan_width' => $planWidth,
                    'difference' => $diff,
                ];
            }
        }

        return [
            'score' => round($score, 2),
            'max' => 100,
            'details' => $details,
        ];
    }

    /**
     * Score label matching (10% weight)
     */
    protected function scoreLabels(array $cabinet): array
    {
        $score = 0;
        $details = [];

        // Has group name
        if (!empty($cabinet['name'])) {
            $score += 40;
            $details['group_name'] = ['found' => true, 'name' => $cabinet['name']];
        }

        // Has parsed identifier
        if (!empty($cabinet['identifier'])) {
            $score += 30;
            $details['identifier'] = ['found' => true, 'value' => $cabinet['identifier']];
        }

        // Has matching labels
        $labels = $cabinet['labels'] ?? [];
        if (!empty($labels)) {
            $score += 30;
            $details['label_count'] = count($labels);
        } else {
            $details['labels'] = ['found' => false, 'reason' => 'No text labels matched to this cabinet'];
        }

        return [
            'score' => round($score, 2),
            'max' => 100,
            'details' => $details,
        ];
    }

    /**
     * Get confidence level from score
     */
    protected function getConfidenceLevel(float $score): string
    {
        if ($score >= 80) {
            return 'high';
        }
        if ($score >= 50) {
            return 'medium';
        }
        return 'low';
    }

    /**
     * Check if a dimension is near a standard size
     */
    protected function isNearStandardDimension(float $value, array $standards): bool
    {
        foreach ($standards as $standard) {
            if (abs($value - $standard) <= self::DIMENSION_TOLERANCE) {
                return true;
            }
        }
        return false;
    }

    /**
     * Find nearest standard dimension
     */
    protected function findNearestStandard(float $value, array $standards): float
    {
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
     * Get maximum horizontal dimension from a set
     */
    protected function getMaxHorizontalDimension(array $dimensions): ?float
    {
        $horizontals = array_filter($dimensions, fn($d) =>
            ($d['orientation'] ?? '') === 'horizontal'
        );

        if (empty($horizontals)) {
            return null;
        }

        $values = array_map(fn($d) => $d['parsed_value'] ?? $d['value'] ?? 0, $horizontals);
        return max($values);
    }

    /**
     * Identify missing required fields
     */
    protected function identifyMissingFields(array $cabinet): array
    {
        $missing = [];

        if (empty($cabinet['width'])) {
            $missing[] = [
                'field' => 'width',
                'importance' => 'critical',
                'suggestion' => 'Check elevation view for horizontal dimension',
            ];
        }

        if (empty($cabinet['height'])) {
            $missing[] = [
                'field' => 'height',
                'importance' => 'critical',
                'suggestion' => 'Check elevation view for vertical dimension',
            ];
        }

        if (empty($cabinet['depth'])) {
            $missing[] = [
                'field' => 'depth',
                'importance' => 'high',
                'suggestion' => 'Check plan view for depth dimension',
            ];
        }

        $components = $cabinet['components'] ?? [];
        if (($components['drawer_count'] ?? 0) === 0 && ($components['door_count'] ?? 0) === 0) {
            $missing[] = [
                'field' => 'components',
                'importance' => 'medium',
                'suggestion' => 'No doors or drawers detected - verify cabinet type',
            ];
        }

        return $missing;
    }

    /**
     * Identify potential issues with the extraction
     */
    protected function identifyIssues(array $cabinet): array
    {
        $issues = [];

        // Check for unrealistic dimensions
        $width = $cabinet['width'] ?? 0;
        $height = $cabinet['height'] ?? 0;
        $depth = $cabinet['depth'] ?? 0;

        if ($width > 0 && ($width < 6 || $width > 120)) {
            $issues[] = [
                'type' => 'dimension_out_of_range',
                'field' => 'width',
                'value' => $width,
                'message' => 'Width outside typical cabinet range (6-120")',
            ];
        }

        if ($height > 0 && ($height < 12 || $height > 108)) {
            $issues[] = [
                'type' => 'dimension_out_of_range',
                'field' => 'height',
                'value' => $height,
                'message' => 'Height outside typical cabinet range (12-108")',
            ];
        }

        if ($depth > 0 && ($depth < 6 || $depth > 36)) {
            $issues[] = [
                'type' => 'dimension_out_of_range',
                'field' => 'depth',
                'value' => $depth,
                'message' => 'Depth outside typical cabinet range (6-36")',
            ];
        }

        // Check for bounding box mismatch
        if (!empty($cabinet['bounding_box'])) {
            $bbox = $cabinet['bounding_box'];
            $bboxWidth = round(($bbox['max'][0] ?? 0) - ($bbox['min'][0] ?? 0), 2);

            if ($width > 0 && abs($bboxWidth - $width) > 5) {
                $issues[] = [
                    'type' => 'bounding_box_mismatch',
                    'field' => 'width',
                    'extracted' => $width,
                    'bounding_box' => $bboxWidth,
                    'message' => 'Extracted width differs significantly from bounding box',
                ];
            }
        }

        return $issues;
    }

    /**
     * Generate recommendations based on scores
     */
    protected function generateRecommendations(array $scores, string $level): array
    {
        $recommendations = [];

        // Dimension recommendations
        if ($scores['dimensions']['score'] < 70) {
            $recommendations[] = [
                'priority' => 'high',
                'area' => 'dimensions',
                'action' => 'Verify missing dimensions manually from Rhino drawing',
            ];
        }

        // Validity recommendations
        if ($scores['validity']['score'] < 50) {
            $recommendations[] = [
                'priority' => 'medium',
                'area' => 'validity',
                'action' => 'Check if non-standard dimensions are intentional',
            ];
        }

        // Multi-view recommendations
        if ($scores['multi_view']['score'] < 50) {
            $recommendations[] = [
                'priority' => 'medium',
                'area' => 'views',
                'action' => 'Ensure cabinet appears in both Plan and Elevation views',
            ];
        }

        // Level-based recommendations
        if ($level === 'low') {
            $recommendations[] = [
                'priority' => 'high',
                'area' => 'review',
                'action' => 'Manual review required before import',
            ];
        } elseif ($level === 'medium') {
            $recommendations[] = [
                'priority' => 'medium',
                'area' => 'review',
                'action' => 'AI interpretation suggested - review corrections before import',
            ];
        }

        return $recommendations;
    }

    /**
     * Batch calculate scores for multiple cabinets
     *
     * @param array $cabinets Array of extracted cabinet data
     * @return array Scores for each cabinet
     */
    public function batchCalculate(array $cabinets): array
    {
        $results = [];

        foreach ($cabinets as $index => $cabinet) {
            $results[$index] = [
                'cabinet_name' => $cabinet['name'] ?? "Cabinet {$index}",
                'score' => $this->calculateScore($cabinet),
            ];
        }

        // Add summary
        $totalScores = array_column(array_column($results, 'score'), 'total');
        $summary = [
            'total_cabinets' => count($cabinets),
            'average_score' => count($totalScores) > 0 ? round(array_sum($totalScores) / count($totalScores), 2) : 0,
            'high_confidence' => count(array_filter($results, fn($r) => $r['score']['level'] === 'high')),
            'medium_confidence' => count(array_filter($results, fn($r) => $r['score']['level'] === 'medium')),
            'low_confidence' => count(array_filter($results, fn($r) => $r['score']['level'] === 'low')),
        ];

        return [
            'cabinets' => $results,
            'summary' => $summary,
        ];
    }
}
