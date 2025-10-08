<?php

namespace Webkul\Project\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Webkul\Product\Models\Product;
use Webkul\Sale\Models\OrderLine;
use Webkul\Security\Models\User;
use Webkul\Chatter\Traits\HasChatter;
use Webkul\Chatter\Traits\HasLogActivity;

class CabinetSpecification extends Model
{
    use SoftDeletes, HasChatter, HasLogActivity;

    protected $table = 'projects_cabinet_specifications';

    protected $fillable = [
        'order_line_id',
        'project_id',
        'room_id',
        'cabinet_run_id',
        'product_variant_id',
        'cabinet_number',
        'position_in_run',
        'wall_position_start_inches',
        'length_inches',
        'width_inches',
        'depth_inches',
        'height_inches',
        'linear_feet',
        'quantity',
        'unit_price_per_lf',
        'total_price',
        'hardware_notes',
        'custom_modifications',
        'shop_notes',
        'creator_id',
    ];

    protected $casts = [
        'length_inches' => 'decimal:2',
        'width_inches' => 'decimal:2',
        'depth_inches' => 'decimal:2',
        'height_inches' => 'decimal:2',
        'linear_feet' => 'decimal:2',
        'quantity' => 'integer',
        'unit_price_per_lf' => 'decimal:2',
        'total_price' => 'decimal:2',
        'position_in_run' => 'integer',
        'wall_position_start_inches' => 'decimal:2',
    ];

    /**
     * Attributes to log for Chatter activity tracking
     */
    protected $logAttributes = [
        'cabinetRun.roomLocation.room.name' => 'Room',
        'cabinetRun.name' => 'Cabinet Run',
        'cabinet_number' => 'Cabinet Number',
        'position_in_run' => 'Position in Run',
        'length_inches' => 'Length (inches)',
        'width_inches' => 'Width (inches)',
        'depth_inches' => 'Depth (inches)',
        'height_inches' => 'Height (inches)',
        'linear_feet' => 'Linear Feet',
        'quantity' => 'Quantity',
        'unit_price_per_lf' => 'Unit Price (per LF)',
        'total_price' => 'Total Price',
        'hardware_notes' => 'Hardware Notes',
        'custom_modifications' => 'Custom Modifications',
        'shop_notes' => 'Shop Notes',
    ];

