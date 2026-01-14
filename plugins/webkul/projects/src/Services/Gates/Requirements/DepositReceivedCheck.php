<?php

namespace Webkul\Project\Services\Gates\Requirements;

use Webkul\Project\Models\GateRequirement;
use Webkul\Project\Models\Project;
use Webkul\Project\Services\Gates\RequirementCheckResult;

/**
 * Check if deposit payment has been received for the project.
 */
class DepositReceivedCheck
{
    /**
     * Check if deposit has been received.
     *
     * @param Project $project
     * @param GateRequirement $requirement
     * @return RequirementCheckResult
     */
    public function check(Project $project, GateRequirement $requirement): RequirementCheckResult
    {
        $salesOrder = $project->orders()->first();

        if (!$salesOrder) {
            return new RequirementCheckResult(
                false,
                'No sales order found for project',
                ['has_order' => false]
            );
        }

        $depositPaid = $salesOrder->deposit_paid_at !== null;

        return new RequirementCheckResult(
            $depositPaid,
            $depositPaid 
                ? "Deposit received on {$salesOrder->deposit_paid_at->format('M j, Y')}"
                : 'Deposit payment not yet recorded',
            [
                'order_id' => $salesOrder->id,
                'order_name' => $salesOrder->name,
                'deposit_paid_at' => $salesOrder->deposit_paid_at?->toIso8601String(),
                'deposit_amount' => $salesOrder->deposit_amount,
            ]
        );
    }
}
