<?php

namespace Webkul\Inventory\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Webkul\Inventory\Database\Factories\PackageLevelFactory;
use Webkul\Security\Models\User;
use Webkul\Support\Models\Company;

/**
 * Package Level Eloquent model
 *
 * @property int $id
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property int $package_id
 * @property int $operation_id
 * @property int $destination_location_id
 * @property int $company_id
 * @property int $creator_id
 * @property-read \Illuminate\Database\Eloquent\Model|null $package
 * @property-read \Illuminate\Database\Eloquent\Model|null $operation
 * @property-read \Illuminate\Database\Eloquent\Model|null $destinationLocation
 * @property-read \Illuminate\Database\Eloquent\Model|null $company
 * @property-read \Illuminate\Database\Eloquent\Model|null $creator
 *
 */
class PackageLevel extends Model
{
    use HasFactory;

    /**
     * Table name.
     *
     * @var string
     */
    protected $table = 'inventories_package_levels';

    /**
     * Fillable.
     *
     * @var array
     */
    protected $fillable = [
        'package_id',
        'operation_id',
        'destination_location_id',
        'company_id',
        'creator_id',
    ];

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
     * Operation
     *
     * @return BelongsTo
     */
    public function operation(): BelongsTo
    {
        return $this->belongsTo(Operation::class);
    }

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
     * @return PackageLevelFactory
     */
    protected static function newFactory(): PackageLevelFactory
    {
        return PackageLevelFactory::new();
    }
}
