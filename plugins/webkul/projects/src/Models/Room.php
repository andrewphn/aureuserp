<?php

namespace Webkul\Project\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Webkul\Project\Database\Factories\RoomFactory;
use Webkul\Security\Models\User;
use Webkul\Chatter\Traits\HasChatter;
use Webkul\Chatter\Traits\HasLogActivity;

/**
 * Room Eloquent model
 *
 * @property int $id
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property \Carbon\Carbon|null $deleted_at
 * @property int $project_id
 * @property string|null $name
 * @property string|null $room_type
 * @property string|null $floor_number
 * @property int $pdf_page_number
 * @property string|null $pdf_room_label
 * @property string|null $pdf_detail_number
 * @property string|null $pdf_notes
 * @property string|null $notes
 * @property int $sort_order
 * @property string|null $room_code
 * @property string|null $cabinet_level
 * @property string|null $material_category
 * @property string|null $finish_option
 * @property int $creator_id
 * @property-read \Illuminate\Database\Eloquent\Collection $locations
 * @property-read \Illuminate\Database\Eloquent\Collection $cabinets
 * @property-read \Illuminate\Database\Eloquent\Model|null $project
 * @property-read \Illuminate\Database\Eloquent\Model|null $creator
 *
 */
class Room extends Model
{
    use HasFactory, SoftDeletes, HasChatter, HasLogActivity;

    protected $table = 'projects_rooms';

    protected $fillable = [
        'project_id',
        'name',
        'room_code',
        'room_type',
        'floor_number',
        'pdf_page_number',
        'pdf_room_label',
        'pdf_detail_number',
        'pdf_notes',
        'notes',
        'sort_order',
        'cabinet_level',
        'material_category',
        'finish_option',
        'creator_id',
        'quoted_price',
        'estimated_project_value',
        'estimated_cabinet_value',
        'total_linear_feet_tier_1',
        'total_linear_feet_tier_2',
        'total_linear_feet_tier_3',
        'total_linear_feet_tier_4',
        'total_linear_feet_tier_5',
    ];

    protected $casts = [
        'pdf_page_number' => 'integer',
        'sort_order' => 'integer',
    ];

    /**
     * Attributes to log for Chatter activity tracking
     */
    protected $logAttributes = [
        'name' => 'Room Name',
        'room_code' => 'Room Code',
        'room_type' => 'Room Type',
        'floor_number' => 'Floor Number',
        'pdf_page_number' => 'PDF Page Number',
        'pdf_room_label' => 'PDF Room Label',
        'pdf_detail_number' => 'PDF Detail Number',
        'notes' => 'Notes',
    ];

    /**
     * Room type to code prefix mapping
     */
    protected static array $roomTypePrefixes = [
        'kitchen' => 'K',
        'bathroom' => 'BTH',
        'laundry' => 'LAU',
        'office' => 'OFF',
        'pantry' => 'PAN',
        'mudroom' => 'MUD',
        'closet' => 'CLO',
        'bedroom' => 'BED',
        'living_room' => 'LR',
        'dining_room' => 'DR',
        'garage' => 'GAR',
        'basement' => 'BSM',
        'utility' => 'UTL',
    ];

    /**
     * Boot the model - auto-generate room_code on saving
     */
    protected static function boot()
    {
        parent::boot();

        static::saving(function ($room) {
            // Only auto-generate if room_code is empty
            if (empty($room->room_code)) {
                $room->room_code = $room->generateRoomCode();
            }
        });

        // When room_code changes, regenerate all descendant full_codes
        static::updated(function ($room) {
            if ($room->isDirty('room_code')) {
                $room->regenerateDescendantCodes();
            }
        });
    }

    /**
     * Generate the room code based on room type and sequence
     * Format: K1, BTH1, LAU1, etc.
     */
    public function generateRoomCode(): string
    {
        $prefix = static::$roomTypePrefixes[$this->room_type] ?? 'RM';

        // Get the sequence number (count of rooms with same type in same project + 1)
        $sequence = $this->sort_order ?? 1;

        // If sort_order isn't set, count existing rooms of same type
        if (!$this->sort_order && $this->project_id) {
            $existingCount = static::where('project_id', $this->project_id)
                ->where('room_type', $this->room_type)
                ->where('id', '!=', $this->id ?? 0)
                ->count();
            $sequence = $existingCount + 1;
        }

        return $prefix . $sequence;
    }

