<?php

namespace Webkul\Account\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\EloquentSortable\Sortable;
use Spatie\EloquentSortable\SortableTrait;
use Webkul\Security\Models\User;
use Webkul\Support\Models\Company;

/**
 * Reconcile Eloquent model
 *
 * @property int $id
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property string|null $sort
 * @property int $company_id
 * @property string|null $past_months_limit
 * @property string|null $created_by
 * @property string|null $rule_type
 * @property string|null $matching_order
 * @property float $counter_part_type
 * @property string|null $match_nature
 * @property float $match_amount
 * @property string|null $match_label
 * @property string|null $match_level_parameters
 * @property string|null $match_note
 * @property string|null $match_note_parameters
 * @property string|null $match_transaction_type
 * @property string|null $match_transaction_type_parameters
 * @property string|null $payment_tolerance_type
 * @property string|null $decimal_separator
 * @property string|null $name
 * @property string|null $auto_reconcile
 * @property string|null $to_check
 * @property string|null $match_text_location_label
 * @property string|null $match_text_location_note
 * @property string|null $match_text_location_reference
 * @property string|null $match_same_currency
 * @property bool $allow_payment_tolerance
 * @property string|null $match_partner
 * @property float $match_amount_min
 * @property float $match_amount_max
 * @property string|null $payment_tolerance_parameters
 * @property-read \Illuminate\Database\Eloquent\Model|null $company
 * @property-read \Illuminate\Database\Eloquent\Model|null $createdBy
 *
 */
class Reconcile extends Model implements Sortable
{
    use HasFactory, SortableTrait;

    protected $table = 'accounts_reconciles';

    protected $fillable = [
        'sort',
        'company_id',
        'past_months_limit',
        'created_by',
        'rule_type',
        'matching_order',
        'counter_part_type',
        'match_nature',
        'match_amount',
        'match_label',
        'match_level_parameters',
        'match_note',
        'match_note_parameters',
        'match_transaction_type',
        'match_transaction_type_parameters',
        'payment_tolerance_type',
        'decimal_separator',
        'name',
        'auto_reconcile',
        'to_check',
        'match_text_location_label',
        'match_text_location_note',
        'match_text_location_reference',
        'match_same_currency',
        'allow_payment_tolerance',
        'match_partner',
        'match_amount_min',
        'match_amount_max',
        'payment_tolerance_parameters',
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
        return $this->belongsTo(User::class, 'created_by');
    }
}
