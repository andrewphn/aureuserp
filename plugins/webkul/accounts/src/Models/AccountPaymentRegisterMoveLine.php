<?php

namespace Webkul\Account\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Account Payment Register Move Line Eloquent model
 *
 * @property int $id
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property int $payment_register_id
 * @property int $move_line_id
 * @property-read \Illuminate\Database\Eloquent\Model|null $paymentRegister
 * @property-read \Illuminate\Database\Eloquent\Model|null $moveLine
 *
 */
class AccountPaymentRegisterMoveLine extends Model
{
    use HasFactory;

    protected $table = 'accounts_account_payment_register_move_lines';

    public $timestamps = false;

    protected $fillable = [
        'payment_register_id',
        'move_line_id',
    ];

    /**
     * Payment Register
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function paymentRegister()
    {
        return $this->belongsTo(PaymentRegister::class, 'payment_register_id');
    }

    /**
     * Move Line
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function moveLine()
    {
        return $this->belongsTo(MoveLine::class, 'move_line_id');
    }
}
