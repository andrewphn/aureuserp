<?php

namespace Webkul\Project\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Webkul\Support\Models\Country;
use Webkul\Support\Models\State;

/**
 * Project Address Eloquent model
 *
 * @property int $id
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property int $project_id
 * @property string|null $type
 * @property string|null $street1
 * @property string|null $street2
 * @property string|null $city
 * @property string|null $zip
 * @property int $state_id
 * @property int $country_id
 * @property string|null $notes
 * @property bool $is_primary
 * @property-read \Illuminate\Database\Eloquent\Model|null $project
 * @property-read \Illuminate\Database\Eloquent\Model|null $state
 * @property-read \Illuminate\Database\Eloquent\Model|null $country
 *
 */
class ProjectAddress extends Model
{
    /**
     * Table name.
     *
     * @var string
     */
    protected $table = 'projects_project_addresses';

    /**
     * Fillable.
     *
     * @var array
     */
    protected $fillable = [
        'project_id',
        'type',
        'street1',
        'street2',
        'city',
        'zip',
        'state_id',
        'country_id',
        'notes',
        'is_primary',
    ];

    /**
     * Casts.
     *
     * @var array
     */
    protected $casts = [
        'is_primary' => 'boolean',
    ];

    /**
     * Get the project that owns the address.
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class, 'project_id');
    }

    /**
     * Get the state.
     */
    public function state(): BelongsTo
    {
        return $this->belongsTo(State::class);
    }

    /**
     * Get the country.
     */
    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }

    /**
     * Get formatted address.
     */
    public function getFormattedAddressAttribute(): string
    {
        $parts = array_filter([
            $this->street1,
            $this->street2,
            $this->city,
            $this->state?->name,
            $this->zip,
            $this->country?->name,
        ]);

        return implode(', ', $parts);
    }
}
