<?php

namespace Webkul\TimeOff\Filament\Clusters\Configurations\Resources\AccrualPlanResource\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Webkul\TimeOff\Traits\LeaveAccrualPlan;

/**
 * Milestone Relation Manager class
 *
 * @see \Filament\Resources\Resource
 */
class MilestoneRelationManager extends RelationManager
{
    use LeaveAccrualPlan;

    protected static string $relationship = 'leaveAccrualLevels';
}
