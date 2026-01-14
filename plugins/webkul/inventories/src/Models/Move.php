<?php

namespace Webkul\Inventory\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Webkul\Inventory\Database\Factories\MoveFactory;
use Webkul\Inventory\Enums\LocationType;
use Webkul\Inventory\Enums\MoveState;
use Webkul\Partner\Models\Partner;
use Webkul\Purchase\Models\OrderLine as PurchaseOrderLine;
use Webkul\Sale\Models\OrderLine as SaleOrderLine;
use Webkul\Security\Models\User;
use Webkul\Support\Models\Company;
use Webkul\Support\Models\UOM;

/**
 * Move Eloquent model
 *
 * @property int $id
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property string|null $name
 * @property mixed $state
 * @property string|null $origin
 * @property string|null $procure_method
 * @property string|null $reference
 * @property string|null $description_picking
 * @property string|null $next_serial
 * @property float $next_serial_count
 * @property bool $is_favorite
 * @property string|null $product_qty
 * @property string|null $product_uom_qty
 * @property float $quantity
 * @property bool $is_picked
 * @property bool $is_scraped
 * @property bool $is_inventory
 * @property bool $is_refund
 * @property \Carbon\Carbon|null $deadline
 * @property \Carbon\Carbon|null $reservation_date
 * @property \Carbon\Carbon|null $scheduled_at
 * @property int $product_id
 * @property int $uom_id
 * @property int $source_location_id
 * @property int $destination_location_id
 * @property int $final_location_id
 * @property int $partner_id
 * @property int $operation_id
 * @property int $rule_id
 * @property int $operation_type_id
 * @property int $origin_returned_move_id
 * @property int $restrict_partner_id
 * @property int $warehouse_id
 * @property int $product_packaging_id
 * @property int $scrap_id
 * @property int $company_id
 * @property int $creator_id
 * @property int $purchase_order_line_id
 * @property int $sale_order_line_id
 * @property-read \Illuminate\Database\Eloquent\Collection $lines
 * @property-read \Illuminate\Database\Eloquent\Model|null $product
 * @property-read \Illuminate\Database\Eloquent\Model|null $uom
 * @property-read \Illuminate\Database\Eloquent\Model|null $sourceLocation
 * @property-read \Illuminate\Database\Eloquent\Model|null $destinationLocation
 * @property-read \Illuminate\Database\Eloquent\Model|null $finalLocation
 * @property-read \Illuminate\Database\Eloquent\Model|null $partner
 * @property-read \Illuminate\Database\Eloquent\Model|null $operation
 * @property-read \Illuminate\Database\Eloquent\Model|null $scrap
 * @property-read \Illuminate\Database\Eloquent\Model|null $rule
 * @property-read \Illuminate\Database\Eloquent\Model|null $operationType
 * @property-read \Illuminate\Database\Eloquent\Model|null $originReturnedMove
 * @property-read \Illuminate\Database\Eloquent\Model|null $restrictPartner
 * @property-read \Illuminate\Database\Eloquent\Model|null $warehouse
 * @property-read \Illuminate\Database\Eloquent\Model|null $packageLevel
 * @property-read \Illuminate\Database\Eloquent\Model|null $productPackaging
 * @property-read \Illuminate\Database\Eloquent\Model|null $company
 * @property-read \Illuminate\Database\Eloquent\Model|null $creator
 * @property-read \Illuminate\Database\Eloquent\Model|null $purchaseOrderLine
 * @property-read \Illuminate\Database\Eloquent\Model|null $saleOrderLine
 * @property-read \Illuminate\Database\Eloquent\Collection $moveDestinations
 *
 */
class Move extends Model
{
    use HasFactory;

    /**
     * Table name.
     *
     * @var string
     */
    protected $table = 'inventories_moves';

    /**
     * Fillable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'state',
        'origin',
        'procure_method',
        'reference',
        'description_picking',
        'next_serial',
        'next_serial_count',
        'is_favorite',
        'product_qty',
        'product_uom_qty',
        'quantity',
        'is_picked',
        'is_scraped',
        'is_inventory',
        'is_refund',
        'ai_confidence',
        'ai_source_sku',
        'ai_matched_by',
        'requires_review',
        'deadline',
        'reservation_date',
        'scheduled_at',
        'product_id',
        'uom_id',
        'source_location_id',
        'destination_location_id',
        'final_location_id',
        'partner_id',
        'operation_id',
        'rule_id',
        'operation_type_id',
        'origin_returned_move_id',
        'restrict_partner_id',
        'warehouse_id',
        'product_packaging_id',
        'scrap_id',
        'company_id',
        'creator_id',
        'purchase_order_line_id',
        'sale_order_line_id',
    ];

    /**
     * Table name.
     *
     * @var string
     */
    protected $casts = [
        'state'            => MoveState::class,
        'is_favorite'      => 'boolean',
        'is_picked'        => 'boolean',
        'is_scraped'       => 'boolean',
        'is_inventory'     => 'boolean',
        'is_refund'        => 'boolean',
        'requires_review'  => 'boolean',
        'ai_confidence'    => 'decimal:2',
        'reservation_date' => 'date',
        'scheduled_at'     => 'datetime',
        'deadline'         => 'datetime',
        'alert_Date'       => 'datetime',
    ];

