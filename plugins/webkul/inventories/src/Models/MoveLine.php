<?php

namespace Webkul\Inventory\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Webkul\Inventory\Database\Factories\MoveLineFactory;
use Webkul\Inventory\Enums\MoveState;
use Webkul\Partner\Models\Partner;
use Webkul\Security\Models\User;
use Webkul\Support\Models\Company;
use Webkul\Support\Models\UOM;

/**
 * Move Line Eloquent model
 *
 * @property int $id
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property string|null $lot_name
 * @property mixed $state
 * @property string|null $reference
 * @property string|null $picking_description
 * @property string|null $qty
 * @property string|null $uom_qty
 * @property bool $is_picked
 * @property \Carbon\Carbon|null $scheduled_at
 * @property int $move_id
 * @property int $operation_id
 * @property int $product_id
 * @property int $uom_id
 * @property int $package_id
 * @property int $result_package_id
 * @property int $package_level_id
 * @property int $lot_id
 * @property int $partner_id
 * @property int $source_location_id
 * @property int $destination_location_id
 * @property int $company_id
 * @property int $creator_id
 * @property-read \Illuminate\Database\Eloquent\Model|null $move
 * @property-read \Illuminate\Database\Eloquent\Model|null $operation
 * @property-read \Illuminate\Database\Eloquent\Model|null $product
 * @property-read \Illuminate\Database\Eloquent\Model|null $uom
 * @property-read \Illuminate\Database\Eloquent\Model|null $sourceLocation
 * @property-read \Illuminate\Database\Eloquent\Model|null $destinationLocation
 * @property-read \Illuminate\Database\Eloquent\Model|null $package
 * @property-read \Illuminate\Database\Eloquent\Model|null $resultPackage
 * @property-read \Illuminate\Database\Eloquent\Model|null $packageLevel
 * @property-read \Illuminate\Database\Eloquent\Model|null $lot
 * @property-read \Illuminate\Database\Eloquent\Model|null $partner
 * @property-read \Illuminate\Database\Eloquent\Model|null $company
 * @property-read \Illuminate\Database\Eloquent\Model|null $creator
 *
 */
class MoveLine extends Model
{
    use HasFactory;

    /**
     * Table name.
     *
     * @var string
     */
    protected $table = 'inventories_move_lines';

    /**
     * Fillable.
     *
     * @var array
     */
    protected $fillable = [
        'lot_name',
        'state',
        'reference',
        'picking_description',
        'qty',
        'uom_qty',
        'is_picked',
        'scheduled_at',
        'move_id',
        'operation_id',
        'product_id',
        'uom_id',
        'package_id',
        'result_package_id',
        'package_level_id',
        'lot_id',
        'partner_id',
        'source_location_id',
        'destination_location_id',
        'company_id',
        'creator_id',
    ];

    /**
     * Table casts.
     *
     * @var array
     */
    protected $casts = [
        'state'             => MoveState::class,
        'is_picked'         => 'boolean',
        'scheduled_at'      => 'datetime',
    ];

    /**
     * Move
     *
     * @return BelongsTo
     */
    public function move(): BelongsTo
    {
        return $this->belongsTo(Move::class);
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
     * Source Location
     *
     * @return BelongsTo
     */
    public function sourceLocation(): BelongsTo
    {
        return $this->belongsTo(Location::class)->withTrashed();
    }

    /**
     * Destination Location
     *
     * @return BelongsTo
     */
    public function destinationLocation(): BelongsTo
    {
        return $this->belongsTo(Location::class)->withTrashed();
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
     * Result Package
     *
     * @return BelongsTo
     */
    public function resultPackage(): BelongsTo
    {
        return $this->belongsTo(Package::class);
    }

    /**
     * Package Level
     *
     * @return BelongsTo
     */
    public function packageLevel(): BelongsTo
    {
        return $this->belongsTo(PackageLevel::class);
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
     * Partner
     *
     * @return BelongsTo
     */
    public function partner(): BelongsTo
    {
        return $this->belongsTo(Partner::class);
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
     * @return MoveLineFactory
     */
    protected static function newFactory(): MoveLineFactory
    {
        return MoveLineFactory::new();
    }
}
