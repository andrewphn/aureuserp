<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Derives production constraints from validated dimensions and notes.
 * This is the EIGHTH step in the drawing analysis pipeline.
 *
 * Purpose: Establish machining parameters and constraints.
 * Each constraint must cite its source (dimension, note, or math reconciliation).
 * Inferred constraints must be explicitly flagged.
 */
class ProductionConstraintDerivationService
{
    protected ?string $geminiKey;
    protected string $model = 'gemini-2.0-flash';

    // Constraint types
    public const CONSTRAINT_TYPES = [
        'gap_standard' => 'Standard gap/reveal dimension',
        'reference_surface' => 'Reference surface for machining',
        'no_cut_zone' => 'Area where no cuts should be made',
        'material_thickness' => 'Material thickness assumption',
        'clearance_zone' => 'Operational clearance zone',
        'alignment_reference' => 'Alignment/registration reference',
        'depth_limit' => 'Maximum machining depth',
        'edge_setback' => 'Minimum distance from edge',
    ];

    // Constraint sources
    public const CONSTRAINT_SOURCES = [
        'explicit_dimension' => 'Directly dimensioned on drawing',
        'explicit_note' => 'Stated in drawing notes',
        'math_reconciliation' => 'Calculated from dimension stack-up',
        'standard_practice' => 'Industry standard practice',
        'inferred' => 'Inferred from context (flagged)',
    ];

    // Constraint scopes
    public const CONSTRAINT_SCOPES = [
        'project' => 'Applies to entire project',
        'room' => 'Applies to specific room',
        'cabinet' => 'Applies to specific cabinet',
        'section' => 'Applies to cabinet section',
        'component' => 'Applies to specific component',
    ];

    // Machining reference surfaces
    public const REFERENCE_SURFACES = [
        'face_frame_front' => 'Front surface of face frame',
        'cabinet_box_front' => 'Front edge of cabinet box',
        'cabinet_box_back' => 'Back panel inner surface',
        'cabinet_box_side' => 'Side panel inner surface',
        'cabinet_box_bottom' => 'Bottom panel top surface',
        'finished_floor' => 'Finished floor level',
        'countertop_underside' => 'Underside of countertop',
    ];

    public function __construct()
    {
        $this->geminiKey = config('services.google.api_key') ?? env('GOOGLE_API_KEY') ?? env('GEMINI_API_KEY');
    }

    /**
     * Derive production constraints from prior analysis.
     *
     * @param array $verificationResult Result from DimensionConsistencyVerifierService
     * @param array $alignmentResult Result from StandardPracticeAlignmentService
     * @param array $notesExtraction Result from DrawingNotesExtractorService
     * @param array $entityExtraction Result from HierarchicalEntityExtractorService
     * @return array Derived constraints with sources
     */
    public function deriveConstraints(
        array $verificationResult,
        array $alignmentResult,
        array $notesExtraction,
        array $entityExtraction
    ): array {
        // Check prerequisites
        if (!$verificationResult['success'] || !$verificationResult['can_proceed']['can_proceed']) {
            return $this->errorResponse('Dimension verification must pass before deriving constraints');
        }

        // Compile constraint data from all sources
        $constraints = [];

        // 1. Gap standards from dimension verification
        $gapConstraints = $this->deriveGapConstraints($verificationResult);
        $constraints = array_merge($constraints, $gapConstraints);

        // 2. Material thickness from notes
        $materialConstraints = $this->deriveMaterialConstraints($notesExtraction);
        $constraints = array_merge($constraints, $materialConstraints);

        // 3. Reference surfaces from entity extraction
        $referenceConstraints = $this->deriveReferenceConstraints($entityExtraction);
        $constraints = array_merge($constraints, $referenceConstraints);

        // 4. No-cut zones from notes and entity relationships
        $noCutConstraints = $this->deriveNoCutZones($notesExtraction, $entityExtraction);
        $constraints = array_merge($constraints, $noCutConstraints);

        // 5. Inferred constraints from alignment check
        $inferredConstraints = $this->deriveInferredConstraints($alignmentResult);
        $constraints = array_merge($constraints, $inferredConstraints);

        // Validate all constraints
        $validation = $this->validateConstraints($constraints);

        // Organize by scope
        $organized = $this->organizeConstraintsByScope($constraints);

        return [
            'success' => true,
            'constraints' => $constraints,
            'organized' => $organized,
            'validation' => $validation,
            'summary' => $this->generateSummary($constraints),
        ];
    }

