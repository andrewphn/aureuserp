<?php

namespace Webkul\Employee\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Webkul\Employee\Database\Factories\WorkLocationFactory;
use Webkul\Employee\Enums\WorkLocation as WorkLocationEnum;
use Webkul\Field\Traits\HasCustomFields;
use Webkul\Security\Models\User;
use Webkul\Support\Models\Company;

/**
 * Work Location Eloquent model
 *
 * @property int $id
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property \Carbon\Carbon|null $deleted_at
 * @property int $company_id
 * @property int $creator_id
 * @property string|null $name
 * @property mixed $location_type
 * @property string|null $location_number
 * @property string|null $latitude
 * @property string|null $longitude
 * @property bool $is_active
 * @property-read \Illuminate\Database\Eloquent\Model|null $company
 * @property-read \Illuminate\Database\Eloquent\Model|null $createdBy
 *
 */
class WorkLocation extends Model
{
    use HasCustomFields, HasFactory, SoftDeletes;

    protected $table = 'employees_work_locations';

    protected $fillable = [
        'company_id',
        'creator_id',
        'name',
        'location_type',
        'location_number',
        'latitude',
        'longitude',
        'is_active',
    ];

    protected $casts = [
        'is_active'      => 'boolean',
        'location_type'  => WorkLocationEnum::class,
    ];

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
     * Created By
     *
     * @return BelongsTo
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creator_id');
    }

    /**
     * Scope a query to only include active work locations.
     */
    /**
     * Scope query to Active
     *
     * @param Builder $query The search query
     * @return Builder
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Get the factory instance for the model.
     */
    /**
     * New Factory
     *
     * @return WorkLocationFactory
     */
    protected static function newFactory(): WorkLocationFactory
    {
        return WorkLocationFactory::new();
    }
}
