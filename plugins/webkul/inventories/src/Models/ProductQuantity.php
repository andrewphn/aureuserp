<?php

namespace Webkul\Inventory\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Webkul\Inventory\Database\Factories\ProductQuantityFactory;
use Webkul\Inventory\Settings\OperationSettings;
use Webkul\Partner\Models\Partner;
use Webkul\Security\Models\User;
use Webkul\Support\Models\Company;

/**
 * Product Quantity Eloquent model
 *
 * @property int $id
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property float $quantity
 * @property float $reserved_quantity
 * @property float $counted_quantity
 * @property float $difference_quantity
 * @property float $inventory_diff_quantity
 * @property bool $inventory_quantity_set
 * @property \Carbon\Carbon|null $scheduled_at
 * @property \Carbon\Carbon|null $incoming_at
 * @property int $product_id
 * @property int $location_id
 * @property int $storage_category_id
 * @property int $lot_id
 * @property int $package_id
 * @property int $partner_id
 * @property int $user_id
 * @property int $company_id
 * @property int $creator_id
 * @property-read \Illuminate\Database\Eloquent\Model|null $product
 * @property-read \Illuminate\Database\Eloquent\Model|null $location
 * @property-read \Illuminate\Database\Eloquent\Model|null $storageCategory
 * @property-read \Illuminate\Database\Eloquent\Model|null $lot
 * @property-read \Illuminate\Database\Eloquent\Model|null $package
 * @property-read \Illuminate\Database\Eloquent\Model|null $partner
 * @property-read \Illuminate\Database\Eloquent\Model|null $user
 * @property-read \Illuminate\Database\Eloquent\Model|null $company
 * @property-read \Illuminate\Database\Eloquent\Model|null $creator
 *
 */
class ProductQuantity extends Model
{
    use HasFactory;

    /**
     * Table name.
     *
     * @var string
     */
    protected $table = 'inventories_product_quantities';

    /**
     * Fillable.
     *
     * @var array
     */
    protected $fillable = [
        'quantity',
        'reserved_quantity',
        'counted_quantity',
        'difference_quantity',
        'inventory_diff_quantity',
        'inventory_quantity_set',
        'scheduled_at',
        'incoming_at',
        'product_id',
        'location_id',
        'storage_category_id',
        'lot_id',
        'package_id',
        'partner_id',
        'user_id',
        'company_id',
        'creator_id',
    ];

    /**
     * Table name.
     *
     * @var string
     */
    protected $casts = [
        'inventory_quantity_set' => 'boolean',
        'scheduled_at'           => 'date',
        'incoming_at'            => 'datetime',
    ];

    /**
     * Product
     *
     * @return BelongsTo
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Location
     *
     * @return BelongsTo
     */
    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
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
     * Lot
     *
     * @return BelongsTo
     */
    public function lot(): BelongsTo
    {
        return $this->belongsTo(Lot::class);
    }

    /**
     * Package
     *
     * @return BelongsTo
     */
    public function package(): BelongsTo
    {
        return $this->belongsTo(Package::class);
    }

    /**
     * Partner
     *
     * @return BelongsTo
     */
    public function partner(): BelongsTo
    {
        return $this->belongsTo(Partner::class);
    }

    /**
     * User
     *
     * @return BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
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

    public function getAvailableQuantityAttribute(): float
    {
        return $this->quantity - $this->reserved_quantity;
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

        static::saving(function ($productQuantity) {
            $productQuantity->updateScheduledAt();
        });
    }

    /**
     * Update the scheduled_at attribute
     */
    /**
     * Update Scheduled At
     *
     */
    public function updateScheduledAt()
    {
        $this->scheduled_at = Carbon::create(
            now()->year,
            app(OperationSettings::class)->annual_inventory_month,
            app(OperationSettings::class)->annual_inventory_day,
            0, 0, 0
        );

        if ($this->location?->cyclic_inventory_frequency) {
            $this->scheduled_at = now()->addDays($this->location->cyclic_inventory_frequency);
        }
    }

    /**
     * New Factory
     *
     * @return ProductQuantityFactory
     */
    protected static function newFactory(): ProductQuantityFactory
    {
        return ProductQuantityFactory::new();
    }
}