    /**
     * Derive gap standard constraints from verification results.
     */
    protected function deriveGapConstraints(array $verificationResult): array
    {
        $constraints = [];
        $cabinetVerifications = $verificationResult['verification']['cabinet_verifications'] ?? [];

        // Collect all gap values
        $gapValues = [];
        foreach ($cabinetVerifications as $cv) {
            $impliedGaps = $cv['implied_gaps'] ?? [];
            foreach ($impliedGaps as $gap) {
                $value = $gap['calculated_value'] ?? null;
                if ($value !== null) {
                    $gapValues[] = [
                        'value' => $value,
                        'type' => $gap['gap_type'] ?? 'unknown',
                        'cabinet_id' => $cv['cabinet_id'] ?? 'unknown',
                        'location' => $gap['location'] ?? 'unknown',
                    ];
                }
            }
        }

        // Group by value to find standards
        $groupedByValue = [];
        foreach ($gapValues as $gv) {
            $key = (string)$gv['value'];
            $groupedByValue[$key][] = $gv;
        }

        // Create constraints for consistent values
        foreach ($groupedByValue as $value => $gaps) {
            $count = count($gaps);
            $isConsistent = $count >= 2;
            $cabinets = array_unique(array_column($gaps, 'cabinet_id'));

            $constraints[] = [
                'id' => 'GAP-' . str_replace('.', '', $value),
                'type' => 'gap_standard',
                'value' => (float)$value,
                'unit' => 'inches',
                'source' => $isConsistent ? 'math_reconciliation' : 'inferred',
                'source_detail' => "Calculated from {$count} instance(s) in dimension stack-up",
                'scope' => count($cabinets) > 1 ? 'project' : 'cabinet',
                'applies_to' => $cabinets,
                'is_inferred' => !$isConsistent,
                'confidence' => $isConsistent ? 0.9 : 0.7,
                'notes' => $isConsistent
                    ? "Consistent {$value}\" gap found across {$count} locations"
                    : "Single instance - verify this is intentional",
            ];
        }

        return $constraints;
    }

    /**
     * Derive material thickness constraints from notes.
     */
    protected function deriveMaterialConstraints(array $notesExtraction): array
    {
        $constraints = [];
        $notes = $notesExtraction['extraction']['notes'] ?? [];

        // Look for material specification notes
        $materialNotes = array_filter($notes, fn($n) => ($n['type'] ?? '') === 'material_spec');

        foreach ($materialNotes as $note) {
            $text = $note['text']['exact'] ?? '';

            // Parse for thickness values (e.g., "3/4\" Maple", "1/2\" plywood")
            if (preg_match('/(\d+\/\d+|\d+(?:\.\d+)?)["\s]*(?:inch|in)?/i', $text, $matches)) {
                $thicknessStr = $matches[1];

                // Convert fraction to decimal
                if (strpos($thicknessStr, '/') !== false) {
                    $parts = explode('/', $thicknessStr);
                    $thickness = (float)$parts[0] / (float)$parts[1];
                } else {
                    $thickness = (float)$thicknessStr;
                }

                $constraints[] = [
                    'id' => 'MAT-' . sprintf('%03d', count($constraints) + 1),
                    'type' => 'material_thickness',
                    'value' => $thickness,
                    'unit' => 'inches',
                    'source' => 'explicit_note',
                    'source_detail' => "Note: \"{$text}\"",
                    'source_note_id' => $note['id'] ?? null,
                    'scope' => $note['scope'] ?? 'project',
                    'applies_to' => [],
                    'is_inferred' => false,
                    'confidence' => ($note['text']['is_clear'] ?? true) ? 0.95 : 0.7,
                    'notes' => "Material specification from drawing note",
                ];
            }
        }

        return $constraints;
    }

