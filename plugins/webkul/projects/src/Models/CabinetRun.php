<?php

namespace Webkul\Project\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Webkul\Security\Models\User;
use Webkul\Chatter\Traits\HasChatter;
use Webkul\Chatter\Traits\HasLogActivity;

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
        'creator_id',
    ];

    protected $casts = [
        'total_linear_feet' => 'decimal:2',
        'start_wall_measurement' => 'decimal:2',
        'end_wall_measurement' => 'decimal:2',
        'sort_order' => 'integer',
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

    public function cabinets(): HasMany
    {
        return $this->hasMany(CabinetSpecification::class, 'cabinet_run_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creator_id');
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
    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }

    /**
     * Scope: Filter by run type
     */
    public function scopeByType($query, string $type)
    {
        return $query->where('run_type', $type);
    }

    /**
     * Scope: With cabinet count
     */
    public function scopeWithCounts($query)
    {
        return $query->withCount('cabinets');
    }

    /**
     * Auto-calculate fields before saving
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
}
