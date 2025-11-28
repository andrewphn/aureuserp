<?php

namespace Webkul\Inventory\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Webkul\Inventory\Database\Factories\StorageCategoryCapacityFactory;
use Webkul\Security\Models\User;

/**
 * Storage Category Capacity Eloquent model
 *
 * @property int $id
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property string|null $qty
 * @property int $product_id
 * @property int $storage_category_id
 * @property int $package_type_id
 * @property int $creator_id
 * @property-read \Illuminate\Database\Eloquent\Model|null $product
 * @property-read \Illuminate\Database\Eloquent\Model|null $storageCategory
 * @property-read \Illuminate\Database\Eloquent\Model|null $packageType
 * @property-read \Illuminate\Database\Eloquent\Model|null $creator
 *
 */
class StorageCategoryCapacity extends Model
{
    /**
     * Table name.
     *
     * @var string
     */
    protected $table = 'inventories_storage_category_capacities';

    /**
     * Fillable.
     *
     * @var array
     */
    protected $fillable = [
        'qty',
        'product_id',
        'storage_category_id',
        'package_type_id',
        'creator_id',
    ];

    /**
     * Product
     *
     * @return BelongsTo
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class)->withTrashed();
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
     * Package Type
     *
     * @return BelongsTo
     */
    public function packageType(): BelongsTo
    {
        return $this->belongsTo(PackageType::class);
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
     * @return StorageCategoryCapacityFactory
     */
    protected static function newFactory(): StorageCategoryCapacityFactory
    {
        return StorageCategoryCapacityFactory::new();
    }
}
