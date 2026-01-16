<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

/**
 * Produces final audit report for drawing analysis pipeline.
 * This is the TENTH and FINAL step in the drawing analysis pipeline.
 *
 * Purpose: Compile all assumptions, inferred standards, unresolved conflicts,
 * and determine readiness level for CNC / production.
 */
class VerificationAuditService
{
    // Verification levels
    public const VERIFICATION_LEVELS = [
        'VERIFIED' => 'All data explicitly derived from drawing with no assumptions',
        'VERIFIED_WITH_ASSUMPTIONS' => 'Data verified but includes documented assumptions',
        'NOT_VERIFIED' => 'Critical data missing or unresolved conflicts exist',
    ];

    // Production readiness categories
    public const READINESS_CATEGORIES = [
        'cnc_ready' => 'Ready for CNC file generation',
        'production_ready' => 'Ready for shop production',
        'material_takeoff_ready' => 'Ready for material estimation',
        'verification_only' => 'Suitable for field verification only',
        'not_ready' => 'Not ready for any production use',
    ];

    // Assumption categories
    public const ASSUMPTION_CATEGORIES = [
        'material_thickness' => 'Material thickness assumption',
        'gap_standard' => 'Gap/reveal standard assumption',
        'reference_plane' => 'Reference plane assumption',
        'hardware_clearance' => 'Hardware clearance assumption',
        'construction_method' => 'Construction method assumption',
        'missing_dimension' => 'Missing dimension substituted',
    ];

    /**
     * Generate final audit report.
     *
     * @param array $allResults Combined results from all pipeline steps
     * @return array Complete audit report
     */
    public function generateAuditReport(array $allResults): array
    {
        // Extract all results
        $contextResult = $allResults['context'] ?? [];
        $dimensionRefResult = $allResults['dimension_references'] ?? [];
        $notesResult = $allResults['notes'] ?? [];
        $validationResult = $allResults['validation'] ?? [];
        $entityResult = $allResults['entities'] ?? [];
        $verificationResult = $allResults['verification'] ?? [];
        $alignmentResult = $allResults['alignment'] ?? [];
        $constraintResult = $allResults['constraints'] ?? [];
        $componentResult = $allResults['components'] ?? [];

        // Compile all assumptions
        $assumptions = $this->compileAssumptions($allResults);

        // Compile inferred standards
        $inferredStandards = $this->compileInferredStandards($allResults);

        // Compile unresolved conflicts
        $unresolvedConflicts = $this->compileUnresolvedConflicts($allResults);

        // Determine verification level
        $verificationLevel = $this->determineVerificationLevel(
            $assumptions,
            $unresolvedConflicts
        );

        // Determine production readiness
        $readiness = $this->determineProductionReadiness(
            $verificationLevel,
            $unresolvedConflicts,
            $componentResult
        );

        // Generate step-by-step audit trail
        $auditTrail = $this->generateAuditTrail($allResults);

        // Generate recommendations
        $recommendations = $this->generateRecommendations(
            $verificationLevel,
            $assumptions,
            $unresolvedConflicts
        );

        return [
            'success' => true,
            'verification_level' => $verificationLevel,
            'readiness' => $readiness,
            'assumptions' => $assumptions,
            'inferred_standards' => $inferredStandards,
            'unresolved_conflicts' => $unresolvedConflicts,
            'audit_trail' => $auditTrail,
            'recommendations' => $recommendations,
            'summary' => $this->generateSummary(
                $verificationLevel,
                $readiness,
                $assumptions,
                $unresolvedConflicts
            ),
        ];
    }

