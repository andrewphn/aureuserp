<?php

namespace Webkul\Project\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Webkul\Security\Models\User;
use Webkul\Chatter\Traits\HasChatter;
use Webkul\Chatter\Traits\HasLogActivity;

/**
 * Room Location Eloquent model
 *
 * @property int $id
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property \Carbon\Carbon|null $deleted_at
 * @property int $room_id
 * @property string|null $name
 * @property string|null $location_type
 * @property int $sequence
 * @property string|null $elevation_reference
 * @property string|null $notes
 * @property int $sort_order
 * @property string|null $cabinet_level
 * @property string|null $material_category
 * @property string|null $finish_option
 * @property int $creator_id
 * @property-read \Illuminate\Database\Eloquent\Collection $cabinetRuns
 * @property-read \Illuminate\Database\Eloquent\Model|null $room
 * @property-read \Illuminate\Database\Eloquent\Model|null $creator
 *
 */
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
        'cabinet_level',
        'material_category',
        'finish_option',
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

    /**
     * Cabinet Runs
     *
     * @return HasMany
     */
    public function cabinetRuns(): HasMany
    {
        return $this->hasMany(CabinetRun::class, 'room_location_id');
    }

    /**
     * Creator
     *
     * @return BelongsTo
     */
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
    /**
     * Scope query to Ordered
     *
     * @param mixed $query The search query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('sequence')->orderBy('name');
    }

    /**
     * Scope: Filter by location type
     */
    /**
     * Scope query to By Type
     *
     * @param mixed $query The search query
     * @param string $type
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByType($query, string $type)
    {
        return $query->where('location_type', $type);
    }

    /**
     * Scope: With run and cabinet counts
     */
    /**
     * Scope query to With Counts
     *
     * @param mixed $query The search query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeWithCounts($query)
    {
        return $query->withCount('cabinetRuns');
    }
}
