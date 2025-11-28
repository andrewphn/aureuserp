<?php

namespace Webkul\Inventory\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\EloquentSortable\Sortable;
use Spatie\EloquentSortable\SortableTrait;
use Webkul\Inventory\Database\Factories\WarehouseFactory;
use Webkul\Inventory\Enums\DeliveryStep;
use Webkul\Inventory\Enums\ReceptionStep;
use Webkul\Partner\Models\Partner;
use Webkul\Security\Models\User;
use Webkul\Support\Models\Company;

/**
 * Warehouse Eloquent model
 *
 * @property int $id
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property \Carbon\Carbon|null $deleted_at
 * @property string|null $name
 * @property string|null $code
 * @property string|null $sort
 * @property mixed $reception_steps
 * @property mixed $delivery_steps
 * @property int $partner_address_id
 * @property int $company_id
 * @property int $creator_id
 * @property int $view_location_id
 * @property int $lot_stock_location_id
 * @property int $input_stock_location_id
 * @property int $qc_stock_location_id
 * @property int $output_stock_location_id
 * @property int $pack_stock_location_id
 * @property int $mto_pull_id
 * @property int $buy_pull_id
 * @property int $pick_type_id
 * @property int $pack_type_id
 * @property int $out_type_id
 * @property int $in_type_id
 * @property int $internal_type_id
 * @property int $qc_type_id
 * @property int $store_type_id
 * @property int $xdock_type_id
 * @property int $crossdock_route_id
 * @property int $reception_route_id
 * @property int $delivery_route_id
 * @property-read \Illuminate\Database\Eloquent\Collection $locations
 * @property-read \Illuminate\Database\Eloquent\Model|null $company
 * @property-read \Illuminate\Database\Eloquent\Model|null $partnerAddress
 * @property-read \Illuminate\Database\Eloquent\Model|null $creator
 * @property-read \Illuminate\Database\Eloquent\Model|null $viewLocation
 * @property-read \Illuminate\Database\Eloquent\Model|null $lotStockLocation
 * @property-read \Illuminate\Database\Eloquent\Model|null $inputStockLocation
 * @property-read \Illuminate\Database\Eloquent\Model|null $qcStockLocation
 * @property-read \Illuminate\Database\Eloquent\Model|null $outputStockLocation
 * @property-read \Illuminate\Database\Eloquent\Model|null $packStockLocation
 * @property-read \Illuminate\Database\Eloquent\Model|null $mtoPull
 * @property-read \Illuminate\Database\Eloquent\Model|null $buyPull
 * @property-read \Illuminate\Database\Eloquent\Model|null $pickType
 * @property-read \Illuminate\Database\Eloquent\Model|null $packType
 * @property-read \Illuminate\Database\Eloquent\Model|null $outType
 * @property-read \Illuminate\Database\Eloquent\Model|null $inType
 * @property-read \Illuminate\Database\Eloquent\Model|null $internalType
 * @property-read \Illuminate\Database\Eloquent\Model|null $qcType
 * @property-read \Illuminate\Database\Eloquent\Model|null $storeType
 * @property-read \Illuminate\Database\Eloquent\Model|null $xdockType
 * @property-read \Illuminate\Database\Eloquent\Model|null $crossdockRoute
 * @property-read \Illuminate\Database\Eloquent\Model|null $receptionRoute
 * @property-read \Illuminate\Database\Eloquent\Model|null $deliveryRoute
 * @property-read \Illuminate\Database\Eloquent\Collection $routes
 * @property-read \Illuminate\Database\Eloquent\Collection $suppliedWarehouses
 * @property-read \Illuminate\Database\Eloquent\Collection $supplierWarehouses
 *
 */
class Warehouse extends Model implements Sortable
{
    use HasFactory, SoftDeletes, SortableTrait;

    /**
     * Table name.
     *
     * @var string
     */
    protected $table = 'inventories_warehouses';

    /**
     * Fillable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'code',
        'sort',
        'reception_steps',
        'delivery_steps',
        'partner_address_id',
        'company_id',
        'creator_id',
        'view_location_id',
        'lot_stock_location_id',
        'input_stock_location_id',
        'qc_stock_location_id',
        'output_stock_location_id',
        'pack_stock_location_id',
        'mto_pull_id',
        'buy_pull_id',
        'pick_type_id',
        'pack_type_id',
        'out_type_id',
        'in_type_id',
        'internal_type_id',
        'qc_type_id',
        'store_type_id',
        'xdock_type_id',
        'crossdock_route_id',
        'reception_route_id',
        'delivery_route_id',
    ];

    /**
     * Table name.
     *
     * @var string
     */
    protected $casts = [
        'reception_steps' => ReceptionStep::class,
        'delivery_steps'  => DeliveryStep::class,
    ];

    public $sortable = [
        'order_column_name'  => 'sort',
        'sort_when_creating' => true,
    ];

    /**
     * Locations
     *
     * @return HasMany
     */
    public function locations(): HasMany
    {
        return $this->hasMany(Location::class, 'parent_id');
    }

    /**
     * Company
     *
     * @return BelongsTo
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Partner Address
     *
     * @return BelongsTo
     */
    public function partnerAddress(): BelongsTo
    {
        return $this->belongsTo(Partner::class);
    }

