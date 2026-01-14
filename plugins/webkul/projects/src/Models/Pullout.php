<?php

namespace Webkul\Project\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Webkul\Product\Models\Product;
use Webkul\Project\Contracts\CabinetComponentInterface;
use Webkul\Support\Traits\HasComplexityScore;
use Webkul\Support\Traits\HasFormattedDimensions;
use Webkul\Support\Traits\HasFullCode;
use Webkul\Project\Traits\HasEntityLock;

/**
 * Pullout Model
 *
 * Represents a cabinet pullout/accessory component.
 * Hierarchy: Project -> Room -> Location -> Cabinet Run -> Cabinet -> Section -> Pullout
 *
 * @property string|null $full_code Hierarchical code (e.g., TCS-0554-15WSANKATY-K1-SW-B1-C-PULL1)
 * @property float|null $complexity_score Calculated complexity score
 * @property array|null $complexity_breakdown JSON breakdown of complexity factors
 */
class Pullout extends Model implements CabinetComponentInterface
{
    use HasFactory, SoftDeletes, HasFullCode, HasComplexityScore, HasFormattedDimensions, HasEntityLock;

    protected $table = 'projects_pullouts';

    protected $fillable = [
        'product_id',
        'cabinet_id',
        'section_id',
        'pullout_number',
        'pullout_name',
        'full_code',
        'sort_order',
        'pullout_type',
        'manufacturer',
        'model_number',
        'description',
        'width_inches',
        'height_inches',
        'depth_inches',
        'mounting_type',
        'slide_type',
        'slide_model',
        'slide_product_id',
        'slide_length_inches',
        'slide_quantity',
        'soft_close',
        'weight_capacity_lbs',
        'unit_cost',
        'quantity',
        'ordered_at',
        'received_at',
        'hardware_installed_at',
        'installed_in_cabinet_at',
        'qc_passed',
        'qc_notes',
        'qc_inspected_at',
        'qc_inspector_id',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
            'width_inches' => 'float',
            'height_inches' => 'float',
            'depth_inches' => 'float',
            'slide_length_inches' => 'float',
            'slide_quantity' => 'integer',
            'soft_close' => 'boolean',
            'weight_capacity_lbs' => 'float',
            'unit_cost' => 'decimal:2',
            'quantity' => 'integer',
            'ordered_at' => 'datetime',
            'received_at' => 'datetime',
            'hardware_installed_at' => 'datetime',
            'installed_in_cabinet_at' => 'datetime',
            'qc_passed' => 'boolean',
            'qc_inspected_at' => 'datetime',
        ];
    }

    public const PULLOUT_TYPES = [
        'trash' => 'Trash Pullout',
        'recycling' => 'Recycling Pullout',
        'spice_rack' => 'Spice Rack',
        'tray_divider' => 'Tray Divider',
        'cutting_board' => 'Cutting Board',
        'mixer_lift' => 'Mixer Lift',
        'blind_corner' => 'Blind Corner Pullout',
        'lazy_susan' => 'Lazy Susan',
        'roll_out_tray' => 'Roll-Out Tray',
        'pantry_pullout' => 'Pantry Pullout',
        'utensil_divider' => 'Utensil Divider',
        'other' => 'Other',
    ];

    public function cabinet(): BelongsTo
    {
        return $this->belongsTo(Cabinet::class, 'cabinet_id');
    }

    public function section(): BelongsTo
    {
        return $this->belongsTo(CabinetSection::class, 'section_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function slideProduct(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'slide_product_id');
    }

    /**
     * Additional hardware/products for this pullout
     */
    public function hardwareRequirements(): HasMany
    {
        return $this->hasMany(HardwareRequirement::class, 'pullout_id');
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order');
    }

    public function getFormattedDimensionsAttribute(): string
    {
        $w = $this->width_inches ?? '?';
        $h = $this->height_inches ?? '?';
        $d = $this->depth_inches ?? '?';
        return "{$w}\"W x {$h}\"H x {$d}\"D";
    }

    /**
     * Get the component code for this pullout
     * Format: PULL1, PULL2, etc.
     */
    public function getComponentCode(): string
    {
        return 'PULL' . ($this->pullout_number ?? 1);
    }

    /**
     * Get the component's name.
     */
    public function getComponentName(): ?string
    {
        return $this->pullout_name;
    }

    /**
     * Get the component's number.
     */
    public function getComponentNumber(): ?int
    {
        return $this->pullout_number;
    }

    /**
     * Get the component type identifier.
     */
    public static function getComponentType(): string
    {
        return 'pullout';
    }
}