    /**
     * Compile all assumptions made throughout the pipeline.
     */
    protected function compileAssumptions(array $allResults): array
    {
        $assumptions = [];

        // From context analysis
        $contextResult = $allResults['context'] ?? [];
        if ($contextResult['success'] ?? false) {
            $ctx = $contextResult['context'] ?? [];

            // Check for assumed baselines
            $baselines = $ctx['baselines'] ?? [];
            if (($baselines['confidence'] ?? 1) < 0.8) {
                $assumptions[] = [
                    'id' => 'ASM-CTX-001',
                    'category' => 'reference_plane',
                    'description' => 'Baseline reference assumed from context',
                    'assumed_value' => $baselines['primary'] ?? 'finished_floor',
                    'source_step' => 'Drawing Context Analysis',
                    'confidence' => $baselines['confidence'] ?? 0.5,
                    'impact' => 'All vertical dimensions reference this baseline',
                ];
            }
        }

        // From constraint derivation
        $constraintResult = $allResults['constraints'] ?? [];
        if ($constraintResult['success'] ?? false) {
            $constraints = $constraintResult['constraints'] ?? [];
            foreach ($constraints as $c) {
                if ($c['is_inferred'] ?? false) {
                    $assumptions[] = [
                        'id' => 'ASM-' . ($c['id'] ?? 'UNK'),
                        'category' => $this->mapConstraintTypeToCategory($c['type'] ?? ''),
                        'description' => $c['notes'] ?? 'Inferred constraint',
                        'assumed_value' => $c['value'] ?? 'unknown',
                        'source_step' => 'Production Constraint Derivation',
                        'confidence' => $c['confidence'] ?? 0.5,
                        'impact' => 'Applied to components: ' . implode(', ', $c['applies_to'] ?? []),
                    ];
                }
            }
        }

        // From component extraction
        $componentResult = $allResults['components'] ?? [];
        if ($componentResult['success'] ?? false) {
            $components = $componentResult['extraction']['components'] ?? [];
            foreach ($components as $comp) {
                $dims = $comp['dimensions'] ?? [];
                foreach ($dims as $dimName => $dim) {
                    $method = $dim['derivation_method'] ?? '';
                    if ($method === 'standard_applied') {
                        $assumptions[] = [
                            'id' => 'ASM-COMP-' . ($comp['id'] ?? 'UNK') . '-' . strtoupper(substr($dimName, 0, 1)),
                            'category' => $this->mapDimensionToCategory($dimName),
                            'description' => "Standard value applied for {$dimName}",
                            'assumed_value' => $dim['value'] ?? 'unknown',
                            'source_step' => 'Component Extraction',
                            'confidence' => $comp['confidence'] ?? 0.7,
                            'impact' => "Affects {$comp['type']} {$comp['id']}",
                        ];
                    }
                }
            }
        }

        return $assumptions;
    }

    /**
     * Map constraint type to assumption category.
     */
    protected function mapConstraintTypeToCategory(string $type): string
    {
        return match ($type) {
            'gap_standard' => 'gap_standard',
            'material_thickness' => 'material_thickness',
            'reference_surface' => 'reference_plane',
            'clearance_zone' => 'hardware_clearance',
            default => 'construction_method',
        };
    }

    /**
     * Map dimension name to assumption category.
     */
    protected function mapDimensionToCategory(string $dimName): string
    {
        return match ($dimName) {
            'depth' => 'material_thickness',
            'height', 'width' => 'missing_dimension',
            default => 'construction_method',
        };
    }

    /**
     * Compile all inferred standards.
     */
    protected function compileInferredStandards(array $allResults): array
    {
        $standards = [];

        // From alignment check
        $alignmentResult = $allResults['alignment'] ?? [];
        if ($alignmentResult['success'] ?? false) {
            $evaluations = $alignmentResult['alignment']['practice_evaluations'] ?? [];
            foreach ($evaluations as $eval) {
                $status = $eval['status'] ?? 'unknown';
                if (in_array($status, ['standard', 'acceptable_variation'])) {
                    $details = $eval['details'] ?? [];
                    $standards[] = [
                        'id' => 'STD-' . strtoupper(substr($eval['category'] ?? 'unk', 0, 3)),
                        'category' => $eval['category'] ?? 'unknown',
                        'standard_value' => $details['standard_value'] ?? null,
                        'observed_value' => $details['observed_value'] ?? null,
                        'status' => $status,
                        'deviation' => $details['deviation'] ?? 0,
                        'assessment' => $eval['assessment'] ?? '',
                    ];
                }
            }
        }

        // From gap calculations
        $verificationResult = $allResults['verification'] ?? [];
        if ($verificationResult['success'] ?? false) {
            $cabVerifications = $verificationResult['verification']['cabinet_verifications'] ?? [];
            $allGaps = [];

            foreach ($cabVerifications as $cv) {
                $gapConsistency = $cv['gap_consistency'] ?? [];
                if ($gapConsistency['are_gaps_consistent'] ?? false) {
                    $allGaps[] = $gapConsistency['standard_gap_value'] ?? null;
                }
            }

            // If consistent gap found across cabinets
            $uniqueGaps = array_unique(array_filter($allGaps));
            if (count($uniqueGaps) === 1) {
                $gap = reset($uniqueGaps);
                $standards[] = [
                    'id' => 'STD-GAP',
                    'category' => 'drawer_reveal',
                    'standard_value' => 0.125, // Industry standard
                    'observed_value' => $gap,
                    'status' => $gap == 0.125 ? 'standard' : 'acceptable_variation',
                    'deviation' => abs($gap - 0.125),
                    'assessment' => "Consistent {$gap}\" gap used throughout project",
                ];
            }
        }

        return $standards;
    }

