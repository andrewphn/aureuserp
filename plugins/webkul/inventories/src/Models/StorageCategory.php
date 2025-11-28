<?php

namespace Webkul\Inventory\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\EloquentSortable\Sortable;
use Spatie\EloquentSortable\SortableTrait;
use Webkul\Inventory\Database\Factories\StorageCategoryFactory;
use Webkul\Inventory\Enums\AllowNewProduct;
use Webkul\Security\Models\User;
use Webkul\Support\Models\Company;

/**
 * Storage Category Eloquent model
 *
 * @property int $id
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property string|null $name
 * @property string|null $sort
 * @property mixed $allow_new_products
 * @property string|null $parent_path
 * @property string|null $max_weight
 * @property int $company_id
 * @property int $creator_id
 * @property-read \Illuminate\Database\Eloquent\Collection $storageCategoryCapacities
 * @property-read \Illuminate\Database\Eloquent\Collection $locations
 * @property-read \Illuminate\Database\Eloquent\Model|null $company
 * @property-read \Illuminate\Database\Eloquent\Model|null $creator
 *
 */
class StorageCategory extends Model implements Sortable
{
    use HasFactory, SortableTrait;

    /**
     * Table name.
     *
     * @var string
     */
    protected $table = 'inventories_storage_categories';

    /**
     * Fillable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'sort',
        'allow_new_products',
        'parent_path',
        'max_weight',
        'company_id',
        'creator_id',
    ];

    /**
     * Table name.
     *
     * @var string
     */
    protected $casts = [
        'allow_new_products' => AllowNewProduct::class,
    ];

    public $sortable = [
        'order_column_name'  => 'sort',
        'sort_when_creating' => true,
    ];

    /**
     * Storage Category Capacities
     *
     * @return HasMany
     */
    public function storageCategoryCapacities(): HasMany
    {
        return $this->hasMany(StorageCategoryCapacity::class, 'storage_category_id');
    }

    /**
     * Storage Category Capacities By Product
     *
     * @return HasMany
     */
    public function storageCategoryCapacitiesByProduct(): HasMany
    {
        return $this->storageCategoryCapacities()->whereNotNull('product_id');
    }

    /**
     * Storage Category Capacities By Package Type
     *
     * @return HasMany
     */
    public function storageCategoryCapacitiesByPackageType(): HasMany
    {
        return $this->storageCategoryCapacities()->whereNotNull('package_type_id');
    }

    /**
     * Locations
     *
     * @return HasMany
     */
    public function locations(): HasMany
    {
        return $this->hasMany(Location::class);
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
     * New Factory
     *
     * @return StorageCategoryFactory
     */
    protected static function newFactory(): StorageCategoryFactory
    {
        return StorageCategoryFactory::new();
    }
}
