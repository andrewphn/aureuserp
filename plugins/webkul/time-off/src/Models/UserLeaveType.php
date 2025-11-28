<?php

namespace Webkul\TimeOff\Models;

use Illuminate\Database\Eloquent\Model;
use Webkul\Security\Models\User;

/**
 * User Leave Type Eloquent model
 *
 * @property int $id
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property int $user_id
 * @property int $leave_type_id
 * @property-read \Illuminate\Database\Eloquent\Model|null $user
 * @property-read \Illuminate\Database\Eloquent\Model|null $leaveType
 *
 */
class UserLeaveType extends Model
{
    protected $table = 'time_off_user_leave_types';

    protected $timestamps = false;

    protected $fillable = [
        'user_id',
        'leave_type_id',
    ];

    /**
     * User
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Leave Type
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function leaveType()
    {
        return $this->belongsTo(LeaveType::class, 'leave_type_id');
    }
}
