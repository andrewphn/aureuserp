<?php

namespace Webkul\Account\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Webkul\Security\Models\User;

/**
 * Cash Rounding Eloquent model
 *
 * @property int $id
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property int $creator_id
 * @property string|null $strategy
 * @property string|null $rounding_method
 * @property string|null $name
 * @property string|null $rounding
 * @property int $profit_account_id
 * @property int $loss_account_id
 * @property-read \Illuminate\Database\Eloquent\Model|null $createdBy
 * @property-read \Illuminate\Database\Eloquent\Model|null $profitAccount
 * @property-read \Illuminate\Database\Eloquent\Model|null $lossAccount
 *
 */
class CashRounding extends Model
{
    use HasFactory;

    protected $table = 'accounts_cash_roundings';

    protected $fillable = [
        'creator_id',
        'strategy',
        'rounding_method',
        'name',
        'rounding',
        'profit_account_id',
        'loss_account_id',
    ];

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
     * Profit Account
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function profitAccount()
    {
        return $this->belongsTo(Account::class, 'profit_account_id');
    }

    /**
     * Loss Account
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function lossAccount()
    {
        return $this->belongsTo(Account::class, 'loss_account_id');
    }
}
