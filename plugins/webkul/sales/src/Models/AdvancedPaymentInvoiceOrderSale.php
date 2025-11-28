<?php

namespace Webkul\Sale\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Advanced Payment Invoice Order Sale Eloquent model
 *
 * @property int $id
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property int $advance_payment_invoice_id
 * @property int $order_id
 * @property-read \Illuminate\Database\Eloquent\Model|null $advancePaymentInvoice
 * @property-read \Illuminate\Database\Eloquent\Model|null $order
 *
 */
class AdvancedPaymentInvoiceOrderSale extends Model
{
    protected $table = 'sales_advance_payment_invoice_order_sales';

    protected $fillable = [
        'advance_payment_invoice_id',
        'order_id',
    ];

    public $timestamps = false;

    /**
     * Advance Payment Invoice
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function advancePaymentInvoice()
    {
        return $this->belongsTo(AdvancedPaymentInvoice::class, 'advance_payment_invoice_id');
    }

    /**
     * Order
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function order()
    {
        return $this->belongsTo(Order::class, 'order_id');
    }
}
