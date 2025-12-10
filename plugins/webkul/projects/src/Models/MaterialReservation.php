<?php

namespace Webkul\Project\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;
use Webkul\Chatter\Traits\HasChatter;
use Webkul\Chatter\Traits\HasLogActivity;
use Webkul\Inventory\Models\Move;
use Webkul\Inventory\Models\Warehouse;
use Webkul\Inventory\Models\Location;
use Webkul\Product\Models\Product;
use Webkul\Security\Models\User;

/**
 * Material Reservation Eloquent model
 *
 * Tracks material reservations for projects from inventory.
 * Links BOM requirements to actual inventory allocations.
 *
 * @property int $id
 * @property int $project_id
 * @property int|null $bom_id
 * @property int $product_id
 * @property int $warehouse_id
 * @property int|null $location_id
 * @property float $quantity_reserved
 * @property string $unit_of_measure
 * @property string $status
 * @property int|null $reserved_by
 * @property \Carbon\Carbon|null $reserved_at
 * @property \Carbon\Carbon|null $issued_at
 * @property \Carbon\Carbon|null $expires_at
 * @property int|null $move_id
 * @property string|null $notes
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property \Carbon\Carbon|null $deleted_at
 *
 * @property-read Project $project
 * @property-read CabinetMaterialsBom|null $bom
 * @property-read Product $product
 * @property-read Warehouse $warehouse
 * @property-read Location|null $location
 * @property-read User|null $reservedByUser
 * @property-read Move|null $move
 * @property-read \Illuminate\Database\Eloquent\Collection|HardwareRequirement[] $hardwareRequirements
 */
class MaterialReservation extends Model
{
    use SoftDeletes, HasChatter, HasLogActivity;

    /**
     * Table name.
     */
    protected $table = 'projects_material_reservations';

    /**
     * Status constants
     */
    public const STATUS_PENDING = 'pending';
    public const STATUS_RESERVED = 'reserved';
    public const STATUS_ISSUED = 'issued';
    public const STATUS_CANCELLED = 'cancelled';

    /**
     * Fillable fields.
     */
    protected $fillable = [
        'project_id',
        'bom_id',
        'product_id',
        'warehouse_id',
        'location_id',
        'quantity_reserved',
        'unit_of_measure',
        'status',
        'reserved_by',
        'reserved_at',
        'issued_at',
        'expires_at',
        'move_id',
        'notes',
    ];

    /**
     * Casts.
     */
    protected $casts = [
        'quantity_reserved' => 'decimal:3',
        'reserved_at' => 'datetime',
        'issued_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    /**
     * Log attributes for activity tracking.
     */
    protected array $logAttributes = [
        'project.name' => 'Project',
        'product.name' => 'Product',
        'quantity_reserved' => 'Quantity Reserved',
        'status' => 'Status',
        'warehouse.name' => 'Warehouse',
    ];

    // =========================================================================
    // Relationships
    // =========================================================================

    /**
     * Project relationship
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * BOM item relationship
     */
    public function bom(): BelongsTo
    {
        return $this->belongsTo(CabinetMaterialsBom::class, 'bom_id');
    }

    /**
     * Product relationship
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Warehouse relationship
     */
    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    /**
     * Location relationship
     */
    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    /**
     * User who created the reservation
     */
    public function reservedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reserved_by');
    }

    /**
     * Inventory move (when issued)
     */
    public function move(): BelongsTo
    {
        return $this->belongsTo(Move::class);
    }

    /**
     * Hardware requirements linked to this reservation
     */
    public function hardwareRequirements(): HasMany
    {
        return $this->hasMany(HardwareRequirement::class, 'material_reservation_id');
    }

    // =========================================================================
    // Scopes
    // =========================================================================

    /**
     * Scope: Pending reservations
     */
    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    /**
     * Scope: Reserved (confirmed) reservations
     */
    public function scopeReserved(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_RESERVED);
    }

    /**
     * Scope: Issued reservations
     */
    public function scopeIssued(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_ISSUED);
    }

    /**
     * Scope: Cancelled reservations
     */
    public function scopeCancelled(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_CANCELLED);
    }

    /**
     * Scope: Active reservations (pending or reserved)
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->whereIn('status', [self::STATUS_PENDING, self::STATUS_RESERVED]);
    }

    /**
     * Scope: Not expired
     */
    public function scopeNotExpired(Builder $query): Builder
    {
        return $query->where(function ($q) {
            $q->whereNull('expires_at')
              ->orWhere('expires_at', '>', now());
        });
    }

    /**
     * Scope: For a specific project
     */
    public function scopeForProject(Builder $query, int $projectId): Builder
    {
        return $query->where('project_id', $projectId);
    }

    /**
     * Scope: For a specific product
     */
    public function scopeForProduct(Builder $query, int $productId): Builder
    {
        return $query->where('product_id', $productId);
    }

    /**
     * Scope: For a specific warehouse
     */
    public function scopeForWarehouse(Builder $query, int $warehouseId): Builder
    {
        return $query->where('warehouse_id', $warehouseId);
    }

    // =========================================================================
    // Accessors
    // =========================================================================

    /**
     * Check if reservation is expired
     */
    public function getIsExpiredAttribute(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    /**
     * Check if reservation can be issued
     */
    public function getCanBeIssuedAttribute(): bool
    {
        return $this->status === self::STATUS_RESERVED && !$this->is_expired;
    }

    /**
     * Check if reservation can be cancelled
     */
    public function getCanBeCancelledAttribute(): bool
    {
        return in_array($this->status, [self::STATUS_PENDING, self::STATUS_RESERVED]);
    }

    /**
     * Get status color for UI
     */
    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_PENDING => 'warning',
            self::STATUS_RESERVED => 'success',
            self::STATUS_ISSUED => 'primary',
            self::STATUS_CANCELLED => 'danger',
            default => 'gray',
        };
    }

    /**
     * Get status label for UI
     */
    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_PENDING => 'Pending',
            self::STATUS_RESERVED => 'Reserved',
            self::STATUS_ISSUED => 'Issued',
            self::STATUS_CANCELLED => 'Cancelled',
            default => 'Unknown',
        };
    }

    // =========================================================================
    // Methods
    // =========================================================================

    /**
     * Mark as reserved
     */
    public function markAsReserved(?int $userId = null): self
    {
        $this->update([
            'status' => self::STATUS_RESERVED,
            'reserved_by' => $userId ?? auth()->id(),
            'reserved_at' => now(),
        ]);

        return $this;
    }

    /**
     * Mark as issued with optional move reference
     */
    public function markAsIssued(?Move $move = null): self
    {
        $this->update([
            'status' => self::STATUS_ISSUED,
            'issued_at' => now(),
            'move_id' => $move?->id,
        ]);

        return $this;
    }

    /**
     * Mark as cancelled
     */
    public function markAsCancelled(?string $reason = null): self
    {
        $this->update([
            'status' => self::STATUS_CANCELLED,
            'notes' => $reason ? ($this->notes . "\nCancelled: " . $reason) : $this->notes,
        ]);

        return $this;
    }

    /**
     * Set expiration date
     */
    public function setExpiration(int $days = 30): self
    {
        $this->update([
            'expires_at' => now()->addDays($days),
        ]);

        return $this;
    }
}
