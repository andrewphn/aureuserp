<?php

namespace Webkul\Inventory\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\EloquentSortable\Sortable;
use Spatie\EloquentSortable\SortableTrait;
use Webkul\Inventory\Database\Factories\OperationTypeFactory;
use Webkul\Inventory\Enums;
use Webkul\Inventory\Enums\CreateBackorder;
use Webkul\Inventory\Enums\MoveType;
use Webkul\Inventory\Enums\ReservationMethod;
use Webkul\Security\Models\User;
use Webkul\Support\Models\Company;

/**
 * Operation Type Eloquent model
 *
 * @property int $id
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property \Carbon\Carbon|null $deleted_at
 * @property string|null $name
 * @property mixed $type
 * @property string|null $sort
 * @property string|null $sequence_code
 * @property mixed $reservation_method
 * @property string|null $reservation_days_before
 * @property string|null $reservation_days_before_priority
 * @property string|null $product_label_format
 * @property string|null $lot_label_format
 * @property string|null $package_label_to_print
 * @property string|null $barcode
 * @property mixed $create_backorder
 * @property mixed $move_type
 * @property bool $show_entire_packs
 * @property bool $use_create_lots
 * @property bool $use_existing_lots
 * @property bool $print_label
 * @property bool $show_operations
 * @property bool $auto_show_reception_report
 * @property bool $auto_print_delivery_slip
 * @property bool $auto_print_return_slip
 * @property bool $auto_print_product_labels
 * @property bool $auto_print_lot_labels
 * @property bool $auto_print_reception_report
 * @property bool $auto_print_reception_report_labels
 * @property bool $auto_print_packages
 * @property bool $auto_print_package_label
 * @property int $return_operation_type_id
 * @property int $source_location_id
 * @property int $destination_location_id
 * @property int $warehouse_id
 * @property int $company_id
 * @property int $creator_id
 * @property-read \Illuminate\Database\Eloquent\Model|null $returnOperationType
 * @property-read \Illuminate\Database\Eloquent\Model|null $storageCategory
 * @property-read \Illuminate\Database\Eloquent\Model|null $sourceLocation
 * @property-read \Illuminate\Database\Eloquent\Model|null $destinationLocation
 * @property-read \Illuminate\Database\Eloquent\Model|null $warehouse
 * @property-read \Illuminate\Database\Eloquent\Model|null $company
 * @property-read \Illuminate\Database\Eloquent\Model|null $creator
 * @property-read \Illuminate\Database\Eloquent\Collection $storageCategoryCapacities
 *
 */
class OperationType extends Model implements Sortable
{
    use HasFactory, SoftDeletes, SortableTrait;

    /**
     * Table name.
     *
     * @var string
     */
    protected $table = 'inventories_operation_types';

    /**
     * Fillable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'type',
        'sort',
        'sequence_code',
        'reservation_method',
        'reservation_days_before',
        'reservation_days_before_priority',
        'product_label_format',
        'lot_label_format',
        'package_label_to_print',
        'barcode',
        'create_backorder',
        'move_type',
        'show_entire_packs',
        'use_create_lots',
        'use_existing_lots',
        'print_label',
        'show_operations',
        'auto_show_reception_report',
        'auto_print_delivery_slip',
        'auto_print_return_slip',
        'auto_print_product_labels',
        'auto_print_lot_labels',
        'auto_print_reception_report',
        'auto_print_reception_report_labels',
        'auto_print_packages',
        'auto_print_package_label',
        'return_operation_type_id',
        'source_location_id',
        'destination_location_id',
        'warehouse_id',
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
        'type'                               => Enums\OperationType::class,
        'reservation_method'                 => ReservationMethod::class,
        'create_backorder'                   => CreateBackorder::class,
        'move_type'                          => MoveType::class,
        'show_entire_packs'                  => 'boolean',
        'use_create_lots'                    => 'boolean',
        'use_existing_lots'                  => 'boolean',
        'print_label'                        => 'boolean',
        'show_operations'                    => 'boolean',
        'auto_show_reception_report'         => 'boolean',
        'auto_print_delivery_slip'           => 'boolean',
        'auto_print_return_slip'             => 'boolean',
        'auto_print_product_labels'          => 'boolean',
        'auto_print_lot_labels'              => 'boolean',
        'auto_print_reception_report'        => 'boolean',
        'auto_print_reception_report_labels' => 'boolean',
        'auto_print_packages'                => 'boolean',
        'auto_print_package_label'           => 'boolean',
    ];

    public $sortable = [
        'order_column_name'  => 'sort',
        'sort_when_creating' => true,
    ];

    /**
     * Return Operation Type
     *
     * @return BelongsTo
     */
    public function returnOperationType(): BelongsTo
    {
        return $this->belongsTo(self::class);
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
     * Warehouse
     *
     * @return BelongsTo
     */
    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class)->withTrashed();
    }

    /**
     * Storage Category Capacities
     *
     * @return BelongsToMany
     */
    public function storageCategoryCapacities(): BelongsToMany
    {
        return $this->belongsToMany(StorageCategoryCapacity::class, 'inventories_storage_category_capacities', 'storage_category_id', 'package_type_id');
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
     * @return OperationTypeFactory
     */
    protected static function newFactory(): OperationTypeFactory
    {
        return OperationTypeFactory::new();
    }
}