    /**
     * Compile unresolved conflicts.
     */
    protected function compileUnresolvedConflicts(array $allResults): array
    {
        $conflicts = [];

        // From dimension reference analysis
        $dimRefResult = $allResults['dimension_references'] ?? [];
        if ($dimRefResult['success'] ?? false) {
            $potentialConflicts = $dimRefResult['references']['potential_conflicts'] ?? [];
            foreach ($potentialConflicts as $c) {
                if ($c['resolution_needed'] ?? false) {
                    $conflicts[] = [
                        'id' => 'CNF-DIM-' . sprintf('%03d', count($conflicts) + 1),
                        'type' => 'dimension_conflict',
                        'description' => $c['description'] ?? 'Dimension conflict',
                        'affected_items' => $c['dimensions'] ?? [],
                        'source_step' => 'Dimension Reference Analysis',
                        'severity' => 'high',
                        'resolution_required' => true,
                    ];
                }
            }
        }

        // From dimension verification
        $verificationResult = $allResults['verification'] ?? [];
        if ($verificationResult['success'] ?? false) {
            $discrepancies = $verificationResult['verification']['discrepancies'] ?? [];
            foreach ($discrepancies as $d) {
                $conflicts[] = [
                    'id' => 'CNF-VER-' . ($d['cabinet_id'] ?? 'UNK'),
                    'type' => 'math_discrepancy',
                    'description' => "Stack-up discrepancy: {$d['difference']}\" in {$d['direction']}",
                    'affected_items' => [$d['cabinet_id'] ?? 'unknown'],
                    'source_step' => 'Dimension Consistency Verification',
                    'severity' => abs($d['difference'] ?? 0) > 0.25 ? 'high' : 'medium',
                    'resolution_required' => true,
                    'details' => $d,
                ];
            }
        }

        // From component extraction
        $componentResult = $allResults['components'] ?? [];
        if ($componentResult['success'] ?? false) {
            $unresolved = $componentResult['extraction']['unresolved_items'] ?? [];
            foreach ($unresolved as $item) {
                $conflicts[] = [
                    'id' => 'CNF-COMP-' . ($item['placeholder_id'] ?? 'UNK'),
                    'type' => 'incomplete_extraction',
                    'description' => $item['reason'] ?? 'Component could not be fully defined',
                    'affected_items' => [$item['placeholder_id'] ?? 'unknown'],
                    'source_step' => 'Component Extraction',
                    'severity' => 'medium',
                    'resolution_required' => true,
                    'recommendation' => $item['recommendation'] ?? null,
                ];
            }
        }

        return $conflicts;
    }

    /**
     * Determine overall verification level.
     */
    protected function determineVerificationLevel(array $assumptions, array $conflicts): array
    {
        $criticalConflicts = array_filter($conflicts, fn($c) => ($c['severity'] ?? '') === 'high');
        $requiredResolutions = array_filter($conflicts, fn($c) => $c['resolution_required'] ?? false);

        // NOT_VERIFIED if any critical conflicts exist
        if (!empty($criticalConflicts)) {
            return [
                'level' => 'NOT_VERIFIED',
                'description' => self::VERIFICATION_LEVELS['NOT_VERIFIED'],
                'reason' => count($criticalConflicts) . ' critical conflict(s) require resolution',
                'blockers' => array_column($criticalConflicts, 'description'),
            ];
        }

        // NOT_VERIFIED if unresolved conflicts that require resolution
        if (!empty($requiredResolutions)) {
            return [
                'level' => 'NOT_VERIFIED',
                'description' => self::VERIFICATION_LEVELS['NOT_VERIFIED'],
                'reason' => count($requiredResolutions) . ' conflict(s) require resolution',
                'blockers' => array_column($requiredResolutions, 'description'),
            ];
        }

        // VERIFIED_WITH_ASSUMPTIONS if assumptions exist
        if (!empty($assumptions)) {
            return [
                'level' => 'VERIFIED_WITH_ASSUMPTIONS',
                'description' => self::VERIFICATION_LEVELS['VERIFIED_WITH_ASSUMPTIONS'],
                'reason' => count($assumptions) . ' documented assumption(s)',
                'assumptions_count' => count($assumptions),
            ];
        }

        // VERIFIED if no assumptions or conflicts
        return [
            'level' => 'VERIFIED',
            'description' => self::VERIFICATION_LEVELS['VERIFIED'],
            'reason' => 'All data explicitly derived from drawing',
        ];
    }

