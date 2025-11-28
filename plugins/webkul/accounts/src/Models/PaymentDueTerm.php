<?php

namespace Webkul\Account\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Webkul\Security\Models\User;

/**
 * Payment Due Term Eloquent model
 *
 * @property int $id
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property string|null $nb_days
 * @property int $payment_id
 * @property int $creator_id
 * @property string|null $value
 * @property string|null $delay_type
 * @property string|null $days_next_month
 * @property float $value_amount
 * @property-read \Illuminate\Database\Eloquent\Model|null $paymentTerm
 * @property-read \Illuminate\Database\Eloquent\Model|null $creator
 *
 */
class PaymentDueTerm extends Model
{
    use HasFactory;

    protected $table = 'accounts_payment_due_terms';

    protected $fillable = [
        'nb_days',
        'payment_id',
        'creator_id',
        'value',
        'delay_type',
        'days_next_month',
        'value_amount',
    ];

    /**
     * Payment Term
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function paymentTerm()
    {
        return $this->belongsTo(PaymentTerm::class, 'payment_id');
    }

    /**
     * Creator
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'creator_id');
    }
}
