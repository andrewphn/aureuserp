<?php

namespace Webkul\Project\Services\Gates\Requirements;

use Webkul\Project\Models\GateRequirement;
use Webkul\Project\Models\Project;
use Webkul\Project\Services\Gates\RequirementCheckResult;

/**
 * Check if all BOM lines are covered by inventory reservation or purchase order.
 */
class AllBomLinesCoveredCheck
{
    /**
     * Check if all BOM lines are covered.
     *
     * @param Project $project
     * @param GateRequirement $requirement
     * @return RequirementCheckResult
     */
    public function check(Project $project, GateRequirement $requirement): RequirementCheckResult
    {
        // Get all BOM lines for the project
        $bomLines = $project->bomLines ?? collect();

        if ($bomLines->isEmpty()) {
            return new RequirementCheckResult(
                false,
                'No BOM lines found for project',
                ['bom_count' => 0]
            );
        }

        $total = $bomLines->count();
        $uncovered = [];

        foreach ($bomLines as $bomLine) {
            // Check if material is allocated or has a PO
            $isCovered = $bomLine->material_allocated 
                || $bomLine->material_issued
                || $this->hasPurchaseOrderCoverage($project, $bomLine);

            if (!$isCovered) {
                $uncovered[] = [
                    'bom_id' => $bomLine->id,
                    'product_id' => $bomLine->product_id,
                    'component_name' => $bomLine->component_name,
                    'quantity_required' => $bomLine->quantity_required,
                ];
            }
        }

        $coveredCount = $total - count($uncovered);
        $passed = count($uncovered) === 0;

        return new RequirementCheckResult(
            $passed,
            $passed 
                ? "All {$total} BOM lines have material coverage"
                : "{$coveredCount}/{$total} BOM lines covered - " . count($uncovered) . " need sourcing",
            [
                'total' => $total,
                'covered' => $coveredCount,
                'uncovered' => $uncovered,
            ]
        );
    }

    /**
     * Check if a BOM line has purchase order coverage.
     */
    protected function hasPurchaseOrderCoverage(Project $project, $bomLine): bool
    {
        // Check material reservations
        $reservation = $project->materialReservations()
            ->where('product_id', $bomLine->product_id)
            ->where('status', '!=', 'cancelled')
            ->first();

        return $reservation !== null;
    }
}
