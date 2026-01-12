<?php

namespace Webkul\Project\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Webkul\Security\Models\User;
use Webkul\Chatter\Traits\HasChatter;
use Webkul\Chatter\Traits\HasLogActivity;
use Webkul\Support\Traits\HasComplexityScore;

/**
 * Room Location Eloquent model
 *
 * @property int $id
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property \Carbon\Carbon|null $deleted_at
 * @property int $room_id
 * @property string|null $name
 * @property string|null $location_code
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
    use SoftDeletes, HasChatter, HasLogActivity, HasComplexityScore;

    protected $table = 'projects_room_locations';

    protected $fillable = [
        'room_id',
        'name',
        'location_code',
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
        'location_code' => 'Location Code',
        'location_type' => 'Location Type',
        'sequence' => 'Sequence',
        'elevation_reference' => 'Elevation Reference',
        'notes' => 'Notes',
    ];

    /**
     * Location name patterns to code mapping
     * Order matters - more specific patterns should come first
     */
    protected static array $locationCodePatterns = [
        '/sink\s*wall/i' => 'SW',
        '/north\s*wall/i' => 'NW',
        '/south\s*wall/i' => 'STH',  // Avoids conflict with Sink Wall
        '/east\s*wall/i' => 'EW',
        '/west\s*wall/i' => 'WW',
        '/back\s*wall/i' => 'BW',
        '/range\s*wall/i' => 'RW',
        '/cooktop\s*wall/i' => 'CW',
        '/fridge\s*wall/i' => 'FW',
        '/island/i' => 'ISL',
        '/peninsula/i' => 'PEN',
        '/corner/i' => 'CRN',
        '/pantry/i' => 'PAN',
        '/bar/i' => 'BAR',
    ];

    /**
     * Boot the model - auto-generate location_code on saving
     */
    protected static function boot()
    {
        parent::boot();

        static::saving(function ($location) {
            // Only auto-generate if location_code is empty
            if (empty($location->location_code)) {
                $location->location_code = $location->generateLocationCode();
            }
        });

        // When location_code changes, regenerate all descendant full_codes
        static::updated(function ($location) {
            if ($location->isDirty('location_code')) {
                $location->regenerateDescendantCodes();
            }
        });
    }

    /**
     * Generate the location code by parsing the name
     * Examples: "Sink Wall" → SW, "North Wall" → NW, "Island" → ISL
     */
    public function generateLocationCode(): string
    {
        $name = $this->name ?? '';

        // Try pattern matching first
        foreach (static::$locationCodePatterns as $pattern => $code) {
            if (preg_match($pattern, $name)) {
                return $code;
            }
        }

        // Fallback: first letter of each word (up to 3 letters)
        $words = preg_split('/\s+/', $name);
        $initials = array_map(fn($word) => strtoupper(substr($word, 0, 1)), $words);
        return implode('', array_slice($initials, 0, 3));
    }

    /**
     * Regenerate full_codes for all descendant entities
     * Called when location_code changes
     */
    public function regenerateDescendantCodes(): void
    {
        $this->load('cabinetRuns.cabinets.sections');

        foreach ($this->cabinetRuns as $run) {
            foreach ($run->cabinets as $cabinet) {
                $cabinet->full_code = $cabinet->generateFullCode();
                $cabinet->saveQuietly();

                foreach ($cabinet->sections as $section) {
                    $section->full_code = $section->generateFullCode();
                    $section->saveQuietly();

                    // Regenerate component codes
                    foreach (['doors', 'drawers', 'shelves', 'pullouts'] as $relation) {
                        if ($section->relationLoaded($relation)) {
                            foreach ($section->$relation as $component) {
                                $component->full_code = $component->generateFullCode();
                                $component->saveQuietly();
                            }
                        }
                    }
                }
            }
        }
    }

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
     * Hardware requirements for this room location
     */
    public function hardwareRequirements(): HasMany
    {
        return $this->hasMany(HardwareRequirement::class, 'room_location_id');
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
     * Uses the calculated linear_feet accessor from each run (which accounts for cabinet qty)
     */
    public function getTotalLinearFeetAttribute(): float
    {
        if ($this->relationLoaded('cabinetRuns')) {
            return $this->cabinetRuns->sum(fn($run) => $run->linear_feet);
        }

        return $this->cabinetRuns()
            ->with('cabinets')
            ->get()
            ->sum(fn($run) => $run->linear_feet);
    }

    /**
     * Get stored linear feet total (from database, not calculated)
     */
    public function getStoredLinearFeetAttribute(): float
    {
        return $this->cabinetRuns()->sum('total_linear_feet') ?? 0;
    }

    /**
     * Check if there's a discrepancy between stored and calculated linear feet
     */
    public function getHasLinearFeetDiscrepancyAttribute(): bool
    {
        $calculated = $this->total_linear_feet;
        $stored = $this->stored_linear_feet;

        // Allow small floating point differences
        return abs($calculated - $stored) > 0.1;
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
