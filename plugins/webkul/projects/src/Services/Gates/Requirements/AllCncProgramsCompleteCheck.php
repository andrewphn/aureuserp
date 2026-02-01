<?php

namespace Webkul\Project\Services\Gates\Requirements;

use Webkul\Project\Models\CncProgram;
use Webkul\Project\Models\GateRequirement;
use Webkul\Project\Models\Project;
use Webkul\Project\Services\Gates\RequirementCheckResult;

/**
 * Check if all CNC programs for a project are complete
 */
class AllCncProgramsCompleteCheck
{
    /**
     * Check if all CNC programs are complete
     *
     * @param Project $project
     * @param GateRequirement $requirement
     * @return RequirementCheckResult
     */
    public function check(Project $project, GateRequirement $requirement): RequirementCheckResult
    {
        $totalPrograms = $project->cncPrograms()->count();

        // If no CNC programs exist, this check passes (nothing to complete)
        if ($totalPrograms === 0) {
            return new RequirementCheckResult(
                true,
                'No CNC programs to complete',
                ['total' => 0, 'complete' => 0]
            );
        }

        $completePrograms = $project->cncPrograms()
            ->where('status', CncProgram::STATUS_COMPLETE)
            ->count();

        $passed = $completePrograms === $totalPrograms;

        if ($passed) {
            return new RequirementCheckResult(
                true,
                "All {$totalPrograms} CNC programs are complete",
                ['total' => $totalPrograms, 'complete' => $completePrograms]
            );
        }

        // Get details about incomplete programs
        $incompletePrograms = $project->cncPrograms()
            ->where('status', '!=', CncProgram::STATUS_COMPLETE)
            ->pluck('name')
            ->toArray();

        return new RequirementCheckResult(
            false,
            "{$completePrograms}/{$totalPrograms} CNC programs complete",
            [
                'total' => $totalPrograms,
                'complete' => $completePrograms,
                'incomplete_programs' => $incompletePrograms,
            ]
        );
    }
}
