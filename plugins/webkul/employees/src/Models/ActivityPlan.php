<?php

namespace Webkul\Employee\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Webkul\Support\Models\ActivityPlan as BaseActivityPlan;

/**
 * Activity Plan Eloquent model
 *
 */
class ActivityPlan extends BaseActivityPlan
{
    /**
     * Department
     *
     * @return BelongsTo
     */
    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }
}