    /**
     * Relationships
     */
    public function orderLine(): BelongsTo
    {
        return $this->belongsTo(OrderLine::class, 'order_line_id');
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class, 'project_id');
    }

    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class, 'room_id');
    }

    public function cabinetRun(): BelongsTo
    {
        return $this->belongsTo(CabinetRun::class, 'cabinet_run_id');
    }

    public function productVariant(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_variant_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creator_id');
    }

    /**
     * Calculated Attributes
     */
    protected $appends = ['size_range', 'common_size_match'];

    /**
     * Get linear feet from length (auto-calculated)
     */
    public function getCalculatedLinearFeetAttribute(): float
    {
        return round($this->length_inches / 12, 2);
    }

    /**
     * Get total price (auto-calculated)
     */
    public function getCalculatedTotalPriceAttribute(): float
    {
        return round(
            $this->unit_price_per_lf * $this->linear_feet * $this->quantity,
            2
        );
    }

    /**
     * Get size range category for analytics/templates
     *
     * Returns: 'small', 'medium', 'large', 'extra-large'
     */
    public function getSizeRangeAttribute(): string
    {
        $linearFeet = $this->linear_feet;

        return match (true) {
            $linearFeet <= 1.5 => 'small',      // 12-18"
            $linearFeet <= 3.0 => 'medium',     // 18-36"
            $linearFeet <= 4.0 => 'large',      // 36-48"
            default => 'extra-large',           // 48"+
        };
    }

    /**
     * Check if dimensions match a common/template size
     *
     * Returns common size name if match found, null otherwise
     */
    public function getCommonSizeMatchAttribute(): ?string
    {
        $commonSizes = [
            '12" Base' => ['length' => 12, 'depth' => 24, 'height' => 30],
            '15" Base' => ['length' => 15, 'depth' => 24, 'height' => 30],
            '18" Base' => ['length' => 18, 'depth' => 24, 'height' => 30],
            '24" Base' => ['length' => 24, 'depth' => 24, 'height' => 30],
            '30" Base' => ['length' => 30, 'depth' => 24, 'height' => 30],
            '36" Base' => ['length' => 36, 'depth' => 24, 'height' => 30],
            '12" Wall' => ['length' => 12, 'depth' => 12, 'height' => 30],
            '15" Wall' => ['length' => 15, 'depth' => 12, 'height' => 30],
            '18" Wall' => ['length' => 18, 'depth' => 12, 'height' => 30],
            '24" Wall' => ['length' => 24, 'depth' => 12, 'height' => 30],
            '30" Wall' => ['length' => 30, 'depth' => 12, 'height' => 30],
            '36" Wall' => ['length' => 36, 'depth' => 12, 'height' => 30],
            '18" Tall Pantry' => ['length' => 18, 'depth' => 24, 'height' => 84],
            '24" Tall Pantry' => ['length' => 24, 'depth' => 24, 'height' => 84],
            '30" Tall Pantry' => ['length' => 30, 'depth' => 24, 'height' => 96],
        ];

        foreach ($commonSizes as $name => $dimensions) {
            if (
                abs($this->length_inches - $dimensions['length']) <= 0.5 &&
                abs($this->depth_inches - $dimensions['depth']) <= 0.5 &&
                abs($this->height_inches - $dimensions['height']) <= 0.5
            ) {
                return $name;
            }
        }

        return null;
    }

    /**
     * Scope: Get specs by size range for template generation
     */
    public function scopeBySizeRange($query, string $range)
    {
        return match ($range) {
            'small' => $query->whereBetween('linear_feet', [0, 1.5]),
            'medium' => $query->whereBetween('linear_feet', [1.5, 3.0]),
            'large' => $query->whereBetween('linear_feet', [3.0, 4.0]),
            'extra-large' => $query->where('linear_feet', '>', 4.0),
            default => $query,
        };
    }

    /**
     * Scope: Get most common dimensions for template suggestions
     */
    public function scopeMostCommon($query, int $limit = 10)
    {
        return $query
            ->selectRaw('
                length_inches,
                width_inches,
                depth_inches,
                height_inches,
                COUNT(*) as usage_count
            ')
            ->groupBy(['length_inches', 'width_inches', 'depth_inches', 'height_inches'])
            ->orderByDesc('usage_count')
            ->limit($limit);
    }

    /**
     * Auto-calculate fields before saving
     */
    protected static function boot()
    {
        parent::boot();

        static::saving(function ($spec) {
            // Auto-calculate linear feet from length
            if ($spec->length_inches && !$spec->linear_feet) {
                $spec->linear_feet = round($spec->length_inches / 12, 2);
            }

            // Auto-calculate total price if not set
            if ($spec->unit_price_per_lf && $spec->linear_feet && $spec->quantity && !$spec->total_price) {
                $spec->total_price = round(
                    $spec->unit_price_per_lf * $spec->linear_feet * $spec->quantity,
                    2
                );
            }
        });
    }

    /**
     * Generate template data from common sizes
     *
     * Returns: Collection of template data for UI presets
     */
    public static function generateTemplates()
    {
        return self::mostCommon(20)
            ->get()
            ->map(function ($spec) {
                return [
                    'name' => "{$spec->length_inches}\" × {$spec->depth_inches}\" × {$spec->height_inches}\"",
                    'length' => $spec->length_inches,
                    'width' => $spec->width_inches,
                    'depth' => $spec->depth_inches,
                    'height' => $spec->height_inches,
                    'usage_count' => $spec->usage_count,
                    'size_range' => self::determineSizeRange($spec->length_inches / 12),
                ];
            });
    }

    /**
     * Helper to determine size range from linear feet
     */
    private static function determineSizeRange(float $linearFeet): string
    {
        return match (true) {
            $linearFeet <= 1.5 => 'small',
            $linearFeet <= 3.0 => 'medium',
            $linearFeet <= 4.0 => 'large',
            default => 'extra-large',
        };
    }
}
