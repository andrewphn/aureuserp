<?php

namespace Webkul\Sale\Models;

use Illuminate\Database\Eloquent\Model;
use Webkul\Security\Models\User;
use Webkul\Support\Models\Company;
use Webkul\Support\Models\UOM;

/**
 * Order Template Product Eloquent model
 *
 * @property int $id
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property int $order_template_id
 * @property int $company_id
 * @property int $product_id
 * @property int $product_uom_id
 * @property int $creator_id
 * @property string|null $name
 * @property float $quantity
 * @property string|null $display_type
 * @property-read \Illuminate\Database\Eloquent\Model|null $orderTemplate
 * @property-read \Illuminate\Database\Eloquent\Model|null $company
 * @property-read \Illuminate\Database\Eloquent\Model|null $product
 * @property-read \Illuminate\Database\Eloquent\Model|null $uom
 * @property-read \Illuminate\Database\Eloquent\Model|null $createdBy
 *
 */
class OrderTemplateProduct extends Model
{
    protected $table = 'sales_order_template_products';

    protected $fillable = [
        'order_template_id',
        'company_id',
        'product_id',
        'product_uom_id',
        'creator_id',
        'name',
        'quantity',
        'display_type',
    ];

    /**
     * Order Template
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function orderTemplate()
    {
        return $this->belongsTo(OrderTemplate::class, 'order_template_id');
    }

    /**
     * Company
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function company()
    {
        return $this->belongsTo(Company::class, 'company_id');
    }

    /**
     * Product
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    /**
     * Uom
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function uom()
    {
        return $this->belongsTo(UOM::class, 'product_uom_id');
    }

    /**
     * Created By
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function createdBy()
    {
        return $this->belongsTo(User::class, 'creator_id');
    }

    /**
     * Bootstrap the model and its traits.
     */
    /**
     * Boot
     *
     * @return void
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($orderTemplateProduct) {
            $orderTemplateProduct->company_id = $orderTemplateProduct->company_id ?? Company::first()?->id;
            $orderTemplateProduct->product_id = $orderTemplateProduct->product_id ?? Product::first()?->id;
            $orderTemplateProduct->product_uom_id = $orderTemplateProduct->product_uom_id ?? UOM::first()?->id;
            $orderTemplateProduct->creator_id = $orderTemplateProduct->creator_id ?? User::first()?->id;
        });
    }
}
