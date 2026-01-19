<?php

namespace App\Services;

use Webkul\Project\Models\Cabinet;
use Webkul\Project\Models\CabinetCalculationAudit;
use Webkul\Project\Models\ConstructionTemplate;
use Webkul\Security\Models\User;
use Illuminate\Support\Collection;

/**
 * Cabinet Calculation Audit Service
 *
 * Performs calculation audits comparing:
 * - Cabinet stored values vs calculated values
 * - Cabinet values vs ConstructionTemplate standards
 * - Material-specific thickness values
 *
 * Used for quality control before production and tracking calculation changes.
 */
class CabinetCalculationAuditService
{
    protected ConstructionStandardsService $standards;

    // Fields to audit (cabinet column => description)
    protected const AUDITABLE_FIELDS = [
        'internal_depth_inches' => 'Internal Depth',
        'box_height_inches' => 'Box Height',
        'face_frame_depth_inches' => 'Face Frame Depth',
        'drawer_depth_inches' => 'Drawer Depth',
        'drawer_clearance_inches' => 'Drawer Clearance',
        'back_wall_gap_inches' => 'Back Wall Gap',
        'back_panel_thickness' => 'Back Panel Thickness',
        'toe_kick_height' => 'Toe Kick Height',
        'face_frame_stile_width' => 'Face Frame Stile Width',
    ];

    public function __construct(?ConstructionStandardsService $standards = null)
    {
        $this->standards = $standards ?? app(ConstructionStandardsService::class);
    }

    /**
     * Run a full audit on a cabinet.
     *
     * @param Cabinet $cabinet Cabinet to audit
     * @param string $auditType Type of audit (see CabinetCalculationAudit::TYPE_*)
     * @param User|null $user User performing the audit
     * @param string|null $triggerSource What triggered this audit (API, UI, Batch, etc.)
     * @return CabinetCalculationAudit The created audit record
     */
    public function auditCabinet(
        Cabinet $cabinet,
        string $auditType = CabinetCalculationAudit::TYPE_VALIDATION,
        ?User $user = null,
        ?string $triggerSource = null
    ): CabinetCalculationAudit {
        // Get the template for this cabinet
        $template = $this->standards->resolveTemplate($cabinet);

        // Gather stored values from cabinet
        $storedValues = $this->getStoredValues($cabinet);

        // Calculate what values SHOULD be based on template/materials
        $calculatedValues = $this->calculateExpectedValues($cabinet, $template);

        // Get template values for reference
        $templateValues = $this->getTemplateValues($template);

        // Compare and find discrepancies
        $discrepancies = $this->findDiscrepancies($storedValues, $calculatedValues);

        // Determine audit status
        $status = $this->determineStatus($discrepancies);

        // Find max discrepancy
        $maxDiscrepancy = $this->findMaxDiscrepancy($discrepancies);

        // Create audit record
        return CabinetCalculationAudit::create([
            'cabinet_id' => $cabinet->id,
            'construction_template_id' => $template?->id,
            'audited_by_user_id' => $user?->id,
            'audit_type' => $auditType,
            'audit_status' => $status,
            'stored_values' => $storedValues,
            'calculated_values' => $calculatedValues,
            'template_values' => $templateValues,
            'discrepancies' => $discrepancies,
            'discrepancy_count' => count($discrepancies),
            'max_discrepancy_inches' => $maxDiscrepancy['amount'] ?? null,
            'max_discrepancy_field' => $maxDiscrepancy['field'] ?? null,
            'trigger_source' => $triggerSource,
        ]);
    }

    /**
     * Audit all cabinets in a project.
     */
    public function auditProject(
        int $projectId,
        string $auditType = CabinetCalculationAudit::TYPE_VALIDATION,
        ?User $user = null
    ): Collection {
        $cabinets = Cabinet::where('project_id', $projectId)->get();

        return $cabinets->map(fn ($cabinet) =>
            $this->auditCabinet($cabinet, $auditType, $user, 'project_batch')
        );
    }

    /**
     * Audit all cabinets in a cabinet run.
     */
    public function auditCabinetRun(
        int $cabinetRunId,
        string $auditType = CabinetCalculationAudit::TYPE_VALIDATION,
        ?User $user = null
    ): Collection {
        $cabinets = Cabinet::where('cabinet_run_id', $cabinetRunId)->get();

        return $cabinets->map(fn ($cabinet) =>
            $this->auditCabinet($cabinet, $auditType, $user, 'run_batch')
        );
    }

