<?php

namespace Webkul\Employee\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Webkul\Employee\Database\Factories\CalendarFactory;
use Webkul\Field\Traits\HasCustomFields;
use Webkul\Security\Models\User;
use Webkul\Support\Models\Company;

/**
 * Calendar Eloquent model
 *
 * @property int $id
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property \Carbon\Carbon|null $deleted_at
 * @property string|null $name
 * @property string|null $timezone
 * @property float $hours_per_day
 * @property bool $is_active
 * @property string|null $two_weeks_calendar
 * @property float $flexible_hours
 * @property float $full_time_required_hours
 * @property int $creator_id
 * @property int $company_id
 * @property-read \Illuminate\Database\Eloquent\Collection $attendance
 * @property-read \Illuminate\Database\Eloquent\Model|null $createdBy
 * @property-read \Illuminate\Database\Eloquent\Model|null $company
 *
 */
class Calendar extends Model
{
    use HasCustomFields, HasFactory, SoftDeletes;

    protected $table = 'employees_calendars';

    protected $fillable = [
        'name',
        'timezone',
        'hours_per_day',
        'is_active',
        'two_weeks_calendar',
        'flexible_hours',
        'full_time_required_hours',
        'creator_id',
        'company_id',
    ];

    /**
     * Created By
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function createdBy()
    {
        return $this->belongsTo(User::class, 'creator_id');
    }

    /**
     * Company
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Attendance
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function attendance()
    {
        return $this->hasMany(CalendarAttendance::class);
    }

    /**
     * Get the factory instance for the model.
     */
    /**
     * New Factory
     *
     * @return CalendarFactory
     */
    protected static function newFactory(): CalendarFactory
    {
        return CalendarFactory::new();
    }
}
