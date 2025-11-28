<?php

namespace Webkul\Inventory\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Webkul\Inventory\Database\Factories\ProductQuantityRelocationFactory;
use Webkul\Security\Models\User;

/**
 * Product Quantity Relocation Eloquent model
 *
 * @property int $id
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property string|null $description
 * @property int $destination_location_id
 * @property int $destination_package_id
 * @property int $creator_id
 * @property-read \Illuminate\Database\Eloquent\Model|null $destinationLocation
 * @property-read \Illuminate\Database\Eloquent\Model|null $destinationPackage
 * @property-read \Illuminate\Database\Eloquent\Model|null $creator
 *
 */
class ProductQuantityRelocation extends Model
{
    use HasFactory;

    /**
     * Table name.
     *
     * @var string
     */
    protected $table = 'inventories_product_quantity_relocations';

    /**
     * Fillable.
     *
     * @var array
     */
    protected $fillable = [
        'description',
        'destination_location_id',
        'destination_package_id',
        'creator_id',
    ];

    /**
     * Destination Location
     *
     * @return BelongsTo
     */
    public function destinationLocation(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    /**
     * Destination Package
     *
     * @return BelongsTo
     */
    public function destinationPackage(): BelongsTo
    {
        return $this->belongsTo(Package::class);
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
     * @return ProductQuantityRelocationFactory
     */
    protected static function newFactory(): ProductQuantityRelocationFactory
    {
        return ProductQuantityRelocationFactory::new();
    }
}
