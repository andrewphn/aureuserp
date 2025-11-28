<?php

namespace Webkul\Employee\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\EloquentSortable\Sortable;
use Spatie\EloquentSortable\SortableTrait;
use Webkul\Employee\Database\Factories\DepartureReasonFactory;
use Webkul\Field\Traits\HasCustomFields;
use Webkul\Security\Models\User;

/**
 * Departure Reason Eloquent model
 *
 * @property int $id
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property string|null $sort
 * @property string|null $reason_code
 * @property int $creator_id
 * @property string|null $name
 * @property-read \Illuminate\Database\Eloquent\Collection $employees
 * @property-read \Illuminate\Database\Eloquent\Model|null $createdBy
 *
 */
class DepartureReason extends Model implements Sortable
{
    use HasCustomFields, HasFactory, SortableTrait;

    protected $table = 'employees_departure_reasons';

    protected $fillable = [
        'sort',
        'reason_code',
        'creator_id',
        'name',
    ];

    public $sortable = [
        'order_column_name'  => 'sort',
        'sort_when_creating' => true,
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
     * Employees
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function employees()
    {
        return $this->hasMany(Employee::class);
    }

    /**
     * Get the factory instance for the model.
     */
    /**
     * New Factory
     *
     * @return DepartureReasonFactory
     */
    protected static function newFactory(): DepartureReasonFactory
    {
        return DepartureReasonFactory::new();
    }
}