    /**
     * Recalculate and update a cabinet's values, then audit.
     */
    public function recalculateAndAudit(Cabinet $cabinet, ?User $user = null): array
    {
        // Get template
        $template = $this->standards->resolveTemplate($cabinet);

        // Calculate expected values
        $calculated = $this->calculateExpectedValues($cabinet, $template);

        // Update cabinet with calculated values
        $cabinet->update([
            'face_frame_depth_inches' => $calculated['face_frame_depth_inches'],
            'internal_depth_inches' => $calculated['internal_depth_inches'],
            'drawer_depth_inches' => $calculated['drawer_depth_inches'],
            'drawer_clearance_inches' => $calculated['drawer_clearance_inches'],
            'back_wall_gap_inches' => $calculated['back_wall_gap_inches'],
            'box_height_inches' => $calculated['box_height_inches'],
            'depth_validated' => $calculated['depth_validated'],
            'depth_validation_message' => $calculated['depth_validation_message'],
            'max_slide_length_inches' => $calculated['max_slide_length_inches'],
            'calculated_at' => now(),
        ]);

        // Run audit (should pass now)
        $audit = $this->auditCabinet(
            $cabinet->fresh(),
            CabinetCalculationAudit::TYPE_RECALC,
            $user,
            'recalculate'
        );

        return [
            'cabinet' => $cabinet->fresh(),
            'audit' => $audit,
            'calculated_values' => $calculated,
        ];
    }

    /**
     * Get stored values from cabinet.
     */
    protected function getStoredValues(Cabinet $cabinet): array
    {
        return [
            'internal_depth_inches' => $cabinet->internal_depth_inches,
            'box_height_inches' => $cabinet->box_height_inches,
            'face_frame_depth_inches' => $cabinet->face_frame_depth_inches,
            'drawer_depth_inches' => $cabinet->drawer_depth_inches,
            'drawer_clearance_inches' => $cabinet->drawer_clearance_inches,
            'back_wall_gap_inches' => $cabinet->back_wall_gap_inches,
            'back_panel_thickness' => $cabinet->back_panel_thickness,
            'toe_kick_height' => $cabinet->toe_kick_height,
            'face_frame_stile_width' => $cabinet->face_frame_stile_width,
            'depth_validated' => $cabinet->depth_validated,
            'max_slide_length_inches' => $cabinet->max_slide_length_inches,
        ];
    }

    /**
     * Calculate expected values from template and cabinet dimensions.
     */
    protected function calculateExpectedValues(Cabinet $cabinet, ?ConstructionTemplate $template): array
    {
        // Get values from template or fallback
        $faceFrameDepth = $cabinet->face_frame_stile_width
            ?? $template?->face_frame_stile_width
            ?? ConstructionStandardsService::getFallbackDefault('face_frame_stile_width', 1.5);

        $backThickness = $template?->getEffectiveBackPanelThickness()
            ?? $cabinet->back_panel_thickness
            ?? ConstructionStandardsService::getFallbackDefault('back_panel_thickness', 0.75);

        $drawerClearance = $template?->drawer_rear_clearance
            ?? ConstructionStandardsService::getFallbackDefault('drawer_rear_clearance', 0.75);

        $backWallGap = $template?->back_wall_gap
            ?? ConstructionStandardsService::getFallbackDefault('back_wall_gap', 0.5);

        $toeKickHeight = $template?->toe_kick_height
            ?? ConstructionStandardsService::getFallbackDefault('toe_kick_height', 4.5);

        // Calculate depth breakdown
        $totalDepth = $cabinet->depth_inches;
        $internalDepth = $totalDepth - $backWallGap - $backThickness;
        $availableForDrawer = $internalDepth - $drawerClearance;

        // Find max slide length
        $maxSlideLength = null;
        foreach ([21, 18, 15, 12, 9] as $len) {
            if ($availableForDrawer >= $len) {
                $maxSlideLength = $len;
                break;
            }
        }

        // Calculate box height
        $boxHeight = $cabinet->height_inches - $toeKickHeight;

        // Validation
        $validated = $maxSlideLength !== null && $maxSlideLength >= 12;
        $validationMessage = $validated
            ? sprintf('Depth sufficient for %d" slides', $maxSlideLength)
            : sprintf('Insufficient depth - only %.2f" available', $availableForDrawer);

        return [
            'internal_depth_inches' => round($internalDepth, 4),
            'box_height_inches' => round($boxHeight, 4),
            'face_frame_depth_inches' => $faceFrameDepth,
            'drawer_depth_inches' => $maxSlideLength,
            'drawer_clearance_inches' => $drawerClearance,
            'back_wall_gap_inches' => $backWallGap,
            'back_panel_thickness' => $backThickness,
            'toe_kick_height' => $toeKickHeight,
            'face_frame_stile_width' => $faceFrameDepth,
            'depth_validated' => $validated,
            'depth_validation_message' => $validationMessage,
            'max_slide_length_inches' => $maxSlideLength,
        ];
    }

