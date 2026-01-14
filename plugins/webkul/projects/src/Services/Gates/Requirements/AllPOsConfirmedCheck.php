<?php

namespace Webkul\Project\Services\Gates\Requirements;

use Webkul\Project\Models\GateRequirement;
use Webkul\Project\Models\Project;
use Webkul\Project\Services\Gates\RequirementCheckResult;

/**
 * Check if all purchase orders for the project are confirmed.
 */
class AllPOsConfirmedCheck
{
    /**
     * States that indicate a confirmed PO.
     */
    protected array $confirmedStates = ['purchase', 'done'];

    /**
     * Check if all POs are confirmed.
     *
     * @param Project $project
     * @param GateRequirement $requirement
     * @return RequirementCheckResult
     */
    public function check(Project $project, GateRequirement $requirement): RequirementCheckResult
    {
        // Get POs linked to this project through material reservations
        $purchaseOrders = $this->getProjectPurchaseOrders($project);

        if ($purchaseOrders->isEmpty()) {
            // No POs might be fine if everything is from inventory
            return new RequirementCheckResult(
                true,
                'No purchase orders linked to project (materials sourced from inventory)',
                ['po_count' => 0]
            );
        }

        $total = $purchaseOrders->count();
        $unconfirmed = $purchaseOrders->filter(function ($po) {
            return !in_array($po->state, $this->confirmedStates);
        });

        $confirmedCount = $total - $unconfirmed->count();
        $passed = $unconfirmed->isEmpty();

        $unconfirmedDetails = $unconfirmed->map(function ($po) {
            return [
                'po_id' => $po->id,
                'po_name' => $po->name,
                'state' => $po->state,
                'vendor' => $po->partner?->name,
            ];
        })->values()->toArray();

        return new RequirementCheckResult(
            $passed,
            $passed 
                ? "All {$total} purchase orders are confirmed"
                : "{$confirmedCount}/{$total} purchase orders confirmed - " . $unconfirmed->count() . " pending",
            [
                'total' => $total,
                'confirmed' => $confirmedCount,
                'unconfirmed' => $unconfirmedDetails,
            ]
        );
    }

    /**
     * Get all purchase orders related to the project.
     */
    protected function getProjectPurchaseOrders(Project $project)
    {
        // Get through material reservations -> purchase order lines
        return \Webkul\Purchase\Models\Order::query()
            ->whereHas('lines', function ($query) use ($project) {
                $query->where('project_id', $project->id);
            })
            ->get();
    }
}
