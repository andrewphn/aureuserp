<?php

namespace Webkul\Project\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;
use Webkul\Product\Models\Product;

/**
 * CNC Material Usage
 *
 * Tracks material usage per CNC program for inventory management and ordering.
 * Links CNC programs to specific inventory products with usage quantities.
 *
 * @property int $id
 * @property int $cnc_program_id
 * @property int $product_id
 * @property float $sheets_required
 * @property float $sqft_required
 * @property float|null $sheets_used
 * @property float|null $sqft_used
 * @property float|null $waste_sqft
 * @property float|null $estimated_cost
 * @property float|null $actual_cost
 * @property string $allocation_status
 * @property \Carbon\Carbon|null $reserved_at
 * @property \Carbon\Carbon|null $issued_at
 * @property int|null $purchase_order_id
 * @property string|null $notes
 */
class CncMaterialUsage extends Model
{
    protected $table = 'projects_cnc_material_usage';

    protected $fillable = [
        'cnc_program_id',
        'product_id',
        'sheets_required',
        'sqft_required',
        'sheets_used',
        'sqft_used',
        'waste_sqft',
        'estimated_cost',
        'actual_cost',
        'allocation_status',
        'reserved_at',
        'issued_at',
        'purchase_order_id',
        'notes',
    ];

    protected $casts = [
        'sheets_required' => 'decimal:2',
        'sqft_required' => 'decimal:2',
        'sheets_used' => 'decimal:2',
        'sqft_used' => 'decimal:2',
        'waste_sqft' => 'decimal:2',
        'estimated_cost' => 'decimal:2',
        'actual_cost' => 'decimal:2',
        'reserved_at' => 'datetime',
        'issued_at' => 'datetime',
    ];

    // =========================================================================
    // Allocation Status Constants
    // =========================================================================

    public const STATUS_PENDING = 'pending';
    public const STATUS_RESERVED = 'reserved';
    public const STATUS_ISSUED = 'issued';
    public const STATUS_RETURNED = 'returned';

    // =========================================================================
    // Relationships
    // =========================================================================

    public function cncProgram(): BelongsTo
    {
        return $this->belongsTo(CncProgram::class, 'cnc_program_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    // =========================================================================
    // Scopes
    // =========================================================================

    public function scopePending(Builder $query): Builder
    {
        return $query->where('allocation_status', self::STATUS_PENDING);
    }

    public function scopeReserved(Builder $query): Builder
    {
        return $query->where('allocation_status', self::STATUS_RESERVED);
    }

    public function scopeIssued(Builder $query): Builder
    {
        return $query->where('allocation_status', self::STATUS_ISSUED);
    }

    public function scopeForProject(Builder $query, int $projectId): Builder
    {
        return $query->whereHas('cncProgram', function ($q) use ($projectId) {
            $q->where('project_id', $projectId);
        });
    }

    public function scopeNeedsMaterial(Builder $query): Builder
    {
        return $query->whereIn('allocation_status', [self::STATUS_PENDING, self::STATUS_RESERVED]);
    }

    // =========================================================================
    // Status Methods
    // =========================================================================

    /**
     * Reserve material from inventory
     */
    public function reserve(): bool
    {
        if ($this->allocation_status !== self::STATUS_PENDING) {
            return false;
        }

        $this->allocation_status = self::STATUS_RESERVED;
        $this->reserved_at = now();

        return $this->save();
    }

    /**
     * Issue material (mark as used)
     */
    public function issue(?float $sheetsUsed = null, ?float $sqftUsed = null): bool
    {
        if (!in_array($this->allocation_status, [self::STATUS_PENDING, self::STATUS_RESERVED])) {
            return false;
        }

        $this->allocation_status = self::STATUS_ISSUED;
        $this->issued_at = now();

        if ($sheetsUsed !== null) {
            $this->sheets_used = $sheetsUsed;
        }
        if ($sqftUsed !== null) {
            $this->sqft_used = $sqftUsed;
        }

        return $this->save();
    }

    /**
     * Return unused material
     */
    public function returnMaterial(?float $sheetsReturned = null): bool
    {
        if ($this->allocation_status !== self::STATUS_ISSUED) {
            return false;
        }

        $this->allocation_status = self::STATUS_RETURNED;

        if ($sheetsReturned !== null) {
            $this->sheets_used = $this->sheets_used - $sheetsReturned;
        }

        return $this->save();
    }

    // =========================================================================
    // Accessors
    // =========================================================================

    /**
     * Get allocation status display
     */
    public function getAllocationStatusDisplayAttribute(): string
    {
        return match ($this->allocation_status) {
            self::STATUS_PENDING => 'Pending',
            self::STATUS_RESERVED => 'Reserved',
            self::STATUS_ISSUED => 'Issued',
            self::STATUS_RETURNED => 'Returned',
            default => 'Unknown',
        };
    }

    /**
     * Get waste percentage
     */
    public function getWastePercentageAttribute(): ?float
    {
        if (!$this->sqft_used || $this->sqft_used <= 0) {
            return null;
        }

        $totalArea = $this->sheets_used * 32; // Assuming 4x8 sheets
        if ($totalArea <= 0) {
            return null;
        }

        return round((($totalArea - $this->sqft_used) / $totalArea) * 100, 1);
    }

    /**
     * Get variance from estimate
     */
    public function getSheetsVarianceAttribute(): ?float
    {
        if ($this->sheets_used === null) {
            return null;
        }

        return $this->sheets_used - $this->sheets_required;
    }

    /**
     * Get cost variance
     */
    public function getCostVarianceAttribute(): ?float
    {
        if ($this->actual_cost === null || $this->estimated_cost === null) {
            return null;
        }

        return $this->actual_cost - $this->estimated_cost;
    }

    // =========================================================================
    // Static Methods
    // =========================================================================

    /**
     * Get all pending material needs for ordering
     */
    public static function getPendingMaterialNeeds(): \Illuminate\Database\Eloquent\Collection
    {
        return static::pending()
            ->with(['product', 'cncProgram.project'])
            ->orderBy('created_at')
            ->get();
    }

    /**
     * Get material needs grouped by product
     */
    public static function getMaterialNeedsByProduct(): \Illuminate\Support\Collection
    {
        return static::pending()
            ->with('product')
            ->get()
            ->groupBy('product_id')
            ->map(function ($usages) {
                return [
                    'product' => $usages->first()->product,
                    'total_sheets' => $usages->sum('sheets_required'),
                    'total_sqft' => $usages->sum('sqft_required'),
                    'programs' => $usages->pluck('cncProgram'),
                    'count' => $usages->count(),
                ];
            });
    }

    /**
     * Get status options
     */
    public static function getStatusOptions(): array
    {
        return [
            self::STATUS_PENDING => 'Pending',
            self::STATUS_RESERVED => 'Reserved',
            self::STATUS_ISSUED => 'Issued',
            self::STATUS_RETURNED => 'Returned',
        ];
    }
}
