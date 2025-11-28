<?php

namespace Webkul\Inventory\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Webkul\Chatter\Traits\HasChatter;
use Webkul\Chatter\Traits\HasLogActivity;
use Webkul\Field\Traits\HasCustomFields;
use Webkul\Inventory\Database\Factories\OperationFactory;
use Webkul\Inventory\Enums\MoveType;
use Webkul\Inventory\Enums\OperationState;
use Webkul\Partner\Models\Partner;
use Webkul\Purchase\Models\Order as PurchaseOrder;
use Webkul\Sale\Models\Order as SaleOrder;
use Webkul\Security\Models\User;
use Webkul\Support\Models\Company;

/**
 * Operation Eloquent model
 *
 * @property int $id
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property string|null $name
 * @property string|null $origin
 * @property mixed $move_type
 * @property mixed $state
 * @property bool $is_favorite
 * @property string|null $description
 * @property bool $has_deadline_issue
 * @property bool $is_printed
 * @property bool $is_locked
 * @property \Carbon\Carbon|null $deadline
 * @property \Carbon\Carbon|null $scheduled_at
 * @property \Carbon\Carbon|null $closed_at
 * @property int $user_id
 * @property int $owner_id
 * @property int $operation_type_id
 * @property int $source_location_id
 * @property int $destination_location_id
 * @property int $back_order_id
 * @property int $return_id
 * @property int $partner_id
 * @property int $company_id
 * @property int $creator_id
 * @property int $sale_order_id
 * @property-read \Illuminate\Database\Eloquent\Collection $moves
 * @property-read \Illuminate\Database\Eloquent\Collection $moveLines
 * @property-read \Illuminate\Database\Eloquent\Model|null $user
 * @property-read \Illuminate\Database\Eloquent\Model|null $owner
 * @property-read \Illuminate\Database\Eloquent\Model|null $operationType
 * @property-read \Illuminate\Database\Eloquent\Model|null $sourceLocation
 * @property-read \Illuminate\Database\Eloquent\Model|null $destinationLocation
 * @property-read \Illuminate\Database\Eloquent\Model|null $backOrderOf
 * @property-read \Illuminate\Database\Eloquent\Model|null $returnOf
 * @property-read \Illuminate\Database\Eloquent\Model|null $partner
 * @property-read \Illuminate\Database\Eloquent\Model|null $company
 * @property-read \Illuminate\Database\Eloquent\Model|null $creator
 * @property-read \Illuminate\Database\Eloquent\Model|null $saleOrder
 * @property-read \Illuminate\Database\Eloquent\Collection $purchaseOrders
 * @property-read \Illuminate\Database\Eloquent\Collection $packages
 *
 */
class Operation extends Model
{
    use HasChatter, HasCustomFields, HasFactory, HasLogActivity;

    /**
     * Table name.
     *
     * @var string
     */
    protected $table = 'inventories_operations';

    /**
     * Fillable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'origin',
        'move_type',
        'state',
        'is_favorite',
        'description',
        'has_deadline_issue',
        'is_printed',
        'is_locked',
        'deadline',
        'scheduled_at',
        'closed_at',
        'user_id',
        'owner_id',
        'operation_type_id',
        'source_location_id',
        'destination_location_id',
        'back_order_id',
        'return_id',
        'partner_id',
        'company_id',
        'creator_id',
        'sale_order_id',
    ];

    /**
     * Table name.
     *
     * @var string
     */
    protected $casts = [
        'state'              => OperationState::class,
        'move_type'          => MoveType::class,
        'is_favorite'        => 'boolean',
        'has_deadline_issue' => 'boolean',
        'is_printed'         => 'boolean',
        'is_locked'          => 'boolean',
        'deadline'           => 'datetime',
        'scheduled_at'       => 'datetime',
        'closed_at'          => 'datetime',
    ];

    protected array $logAttributes = [
        'name',
        'origin',
        'move_type',
        'state',
        'is_favorite',
        'description',
        'has_deadline_issue',
        'is_printed',
        'is_locked',
        'deadline',
        'scheduled_at',
        'closed_at',
        'user.name'                     => 'User',
        'owner.name'                    => 'Owner',
        'operationType.name'            => 'Operation Type',
        'sourceLocation.full_name'      => 'Source Location',
        'destinationLocation.full_name' => 'Destination Location',
        'backOrder.name'                => 'Back Order',
        'return.name'                   => 'Return',
        'partner.name'                  => 'Partner',
        'company.name'                  => 'Company',
        'creator.name'                  => 'Creator',
    ];

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
     * Owner
     *
     * @return BelongsTo
     */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Operation Type
     *
     * @return BelongsTo
     */
    public function operationType(): BelongsTo
    {
        return $this->belongsTo(OperationType::class)->withTrashed();
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
     * Back Order Of
     *
     * @return BelongsTo
     */
    public function backOrderOf(): BelongsTo
    {
        return $this->belongsTo(self::class);
    }

    /**
     * Return Of
     *
     * @return BelongsTo
     */
    public function returnOf(): BelongsTo
    {
        return $this->belongsTo(self::class);
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

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Moves
     *
     * @return HasMany
     */
    public function moves(): HasMany
    {
        return $this->hasMany(Move::class, 'operation_id');
    }

    /**
     * Move Lines
     *
     * @return HasMany
     */
    public function moveLines(): HasMany
    {
        return $this->hasMany(MoveLine::class, 'operation_id');
    }

    /**
     * Packages
     *
     * @return HasManyThrough
     */
    public function packages(): HasManyThrough
    {
        return $this->hasManyThrough(Package::class, MoveLine::class, 'operation_id', 'id', 'id', 'result_package_id');
    }

    /**
     * Purchase Orders
     *
     * @return BelongsToMany
     */
    public function purchaseOrders(): BelongsToMany
    {
        return $this->belongsToMany(PurchaseOrder::class, 'purchases_order_operations', 'inventory_operation_id', 'purchase_order_id');
    }

    /**
     * Sale Order
     *
     * @return BelongsTo
     */
    public function saleOrder(): BelongsTo
    {
        return $this->belongsTo(SaleOrder::class, 'sale_order_id');
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

        static::saving(function ($operation) {
            $operation->updateName();
        });

        static::created(function ($operation) {
            $operation->update(['name' => $operation->name]);
        });

        static::updated(function ($operation) {
            if ($operation->wasChanged('operation_type_id')) {
                $operation->updateChildrenNames();
            }
        });
    }

    /**
     * Update the full name without triggering additional events
     */
    /**
     * Update Name
     *
     */
    public function updateName()
    {
        if (! $this->operationType->warehouse) {
            $this->name = $this->operationType->sequence_code.'/'.$this->id;
        } else {
            $this->name = $this->operationType->warehouse->code.'/'.$this->operationType->sequence_code.'/'.$this->id;
        }
    }

    /**
     * Update Children Names
     *
     * @return void
     */
    public function updateChildrenNames(): void
    {
        foreach ($this->moves as $move) {
            $move->update(['name' => $this->name]);
        }

        foreach ($this->moveLines as $moveLine) {
            $moveLine->update(['name' => $this->name]);
        }
    }

    /**
     * New Factory
     *
     * @return OperationFactory
     */
    protected static function newFactory(): OperationFactory
    {
        return OperationFactory::new();
    }
}
