<?php

namespace Webkul\Purchase\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Webkul\Purchase\Database\Factories\RequisitionLineFactory;
use Webkul\Security\Models\User;
use Webkul\Support\Models\Company;
use Webkul\Support\Models\UOM;

/**
 * Requisition Line Eloquent model
 *
 * @property int $id
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property string|null $qty
 * @property float $price_unit
 * @property int $requisition_id
 * @property int $product_id
 * @property int $uom_id
 * @property int $company_id
 * @property int $creator_id
 * @property-read \Illuminate\Database\Eloquent\Model|null $requisition
 * @property-read \Illuminate\Database\Eloquent\Model|null $product
 * @property-read \Illuminate\Database\Eloquent\Model|null $uom
 * @property-read \Illuminate\Database\Eloquent\Model|null $company
 * @property-read \Illuminate\Database\Eloquent\Model|null $creator
 *
 */
class RequisitionLine extends Model
{
    use HasFactory;

    /**
     * Table name.
     *
     * @var string
     */
    protected $table = 'purchases_requisition_lines';

    /**
     * Fillable.
     *
     * @var array
     */
    protected $fillable = [
        'qty',
        'price_unit',
        'requisition_id',
        'product_id',
        'uom_id',
        'company_id',
        'creator_id',
    ];

    /**
     * Requisition
     *
     * @return BelongsTo
     */
    public function requisition(): BelongsTo
    {
        return $this->belongsTo(Requisition::class);
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
     * @return RequisitionLineFactory
     */
    protected static function newFactory(): RequisitionLineFactory
    {
        return RequisitionLineFactory::new();
    }
}
