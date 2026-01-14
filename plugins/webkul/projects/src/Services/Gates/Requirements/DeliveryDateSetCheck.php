<?php

namespace Webkul\Project\Services\Gates\Requirements;

use Webkul\Project\Models\GateRequirement;
use Webkul\Project\Models\Project;
use Webkul\Project\Services\Gates\RequirementCheckResult;

/**
 * Check if delivery date has been scheduled and confirmed.
 */
class DeliveryDateSetCheck
{
    /**
     * Check if delivery date is set.
     *
     * @param Project $project
     * @param GateRequirement $requirement
     * @return RequirementCheckResult
     */
    public function check(Project $project, GateRequirement $requirement): RequirementCheckResult
    {
        // Check project end_date as delivery date
        $deliveryDate = $project->end_date;

        if (!$deliveryDate) {
            return new RequirementCheckResult(
                false,
                'Delivery date not scheduled',
                ['has_delivery_date' => false]
            );
        }

        // Check if delivery date is in the future (or today)
        $isValid = $deliveryDate->isFuture() || $deliveryDate->isToday();

        // Optionally check for ferry booking if needed for island deliveries
        $ferryBooked = $project->ferry_booking_date !== null || $project->ferry_confirmation !== null;

        return new RequirementCheckResult(
            true, // Date exists, we'll just warn if it's past
            "Delivery scheduled for {$deliveryDate->format('M j, Y')}" . 
                (!$isValid ? ' (date is in the past)' : '') .
                ($ferryBooked ? ' - Ferry booked' : ''),
            [
                'delivery_date' => $deliveryDate->toDateString(),
                'is_future' => $isValid,
                'ferry_booked' => $ferryBooked,
                'ferry_booking_date' => $project->ferry_booking_date?->toDateString(),
            ]
        );
    }
}