    /**
     * Creator
     *
     * @return BelongsTo
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * View Location
     *
     * @return BelongsTo
     */
    public function viewLocation(): BelongsTo
    {
        return $this->belongsTo(Location::class, 'view_location_id');
    }

    /**
     * Lot Stock Location
     *
     * @return BelongsTo
     */
    public function lotStockLocation(): BelongsTo
    {
        return $this->belongsTo(Location::class, 'lot_stock_location_id');
    }

    /**
     * Input Stock Location
     *
     * @return BelongsTo
     */
    public function inputStockLocation(): BelongsTo
    {
        return $this->belongsTo(Location::class, 'input_stock_location_id');
    }

    /**
     * Qc Stock Location
     *
     * @return BelongsTo
     */
    public function qcStockLocation(): BelongsTo
    {
        return $this->belongsTo(Location::class, 'qc_stock_location_id');
    }

    /**
     * Output Stock Location
     *
     * @return BelongsTo
     */
    public function outputStockLocation(): BelongsTo
    {
        return $this->belongsTo(Location::class, 'output_stock_location_id');
    }

    /**
     * Pack Stock Location
     *
     * @return BelongsTo
     */
    public function packStockLocation(): BelongsTo
    {
        return $this->belongsTo(Location::class, 'pack_stock_location_id');
    }

    /**
     * Mto Pull
     *
     * @return BelongsTo
     */
    public function mtoPull(): BelongsTo
    {
        return $this->belongsTo(Rule::class, 'mto_pull_id');
    }

    /**
     * Buy Pull
     *
     * @return BelongsTo
     */
    public function buyPull(): BelongsTo
    {
        return $this->belongsTo(Rule::class, 'buy_pull_id');
    }

    /**
     * Pick Type
     *
     * @return BelongsTo
     */
    public function pickType(): BelongsTo
    {
        return $this->belongsTo(OperationType::class, 'pick_type_id');
    }

    /**
     * Pack Type
     *
     * @return BelongsTo
     */
    public function packType(): BelongsTo
    {
        return $this->belongsTo(OperationType::class, 'pack_type_id');
    }

    /**
     * Out Type
     *
     * @return BelongsTo
     */
    public function outType(): BelongsTo
    {
        return $this->belongsTo(OperationType::class, 'out_type_id');
    }

    /**
     * In Type
     *
     * @return BelongsTo
     */
    public function inType(): BelongsTo
    {
        return $this->belongsTo(OperationType::class, 'in_type_id');
    }

    /**
     * Internal Type
     *
     * @return BelongsTo
     */
    public function internalType(): BelongsTo
    {
        return $this->belongsTo(OperationType::class, 'internal_type_id');
    }

    /**
     * Qc Type
     *
     * @return BelongsTo
     */
    public function qcType(): BelongsTo
    {
        return $this->belongsTo(OperationType::class, 'qc_type_id');
    }

    /**
     * Store Type
     *
     * @return BelongsTo
     */
    public function storeType(): BelongsTo
    {
        return $this->belongsTo(OperationType::class, 'store_type_id');
    }

    /**
     * Xdock Type
     *
     * @return BelongsTo
     */
    public function xdockType(): BelongsTo
    {
        return $this->belongsTo(OperationType::class, 'xdock_type_id');
    }

    /**
     * Crossdock Route
     *
     * @return BelongsTo
     */
    public function crossdockRoute(): BelongsTo
    {
        return $this->belongsTo(Route::class, 'crossdock_route_id');
    }

    /**
     * Reception Route
     *
     * @return BelongsTo
     */
    public function receptionRoute(): BelongsTo
    {
        return $this->belongsTo(Route::class, 'reception_route_id');
    }

    /**
     * Delivery Route
     *
     * @return BelongsTo
     */
    public function deliveryRoute(): BelongsTo
    {
        return $this->belongsTo(Route::class, 'delivery_route_id');
    }

    /**
     * Routes
     *
     * @return BelongsToMany
     */
    public function routes(): BelongsToMany
    {
        return $this->belongsToMany(Route::class, 'inventories_route_warehouses', 'warehouse_id', 'route_id');
    }

    /**
     * Supplied Warehouses
     *
     * @return BelongsToMany
     */
    public function suppliedWarehouses(): BelongsToMany
    {
        return $this->belongsToMany(
            Warehouse::class,
            'inventories_warehouse_resupplies',
            'supplier_warehouse_id',
            'supplied_warehouse_id'
        );
    }

    /**
     * Supplier Warehouses
     *
     * @return BelongsToMany
     */
    public function supplierWarehouses(): BelongsToMany
    {
        return $this->belongsToMany(
            Warehouse::class,
            'inventories_warehouse_resupplies',
            'supplied_warehouse_id',
            'supplier_warehouse_id'
        );
    }

    /**
     * Bootstrap any application services.
     */
    /**
     * Boot
     *
     * @return void
     */
    protected static function boot()
    {
        parent::boot();

        static::updated(function ($warehouse) {
            if ($warehouse->wasChanged('code')) {
                $warehouse->viewLocation->update(['name' => $warehouse->code]);
            }
        });
    }

    /**
     * New Factory
     *
     * @return WarehouseFactory
     */
    protected static function newFactory(): WarehouseFactory
    {
        return WarehouseFactory::new();
    }
}
