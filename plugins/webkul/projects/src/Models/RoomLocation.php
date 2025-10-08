<?php

namespace Webkul\Project\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Webkul\Security\Models\User;
use Webkul\Chatter\Traits\HasChatter;
use Webkul\Chatter\Traits\HasLogActivity;

class RoomLocation extends Model
{
    use SoftDeletes, HasChatter, HasLogActivity;

    protected $table = 'projects_room_locations';

    protected $fillable = [
        'room_id',
        'name',
        'location_type',
        'sequence',
        'elevation_reference',
        'notes',
        'sort_order',
        'creator_id',
    ];

    protected $casts = [
        'sequence' => 'integer',
        'sort_order' => 'integer',
    ];

    /**
     * Attributes to log for Chatter activity tracking
     */
    protected $logAttributes = [
        'room.name' => 'Room',
        'name' => 'Location Name',
        'location_type' => 'Location Type',
        'sequence' => 'Sequence',
        'elevation_reference' => 'Elevation Reference',
        'notes' => 'Notes',
    ];

    /**
     * Relationships
     */
    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class, 'room_id');
    }

    public function cabinetRuns(): HasMany
    {
        return $this->hasMany(CabinetRun::class, 'room_location_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creator_id');
    }

    /**
     * Calculated Attributes
     */

    /**
     * Get total linear feet for all cabinet runs in this location
     */
    public function getTotalLinearFeetAttribute(): float
    {
        return $this->cabinetRuns()->sum('total_linear_feet') ?? 0;
    }

    /**
     * Get count of cabinet runs in this location
     */
    public function getRunCountAttribute(): int
    {
        return $this->cabinetRuns()->count();
    }

    /**
     * Get total cabinet count across all runs
     */
    public function getCabinetCountAttribute(): int
    {
        return $this->cabinetRuns()
            ->withCount('cabinets')
            ->get()
            ->sum('cabinets_count');
    }

    /**
     * Scopes
     */

    /**
     * Scope: Order by sort order and sequence
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('sequence')->orderBy('name');
    }

    /**
     * Scope: Filter by location type
     */
    public function scopeByType($query, string $type)
    {
        return $query->where('location_type', $type);
    }

    /**
     * Scope: With run and cabinet counts
     */
    public function scopeWithCounts($query)
    {
        return $query->withCount('cabinetRuns');
    }
}
