<?php

namespace Webkul\Account\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\EloquentSortable\Sortable;
use Spatie\EloquentSortable\SortableTrait;
use Webkul\Account\Enums\DisplayType;
use Webkul\Account\Enums\MoveState;
use Webkul\Invoice\Models\Product;
use Webkul\Partner\Models\Partner;
use Webkul\Security\Models\User;
use Webkul\Support\Models\Company;
use Webkul\Support\Models\Currency;
use Webkul\Support\Models\UOM;

/**
 * Move Line Eloquent model
 *
 * @property int $id
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property string|null $sort
 * @property int $move_id
 * @property int $journal_id
 * @property int $company_id
 * @property int $company_currency_id
 * @property int $reconcile_id
 * @property int $payment_id
 * @property int $tax_repartition_line_id
 * @property int $account_id
 * @property int $currency_id
 * @property int $partner_id
 * @property int $group_tax_id
 * @property int $tax_line_id
 * @property int $tax_group_id
 * @property int $statement_id
 * @property int $statement_line_id
 * @property int $product_id
 * @property int $uom_id
 * @property string|null $created_by
 * @property string|null $move_name
 * @property mixed $parent_state
 * @property string|null $reference
 * @property string|null $name
 * @property string|null $matching_number
 * @property mixed $display_type
 * @property \Carbon\Carbon|null $date
 * @property \Carbon\Carbon|null $invoice_date
 * @property string|null $date_maturity
 * @property \Carbon\Carbon|null $discount_date
 * @property string|null $analytic_distribution
 * @property string|null $debit
 * @property string|null $credit
 * @property string|null $balance
 * @property float $amount_currency
 * @property float $tax_base_amount
 * @property float $amount_residual
 * @property float $amount_residual_currency
 * @property float $quantity
 * @property float $price_unit
 * @property float $price_subtotal
 * @property float $price_total
 * @property float $discount
 * @property float $discount_amount_currency
 * @property float $discount_balance
 * @property bool $is_imported
 * @property string|null $tax_tag_invert
 * @property string|null $reconciled
 * @property bool $is_downpayment
 * @property int $full_reconcile_id
 * @property-read \Illuminate\Database\Eloquent\Collection $moveLines
 * @property-read \Illuminate\Database\Eloquent\Model|null $move
 * @property-read \Illuminate\Database\Eloquent\Model|null $journal
 * @property-read \Illuminate\Database\Eloquent\Model|null $company
 * @property-read \Illuminate\Database\Eloquent\Model|null $account
 * @property-read \Illuminate\Database\Eloquent\Model|null $currency
 * @property-read \Illuminate\Database\Eloquent\Model|null $partner
 * @property-read \Illuminate\Database\Eloquent\Model|null $groupTax
 * @property-read \Illuminate\Database\Eloquent\Model|null $taxGroup
 * @property-read \Illuminate\Database\Eloquent\Model|null $statement
 * @property-read \Illuminate\Database\Eloquent\Model|null $statementLine
 * @property-read \Illuminate\Database\Eloquent\Model|null $product
 * @property-read \Illuminate\Database\Eloquent\Model|null $uom
 * @property-read \Illuminate\Database\Eloquent\Model|null $createdBy
 * @property-read \Illuminate\Database\Eloquent\Model|null $payment
 * @property-read \Illuminate\Database\Eloquent\Model|null $fullReconcile
 * @property-read \Illuminate\Database\Eloquent\Collection $taxes
 *
 */
class MoveLine extends Model implements Sortable
{
    use HasFactory, SortableTrait;

    protected $table = 'accounts_account_move_lines';

    protected $fillable = [
        'sort',
        'move_id',
        'journal_id',
        'company_id',
        'company_currency_id',
        'reconcile_id',
        'payment_id',
        'tax_repartition_line_id',
        'account_id',
        'currency_id',
        'partner_id',
        'group_tax_id',
        'tax_line_id',
        'tax_group_id',
        'statement_id',
        'statement_line_id',
        'product_id',
        'uom_id',
        'created_by',
        'move_name',
        'parent_state',
        'reference',
        'name',
        'matching_number',
        'display_type',
        'date',
        'invoice_date',
        'date_maturity',
        'discount_date',
        'analytic_distribution',
        'debit',
        'credit',
        'balance',
        'amount_currency',
        'tax_base_amount',
        'amount_residual',
        'amount_residual_currency',
        'quantity',
        'price_unit',
        'price_subtotal',
        'price_total',
        'discount',
        'discount_amount_currency',
        'discount_balance',
        'is_imported',
        'tax_tag_invert',
        'reconciled',
        'is_downpayment',
        'full_reconcile_id',
    ];

    protected $casts = [
        'parent_state' => MoveState::class,
        'display_type' => DisplayType::class,
    ];

    public $sortable = [
        'order_column_name'  => 'sort',
        'sort_when_creating' => true,
    ];

    /**
     * Move
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function move()
    {
        return $this->belongsTo(Move::class);
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
     * Account
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function account()
    {
        return $this->belongsTo(Account::class);
    }

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
     * Partner
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function partner()
    {
        return $this->belongsTo(Partner::class);
    }

    /**
     * Group Tax
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function groupTax()
    {
        return $this->belongsTo(Tax::class);
    }

    /**
     * Taxes
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function taxes()
    {
        return $this->belongsToMany(Tax::class, 'accounts_accounts_move_line_taxes', 'move_line_id', 'tax_id');
    }

    /**
     * Tax Group
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function taxGroup()
    {
        return $this->belongsTo(TaxGroup::class);
    }

    /**
     * Statement
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function statement()
    {
        return $this->belongsTo(BankStatement::class);
    }

    /**
     * Statement Line
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function statementLine()
    {
        return $this->belongsTo(BankStatementLine::class);
    }

    /**
     * Product
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Uom
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function uom()
    {
        return $this->belongsTo(UOM::class, 'uom_id');
    }

    /**
     * Created By
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function createdBy()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Move Lines
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function moveLines()
    {
        return $this->hasMany(MoveLine::class, 'reconcile_id');
    }

    /**
     * Payment
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function payment()
    {
        return $this->belongsTo(Payment::class);
    }

    /**
     * Full Reconcile
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function fullReconcile()
    {
        return $this->belongsTo(FullReconcile::class);
    }
}
