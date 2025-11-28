<?php

namespace Webkul\Inventory\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Webkul\Inventory\Database\Factories\LocationFactory;
use Webkul\Inventory\Enums\LocationType;
use Webkul\Product\Enums\ProductRemoval;
use Webkul\Security\Models\User;
use Webkul\Support\Models\Company;

/**
 * Location Eloquent model
 *
 * @property int $id
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property \Carbon\Carbon|null $deleted_at
 * @property string|null $position_x
 * @property string|null $position_y
 * @property string|null $position_z
 * @property mixed $type
 * @property string|null $name
 * @property string|null $full_name
 * @property string|null $description
 * @property string|null $parent_path
 * @property string|null $barcode
 * @property mixed $removal_strategy
 * @property string|null $cyclic_inventory_frequency
 * @property \Carbon\Carbon|null $last_inventory_date
 * @property \Carbon\Carbon|null $next_inventory_date
 * @property bool $is_scrap
 * @property bool $is_replenish
 * @property bool $is_dock
 * @property int $parent_id
 * @property int $storage_category_id
 * @property int $warehouse_id
 * @property int $company_id
 * @property int $creator_id
 * @property-read \Illuminate\Database\Eloquent\Collection $children
 * @property-read \Illuminate\Database\Eloquent\Model|null $parent
 * @property-read \Illuminate\Database\Eloquent\Model|null $storageCategory
 * @property-read \Illuminate\Database\Eloquent\Model|null $warehouse
 * @property-read \Illuminate\Database\Eloquent\Model|null $company
 * @property-read \Illuminate\Database\Eloquent\Model|null $creator
 *
 */
class Location extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * Table name.
     *
     * @var string
     */
    protected $table = 'inventories_locations';

    /**
     * Fillable.
     *
     * @var array
     */
    protected $fillable = [
        'position_x',
        'position_y',
        'position_z',
        'type',
        'name',
        'full_name',
        'description',
        'parent_path',
        'barcode',
        'removal_strategy',
        'cyclic_inventory_frequency',
        'last_inventory_date',
        'next_inventory_date',
        'is_scrap',
        'is_replenish',
        'is_dock',
        'parent_id',
        'storage_category_id',
        'warehouse_id',
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
        'type'                => LocationType::class,
        'removal_strategy'    => ProductRemoval::class,
        'last_inventory_date' => 'date',
        'next_inventory_date' => 'date',
        'is_scrap'            => 'boolean',
        'is_replenish'        => 'boolean',
        'is_dock'             => 'boolean',
    ];

    /**
     * Parent
     *
     * @return BelongsTo
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class);
    }

    /**
     * Children
     *
     * @return HasMany
     */
    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    /**
     * Storage Category
     *
     * @return BelongsTo
     */
    public function storageCategory(): BelongsTo
    {
        return $this->belongsTo(StorageCategory::class);
    }

    /**
     * Warehouse
     *
     * @return BelongsTo
     */
    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class)->withTrashed();
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

    public function getIsStockLocationAttribute(): bool
    {
        if (! $this->warehouse_id) {
            return false;
        }

        return $this->warehouse->lot_stock_location_id == $this->id
            || ($this->parent_id && $this->parent->is_stock_location);
    }

    /**
     * Should Bypass Reservation
     *
     * @return bool
     */
    public function shouldBypassReservation(): bool
    {
        return in_array($this->type, [
            LocationType::SUPPLIER,
            LocationType::CUSTOMER,
            LocationType::INVENTORY,
            LocationType::PRODUCTION,
        ]) || $this->is_scrap;
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

        static::saving(function ($category) {
            $category->updateParentPath();

            $category->updateFullName();
        });

        static::updated(function ($category) {
            $category->updateChildrenParentPaths();

            if ($category->wasChanged('full_name')) {
                $category->updateChildrenFullNames();
            }
        });
    }

    /**
     * Update the full name without triggering additional events
     */
    /**
     * Update Full Name
     *
     */
    public function updateFullName()
    {
        if ($this->type === LocationType::VIEW) {
            $this->full_name = $this->name;
        } else {
            $this->full_name = $this->parent
                ? $this->parent->full_name.'/'.$this->name
                : $this->name;
        }
    }

    /**
     * Update the full name without triggering additional events
     */
    /**
     * Update Parent Path
     *
     */
    public function updateParentPath()
    {
        if ($this->type === LocationType::VIEW) {
            $this->parent_path = $this->id.'/';
        } else {
            $this->parent_path = $this->parent
                ? $this->parent->parent_path.$this->id.'/'
                : $this->id.'/';
        }
    }

    /**
     * Update Children Full Names
     *
     * @return void
     */
    public function updateChildrenFullNames(): void
    {
        $children = $this->children()->getModel()
            ->withTrashed()
            ->where('parent_id', $this->id)
            ->get();

        $children->each(function ($child) {
            $child->updateFullName();
            $child->saveQuietly();

            $child->updateChildrenFullNames();
        });
    }

    /**
     * Update Children Parent Paths
     *
     * @return void
     */
    public function updateChildrenParentPaths(): void
    {
        $children = $this->children()->getModel()
            ->withTrashed()
            ->where('parent_id', $this->id)
            ->get();

        $children->each(function ($child) {
            $child->updateParentPath();
            $child->saveQuietly();

            $child->updateChildrenParentPaths();
        });
    }

    /**
     * New Factory
     *
     * @return LocationFactory
     */
    protected static function newFactory(): LocationFactory
    {
        return LocationFactory::new();
    }
}
