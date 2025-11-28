<?php

namespace Webkul\Project\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Webkul\Support\Models\Country;
use Webkul\Support\Models\State;

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
