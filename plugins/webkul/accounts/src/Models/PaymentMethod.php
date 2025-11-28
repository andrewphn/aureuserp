<?php

namespace Webkul\Account\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Webkul\Security\Models\User;

/**
 * Payment Method Eloquent model
 *
 * @property int $id
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property string|null $code
 * @property string|null $payment_type
 * @property string|null $name
 * @property string|null $created_by
 * @property-read \Illuminate\Database\Eloquent\Collection $accountMovePayment
 * @property-read \Illuminate\Database\Eloquent\Model|null $createdBy
 *
 */
class PaymentMethod extends Model
{
    use HasFactory;

    protected $table = 'accounts_payment_methods';

    protected $fillable = ['code', 'payment_type', 'name', 'created_by'];

    /**
     * Created By
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Account Move Payment
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function accountMovePayment()
    {
        return $this->hasMany(Move::class, 'payment_id');
    }
}