    /**
     * Determine production readiness for each category.
     */
    protected function determineProductionReadiness(
        array $verificationLevel,
        array $conflicts,
        array $componentResult
    ): array {
        $level = $verificationLevel['level'] ?? 'NOT_VERIFIED';

        $readiness = [];

        // CNC Ready
        if ($level === 'VERIFIED') {
            $readiness['cnc_ready'] = [
                'ready' => true,
                'confidence' => 0.95,
                'notes' => 'All dimensions verified, no assumptions',
            ];
        } elseif ($level === 'VERIFIED_WITH_ASSUMPTIONS') {
            $assumptionCount = $verificationLevel['assumptions_count'] ?? 0;
            $ready = $assumptionCount <= 3;
            $readiness['cnc_ready'] = [
                'ready' => $ready,
                'confidence' => $ready ? 0.80 : 0.60,
                'notes' => $ready
                    ? 'Ready with documented assumptions - verify before machining'
                    : 'Too many assumptions for safe CNC generation',
            ];
        } else {
            $readiness['cnc_ready'] = [
                'ready' => false,
                'confidence' => 0,
                'notes' => 'Unresolved conflicts prevent CNC file generation',
                'blockers' => array_slice(array_column($conflicts, 'description'), 0, 3),
            ];
        }

        // Production Ready (shop drawings)
        if ($level !== 'NOT_VERIFIED') {
            $readiness['production_ready'] = [
                'ready' => true,
                'confidence' => $level === 'VERIFIED' ? 0.95 : 0.75,
                'notes' => $level === 'VERIFIED'
                    ? 'Ready for production'
                    : 'Ready with documented assumptions - review before production',
            ];
        } else {
            $readiness['production_ready'] = [
                'ready' => false,
                'confidence' => 0,
                'notes' => 'Resolve conflicts before production',
            ];
        }

        // Material Takeoff Ready
        $hasComponents = ($componentResult['success'] ?? false) &&
            !empty($componentResult['extraction']['components'] ?? []);
        $readiness['material_takeoff_ready'] = [
            'ready' => $hasComponents,
            'confidence' => $hasComponents ? 0.85 : 0,
            'notes' => $hasComponents
                ? 'Components extracted - material quantities can be estimated'
                : 'Component extraction required for material takeoff',
        ];

        // Verification Only
        $readiness['verification_only'] = [
            'ready' => true,
            'confidence' => 0.95,
            'notes' => 'Drawing analysis available for field verification',
        ];

        return $readiness;
    }

    /**
     * Generate step-by-step audit trail.
     */
    protected function generateAuditTrail(array $allResults): array
    {
        $trail = [];

        $steps = [
            ['key' => 'context', 'name' => 'Drawing Context Analysis', 'number' => 1],
            ['key' => 'dimension_references', 'name' => 'Dimension Reference Analysis', 'number' => 2],
            ['key' => 'notes', 'name' => 'Notes Extraction', 'number' => 3],
            ['key' => 'validation', 'name' => 'Drawing Intent Validation', 'number' => 4],
            ['key' => 'entities', 'name' => 'Hierarchical Entity Extraction', 'number' => 5],
            ['key' => 'verification', 'name' => 'Dimension Consistency Verification', 'number' => 6],
            ['key' => 'alignment', 'name' => 'Standard Practice Alignment', 'number' => 7],
            ['key' => 'constraints', 'name' => 'Production Constraint Derivation', 'number' => 8],
            ['key' => 'components', 'name' => 'Component Extraction', 'number' => 9],
        ];

        foreach ($steps as $step) {
            $result = $allResults[$step['key']] ?? [];
            $success = $result['success'] ?? false;
            $validation = $result['validation'] ?? [];

            $trail[] = [
                'step_number' => $step['number'],
                'step_name' => $step['name'],
                'status' => $success ? 'passed' : 'failed',
                'validation_passed' => $validation['is_valid'] ?? false,
                'error_count' => count($validation['errors'] ?? []),
                'warning_count' => count($validation['warnings'] ?? []),
                'notes' => $success
                    ? ($validation['warnings'][0] ?? 'Completed successfully')
                    : ($result['error'] ?? 'Step failed'),
            ];
        }

        return $trail;
    }

