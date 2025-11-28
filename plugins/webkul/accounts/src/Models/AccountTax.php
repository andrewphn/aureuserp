<?php

namespace Webkul\Account\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Account Tax Eloquent model
 *
 * @property int $id
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property int $account_id
 * @property int $tax_id
 * @property-read \Illuminate\Database\Eloquent\Model|null $account
 * @property-read \Illuminate\Database\Eloquent\Model|null $tax
 *
 */
class AccountTax extends Model
{
    protected $table = 'accounts_account_taxes';

    protected $fillable = [
        'account_id',
        'tax_id',
    ];

    public $timestamps = false;

    /**
     * Account
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function account()
    {
        return $this->belongsTo(Account::class);
    }

    /**
     * Tax
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function tax()
    {
        return $this->belongsTo(Tax::class);
    }
}
