<?php

namespace Webkul\Project\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Webkul\Product\Models\Product;
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
 * @property string|null $run_code
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
        'run_code',
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
        // Default hardware products for this run
        'default_hinge_product_id',
        'default_slide_product_id',
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
        'run_code' => 'Run Code',
        'run_type' => 'Run Type',
        'total_linear_feet' => 'Total Linear Feet',
        'start_wall_measurement' => 'Start Measurement',
        'end_wall_measurement' => 'End Measurement',
        'notes' => 'Notes',
    ];

    /**
     * Run type to code prefix mapping
     */
    protected static array $runTypePrefixes = [
        'base' => 'B',
        'wall' => 'U',      // Upper
        'tall' => 'T',
        'specialty' => 'S',
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
     * Hardware requirements for this cabinet run
     */
    public function hardwareRequirements(): HasMany
    {
        return $this->hasMany(HardwareRequirement::class, 'cabinet_run_id');
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
     * Default hinge product for this cabinet run
     *
     * @return BelongsTo
     */
    public function defaultHingeProduct(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'default_hinge_product_id');
    }

    /**
     * Default slide product for this cabinet run
     *
     * @return BelongsTo
     */
    public function defaultSlideProduct(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'default_slide_product_id');
    }

    /**
     * Calculated Attributes
     */

    /**
     * Get calculated total linear feet from cabinets
     * Accounts for cabinet quantity (e.g., 2x B24 = 4 LF not 2 LF)
     */
    public function getCalculatedLinearFeetAttribute(): float
    {
        return $this->cabinets()
            ->get()
            ->sum(fn($cabinet) => ($cabinet->linear_feet ?? 0) * ($cabinet->quantity ?? 1));
    }

    /**
     * Get linear feet - prefers calculated from cabinets, falls back to stored value
     * This accessor allows using $run->linear_feet consistently
     */
    public function getLinearFeetAttribute(): float
    {
        // If cabinets exist, calculate from them (with quantity)
        if ($this->relationLoaded('cabinets') && $this->cabinets->count() > 0) {
            return $this->cabinets->sum(fn($cabinet) => ($cabinet->linear_feet ?? 0) * ($cabinet->quantity ?? 1));
        }

        // If not loaded but we can query
        $cabinetCount = $this->cabinets()->count();
        if ($cabinetCount > 0) {
            return $this->cabinets()
                ->get()
                ->sum(fn($cabinet) => ($cabinet->linear_feet ?? 0) * ($cabinet->quantity ?? 1));
        }

        // Fall back to stored total_linear_feet
        return (float) ($this->total_linear_feet ?? 0);
    }

    /**
     * Get stored linear feet (the manually entered value)
     */
    public function getStoredLinearFeetAttribute(): float
    {
        return (float) ($this->total_linear_feet ?? 0);
    }

    /**
     * Check if there's a discrepancy between stored and calculated linear feet
     * Returns true if cabinets exist AND their sum differs from stored value
     */
    public function getHasLinearFeetDiscrepancyAttribute(): bool
    {
        $cabinetCount = $this->relationLoaded('cabinets')
            ? $this->cabinets->count()
            : $this->cabinets()->count();

        // No discrepancy if no cabinets (using stored value is expected)
        if ($cabinetCount === 0) {
            return false;
        }

        $calculated = $this->calculated_linear_feet;
        $stored = $this->stored_linear_feet;

        // Allow small floating point differences
        return abs($calculated - $stored) > 0.1 && $stored > 0;
    }

    /**
     * Get discrepancy amount (positive = calculated > stored)
     */
    public function getLinearFeetDiscrepancyAttribute(): float
    {
        return $this->calculated_linear_feet - $this->stored_linear_feet;
    }

    /**
     * Check if stored LF is greater than calculated (missing cabinet measurements)
     * This indicates cabinets may be missing or cabinets may be missing width values
     */
    public function getHasMissingMeasurementsAttribute(): bool
    {
        $stored = $this->stored_linear_feet;
        $calculated = $this->calculated_linear_feet;

        // If stored is greater than calculated, we're missing cabinet measurements
        return $stored > 0 && $calculated < $stored && abs($stored - $calculated) > 0.1;
    }

    /**
     * Get the amount of linear feet that are unaccounted for
     * Positive value means stored > calculated (missing measurements)
     */
    public function getMissingLinearFeetAttribute(): float
    {
        return max(0, $this->stored_linear_feet - $this->calculated_linear_feet);
    }

    /**
     * Get count of cabinets that are missing linear_feet (width) values
     */
    public function getCabinetsMissingWidthCountAttribute(): int
    {
        return $this->cabinets()
            ->where(function ($q) {
                $q->whereNull('linear_feet')
                  ->orWhere('linear_feet', 0);
            })
            ->count();
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
            // Auto-generate run_code if empty
            if (empty($run->run_code)) {
                $run->run_code = $run->generateRunCode();
            }

            // Auto-calculate total linear feet from cabinets if not manually set
            if (!$run->isDirty('total_linear_feet')) {
                $calculated = $run->cabinets()->sum('linear_feet');
                if ($calculated > 0) {
                    $run->total_linear_feet = $calculated;
                }
            }
        });

        // When run_code changes, regenerate all descendant full_codes
        static::updated(function ($run) {
            if ($run->isDirty('run_code')) {
                $run->regenerateDescendantCodes();
            }
        });
    }

    /**
     * Generate the run code based on run type and position
     * Format: U1, B2, T1, etc.
     */
    public function generateRunCode(): string
    {
        $prefix = static::$runTypePrefixes[$this->run_type] ?? 'R';

        // Get position from sort_order or calculate from siblings
        $position = $this->sort_order ?? 1;

        // If sort_order isn't set, count existing runs of same type in same location
        if (!$this->sort_order && $this->room_location_id) {
            $existingCount = static::where('room_location_id', $this->room_location_id)
                ->where('run_type', $this->run_type)
                ->where('id', '!=', $this->id ?? 0)
                ->count();
            $position = $existingCount + 1;
        }

        return $prefix . $position;
    }

    /**
     * Regenerate full_codes for all descendant entities
     * Called when run_code changes
     */
    public function regenerateDescendantCodes(): void
    {
        $this->load('cabinets.sections');

        foreach ($this->cabinets as $cabinet) {
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
