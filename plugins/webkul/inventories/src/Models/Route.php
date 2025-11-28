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
use Webkul\Inventory\Database\Factories\RouteFactory;
use Webkul\Security\Models\User;
use Webkul\Support\Models\Company;

/**
 * Route Eloquent model
 *
 * @property int $id
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property \Carbon\Carbon|null $deleted_at
 * @property string|null $sort
 * @property string|null $name
 * @property bool $product_selectable
 * @property bool $product_category_selectable
 * @property bool $warehouse_selectable
 * @property bool $packaging_selectable
 * @property int $supplied_warehouse_id
 * @property int $supplier_warehouse_id
 * @property int $company_id
 * @property int $creator_id
 * @property-read \Illuminate\Database\Eloquent\Collection $rules
 * @property-read \Illuminate\Database\Eloquent\Model|null $suppliedWarehouse
 * @property-read \Illuminate\Database\Eloquent\Model|null $supplierWarehouse
 * @property-read \Illuminate\Database\Eloquent\Model|null $company
 * @property-read \Illuminate\Database\Eloquent\Model|null $creator
 * @property-read \Illuminate\Database\Eloquent\Collection $warehouses
 * @property-read \Illuminate\Database\Eloquent\Collection $packagings
 *
 */
class Route extends Model implements Sortable
{
    use HasFactory, SoftDeletes, SortableTrait;

    /**
     * Table name.
     *
     * @var string
     */
    protected $table = 'inventories_routes';

    /**
     * Fillable.
     *
     * @var array
     */
    protected $fillable = [
        'sort',
        'name',
        'product_selectable',
        'product_category_selectable',
        'warehouse_selectable',
        'packaging_selectable',
        'supplied_warehouse_id',
        'supplier_warehouse_id',
        'company_id',
        'creator_id',
        'deleted_at',
    ];

    /**
     * Table name.
     *
     * @var string
     */
    protected $casts = [
        'product_selectable'          => 'boolean',
        'product_category_selectable' => 'boolean',
        'warehouse_selectable'        => 'boolean',
        'packaging_selectable'        => 'boolean',
    ];

    public $sortable = [
        'order_column_name'  => 'sort',
        'sort_when_creating' => true,
    ];

    /**
     * Supplied Warehouse
     *
     * @return BelongsTo
     */
    public function suppliedWarehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    /**
     * Supplier Warehouse
     *
     * @return BelongsTo
     */
    public function supplierWarehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    /**
     * Warehouses
     *
     * @return BelongsToMany
     */
    public function warehouses(): BelongsToMany
    {
        return $this->belongsToMany(Warehouse::class, 'inventories_route_warehouses');
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
     * Creator
     *
     * @return BelongsTo
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Packagings
     *
     * @return BelongsToMany
     */
    public function packagings(): BelongsToMany
    {
        return $this->belongsToMany(Route::class, 'inventories_route_packagings', 'route_id', 'packaging_id');
    }

    /**
     * Rules
     *
     * @return HasMany
     */
    public function rules(): HasMany
    {
        return $this->hasMany(Rule::class);
    }

    /**
     * New Factory
     *
     * @return RouteFactory
     */
    protected static function newFactory(): RouteFactory
    {
        return RouteFactory::new();
    }
}
