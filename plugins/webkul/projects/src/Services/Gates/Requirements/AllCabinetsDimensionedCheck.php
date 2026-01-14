<?php

namespace Webkul\Project\Services\Gates\Requirements;

use Webkul\Project\Models\GateRequirement;
use Webkul\Project\Models\Project;
use Webkul\Project\Services\Gates\RequirementCheckResult;

/**
 * Check if all cabinets have required dimensions specified.
 */
class AllCabinetsDimensionedCheck
{
    /**
     * Required dimension fields for a cabinet.
     */
    protected array $requiredFields = [
        'length_inches',  // width
        'width_inches',   // depth
        'height_inches',
    ];

    /**
     * Check if all cabinets have dimensions.
     *
     * @param Project $project
     * @param GateRequirement $requirement
     * @return RequirementCheckResult
     */
    public function check(Project $project, GateRequirement $requirement): RequirementCheckResult
    {
        $cabinets = $project->cabinets;

        if ($cabinets->isEmpty()) {
            return new RequirementCheckResult(
                false,
                'No cabinets found in project',
                ['cabinet_count' => 0]
            );
        }

        $total = $cabinets->count();
        $incomplete = [];

        foreach ($cabinets as $cabinet) {
            $missingFields = [];
            
            foreach ($this->requiredFields as $field) {
                if (empty($cabinet->{$field}) || $cabinet->{$field} <= 0) {
                    $missingFields[] = $field;
                }
            }

            if (!empty($missingFields)) {
                $incomplete[] = [
                    'cabinet_id' => $cabinet->id,
                    'cabinet_name' => $cabinet->name ?? "Cabinet #{$cabinet->id}",
                    'missing_fields' => $missingFields,
                ];
            }
        }

        $dimensionedCount = $total - count($incomplete);
        $passed = count($incomplete) === 0;

        return new RequirementCheckResult(
            $passed,
            $passed 
                ? "All {$total} cabinets have complete dimensions"
                : "{$dimensionedCount}/{$total} cabinets have complete dimensions",
            [
                'total' => $total,
                'dimensioned' => $dimensionedCount,
                'incomplete' => $incomplete,
            ]
        );
    }
}
