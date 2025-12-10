<?php

namespace Webkul\Project\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;
use Webkul\Product\Models\Product;
use Webkul\Security\Models\User;
use Webkul\Inventory\Models\Warehouse;
use Webkul\Chatter\Traits\HasChatter;
use Webkul\Chatter\Traits\HasLogActivity;

/**
 * Hardware Requirement Eloquent model
 *
 * @property int $id
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property \Carbon\Carbon|null $deleted_at
 * @property int $cabinet_id
 * @property int $cabinet_run_id
 * @property int $product_id
 * @property string|null $hardware_type
 * @property string|null $manufacturer
 * @property string|null $model_number
 * @property int $quantity_required
 * @property string|null $unit_of_measure
 * @property string|null $applied_to
 * @property int $door_number
 * @property int $drawer_number
 * @property string|null $mounting_location
 * @property string|null $hinge_type
 * @property int $hinge_opening_angle
 * @property float $overlay_dimension_mm
 * @property string|null $slide_type
 * @property float $slide_length_inches
 * @property int $slide_weight_capacity_lbs
 * @property string|null $shelf_pin_type
 * @property float $shelf_pin_diameter_mm
 * @property float $accessory_width_inches
 * @property float $accessory_depth_inches
 * @property float $accessory_height_inches
 * @property string|null $accessory_configuration
 * @property string|null $finish
 * @property string|null $color_match
 * @property float $unit_cost
 * @property float $total_hardware_cost
 * @property string|null $installation_notes
 * @property int $install_sequence
 * @property bool $requires_jig
 * @property string|null $jig_name
 * @property bool $hardware_kitted
 * @property \Carbon\Carbon|null $hardware_kitted_at
 * @property bool $hardware_installed
 * @property \Carbon\Carbon|null $hardware_installed_at
 * @property int $installed_by_user_id
 * @property bool $hardware_allocated
 * @property \Carbon\Carbon|null $hardware_allocated_at
 * @property bool $hardware_issued
 * @property \Carbon\Carbon|null $hardware_issued_at
 * @property int $substituted_product_id
 * @property string|null $substitution_reason
 * @property bool $has_defect
 * @property string|null $defect_description
 * @property bool $returned_to_supplier
 * @property int|null $material_reservation_id
 * @property-read \Illuminate\Database\Eloquent\Model|null $cabinet
 * @property-read \Illuminate\Database\Eloquent\Model|null $cabinetRun
 * @property-read \Illuminate\Database\Eloquent\Model|null $product
 * @property-read \Illuminate\Database\Eloquent\Model|null $installedBy
 * @property-read \Illuminate\Database\Eloquent\Model|null $substitutedProduct
 * @property-read MaterialReservation|null $materialReservation
 *
 */
class HardwareRequirement extends Model
{
    use SoftDeletes, HasChatter, HasLogActivity;

    protected $table = 'hardware_requirements';

