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
 * Cabinet Run Eloquent model
 *
 * @property int $id
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property \Carbon\Carbon|null $deleted_at
 * @property int $room_location_id
 * @property string|null $name
 * @property string|null $run_type
 * @property float $total_linear_feet
 * @property float $start_wall_measurement
 * @property float $end_wall_measurement
 * @property string|null $notes
 * @property int $sort_order
 * @property string|null $cabinet_level
 * @property string|null $material_category
 * @property string|null $finish_option
 * @property int $creator_id
 * @property-read \Illuminate\Database\Eloquent\Collection $cabinets
 * @property-read \Illuminate\Database\Eloquent\Model|null $roomLocation
 * @property-read \Illuminate\Database\Eloquent\Model|null $creator
 *
 */
class CabinetRun extends Model
{
    use SoftDeletes, HasChatter, HasLogActivity;

    protected $table = 'projects_cabinet_runs';

    protected $fillable = [
        'room_location_id',
        'name',
        'run_type',
        'total_linear_feet',
        'start_wall_measurement',
        'end_wall_measurement',
        'notes',
        'sort_order',
        'cabinet_level',
        'material_category',
        'finish_option',
        'creator_id',
        // Hardware summary counts (brand-agnostic)
        'hinges_count',
        'slides_count',
        'shelf_pins_count',
        'pullouts_count',
    ];

    protected $casts = [
        'total_linear_feet' => 'decimal:2',
        'start_wall_measurement' => 'decimal:2',
        'end_wall_measurement' => 'decimal:2',
        'sort_order' => 'integer',
        'hinges_count' => 'integer',
        'slides_count' => 'integer',
        'shelf_pins_count' => 'integer',
        'pullouts_count' => 'integer',
    ];

    /**
     * Attributes to log for Chatter activity tracking
     */
    protected $logAttributes = [
        'roomLocation.room.name' => 'Room',
        'roomLocation.name' => 'Location',
        'name' => 'Cabinet Run Name',
        'run_type' => 'Run Type',
        'total_linear_feet' => 'Total Linear Feet',
        'start_wall_measurement' => 'Start Measurement',
        'end_wall_measurement' => 'End Measurement',
        'notes' => 'Notes',
    ];

    /**
     * Relationships
     */
    public function roomLocation(): BelongsTo
    {
        return $this->belongsTo(RoomLocation::class, 'room_location_id');
    }

    /**
     * Cabinets
     *
     * @return HasMany
     */
    public function cabinets(): HasMany
    {
        return $this->hasMany(Cabinet::class, 'cabinet_run_id');
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
     * Hardware requirements for this cabinet run (brand-agnostic)
     *
     * @return HasMany
     */
    public function hardwareRequirements(): HasMany
    {
        return $this->hasMany(HardwareRequirement::class, 'cabinet_run_id');
    }

    /**
     * Calculated Attributes
     */

    /**
     * Get calculated total linear feet from cabinets
     */
    public function getCalculatedLinearFeetAttribute(): float
    {
        return $this->cabinets()
            ->sum('linear_feet') ?? 0;
    }

    /**
     * Get cabinet count in this run
     */
    public function getCabinetCountAttribute(): int
    {
        return $this->cabinets()->count();
    }

    /**
     * Get total wall span (start to end measurement)
     */
    public function getWallSpanInchesAttribute(): ?float
    {
        if (!$this->start_wall_measurement || !$this->end_wall_measurement) {
            return null;
        }

        return abs($this->end_wall_measurement - $this->start_wall_measurement);
    }

    /**
     * Get run type display name
     */
    public function getRunTypeDisplayAttribute(): string
    {
        return match($this->run_type) {
            'base' => 'Base Cabinets',
            'wall' => 'Wall Cabinets',
            'tall' => 'Tall Cabinets',
            'specialty' => 'Specialty',
            default => ucfirst($this->run_type ?? 'Unknown'),
        };
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
     * Scope: Filter by run type
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
        return $query->where('run_type', $type);
    }

    /**
     * Scope: With cabinet count
     */
    /**
     * Scope query to With Counts
     *
     * @param mixed $query The search query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeWithCounts($query)
    {
        return $query->withCount('cabinets');
    }

    /**
     * Auto-calculate fields before saving
     */
    /**
     * Boot
     *
     * @return void
     */
    protected static function boot()
    {
        parent::boot();

        static::saving(function ($run) {
            // Auto-calculate total linear feet from cabinets if not manually set
            if (!$run->isDirty('total_linear_feet')) {
                $calculated = $run->cabinets()->sum('linear_feet');
                if ($calculated > 0) {
                    $run->total_linear_feet = $calculated;
                }
            }
        });
    }

    /**
     * Material BOM Methods
     */

    /**
     * Generate Bill of Materials for all cabinets in this run
     *
     * @return \Illuminate\Support\Collection
     */
    /**
     * Generate Bom
     *
     */
    public function generateBom(): \Illuminate\Support\Collection
    {
        $bomService = new \Webkul\Project\Services\MaterialBomService();
        return $bomService->generateBomForCabinets($this->cabinets);
    }

    /**
     * Get formatted BOM with product details
     *
     * @return \Illuminate\Support\Collection
     */
    public function getFormattedBom(): \Illuminate\Support\Collection
    {
        $bomService = new \Webkul\Project\Services\MaterialBomService();
        $bom = $bomService->generateBomForCabinets($this->cabinets);
        return $bomService->formatBom($bom, true);
    }

    /**
     * Get material cost estimate for this cabinet run
     *
     * @return float
     */
    /**
     * Estimate Material Cost
     *
     * @return float
     */
    public function estimateMaterialCost(): float
    {
        $bomService = new \Webkul\Project\Services\MaterialBomService();
        $bom = $bomService->generateBomForCabinets($this->cabinets);
        return $bomService->estimateMaterialCost($bom);
    }

    /**
     * Check if materials are available in inventory for this run
     *
     * @return array
     */
    /**
     * Check Material Availability
     *
     * @return array
     */
    public function checkMaterialAvailability(): array
    {
        $bomService = new \Webkul\Project\Services\MaterialBomService();
        $bom = $bomService->generateBomForCabinets($this->cabinets);
        return $bomService->checkMaterialAvailability($bom);
    }

    /**
     * Hardware Summary Methods
     */

    /**
     * Recalculate hardware totals from hardware_requirements table
     * This aggregates all hardware requirements linked to this cabinet run
     * and updates the denormalized count columns for quick access.
     *
     * @return void
     */
    public function recalculateHardwareTotals(): void
    {
        $this->hinges_count = $this->hardwareRequirements()->hinges()->sum('quantity_required');
        $this->slides_count = $this->hardwareRequirements()->slides()->sum('quantity_required');
        $this->shelf_pins_count = $this->hardwareRequirements()->shelfPins()->sum('quantity_required');
        $this->pullouts_count = $this->hardwareRequirements()->pullouts()->sum('quantity_required');
        $this->saveQuietly(); // Avoid triggering observers recursively
    }

    /**
     * Get hardware summary as array (for API/exports)
     *
     * @return array
     */
    public function getHardwareSummary(): array
    {
        return [
            'hinges' => $this->hinges_count ?? 0,
            'slides' => $this->slides_count ?? 0,
            'shelf_pins' => $this->shelf_pins_count ?? 0,
            'pullouts' => $this->pullouts_count ?? 0,
            'total_items' => ($this->hinges_count ?? 0) + ($this->slides_count ?? 0) +
                            ($this->shelf_pins_count ?? 0) + ($this->pullouts_count ?? 0),
        ];
    }
}