    /**
     * Generate recommendations based on audit results.
     */
    protected function generateRecommendations(
        array $verificationLevel,
        array $assumptions,
        array $conflicts
    ): array {
        $recommendations = [];

        // Address conflicts first
        foreach ($conflicts as $conflict) {
            if ($conflict['resolution_required'] ?? false) {
                $recommendations[] = [
                    'priority' => 'critical',
                    'action' => "Resolve: {$conflict['description']}",
                    'affects' => implode(', ', $conflict['affected_items'] ?? []),
                    'step' => $conflict['source_step'] ?? 'Unknown',
                ];
            }
        }

        // Document assumptions
        if (!empty($assumptions)) {
            $recommendations[] = [
                'priority' => 'high',
                'action' => 'Review all documented assumptions before production',
                'affects' => 'All components using assumed values',
                'step' => 'Production Planning',
            ];

            // Specific assumption recommendations
            $materialAssumptions = array_filter($assumptions, fn($a) => $a['category'] === 'material_thickness');
            if (!empty($materialAssumptions)) {
                $recommendations[] = [
                    'priority' => 'medium',
                    'action' => 'Verify material thickness assumptions with material on hand',
                    'affects' => count($materialAssumptions) . ' component(s)',
                    'step' => 'Material Verification',
                ];
            }
        }

        // Level-specific recommendations
        $level = $verificationLevel['level'] ?? 'NOT_VERIFIED';
        if ($level === 'VERIFIED') {
            $recommendations[] = [
                'priority' => 'low',
                'action' => 'Proceed with confidence - all data verified',
                'affects' => 'Production workflow',
                'step' => 'Ready for Production',
            ];
        } elseif ($level === 'VERIFIED_WITH_ASSUMPTIONS') {
            $recommendations[] = [
                'priority' => 'medium',
                'action' => 'Create assumption sign-off checklist for production team',
                'affects' => 'Quality control',
                'step' => 'Production Handoff',
            ];
        }

        return $recommendations;
    }

    /**
     * Generate human-readable summary.
     */
    protected function generateSummary(
        array $verificationLevel,
        array $readiness,
        array $assumptions,
        array $conflicts
    ): array {
        $level = $verificationLevel['level'] ?? 'NOT_VERIFIED';

        return [
            'verification_status' => $level,
            'verification_description' => $verificationLevel['description'] ?? '',
            'total_assumptions' => count($assumptions),
            'total_conflicts' => count($conflicts),
            'cnc_ready' => $readiness['cnc_ready']['ready'] ?? false,
            'production_ready' => $readiness['production_ready']['ready'] ?? false,
            'material_takeoff_ready' => $readiness['material_takeoff_ready']['ready'] ?? false,
            'headline' => $this->generateHeadline($level, count($assumptions), count($conflicts)),
        ];
    }

    /**
     * Generate headline summary.
     */
    protected function generateHeadline(string $level, int $assumptionCount, int $conflictCount): string
    {
        if ($level === 'VERIFIED') {
            return '✓ VERIFIED - Ready for production';
        } elseif ($level === 'VERIFIED_WITH_ASSUMPTIONS') {
            return "⚠ VERIFIED WITH {$assumptionCount} ASSUMPTION(S) - Review before production";
        } else {
            return "✗ NOT VERIFIED - {$conflictCount} conflict(s) require resolution";
        }
    }

