<?php

namespace Webkul\Inventory\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Webkul\Inventory\Database\Factories\PackageFactory;
use Webkul\Inventory\Enums\PackageUse;
use Webkul\Security\Models\User;
use Webkul\Support\Models\Company;

/**
 * Package Eloquent model
 *
 * @property int $id
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property string|null $name
 * @property mixed $package_use
 * @property \Carbon\Carbon|null $pack_date
 * @property int $package_type_id
 * @property int $location_id
 * @property int $company_id
 * @property int $creator_id
 * @property-read \Illuminate\Database\Eloquent\Collection $quantities
 * @property-read \Illuminate\Database\Eloquent\Collection $moveLines
 * @property-read \Illuminate\Database\Eloquent\Model|null $packageType
 * @property-read \Illuminate\Database\Eloquent\Model|null $location
 * @property-read \Illuminate\Database\Eloquent\Model|null $company
 * @property-read \Illuminate\Database\Eloquent\Model|null $creator
 * @property-read \Illuminate\Database\Eloquent\Collection $operations
 * @property-read \Illuminate\Database\Eloquent\Collection $moves
 *
 */
class Package extends Model
{
    use HasFactory;

    /**
     * Table name.
     *
     * @var string
     */
    protected $table = 'inventories_packages';

    /**
     * Fillable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'package_use',
        'pack_date',
        'package_type_id',
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
        'package_use' => PackageUse::class,
        'pack_date'   => 'date',
    ];

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
     * Location
     *
     * @return BelongsTo
     */
    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
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

    /**
     * Operations
     *
     * @return HasManyThrough
     */
    public function operations(): HasManyThrough
    {
        return $this->hasManyThrough(
            Operation::class,
            MoveLine::class,
            'result_package_id',
            'id',
            'id',
            'operation_id'
        );
    }

    /**
     * Moves
     *
     * @return HasManyThrough
     */
    public function moves(): HasManyThrough
    {
        return $this->hasManyThrough(
            Move::class,
            MoveLine::class,
            'package_id',
            'id',
            'id',
            'move_id'
        );
    }

    /**
     * Move Lines
     *
     * @return HasMany
     */
    public function moveLines(): HasMany
    {
        return $this->hasMany(MoveLine::class);
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
     * @return PackageFactory
     */
    protected static function newFactory(): PackageFactory
    {
        return PackageFactory::new();
    }
}
