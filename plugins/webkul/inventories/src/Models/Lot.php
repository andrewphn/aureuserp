<?php

namespace Webkul\Inventory\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Webkul\Inventory\Database\Factories\LotFactory;
use Webkul\Inventory\Enums\LocationType;
use Webkul\Security\Models\User;
use Webkul\Support\Models\Company;
use Webkul\Support\Models\UOM;

/**
 * Lot Eloquent model
 *
 * @property int $id
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property string|null $name
 * @property string|null $description
 * @property string|null $reference
 * @property array $properties
 * @property bool $expiry_reminded
 * @property \Carbon\Carbon|null $expiration_date
 * @property \Carbon\Carbon|null $use_date
 * @property \Carbon\Carbon|null $removal_date
 * @property \Carbon\Carbon|null $alert_date
 * @property int $product_id
 * @property int $uom_id
 * @property int $location_id
 * @property int $company_id
 * @property int $creator_id
 * @property-read \Illuminate\Database\Eloquent\Collection $quantities
 * @property-read \Illuminate\Database\Eloquent\Model|null $product
 * @property-read \Illuminate\Database\Eloquent\Model|null $uom
 * @property-read \Illuminate\Database\Eloquent\Model|null $location
 * @property-read \Illuminate\Database\Eloquent\Model|null $company
 * @property-read \Illuminate\Database\Eloquent\Model|null $creator
 *
 */
class Lot extends Model
{
    use HasFactory;

    /**
     * Table name.
     *
     * @var string
     */
    protected $table = 'inventories_lots';

    /**
     * Fillable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'description',
        'reference',
        'properties',
        'expiry_reminded',
        'expiration_date',
        'use_date',
        'removal_date',
        'alert_date',
        'product_id',
        'uom_id',
        'location_id',
        'company_id',
        'creator_id',
    ];

    /**
     * Table name.
     *
     * @var string
     */
    protected $casts = [
        'properties'      => 'array',
        'expiry_reminded' => 'boolean',
        'expiration_date' => 'datetime',
        'use_date'        => 'datetime',
        'removal_date'    => 'datetime',
        'alert_date'      => 'datetime',
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
     * Uom
     *
     * @return BelongsTo
     */
    public function uom(): BelongsTo
    {
        return $this->belongsTo(UOM::class);
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
     * Quantities
     *
     * @return HasMany
     */
    public function quantities(): HasMany
    {
        return $this->hasMany(ProductQuantity::class);
    }

    public function getTotalQuantityAttribute()
    {
        return $this->quantities()
            ->whereHas('location', function ($query) {
                $query->where('type', LocationType::INTERNAL)
                    ->where('is_scrap', false);
            })
            ->sum('quantity');
    }

    /**
     * New Factory
     *
     * @return LotFactory
     */
    protected static function newFactory(): LotFactory
    {
        return LotFactory::new();
    }
}