    /**
     * Create a formatted report string.
     */
    public function formatReport(array $auditResult): string
    {
        if (!$auditResult['success']) {
            return "Audit failed: " . ($auditResult['error'] ?? 'Unknown error');
        }

        $lines = [];
        $summary = $auditResult['summary'];
        $verificationLevel = $auditResult['verification_level'];

        // Header
        $lines[] = "═══════════════════════════════════════════════════════════";
        $lines[] = "           DRAWING ANALYSIS VERIFICATION AUDIT";
        $lines[] = "═══════════════════════════════════════════════════════════";
        $lines[] = "";

        // Headline
        $lines[] = $summary['headline'];
        $lines[] = "";
        $lines[] = str_repeat('-', 60);

        // Verification Level
        $lines[] = "";
        $lines[] = "VERIFICATION LEVEL: {$verificationLevel['level']}";
        $lines[] = "  {$verificationLevel['description']}";
        $lines[] = "  Reason: {$verificationLevel['reason']}";

        // Readiness Summary
        $lines[] = "";
        $lines[] = "PRODUCTION READINESS:";
        $readiness = $auditResult['readiness'];
        foreach ($readiness as $category => $status) {
            $icon = ($status['ready'] ?? false) ? '✓' : '✗';
            $conf = round(($status['confidence'] ?? 0) * 100);
            $catName = str_replace('_', ' ', ucfirst($category));
            $lines[] = "  {$icon} {$catName}: {$conf}% confidence";
        }

        // Assumptions
        $assumptions = $auditResult['assumptions'];
        if (!empty($assumptions)) {
            $lines[] = "";
            $lines[] = "ASSUMPTIONS MADE (" . count($assumptions) . "):";
            foreach (array_slice($assumptions, 0, 10) as $a) {
                $lines[] = "  • [{$a['category']}] {$a['description']}";
                $lines[] = "    Value: {$a['assumed_value']} (confidence: " . round($a['confidence'] * 100) . "%)";
            }
            if (count($assumptions) > 10) {
                $lines[] = "  ... and " . (count($assumptions) - 10) . " more";
            }
        }

        // Inferred Standards
        $standards = $auditResult['inferred_standards'];
        if (!empty($standards)) {
            $lines[] = "";
            $lines[] = "INFERRED STANDARDS (" . count($standards) . "):";
            foreach ($standards as $s) {
                $lines[] = "  • {$s['category']}: {$s['observed_value']} ({$s['status']})";
            }
        }

        // Unresolved Conflicts
        $conflicts = $auditResult['unresolved_conflicts'];
        if (!empty($conflicts)) {
            $lines[] = "";
            $lines[] = "⚠ UNRESOLVED CONFLICTS (" . count($conflicts) . "):";
            foreach ($conflicts as $c) {
                $sev = strtoupper($c['severity'] ?? 'unknown');
                $lines[] = "  [{$sev}] {$c['description']}";
                $lines[] = "    Source: {$c['source_step']}";
            }
        }

        // Audit Trail
        $lines[] = "";
        $lines[] = "AUDIT TRAIL:";
        foreach ($auditResult['audit_trail'] as $step) {
            $icon = $step['status'] === 'passed' ? '✓' : '✗';
            $lines[] = "  {$step['step_number']}. {$icon} {$step['step_name']}";
            if ($step['error_count'] > 0 || $step['warning_count'] > 0) {
                $lines[] = "     Errors: {$step['error_count']}, Warnings: {$step['warning_count']}";
            }
        }

        // Recommendations
        $recommendations = $auditResult['recommendations'];
        if (!empty($recommendations)) {
            $lines[] = "";
            $lines[] = "RECOMMENDATIONS:";
            foreach (array_slice($recommendations, 0, 5) as $r) {
                $pri = strtoupper($r['priority']);
                $lines[] = "  [{$pri}] {$r['action']}";
            }
        }

        // Footer
        $lines[] = "";
        $lines[] = str_repeat('═', 60);
        $lines[] = "Generated: " . date('Y-m-d H:i:s');
        $lines[] = str_repeat('═', 60);

        return implode("\n", $lines);
    }

    /**
     * Export audit report as JSON.
     */
    public function exportAsJson(array $auditResult): string
    {
        return json_encode($auditResult, JSON_PRETTY_PRINT);
    }

    /**
     * Check if service is configured.
     */
    public function isConfigured(): bool
    {
        return true; // This service doesn't require external configuration
    }

    /**
     * Get valid values for categories.
     */
    public static function getValidValues(string $category): array
    {
        return match ($category) {
            'verification_levels' => self::VERIFICATION_LEVELS,
            'readiness_categories' => self::READINESS_CATEGORIES,
            'assumption_categories' => self::ASSUMPTION_CATEGORIES,
            default => [],
        };
    }
}