    /**
     * Derive reference surface constraints from entity extraction.
     */
    protected function deriveReferenceConstraints(array $entityExtraction): array
    {
        $constraints = [];
        $entities = $entityExtraction['extraction']['entities'] ?? [];
        $cabinets = $entities['cabinets'] ?? [];

        foreach ($cabinets as $cabinet) {
            $cabId = $cabinet['id'] ?? 'unknown';
            $type = $cabinet['type'] ?? 'unknown';

            // Determine primary machining reference based on cabinet type
            $primaryReference = $this->determinePrimaryReference($type);

            $constraints[] = [
                'id' => "REF-{$cabId}",
                'type' => 'reference_surface',
                'value' => $primaryReference,
                'unit' => null,
                'source' => 'standard_practice',
                'source_detail' => "Standard reference for {$type} cabinet type",
                'scope' => 'cabinet',
                'applies_to' => [$cabId],
                'is_inferred' => false,
                'confidence' => 0.85,
                'notes' => "Primary machining reference: {$primaryReference}",
            ];
        }

        return $constraints;
    }

    /**
     * Determine primary machining reference based on cabinet type.
     */
    protected function determinePrimaryReference(string $cabinetType): string
    {
        // Face frame cabinets typically reference face frame front
        $faceFrameTypes = ['base', 'sink_base', 'drawer_base', 'wall', 'vanity', 'vanity_sink'];

        if (in_array($cabinetType, $faceFrameTypes)) {
            return 'face_frame_front';
        }

        // Frameless would reference cabinet box front
        return 'cabinet_box_front';
    }

    /**
     * Derive no-cut zone constraints.
     */
    protected function deriveNoCutZones(array $notesExtraction, array $entityExtraction): array
    {
        $constraints = [];
        $notes = $notesExtraction['extraction']['notes'] ?? [];
        $entities = $entityExtraction['extraction']['entities'] ?? [];

        // Look for warning notes that indicate no-cut zones
        $warningNotes = array_filter($notes, fn($n) => ($n['type'] ?? '') === 'warning');

        foreach ($warningNotes as $note) {
            $text = strtolower($note['text']['exact'] ?? '');

            // Check for common no-cut indicators
            $noCutIndicators = ['do not cut', 'no cut', 'avoid', 'clear zone', 'no machining'];
            foreach ($noCutIndicators as $indicator) {
                if (strpos($text, $indicator) !== false) {
                    $constraints[] = [
                        'id' => 'NCZ-' . sprintf('%03d', count($constraints) + 1),
                        'type' => 'no_cut_zone',
                        'value' => $note['text']['exact'],
                        'unit' => null,
                        'source' => 'explicit_note',
                        'source_detail' => "Warning note indicates no-cut zone",
                        'source_note_id' => $note['id'] ?? null,
                        'scope' => $note['scope'] ?? 'cabinet',
                        'applies_to' => [],
                        'is_inferred' => false,
                        'confidence' => 0.9,
                        'notes' => "No-cut zone from drawing warning",
                    ];
                    break;
                }
            }
        }

        // Add standard no-cut zones for components
        $components = $entities['components'] ?? [];
        foreach ($components as $comp) {
            if (($comp['type'] ?? '') === 'sink_cutout') {
                $constraints[] = [
                    'id' => "NCZ-SINK-{$comp['id']}",
                    'type' => 'no_cut_zone',
                    'value' => 'Sink cutout area - coordinate with sink template',
                    'unit' => null,
                    'source' => 'standard_practice',
                    'source_detail' => "Standard practice for sink installations",
                    'scope' => 'component',
                    'applies_to' => [$comp['parent_id'] ?? 'unknown'],
                    'is_inferred' => false,
                    'confidence' => 0.95,
                    'notes' => "Sink cutout requires field verification",
                ];
            }
        }

        return $constraints;
    }

