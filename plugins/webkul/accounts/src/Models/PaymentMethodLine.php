<?php

namespace Webkul\Account\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\EloquentSortable\Sortable;
use Spatie\EloquentSortable\SortableTrait;
use Webkul\Security\Models\User;

/**
 * Payment Method Line Eloquent model
 *
 * @property int $id
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property string|null $sort
 * @property int $payment_method_id
 * @property int $payment_account_id
 * @property int $journal_id
 * @property string|null $name
 * @property int $creator_id
 * @property-read \Illuminate\Database\Eloquent\Model|null $createdBy
 * @property-read \Illuminate\Database\Eloquent\Model|null $paymentMethod
 * @property-read \Illuminate\Database\Eloquent\Model|null $paymentAccount
 * @property-read \Illuminate\Database\Eloquent\Model|null $journal
 *
 */
class PaymentMethodLine extends Model implements Sortable
{
    use HasFactory, SortableTrait;

    protected $table = 'accounts_payment_method_lines';

    protected $fillable = [
        'sort',
        'payment_method_id',
        'payment_account_id',
        'journal_id',
        'name',
        'creator_id',
    ];

    public $sortable = [
        'order_column_name'  => 'sort',
        'sort_when_creating' => true,
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
     * Payment Method
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function paymentMethod()
    {
        return $this->belongsTo(PaymentMethod::class);
    }

    /**
     * Payment Account
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function paymentAccount()
    {
        return $this->belongsTo(Account::class);
    }

    /**
     * Journal
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function journal()
    {
        return $this->belongsTo(Journal::class);
    }
}
