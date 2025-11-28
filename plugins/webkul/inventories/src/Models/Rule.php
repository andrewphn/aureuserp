<?php

namespace Webkul\Inventory\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\EloquentSortable\Sortable;
use Spatie\EloquentSortable\SortableTrait;
use Webkul\Inventory\Database\Factories\RuleFactory;
use Webkul\Inventory\Enums\GroupPropagation;
use Webkul\Inventory\Enums\ProcureMethod;
use Webkul\Inventory\Enums\RuleAction;
use Webkul\Inventory\Enums\RuleAuto;
use Webkul\Partner\Models\Partner;
use Webkul\Security\Models\User;
use Webkul\Support\Models\Company;

/**
 * Rule Eloquent model
 *
 * @property int $id
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property \Carbon\Carbon|null $deleted_at
 * @property string|null $sort
 * @property string|null $name
 * @property string|null $route_sort
 * @property string|null $delay
 * @property mixed $group_propagation_option
 * @property mixed $action
 * @property mixed $procure_method
 * @property mixed $auto
 * @property string|null $push_domain
 * @property bool $location_dest_from_rule
 * @property bool $propagate_cancel
 * @property bool $propagate_carrier
 * @property int $source_location_id
 * @property int $destination_location_id
 * @property int $route_id
 * @property int $operation_type_id
 * @property int $partner_address_id
 * @property int $warehouse_id
 * @property int $propagate_warehouse_id
 * @property int $company_id
 * @property int $creator_id
 * @property-read \Illuminate\Database\Eloquent\Model|null $sourceLocation
 * @property-read \Illuminate\Database\Eloquent\Model|null $destinationLocation
 * @property-read \Illuminate\Database\Eloquent\Model|null $route
 * @property-read \Illuminate\Database\Eloquent\Model|null $operationType
 * @property-read \Illuminate\Database\Eloquent\Model|null $warehouse
 * @property-read \Illuminate\Database\Eloquent\Model|null $propagateWarehouse
 * @property-read \Illuminate\Database\Eloquent\Model|null $partnerAddress
 * @property-read \Illuminate\Database\Eloquent\Model|null $company
 * @property-read \Illuminate\Database\Eloquent\Model|null $creator
 *
 */
class Rule extends Model implements Sortable
{
    use HasFactory, SoftDeletes, SortableTrait;

    /**
     * Table name.
     *
     * @var string
     */
    protected $table = 'inventories_rules';

    /**
     * Fillable.
     *
     * @var array
     */
    protected $fillable = [
        'sort',
        'name',
        'route_sort',
        'delay',
        'group_propagation_option',
        'action',
        'procure_method',
        'auto',
        'push_domain',
        'location_dest_from_rule',
        'propagate_cancel',
        'propagate_carrier',
        'source_location_id',
        'destination_location_id',
        'route_id',
        'operation_type_id',
        'partner_address_id',
        'warehouse_id',
        'propagate_warehouse_id',
        'company_id',
        'creator_id',
        'deleted_at',
    ];

    /**
     * Table name.
     *
     * @var string
     */
    protected $casts = [
        'action'                   => RuleAction::class,
        'group_propagation_option' => GroupPropagation::class,
        'auto'                     => RuleAuto::class,
        'procure_method'           => ProcureMethod::class,
        'location_dest_from_rule'  => 'boolean',
        'propagate_cancel'         => 'boolean',
        'propagate_carrier'        => 'boolean',
    ];

    public $sortable = [
        'order_column_name'  => 'sort',
        'sort_when_creating' => true,
    ];

    /**
     * Source Location
     *
     * @return BelongsTo
     */
    public function sourceLocation(): BelongsTo
    {
        return $this->belongsTo(Location::class);
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
     * Route
     *
     * @return BelongsTo
     */
    public function route(): BelongsTo
    {
        return $this->belongsTo(Route::class);
    }

    /**
     * Operation Type
     *
     * @return BelongsTo
     */
    public function operationType(): BelongsTo
    {
        return $this->belongsTo(OperationType::class);
    }

    /**
     * Warehouse
     *
     * @return BelongsTo
     */
    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    /**
     * Propagate Warehouse
     *
     * @return BelongsTo
     */
    public function propagateWarehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    /**
     * Partner Address
     *
     * @return BelongsTo
     */
    public function partnerAddress(): BelongsTo
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
     * @return RuleFactory
     */
    protected static function newFactory(): RuleFactory
    {
        return RuleFactory::new();
    }
}
