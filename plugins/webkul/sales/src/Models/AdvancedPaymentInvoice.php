<?php

namespace Webkul\Sale\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Webkul\Security\Models\User;
use Webkul\Support\Models\Company;
use Webkul\Support\Models\Currency;

/**
 * Advanced Payment Invoice Eloquent model
 *
 * @property int $id
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property int $currency_id
 * @property int $company_id
 * @property int $creator_id
 * @property string|null $advance_payment_method
 * @property float $fixed_amount
 * @property string|null $deduct_down_payments
 * @property string|null $consolidated_billing
 * @property float $amount
 * @property-read \Illuminate\Database\Eloquent\Model|null $currency
 * @property-read \Illuminate\Database\Eloquent\Model|null $company
 * @property-read \Illuminate\Database\Eloquent\Model|null $creator
 * @property-read \Illuminate\Database\Eloquent\Collection $orders
 *
 */
class AdvancedPaymentInvoice extends Model
{
    protected $table = 'sales_advance_payment_invoices';

    protected $fillable = [
        'currency_id',
        'company_id',
        'creator_id',
        'advance_payment_method',
        'fixed_amount',
        'deduct_down_payments',
        'consolidated_billing',
        'amount',
    ];

    /**
     * Currency
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function currency()
    {
        return $this->belongsTo(Currency::class);
    }

    /**
     * Company
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Creator
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function creator()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Orders
     *
     * @return BelongsToMany
     */
    public function orders(): BelongsToMany
    {
        return $this->belongsToMany(Order::class, 'sales_advance_payment_invoice_order_sales', 'advance_payment_invoice_id', 'order_id');
    }
}
