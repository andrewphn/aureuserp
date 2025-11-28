<?php

namespace Webkul\Account\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Webkul\Partner\Models\BankAccount;
use Webkul\Partner\Models\Partner;
use Webkul\Security\Models\User;
use Webkul\Support\Models\Company;
use Webkul\Support\Models\Currency;

/**
 * Payment Register Eloquent model
 *
 * @property int $id
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property int $currency_id
 * @property int $journal_id
 * @property int $partner_bank_id
 * @property int $custom_user_currency_id
 * @property int $source_currency_id
 * @property int $company_id
 * @property int $partner_id
 * @property int $payment_method_line_id
 * @property int $writeoff_account_id
 * @property int $creator_id
 * @property string|null $communication
 * @property string|null $installments_mode
 * @property string|null $payment_type
 * @property string|null $partner_type
 * @property string|null $payment_difference_handling
 * @property string|null $writeoff_label
 * @property \Carbon\Carbon|null $payment_date
 * @property float $amount
 * @property float $custom_user_amount
 * @property float $source_amount
 * @property float $source_amount_currency
 * @property string|null $group_payment
 * @property bool $can_group_payments
 * @property int $payment_token_id
 * @property-read \Illuminate\Database\Eloquent\Model|null $journal
 * @property-read \Illuminate\Database\Eloquent\Model|null $partnerBank
 * @property-read \Illuminate\Database\Eloquent\Model|null $customUserCurrency
 * @property-read \Illuminate\Database\Eloquent\Model|null $sourceCurrency
 * @property-read \Illuminate\Database\Eloquent\Model|null $company
 * @property-read \Illuminate\Database\Eloquent\Model|null $partner
 * @property-read \Illuminate\Database\Eloquent\Model|null $paymentMethodLine
 * @property-read \Illuminate\Database\Eloquent\Model|null $writeoffAccount
 * @property-read \Illuminate\Database\Eloquent\Model|null $creator
 * @property-read \Illuminate\Database\Eloquent\Collection $registerMoveLines
 *
 */
class PaymentRegister extends Model
{
    use HasFactory;

    protected $table = 'accounts_payment_registers';

    protected $fillable = [
        'currency_id',
        'journal_id',
        'partner_bank_id',
        'custom_user_currency_id',
        'source_currency_id',
        'company_id',
        'partner_id',
        'payment_method_line_id',
        'writeoff_account_id',
        'creator_id',
        'communication',
        'installments_mode',
        'payment_type',
        'partner_type',
        'payment_difference_handling',
        'writeoff_label',
        'payment_date',
        'amount',
        'custom_user_amount',
        'source_amount',
        'source_amount_currency',
        'group_payment',
        'can_group_payments',
        'payment_token_id',
    ];

    /**
     * Journal
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function journal()
    {
        return $this->belongsTo(Journal::class, 'journal_id');
    }

    public function partnerBank()
    {
        return $this->belongsTo(BankAccount::class, 'partner_bank_id');
    }

    /**
     * Custom User Currency
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function customUserCurrency()
    {
        return $this->belongsTo(Currency::class, 'custom_user_currency_id');
    }

    /**
     * Source Currency
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function sourceCurrency()
    {
        return $this->belongsTo(Currency::class, 'source_currency_id');
    }

    /**
     * Company
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function company()
    {
        return $this->belongsTo(Company::class, 'company_id');
    }

    /**
     * Partner
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function partner()
    {
        return $this->belongsTo(Partner::class, 'partner_id');
    }

    public function paymentMethodLine()
    {
        return $this->belongsTo(PaymentMethodLine::class, 'payment_method_line_id');
    }

    /**
     * Writeoff Account
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function writeoffAccount()
    {
        return $this->belongsTo(Account::class, 'writeoff_account_id');
    }

    /**
     * Creator
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function creator()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Register Move Lines
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function registerMoveLines()
    {
        return $this->belongsToMany(MoveLine::class, 'accounts_account_payment_register_move_lines', 'payment_register_id', 'move_line_id');
    }
}
