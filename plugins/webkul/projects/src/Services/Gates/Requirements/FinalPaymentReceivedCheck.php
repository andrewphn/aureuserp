<?php

namespace Webkul\Project\Services\Gates\Requirements;

use Webkul\Project\Models\GateRequirement;
use Webkul\Project\Models\Project;
use Webkul\Project\Services\Gates\RequirementCheckResult;

/**
 * Check if final payment has been received for the project.
 */
class FinalPaymentReceivedCheck
{
    /**
     * Check if final payment has been received.
     *
     * @param Project $project
     * @param GateRequirement $requirement
     * @return RequirementCheckResult
     */
    public function check(Project $project, GateRequirement $requirement): RequirementCheckResult
    {
        $salesOrders = $project->orders;

        if ($salesOrders->isEmpty()) {
            return new RequirementCheckResult(
                false,
                'No sales orders found for project',
                ['has_orders' => false]
            );
        }

        // Check for a final payment order or if all orders are paid
        $finalPaymentOrder = $salesOrders->firstWhere('woodworking_order_type', 'final_payment');
        
        if ($finalPaymentOrder) {
            $finalPaid = $finalPaymentOrder->final_paid_at !== null 
                || $finalPaymentOrder->invoice_status === 'invoiced';

            return new RequirementCheckResult(
                $finalPaid,
                $finalPaid 
                    ? "Final payment received on " . ($finalPaymentOrder->final_paid_at?->format('M j, Y') ?? 'N/A')
                    : 'Final payment not yet received',
                [
                    'order_id' => $finalPaymentOrder->id,
                    'order_name' => $finalPaymentOrder->name,
                    'final_paid_at' => $finalPaymentOrder->final_paid_at?->toIso8601String(),
                    'amount' => $finalPaymentOrder->amount_total,
                ]
            );
        }

        // If no specific final payment order, check if primary order is fully paid
        $primaryOrder = $salesOrders->first();
        $fullyPaid = $primaryOrder->final_paid_at !== null;

        return new RequirementCheckResult(
            $fullyPaid,
            $fullyPaid 
                ? 'All payments received'
                : 'Final payment not recorded',
            [
                'order_id' => $primaryOrder->id,
                'total_orders' => $salesOrders->count(),
                'final_paid_at' => $primaryOrder->final_paid_at?->toIso8601String(),
            ]
        );
    }
}
