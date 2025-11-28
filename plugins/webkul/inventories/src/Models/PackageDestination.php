<?php

namespace Webkul\Inventory\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Webkul\Inventory\Database\Factories\PackageDestinationFactory;
use Webkul\Security\Models\User;

/**
 * Package Destination Eloquent model
 *
 * @property int $id
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property int $operation_id
 * @property int $destination_location_id
 * @property int $creator_id
 * @property-read \Illuminate\Database\Eloquent\Model|null $operation
 * @property-read \Illuminate\Database\Eloquent\Model|null $destinationLocation
 * @property-read \Illuminate\Database\Eloquent\Model|null $creator
 *
 */
class PackageDestination extends Model
{
    use HasFactory;

    /**
     * Table name.
     *
     * @var string
     */
    protected $table = 'inventories_package_destinations';

    /**
     * Fillable.
     *
     * @var array
     */
    protected $fillable = [
        'operation_id',
        'destination_location_id',
        'creator_id',
    ];

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
     * @return PackageDestinationFactory
     */
    protected static function newFactory(): PackageDestinationFactory
    {
        return PackageDestinationFactory::new();
    }
}
