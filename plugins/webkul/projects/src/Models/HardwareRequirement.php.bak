<?php

namespace Webkul\Project\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Webkul\Product\Models\Product;
use Webkul\Security\Models\User;
use Webkul\Chatter\Traits\HasChatter;
use Webkul\Chatter\Traits\HasLogActivity;

class HardwareRequirement extends Model
{
    use SoftDeletes, HasChatter, HasLogActivity;

    protected $table = 'hardware_requirements';

    protected $fillable = [
        'cabinet_specification_id',
        'cabinet_run_id',
        'product_id',
        'hardware_type',
        'manufacturer',
        'model_number',
        'quantity_required',
        'unit_of_measure',
        'applied_to',
        'door_number',
        'drawer_number',
        'mounting_location',
        'hinge_type',
        'hinge_opening_angle',
        'overlay_dimension_mm',
        'slide_type',
        'slide_length_inches',
        'slide_weight_capacity_lbs',
        'shelf_pin_type',
        'shelf_pin_diameter_mm',
        'accessory_width_inches',
        'accessory_depth_inches',
        'accessory_height_inches',
        'accessory_configuration',
        'finish',
        'color_match',
        'unit_cost',
        'total_hardware_cost',
        'installation_notes',
        'install_sequence',
        'requires_jig',
        'jig_name',
        'hardware_kitted',
        'hardware_kitted_at',
        'hardware_installed',
        'hardware_installed_at',
        'installed_by_user_id',
        'hardware_allocated',
        'hardware_allocated_at',
        'hardware_issued',
        'hardware_issued_at',
        'substituted_product_id',
        'substitution_reason',
        'has_defect',
        'defect_description',
        'returned_to_supplier',
    ];

    protected $casts = [
        'quantity_required' => 'integer',
        'door_number' => 'integer',
        'drawer_number' => 'integer',
        'hinge_opening_angle' => 'integer',
        'overlay_dimension_mm' => 'decimal:2',
        'slide_length_inches' => 'decimal:1',
        'slide_weight_capacity_lbs' => 'integer',
        'shelf_pin_diameter_mm' => 'decimal:2',
        'accessory_width_inches' => 'decimal:3',
        'accessory_depth_inches' => 'decimal:3',
        'accessory_height_inches' => 'decimal:3',
        'unit_cost' => 'decimal:2',
        'total_hardware_cost' => 'decimal:2',
        'install_sequence' => 'integer',
        'requires_jig' => 'boolean',
        'hardware_kitted' => 'boolean',
        'hardware_kitted_at' => 'datetime',
        'hardware_installed' => 'boolean',
        'hardware_installed_at' => 'datetime',
        'hardware_allocated' => 'boolean',
        'hardware_allocated_at' => 'datetime',
        'hardware_issued' => 'boolean',
        'hardware_issued_at' => 'datetime',
        'has_defect' => 'boolean',
        'returned_to_supplier' => 'boolean',
    ];

    /**
     * Attributes to log for Chatter activity tracking
     */
    protected $logAttributes = [
        'product.name' => 'Hardware Product',
        'hardware_type' => 'Hardware Type',
        'manufacturer' => 'Manufacturer',
        'model_number' => 'Model Number',
        'quantity_required' => 'Quantity',
        'hardware_kitted' => 'Kitted',
        'hardware_installed' => 'Installed',
        'has_defect' => 'Has Defect',
    ];

    /**
     * Relationships
     */
    public function cabinetSpecification(): BelongsTo
    {
        return $this->belongsTo(CabinetSpecification::class, 'cabinet_specification_id');
    }

    public function cabinetRun(): BelongsTo
    {
        return $this->belongsTo(CabinetRun::class, 'cabinet_run_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function installedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'installed_by_user_id');
    }

    public function substitutedProduct(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'substituted_product_id');
    }

    /**
     * Scopes
     */
    public function scopeHinges($query)
    {
        return $query->where('hardware_type', 'hinge');
    }

    public function scopeSlides($query)
    {
        return $query->where('hardware_type', 'slide');
    }

    public function scopeShelfPins($query)
    {
        return $query->where('hardware_type', 'shelf_pin');
    }

    public function scopePullouts($query)
    {
        return $query->where('hardware_type', 'pullout');
    }

    public function scopeKitted($query)
    {
        return $query->where('hardware_kitted', true);
    }

    public function scopeInstalled($query)
    {
        return $query->where('hardware_installed', true);
    }

    public function scopePending($query)
    {
        return $query->where('hardware_kitted', false);
    }

    public function scopeDefective($query)
    {
        return $query->where('has_defect', true);
    }

    public function scopeByManufacturer($query, string $manufacturer)
    {
        return $query->where('manufacturer', 'LIKE', "%{$manufacturer}%");
    }

    /**
     * Auto-calculate fields before saving
     */
    protected static function boot()
    {
        parent::boot();

        static::saving(function ($hardware) {
            // Auto-calculate total hardware cost
            if ($hardware->quantity_required && $hardware->unit_cost) {
                $hardware->total_hardware_cost = round(
                    $hardware->quantity_required * $hardware->unit_cost,
                    2
                );
            }
        });
    }
}