    protected $fillable = [
        'room_id',
        'room_location_id',
        'cabinet_id',
        'cabinet_run_id',
        'door_id',
        'drawer_id',
        'shelf_id',
        'pullout_id',
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
        'material_reservation_id',
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
    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class, 'room_id');
    }

    public function roomLocation(): BelongsTo
    {
        return $this->belongsTo(RoomLocation::class, 'room_location_id');
    }

    public function cabinet(): BelongsTo
    {
        return $this->belongsTo(Cabinet::class, 'cabinet_id');
    }

    /**
     * Cabinet Run
     *
     * @return BelongsTo
     */
    public function cabinetRun(): BelongsTo
    {
        return $this->belongsTo(CabinetRun::class, 'cabinet_run_id');
    }

    /**
     * Door component
     */
    public function door(): BelongsTo
    {
        return $this->belongsTo(Door::class, 'door_id');
    }

    /**
     * Drawer component
     */
    public function drawer(): BelongsTo
    {
        return $this->belongsTo(Drawer::class, 'drawer_id');
    }

    /**
     * Shelf component
     */
    public function shelf(): BelongsTo
    {
        return $this->belongsTo(Shelf::class, 'shelf_id');
    }

    /**
     * Pullout component
     */
    public function pullout(): BelongsTo
    {
        return $this->belongsTo(Pullout::class, 'pullout_id');
    }

    /**
     * Product
     *
     * @return BelongsTo
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    /**
     * Installed By
     *
     * @return BelongsTo
     */
    public function installedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'installed_by_user_id');
    }

    /**
     * Substituted Product
     *
     * @return BelongsTo
     */
    public function substitutedProduct(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'substituted_product_id');
    }

    /**
     * Material Reservation - links to inventory allocation
     *
     * @return BelongsTo
     */
    public function materialReservation(): BelongsTo
    {
        return $this->belongsTo(MaterialReservation::class, 'material_reservation_id');
    }

    /**
     * Scopes
     */
    /**
     * Scope query to Hinges
     *
     * @param mixed $query The search query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeHinges($query)
    {
        return $query->where('hardware_type', 'hinge');
    }

    /**
     * Scope query to Slides
     *
     * @param mixed $query The search query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeSlides($query)
    {
        return $query->where('hardware_type', 'slide');
    }

    /**
     * Scope query to Shelf Pins
     *
     * @param mixed $query The search query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeShelfPins($query)
    {
        return $query->where('hardware_type', 'shelf_pin');
    }

    /**
     * Scope query to Pullouts
     *
     * @param mixed $query The search query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopePullouts($query)
    {
        return $query->where('hardware_type', 'pullout');
    }

    /**
     * Scope query to Kitted
     *
     * @param mixed $query The search query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeKitted($query)
    {
        return $query->where('hardware_kitted', true);
    }

    /**
     * Scope query to Installed
     *
     * @param mixed $query The search query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeInstalled($query)
    {
        return $query->where('hardware_installed', true);
    }

    /**
     * Scope query to Pending
     *
     * @param mixed $query The search query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopePending($query)
    {
        return $query->where('hardware_kitted', false);
    }

    /**
     * Scope query to Defective
     *
     * @param mixed $query The search query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeDefective($query)
    {
        return $query->where('has_defect', true);
    }

    /**
     * Scope query to By Manufacturer
     *
     * @param mixed $query The search query
     * @param string $manufacturer
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByManufacturer($query, string $manufacturer)
    {
        return $query->where('manufacturer', 'LIKE', "%{$manufacturer}%");
    }

    /**
     * Scope query to Allocated
     *
     * @param Builder $query
     * @return Builder
     */
    public function scopeAllocated(Builder $query): Builder
    {
        return $query->where('hardware_allocated', true);
    }

    /**
     * Scope query to Unallocated
     *
     * @param Builder $query
     * @return Builder
     */
    public function scopeUnallocated(Builder $query): Builder
    {
        return $query->where('hardware_allocated', false);
    }

    /**
     * Scope query to With Reservation
     *
     * @param Builder $query
     * @return Builder
     */
    public function scopeWithReservation(Builder $query): Builder
    {
        return $query->whereNotNull('material_reservation_id');
    }

    /**
     * Scope query to Without Reservation
     *
     * @param Builder $query
     * @return Builder
     */
    public function scopeWithoutReservation(Builder $query): Builder
    {
        return $query->whereNull('material_reservation_id');
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

    // =========================================================================
    // Allocation Workflow Methods
    // =========================================================================

    /**
     * Get the project this hardware requirement belongs to
     * Traverses: HardwareRequirement → Cabinet → CabinetRun → RoomLocation → Room → Project
     * or: HardwareRequirement → CabinetRun → RoomLocation → Room → Project
     *
     * @return Project|null
     */
    public function getProject(): ?Project
    {
        // Via cabinet
        if ($this->cabinet_id && $this->cabinet) {
            return $this->cabinet->cabinetRun?->roomLocation?->room?->project;
        }

        // Via cabinet run directly
        if ($this->cabinet_run_id && $this->cabinetRun) {
            return $this->cabinetRun->roomLocation?->room?->project;
        }

        return null;
    }

    /**
     * Allocate hardware from inventory
     * Creates a MaterialReservation record and links it to this hardware requirement
     *
     * @param Warehouse $warehouse The warehouse to allocate from
     * @param int|null $locationId Specific location/bin within warehouse
     * @param int|null $userId User making the allocation
     * @return MaterialReservation|null
     */
    public function allocate(Warehouse $warehouse, ?int $locationId = null, ?int $userId = null): ?MaterialReservation
    {
        // Already allocated?
        if ($this->hardware_allocated && $this->material_reservation_id) {
            return $this->materialReservation;
        }

        // Need a product to allocate
        if (!$this->product_id) {
            return null;
        }

        // Get the project
        $project = $this->getProject();
        if (!$project) {
            return null;
        }

        // Create the material reservation
        $reservation = MaterialReservation::create([
            'project_id' => $project->id,
            'product_id' => $this->product_id,
            'warehouse_id' => $warehouse->id,
            'location_id' => $locationId,
            'quantity_reserved' => $this->quantity_required,
            'unit_of_measure' => $this->unit_of_measure ?? 'EA',
            'status' => MaterialReservation::STATUS_PENDING,
            'reserved_by' => $userId ?? auth()->id(),
            'notes' => "Hardware allocation for {$this->hardware_type} - {$this->model_number}",
        ]);

        // Link and update allocation status
        $this->update([
            'material_reservation_id' => $reservation->id,
            'hardware_allocated' => true,
            'hardware_allocated_at' => now(),
        ]);

        return $reservation;
    }

    /**
     * Confirm allocation (mark reservation as reserved)
     *
     * @param int|null $userId User confirming
     * @return bool
     */
    public function confirmAllocation(?int $userId = null): bool
    {
        if (!$this->materialReservation) {
            return false;
        }

        $this->materialReservation->markAsReserved($userId);
        return true;
    }

    /**
     * Issue hardware (mark as issued from inventory)
     *
     * @return bool
     */
    public function issue(): bool
    {
        if (!$this->hardware_allocated) {
            return false;
        }

        // Update reservation status if linked
        if ($this->materialReservation) {
            $this->materialReservation->markAsIssued();
        }

        $this->update([
            'hardware_issued' => true,
            'hardware_issued_at' => now(),
        ]);

        return true;
    }

    /**
     * Deallocate hardware (release reservation)
     *
     * @param string|null $reason Reason for deallocation
     * @return bool
     */
    public function deallocate(?string $reason = null): bool
    {
        // Cancel the reservation if exists
        if ($this->materialReservation && $this->materialReservation->can_be_cancelled) {
            $this->materialReservation->markAsCancelled($reason);
        }

        $this->update([
            'material_reservation_id' => null,
            'hardware_allocated' => false,
            'hardware_allocated_at' => null,
            'hardware_issued' => false,
            'hardware_issued_at' => null,
        ]);

        return true;
    }

    /**
     * Check if hardware can be allocated
     *
     * @return bool
     */
    public function canBeAllocated(): bool
    {
        return !$this->hardware_allocated
            && $this->product_id
            && $this->quantity_required > 0;
    }

    /**
     * Check if hardware is fully processed (issued and installed)
     *
     * @return bool
     */
    public function isFullyProcessed(): bool
    {
        return $this->hardware_allocated
            && $this->hardware_issued
            && $this->hardware_kitted
            && $this->hardware_installed;
    }

    /**
     * Get allocation status for display
     *
     * @return array{status: string, color: string, label: string}
     */
    public function getAllocationStatus(): array
    {
        if ($this->hardware_installed) {
            return ['status' => 'installed', 'color' => 'success', 'label' => 'Installed'];
        }

        if ($this->hardware_kitted) {
            return ['status' => 'kitted', 'color' => 'info', 'label' => 'Kitted'];
        }

        if ($this->hardware_issued) {
            return ['status' => 'issued', 'color' => 'primary', 'label' => 'Issued'];
        }

        if ($this->hardware_allocated) {
            return ['status' => 'allocated', 'color' => 'warning', 'label' => 'Allocated'];
        }

        return ['status' => 'pending', 'color' => 'gray', 'label' => 'Pending Allocation'];
    }
}
