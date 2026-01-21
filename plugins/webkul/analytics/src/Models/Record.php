<?php

namespace Webkul\Analytic\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Webkul\Employee\Models\WorkLocation;
use Webkul\Partner\Models\Partner;
use Webkul\Security\Models\User;
use Webkul\Support\Models\Company;

/**
 * Record Eloquent model
 *
 * @property int $id
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property string|null $type
 * @property string|null $name
 * @property \Carbon\Carbon|null $date
 * @property float $amount
 * @property float $unit_amount
 * @property int $partner_id
 * @property int $company_id
 * @property int $user_id
 * @property int $creator_id
 * @property-read \Illuminate\Database\Eloquent\Model|null $partner
 * @property-read \Illuminate\Database\Eloquent\Model|null $creator
 * @property-read \Illuminate\Database\Eloquent\Model|null $user
 * @property-read \Illuminate\Database\Eloquent\Model|null $company
 *
 */
class Record extends Model
{
    /**
     * Table name.
     *
     * @var string
     */
    protected $table = 'analytic_records';

    /**
     * Fillable.
     *
     * @var array
     */
    protected $fillable = [
        'type',
        'name',
        'date',
        'amount',
        'unit_amount',
        'partner_id',
        'company_id',
        'user_id',
        'creator_id',
        // Clock fields
        'clock_in_time',
        'clock_out_time',
        'break_duration_minutes',
        'entry_type',
        'approved_by',
        'approved_at',
        'work_location_id',
        'clock_notes',
        // Lunch fields
        'lunch_start_time',
        'lunch_end_time',
    ];

    /**
     * Casts.
     *
     * @var array
     */
    protected $casts = [
        'date' => 'date',
        'clock_in_time' => 'datetime:H:i:s',
        'clock_out_time' => 'datetime:H:i:s',
        'break_duration_minutes' => 'integer',
        'approved_at' => 'datetime',
        'lunch_start_time' => 'datetime:H:i:s',
        'lunch_end_time' => 'datetime:H:i:s',
    ];

    /**
     * Partner
     *
     * @return BelongsTo
     */
    public function partner(): BelongsTo
    {
        return $this->belongsTo(Partner::class);
    }

    /**
     * Creator
     *
     * @return BelongsTo
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * User
     *
     * @return BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Company
     *
     * @return BelongsTo
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Approved By (User who approved manual entry)
     *
     * @return BelongsTo
     */
    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * Work Location
     *
     * @return BelongsTo
     */
    public function workLocation(): BelongsTo
    {
        return $this->belongsTo(WorkLocation::class);
    }

    /**
     * Check if this is a clock entry (vs timesheet)
     */
    public function isClockEntry(): bool
    {
        return $this->entry_type === 'clock';
    }

    /**
     * Check if this entry needs approval
     */
    public function needsApproval(): bool
    {
        return $this->entry_type === 'manual' && is_null($this->approved_by);
    }

    /**
     * Calculate working hours from clock times
     * Returns hours as decimal (e.g., 8.5 for 8 hours 30 minutes)
     */
    public function calculateWorkingHours(): ?float
    {
        if (!$this->clock_in_time || !$this->clock_out_time) {
            return null;
        }

        $clockIn = \Carbon\Carbon::parse($this->clock_in_time);
        $clockOut = \Carbon\Carbon::parse($this->clock_out_time);

        // Total minutes worked
        $totalMinutes = $clockOut->diffInMinutes($clockIn);

        // Subtract break duration
        $workingMinutes = $totalMinutes - ($this->break_duration_minutes ?? 60);

        // Convert to hours (decimal)
        return round($workingMinutes / 60, 2);
    }
}
