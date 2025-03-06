<?php

namespace Webkul\Sale\Models;

use Illuminate\Database\Eloquent\Model;
use Webkul\Sale\Models\AdvancePaymentInvoice;
use Webkul\Sale\Models\Order;

class AdvancePaymentInvoiceOrderSale extends Model
{
    protected $table = 'sales_advance_payment_invoice_order_sales';

    protected $fillable = [
        'advance_payment_invoice_id',
        'order_id',
    ];

    public $timestamps = false;

    public function advancePaymentInvoice()
    {
        return $this->belongsTo(AdvancePaymentInvoice::class, 'advance_payment_invoice_id');
    }

    public function order()
    {
        return $this->belongsTo(Order::class, 'order_id');
    }
}
