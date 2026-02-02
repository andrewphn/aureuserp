<?php

namespace Webkul\Project\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;
use Webkul\Partner\Models\Partner;
use Webkul\Product\Models\Product;

/**
 * CNC Material to Product Mapping
 *
 * Maps CNC material codes (FL, PreFin, RiftWOPly, etc.) to actual inventory products.
 * This enables material usage tracking, stock visibility, and purchase order generation.
 *
 * @property int $id
 * @property string $material_code
 * @property int $product_id
 * @property string $material_type
 * @property string|null $sheet_size
 * @property float|null $thickness_inches
 * @property float $sqft_per_sheet
 * @property float|null $cost_per_sheet
 * @property float|null $cost_per_sqft
 * @property int|null $preferred_vendor_id
 * @property string|null $vendor_sku
 * @property int|null $lead_time_days
 * @property int $min_stock_sheets
 * @property int $reorder_qty_sheets
 * @property bool $is_active
 * @property bool $is_default
 * @property string|null $notes
 */
class CncMaterialProduct extends Model
{
    protected $table = 'projects_cnc_material_products';

    protected $fillable = [
        'material_code',
        'product_id',
        'material_type',
        'sheet_size',
        'thickness_inches',
        'sqft_per_sheet',
        'cost_per_sheet',
        'cost_per_sqft',
        'preferred_vendor_id',
        'vendor_sku',
        'lead_time_days',
        'min_stock_sheets',
        'reorder_qty_sheets',
        'is_active',
        'is_default',
        'notes',
    ];

    protected $casts = [
        'thickness_inches' => 'decimal:3',
        'sqft_per_sheet' => 'decimal:2',
        'cost_per_sheet' => 'decimal:2',
        'cost_per_sqft' => 'decimal:4',
        'is_active' => 'boolean',
        'is_default' => 'boolean',
    ];

    // =========================================================================
    // Material Type Constants
    // =========================================================================

    public const TYPE_SHEET_GOODS = 'sheet_goods';
    public const TYPE_SOLID_WOOD = 'solid_wood';
    public const TYPE_MDF = 'mdf';
    public const TYPE_MELAMINE = 'melamine';
    public const TYPE_LAMINATE = 'laminate';

    // =========================================================================
    // Relationships
    // =========================================================================

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function preferredVendor(): BelongsTo
    {
        return $this->belongsTo(Partner::class, 'preferred_vendor_id');
    }

    // =========================================================================
    // Scopes
    // =========================================================================

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeForMaterialCode(Builder $query, string $materialCode): Builder
    {
        return $query->where('material_code', $materialCode);
    }

    public function scopeDefault(Builder $query): Builder
    {
        return $query->where('is_default', true);
    }

    public function scopeSheetGoods(Builder $query): Builder
    {
        return $query->where('material_type', self::TYPE_SHEET_GOODS);
    }

    public function scopeNeedsReorder(Builder $query): Builder
    {
        // This would need to join with inventory quantities
        return $query->where('min_stock_sheets', '>', 0);
    }

    // =========================================================================
    // Static Methods
    // =========================================================================

    /**
     * Get the default product for a material code
     */
    public static function getDefaultForCode(string $materialCode): ?self
    {
        return static::active()
            ->forMaterialCode($materialCode)
            ->default()
            ->with('product')
            ->first();
    }

    /**
     * Get all products for a material code
     */
    public static function getProductsForCode(string $materialCode): \Illuminate\Database\Eloquent\Collection
    {
        return static::active()
            ->forMaterialCode($materialCode)
            ->with('product')
            ->orderByDesc('is_default')
            ->get();
    }

    /**
     * Get material type options
     */
    public static function getMaterialTypes(): array
    {
        return [
            self::TYPE_SHEET_GOODS => 'Sheet Goods (Plywood)',
            self::TYPE_SOLID_WOOD => 'Solid Wood / Lumber',
            self::TYPE_MDF => 'MDF',
            self::TYPE_MELAMINE => 'Melamine',
            self::TYPE_LAMINATE => 'Laminate',
        ];
    }

    // =========================================================================
    // Accessors
    // =========================================================================

    /**
     * Get the material code display name
     */
    public function getMaterialCodeDisplayAttribute(): string
    {
        return CncProgram::getMaterialCodes()[$this->material_code] ?? $this->material_code;
    }

    /**
     * Get current stock in sheets (from inventory)
     */
    public function getCurrentStockSheetsAttribute(): float
    {
        if (!$this->product) {
            return 0;
        }

        // Get on-hand quantity from product
        $onHand = $this->product->on_hand_quantity ?? 0;

        // Convert to sheets if product is tracked in sqft
        if ($this->sqft_per_sheet > 0) {
            return round($onHand / $this->sqft_per_sheet, 1);
        }

        return $onHand;
    }

    /**
     * Check if stock is below minimum
     */
    public function getIsLowStockAttribute(): bool
    {
        return $this->current_stock_sheets < $this->min_stock_sheets;
    }

    /**
     * Get sheets needed to reach minimum stock
     */
    public function getSheetsNeededAttribute(): int
    {
        $needed = $this->min_stock_sheets - $this->current_stock_sheets;
        return max(0, (int) ceil($needed));
    }

    /**
     * Calculate cost for given number of sheets
     */
    public function calculateCost(float $sheets): float
    {
        if ($this->cost_per_sheet) {
            return round($sheets * $this->cost_per_sheet, 2);
        }

        if ($this->cost_per_sqft && $this->sqft_per_sheet) {
            return round($sheets * $this->sqft_per_sheet * $this->cost_per_sqft, 2);
        }

        // Try to get from product cost
        if ($this->product && $this->product->cost) {
            return round($sheets * $this->product->cost, 2);
        }

        return 0;
    }

    /**
     * Calculate sheets needed for given square footage
     */
    public function calculateSheetsForSqft(float $sqft, float $utilizationPct = 75): float
    {
        if ($this->sqft_per_sheet <= 0) {
            return 0;
        }

        // Account for utilization (waste)
        $effectiveSqftPerSheet = $this->sqft_per_sheet * ($utilizationPct / 100);

        return ceil($sqft / $effectiveSqftPerSheet);
    }
}