    /**
     * Derive inferred constraints from alignment results.
     */
    protected function deriveInferredConstraints(array $alignmentResult): array
    {
        $constraints = [];

        if (!$alignmentResult['success']) {
            return $constraints;
        }

        $evaluations = $alignmentResult['alignment']['practice_evaluations'] ?? [];

        foreach ($evaluations as $eval) {
            // Only create constraints for standard or acceptable variations
            $status = $eval['status'] ?? 'unknown';
            if (!in_array($status, ['standard', 'acceptable_variation'])) {
                continue;
            }

            $category = $eval['category'] ?? 'unknown';
            $details = $eval['details'] ?? [];
            $value = $details['observed_value'] ?? null;

            if ($value === null) {
                continue;
            }

            $isStandard = $status === 'standard';

            $constraints[] = [
                'id' => 'INF-' . strtoupper(substr($category, 0, 3)) . '-001',
                'type' => $this->mapCategoryToConstraintType($category),
                'value' => $value,
                'unit' => 'inches',
                'source' => $isStandard ? 'standard_practice' : 'inferred',
                'source_detail' => $eval['assessment'] ?? "Derived from {$category} evaluation",
                'scope' => 'project',
                'applies_to' => $eval['affected_cabinets'] ?? [],
                'is_inferred' => !$isStandard,
                'confidence' => $eval['confidence'] ?? 0.75,
                'notes' => $isStandard
                    ? "Matches standard practice"
                    : "INFERRED - verify before production",
            ];
        }

        return $constraints;
    }

    /**
     * Map practice category to constraint type.
     */
    protected function mapCategoryToConstraintType(string $category): string
    {
        return match ($category) {
            'drawer_spacing' => 'gap_standard',
            'face_frame_overlap' => 'edge_setback',
            'slide_clearance' => 'clearance_zone',
            'stretcher_placement' => 'reference_surface',
            default => 'gap_standard',
        };
    }

    /**
     * Validate all derived constraints.
     */
    protected function validateConstraints(array $constraints): array
    {
        $errors = [];
        $warnings = [];

        foreach ($constraints as $constraint) {
            $id = $constraint['id'] ?? 'unknown';

            // Check for required fields
            if (!isset($constraint['type'])) {
                $errors[] = "{$id}: Missing constraint type";
            }
            if (!isset($constraint['source'])) {
                $errors[] = "{$id}: Missing constraint source";
            }

            // Warn about inferred constraints
            if ($constraint['is_inferred'] ?? false) {
                $warnings[] = "{$id}: Constraint is INFERRED - requires verification";
            }

            // Warn about low confidence
            $confidence = $constraint['confidence'] ?? 0;
            if ($confidence < 0.7) {
                $warnings[] = "{$id}: Low confidence ({$confidence}) - manual review needed";
            }
        }

        // Check for conflicting constraints
        $gapConstraints = array_filter($constraints, fn($c) => ($c['type'] ?? '') === 'gap_standard');
        $gapValues = array_column($gapConstraints, 'value');
        $uniqueGaps = array_unique($gapValues);
        if (count($uniqueGaps) > 2) {
            $warnings[] = "Multiple different gap standards found (" . implode(', ', $uniqueGaps) . ") - verify consistency";
        }

        return [
            'is_valid' => empty($errors),
            'errors' => $errors,
            'warnings' => $warnings,
            'inferred_count' => count(array_filter($constraints, fn($c) => $c['is_inferred'] ?? false)),
        ];
    }

    /**
     * Organize constraints by scope for easy access.
     */
    protected function organizeConstraintsByScope(array $constraints): array
    {
        $organized = [
            'project' => [],
            'room' => [],
            'cabinet' => [],
            'section' => [],
            'component' => [],
        ];

        foreach ($constraints as $constraint) {
            $scope = $constraint['scope'] ?? 'project';
            if (isset($organized[$scope])) {
                $organized[$scope][] = $constraint;
            } else {
                $organized['project'][] = $constraint;
            }
        }

        return $organized;
    }