    /**
     * Determines if a stock move is a purchase return
     *
     * @return bool True if the move is a purchase return, false otherwise
     */
    public function isPurchaseReturn()
    {
        return $this->destinationLocation->type === LocationType::SUPPLIER
            || (
                $this->originReturnedMove
                && $this->destinationLocation->id === $this->destinationLocation->company->inter_company_location_id
            );
    }

    /**
     * Determines if a stock move is a purchase return
     *
     * @return bool True if the move is a purchase return, false otherwise
     */
    public function isDropshipped()
    {
        return (
            $this->sourceLocation->type === LocationType::SUPPLIER
            || ($this->sourceLocation->type === LocationType::TRANSIT && ! $this->sourceLocation->company_id)
        )
            && (
                $this->destinationLocation->type === LocationType::CUSTOMER
                || ($this->destinationLocation->type === LocationType::TRANSIT && ! $this->destinationLocation->company_id)
            );
    }

    /**
     * Determines if a stock move is a purchase return
     *
     * @return bool True if the move is a purchase return, false otherwise
     */
    public function isDropshippedReturned()
    {
        return (
            $this->sourceLocation->type === LocationType::CUSTOMER
            || ($this->sourceLocation->type === LocationType::TRANSIT && ! $this->sourceLocation->company_id)
        )
            && (
                $this->destinationLocation->type === LocationType::SUPPLIER
                || ($this->destinationLocation->type === LocationType::TRANSIT && ! $this->destinationLocation->company_id)
            );
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
     * Final Location
     *
     * @return BelongsTo
     */
    public function finalLocation(): BelongsTo
    {
        return $this->belongsTo(Location::class)->withTrashed();
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
     * Operation
     *
     * @return BelongsTo
     */
    public function operation(): BelongsTo
    {
        return $this->belongsTo(Operation::class);
    }

    /**
     * Scrap
     *
     * @return BelongsTo
     */
    public function scrap(): BelongsTo
    {
        return $this->belongsTo(Scrap::class);
    }

    /**
     * Rule
     *
     * @return BelongsTo
     */
    public function rule(): BelongsTo
    {
        return $this->belongsTo(Rule::class);
    }

    public function operationType(): BelongsTo
    {
        return $this->belongsTo(OperationType::class);
    }

    /**
     * Origin Returned Move
     *
     * @return BelongsTo
     */
    public function originReturnedMove(): BelongsTo
    {
        return $this->belongsTo(self::class);
    }

    /**
     * Restrict Partner
     *
     * @return BelongsTo
     */
    public function restrictPartner(): BelongsTo
    {
        return $this->belongsTo(Partner::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
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
     * Product Packaging
     *
     * @return BelongsTo
     */
    public function productPackaging(): BelongsTo
    {
        return $this->belongsTo(Packaging::class);
    }

    /**
     * Lines
     *
     * @return HasMany
     */
    public function lines(): HasMany
    {
        return $this->hasMany(MoveLine::class);
    }

    /**
     * Move Destinations
     *
     * @return BelongsToMany
     */
    public function moveDestinations(): BelongsToMany
    {
        return $this->belongsToMany(Move::class, 'inventories_move_destinations', 'origin_move_id', 'destination_move_id');
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
     * Should Bypass Reservation
     *
     * @return bool
     */
    public function shouldBypassReservation(): bool
    {
        return $this->sourceLocation->shouldBypassReservation() || ! $this->product->is_storable;
    }

    /**
     * Purchase Order Line
     *
     * @return BelongsTo
     */
    public function purchaseOrderLine(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrderLine::class, 'purchase_order_line_id');
    }

    /**
     * Sale Order Line
     *
     * @return BelongsTo
     */
    public function saleOrderLine(): BelongsTo
    {
        return $this->belongsTo(SaleOrderLine::class, 'sale_order_line_id');
    }

    protected static function newFactory(): MoveFactory
    {
        return MoveFactory::new();
    }

    /**
     * Check if this move was populated by AI
     */
    public function wasAiPopulated(): bool
    {
        return $this->ai_confidence !== null;
    }

    /**
     * Get the AI confidence as a percentage
     */
    public function getAiConfidencePercentAttribute(): ?int
    {
        if ($this->ai_confidence === null) {
            return null;
        }
        return (int) round($this->ai_confidence * 100);
    }

    /**
     * Get the confidence badge color
     */
    public function getAiConfidenceColorAttribute(): string
    {
        if ($this->ai_confidence === null) {
            return 'gray';
        }

        if ($this->ai_confidence >= 0.9) {
            return 'success';
        } elseif ($this->ai_confidence >= 0.7) {
            return 'warning';
        } else {
            return 'danger';
        }
    }
}