    /**
     * Regenerate full_codes for all descendant entities
     * Called when room_code changes
     */
    public function regenerateDescendantCodes(): void
    {
        // Regenerate codes for all locations, runs, sections, and components
        $this->load('locations.cabinetRuns.cabinets.sections');

        foreach ($this->locations as $location) {
            foreach ($location->cabinetRuns as $run) {
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
    }

    /**
     * Relationships
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class, 'project_id');
    }

    /**
     * Locations
     *
     * @return HasMany
     */
    public function locations(): HasMany
    {
        return $this->hasMany(RoomLocation::class, 'room_id');
    }

    /**
     * Hardware requirements for this room
     */
    public function hardwareRequirements(): HasMany
    {
        return $this->hasMany(HardwareRequirement::class, 'room_id');
    }

    /**
     * Cabinets
     *
     * @return HasMany
     */
    public function cabinets(): HasMany
    {
        return $this->hasMany(Cabinet::class, 'room_id');
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
     * Get total linear feet for all cabinets in this room
     * Calculates from cabinets with quantity, rolling up through locations and runs
     */
    public function getTotalLinearFeetAttribute(): float
    {
        // Roll up from locations -> runs -> cabinets (with quantity)
        if ($this->relationLoaded('locations')) {
            return $this->locations->sum(fn($loc) => $loc->total_linear_feet);
        }

        return $this->locations()
            ->with('cabinetRuns.cabinets')
            ->get()
            ->sum(fn($loc) => $loc->total_linear_feet);
    }

    /**
     * Get stored linear feet (sum of stored values, not calculated)
     */
    public function getStoredLinearFeetAttribute(): float
    {
        return $this->cabinets()->sum('linear_feet') ?? 0;
    }

    /**
     * Check if there's a discrepancy between stored and calculated linear feet
     */
    public function getHasLinearFeetDiscrepancyAttribute(): bool
    {
        $calculated = $this->total_linear_feet;
        $stored = $this->stored_linear_feet;

        return abs($calculated - $stored) > 0.1;
    }

    /**
     * Get count of cabinets in this room
     */
    public function getCabinetCountAttribute(): int
    {
        return $this->cabinets()->count();
    }

    /**
     * Get PDF reference display string
     */
    public function getPdfReferenceAttribute(): ?string
    {
        if (!$this->pdf_page_number) {
            return null;
        }

        $parts = ["Page {$this->pdf_page_number}"];

        if ($this->pdf_room_label) {
            $parts[] = $this->pdf_room_label;
        }

        if ($this->pdf_detail_number) {
            $parts[] = "Detail {$this->pdf_detail_number}";
        }

        return implode(' - ', $parts);
    }

    /**
     * Scopes
     */

    /**
     * Scope: Order by sort order
     */
    /**
     * Scope query to Ordered
     *
     * @param mixed $query The search query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }

    /**
     * Scope: Filter by room type
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
        return $query->where('room_type', $type);
    }

    /**
     * Scope: Filter by floor number
     */
    /**
     * Scope query to By Floor
     *
     * @param mixed $query The search query
     * @param string $floor
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByFloor($query, string $floor)
    {
        return $query->where('floor_number', $floor);
    }

    /**
     * Scope: Rooms on specific PDF page
     */
    /**
     * Scope query to On Page
     *
     * @param mixed $query The search query
     * @param int $pageNumber
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeOnPage($query, int $pageNumber)
    {
        return $query->where('pdf_page_number', $pageNumber);
    }

    /**
     * Scope: With cabinet and location counts
     */
    /**
     * Scope query to With Counts
     *
     * @param mixed $query The search query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeWithCounts($query)
    {
        return $query->withCount(['cabinets', 'locations']);
    }

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): RoomFactory
    {
        return RoomFactory::new();
    }
}
