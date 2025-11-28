<?php

namespace Webkul\Account\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\EloquentSortable\Sortable;
use Spatie\EloquentSortable\SortableTrait;
use Webkul\Account\Enums\DelayType;
use Webkul\Account\Enums\DueTermValue;
use Webkul\Security\Models\User;
use Webkul\Support\Models\Company;

/**
 * Payment Term Eloquent model
 *
 * @property int $id
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property \Carbon\Carbon|null $deleted_at
 * @property int $company_id
 * @property string|null $sort
 * @property float $discount_days
 * @property int $creator_id
 * @property float $early_pay_discount
 * @property string|null $name
 * @property string|null $note
 * @property string|null $display_on_invoice
 * @property float $early_discount
 * @property float $discount_percentage
 * @property-read \Illuminate\Database\Eloquent\Model|null $dueTerm
 * @property-read \Illuminate\Database\Eloquent\Model|null $company
 * @property-read \Illuminate\Database\Eloquent\Model|null $createdBy
 *
 */
class PaymentTerm extends Model implements Sortable
{
    use HasFactory, SoftDeletes, SortableTrait;

    protected $table = 'accounts_payment_terms';

    protected $fillable = [
        'company_id',
        'sort',
        'discount_days',
        'creator_id',
        'early_pay_discount',
        'name',
        'note',
        'display_on_invoice',
        'early_discount',
        'discount_percentage',
    ];

    public $sortable = [
        'order_column_name'  => 'sort',
        'sort_when_creating' => true,
    ];

    /**
     * Company
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function company()
    {
        return $this->belongsTo(Company::class, 'company_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'creator_id');
    }

    /**
     * Due Term
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function dueTerm()
    {
        return $this->hasOne(PaymentDueTerm::class, 'payment_id');
    }

    /**
     * Boot
     *
     * @return void
     */
    protected static function boot()
    {
        parent::boot();

        static::created(function ($paymentTerm) {
            $paymentTerm->dueTerm()->create([
                'value'           => DueTermValue::PERCENT->value,
                'value_amount'    => 100,
                'delay_type'      => DelayType::DAYS_AFTER->value,
                'days_next_month' => 10,
                'nb_days'         => 0,
                'payment_id'      => $paymentTerm->id,
            ]);
        });
    }
}