    /**
     * Generate summary of derived constraints.
     */
    protected function generateSummary(array $constraints): array
    {
        $byType = [];
        $bySource = [];
        $inferredCount = 0;

        foreach ($constraints as $c) {
            $type = $c['type'] ?? 'unknown';
            $source = $c['source'] ?? 'unknown';

            $byType[$type] = ($byType[$type] ?? 0) + 1;
            $bySource[$source] = ($bySource[$source] ?? 0) + 1;

            if ($c['is_inferred'] ?? false) {
                $inferredCount++;
            }
        }

        return [
            'total_constraints' => count($constraints),
            'by_type' => $byType,
            'by_source' => $bySource,
            'inferred_count' => $inferredCount,
            'explicit_count' => count($constraints) - $inferredCount,
        ];
    }

    /**
     * Get constraints that are inferred (need verification).
     */
    public function getInferredConstraints(array $derivationResult): array
    {
        if (!$derivationResult['success']) {
            return [];
        }

        return array_filter(
            $derivationResult['constraints'],
            fn($c) => $c['is_inferred'] ?? false
        );
    }

    /**
     * Get constraints for a specific cabinet.
     */
    public function getConstraintsForCabinet(array $derivationResult, string $cabinetId): array
    {
        if (!$derivationResult['success']) {
            return [];
        }

        $constraints = $derivationResult['constraints'];

        return array_filter($constraints, function ($c) use ($cabinetId) {
            $appliesTo = $c['applies_to'] ?? [];
            $scope = $c['scope'] ?? '';

            // Include project-level constraints and cabinet-specific ones
            return $scope === 'project' || in_array($cabinetId, $appliesTo);
        });
    }

    /**
     * Create a summary suitable for logging or display.
     */
    public function summarizeConstraints(array $derivationResult): string
    {
        if (!$derivationResult['success']) {
            return "Constraint derivation failed: " . ($derivationResult['error'] ?? 'Unknown error');
        }

        $summary = $derivationResult['summary'];
        $validation = $derivationResult['validation'];
        $lines = [];

        $lines[] = "Production Constraint Derivation";
        $lines[] = str_repeat('-', 40);

        // Summary stats
        $lines[] = "Total constraints: {$summary['total_constraints']}";
        $lines[] = "Explicit (from drawing): {$summary['explicit_count']}";
        $lines[] = "Inferred (flagged): {$summary['inferred_count']}";

        // By type
        $lines[] = "";
        $lines[] = "By Type:";
        foreach ($summary['by_type'] as $type => $count) {
            $lines[] = "  {$type}: {$count}";
        }

        // By source
        $lines[] = "";
        $lines[] = "By Source:";
        foreach ($summary['by_source'] as $source => $count) {
            $lines[] = "  {$source}: {$count}";
        }

        // Inferred constraints warning
        if ($summary['inferred_count'] > 0) {
            $lines[] = "";
            $lines[] = "⚠ INFERRED CONSTRAINTS ({$summary['inferred_count']}):";
            foreach ($derivationResult['constraints'] as $c) {
                if ($c['is_inferred'] ?? false) {
                    $lines[] = "  • {$c['id']}: {$c['notes']}";
                }
            }
        }

        // Validation warnings
        if (!empty($validation['warnings'])) {
            $lines[] = "";
            $lines[] = "Warnings:";
            foreach (array_slice($validation['warnings'], 0, 5) as $w) {
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
            'constraints' => [],
            'organized' => [],
            'validation' => ['is_valid' => false, 'errors' => [$message], 'warnings' => []],
            'summary' => ['total_constraints' => 0],
        ];
    }

    /**
     * Check if service is configured.
     */
    public function isConfigured(): bool
    {
        return true; // This service doesn't require API key - it processes prior results
    }

    /**
     * Get valid values for categories.
     */
    public static function getValidValues(string $category): array
    {
        return match ($category) {
            'constraint_types' => self::CONSTRAINT_TYPES,
            'constraint_sources' => self::CONSTRAINT_SOURCES,
            'constraint_scopes' => self::CONSTRAINT_SCOPES,
            'reference_surfaces' => self::REFERENCE_SURFACES,
            default => [],
        };
    }
}