    /**
     * Get template values for audit reference.
     */
    protected function getTemplateValues(?ConstructionTemplate $template): array
    {
        if (!$template) {
            return ['source' => 'fallback_defaults'];
        }

        return [
            'source' => 'template',
            'template_id' => $template->id,
            'template_name' => $template->name,
            'face_frame_stile_width' => $template->face_frame_stile_width,
            'back_panel_thickness' => $template->back_panel_thickness,
            'drawer_rear_clearance' => $template->drawer_rear_clearance,
            'back_wall_gap' => $template->back_wall_gap,
            'toe_kick_height' => $template->toe_kick_height,
            'default_slide_length' => $template->default_slide_length,
            'box_material_thickness' => $template->box_material_thickness,
        ];
    }

    /**
     * Find discrepancies between stored and calculated values.
     */
    protected function findDiscrepancies(array $stored, array $calculated): array
    {
        $discrepancies = [];

        foreach (self::AUDITABLE_FIELDS as $field => $label) {
            $storedValue = $stored[$field] ?? null;
            $calculatedValue = $calculated[$field] ?? null;

            // Skip if both are null
            if ($storedValue === null && $calculatedValue === null) {
                continue;
            }

            // If one is null and other isn't, that's a discrepancy
            if ($storedValue === null || $calculatedValue === null) {
                $discrepancies[] = [
                    'field' => $field,
                    'label' => $label,
                    'stored' => $storedValue,
                    'calculated' => $calculatedValue,
                    'difference' => null,
                    'severity' => 'warning',
                    'message' => $storedValue === null
                        ? "Missing stored value for {$label}"
                        : "Missing calculated value for {$label}",
                ];
                continue;
            }

            // Calculate difference
            $difference = abs((float) $storedValue - (float) $calculatedValue);

            // Only record if there's a meaningful difference
            if ($difference > 0.001) {
                $severity = $this->determineSeverity($difference);
                $discrepancies[] = [
                    'field' => $field,
                    'label' => $label,
                    'stored' => $storedValue,
                    'calculated' => $calculatedValue,
                    'difference' => round($difference, 4),
                    'severity' => $severity,
                    'message' => sprintf(
                        '%s differs by %.4f" (stored: %.4f", expected: %.4f")',
                        $label,
                        $difference,
                        $storedValue,
                        $calculatedValue
                    ),
                ];
            }
        }

        return $discrepancies;
    }

    /**
     * Determine severity based on discrepancy amount.
     */
    protected function determineSeverity(float $difference): string
    {
        if ($difference >= CabinetCalculationAudit::TOLERANCE_FAILURE) {
            return 'error';
        }
        if ($difference >= CabinetCalculationAudit::TOLERANCE_WARNING) {
            return 'warning';
        }
        return 'info';
    }

    /**
     * Determine overall audit status from discrepancies.
     */
    protected function determineStatus(array $discrepancies): string
    {
        if (empty($discrepancies)) {
            return CabinetCalculationAudit::STATUS_PASSED;
        }

        $hasError = collect($discrepancies)->contains('severity', 'error');
        if ($hasError) {
            return CabinetCalculationAudit::STATUS_FAILED;
        }

        $hasWarning = collect($discrepancies)->contains('severity', 'warning');
        if ($hasWarning) {
            return CabinetCalculationAudit::STATUS_WARNING;
        }

        return CabinetCalculationAudit::STATUS_PASSED;
    }

    /**
     * Find the maximum discrepancy.
     */
    protected function findMaxDiscrepancy(array $discrepancies): array
    {
        if (empty($discrepancies)) {
            return ['field' => null, 'amount' => null];
        }

        $max = collect($discrepancies)
            ->filter(fn ($d) => $d['difference'] !== null)
            ->sortByDesc('difference')
            ->first();

        return [
            'field' => $max['field'] ?? null,
            'amount' => $max['difference'] ?? null,
        ];
    }

    /**
     * Get audit summary for a cabinet.
     */
    public function getAuditSummary(Cabinet $cabinet): array
    {
        $latestAudit = CabinetCalculationAudit::forCabinet($cabinet->id)
            ->latest()
            ->first();

        $auditCount = CabinetCalculationAudit::forCabinet($cabinet->id)->count();
        $failedCount = CabinetCalculationAudit::forCabinet($cabinet->id)->failed()->count();
        $warningCount = CabinetCalculationAudit::forCabinet($cabinet->id)->warning()->count();

        return [
            'cabinet_id' => $cabinet->id,
            'latest_audit' => $latestAudit,
            'latest_status' => $latestAudit?->audit_status ?? 'never_audited',
            'total_audits' => $auditCount,
            'failed_audits' => $failedCount,
            'warning_audits' => $warningCount,
            'needs_attention' => $latestAudit?->needsAttention() ?? false,
            'last_audited_at' => $latestAudit?->created_at,
        ];
    }
}
