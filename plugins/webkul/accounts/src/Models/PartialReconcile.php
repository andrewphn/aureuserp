<?php

namespace Webkul\Account\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Webkul\Support\Models\Currency;

/**
 * Partial Reconcile Eloquent model
 *
 * @property int $id
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property int $debit_move_id
 * @property int $credit_move_id
 * @property int $full_reconcile_id
 * @property int $exchange_move_id
 * @property int $debit_currency_id
 * @property int $credit_currency_id
 * @property int $company_id
 * @property string|null $created_by
 * @property \Carbon\Carbon|null $max_date
 * @property float $amount
 * @property float $debit_amount_currency
 * @property float $credit_amount_currency
 * @property-read \Illuminate\Database\Eloquent\Model|null $debitMove
 * @property-read \Illuminate\Database\Eloquent\Model|null $creditMove
 * @property-read \Illuminate\Database\Eloquent\Model|null $fullReconcile
 * @property-read \Illuminate\Database\Eloquent\Model|null $exchangeMove
 * @property-read \Illuminate\Database\Eloquent\Model|null $debitCurrency
 *
 */
class PartialReconcile extends Model
{
    use HasFactory;

    protected $table = 'accounts_partial_reconciles';

    protected $fillable = [
        'debit_move_id',
        'credit_move_id',
        'full_reconcile_id',
        'exchange_move_id',
        'debit_currency_id',
        'credit_currency_id',
        'company_id',
        'created_by',
        'max_date',
        'amount',
        'debit_amount_currency',
        'credit_amount_currency',
    ];

    /**
     * Debit Move
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function debitMove()
    {
        return $this->belongsTo(Move::class, 'debit_move_id');
    }

    public function creditMove()
    {
        return $this->belongsTo(Move::class, 'credit_move_id');
    }

    /**
     * Full Reconcile
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function fullReconcile()
    {
        return $this->belongsTo(FullReconcile::class, 'full_reconcile_id');
    }

    /**
     * Exchange Move
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function exchangeMove()
    {
        return $this->belongsTo(Move::class, 'exchange_move_id');
    }

    /**
     * Debit Currency
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function debitCurrency()
    {
        return $this->belongsTo(Currency::class, 'debit_currency_id');
    }
}
