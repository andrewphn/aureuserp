<?php

namespace Webkul\Account\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Webkul\Chatter\Traits\HasChatter;
use Webkul\Chatter\Traits\HasLogActivity;
use Webkul\Partner\Models\BankAccount;
use Webkul\Partner\Models\Partner;
use Webkul\Payment\Models\PaymentToken;
use Webkul\Payment\Models\PaymentTransaction;
use Webkul\Security\Models\User;
use Webkul\Support\Models\Company;
use Webkul\Support\Models\Currency;

/**
 * Payment Eloquent model
 *
 * @property int $id
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property int $move_id
 * @property int $journal_id
 * @property int $company_id
 * @property int $partner_bank_id
 * @property int $paired_internal_transfer_payment_id
 * @property int $payment_method_line_id
 * @property int $payment_method_id
 * @property int $currency_id
 * @property int $partner_id
 * @property int $outstanding_account_id
 * @property int $destination_account_id
 * @property string|null $created_by
 * @property string|null $name
 * @property string|null $state
 * @property string|null $payment_type
 * @property string|null $partner_type
 * @property string|null $memo
 * @property string|null $payment_reference
 * @property \Carbon\Carbon|null $date
 * @property float $amount
 * @property float $amount_company_currency_signed
 * @property bool $is_reconciled
 * @property bool $is_matched
 * @property bool $is_sent
 * @property int $payment_transaction_id
 * @property int $source_payment_id
 * @property int $payment_token_id
 * @property-read \Illuminate\Database\Eloquent\Model|null $move
 * @property-read \Illuminate\Database\Eloquent\Model|null $journal
 * @property-read \Illuminate\Database\Eloquent\Model|null $company
 * @property-read \Illuminate\Database\Eloquent\Model|null $partnerBank
 * @property-read \Illuminate\Database\Eloquent\Model|null $pairedInternalTransferPayment
 * @property-read \Illuminate\Database\Eloquent\Model|null $paymentMethodLine
 * @property-read \Illuminate\Database\Eloquent\Model|null $paymentMethod
 * @property-read \Illuminate\Database\Eloquent\Model|null $currency
 * @property-read \Illuminate\Database\Eloquent\Model|null $partner
 * @property-read \Illuminate\Database\Eloquent\Model|null $outstandingAccount
 * @property-read \Illuminate\Database\Eloquent\Model|null $destinationAccount
 * @property-read \Illuminate\Database\Eloquent\Model|null $createdBy
 * @property-read \Illuminate\Database\Eloquent\Model|null $paymentTransaction
 * @property-read \Illuminate\Database\Eloquent\Model|null $sourcePayment
 * @property-read \Illuminate\Database\Eloquent\Model|null $paymentToken
 * @property-read \Illuminate\Database\Eloquent\Collection $accountMovePayment
 *
 */
class Payment extends Model
{
    use HasChatter, HasFactory, HasLogActivity;

    protected $table = 'accounts_account_payments';

    protected $fillable = [
        'move_id',
        'journal_id',
        'company_id',
        'partner_bank_id',
        'paired_internal_transfer_payment_id',
        'payment_method_line_id',
        'payment_method_id',
        'currency_id',
        'partner_id',
        'outstanding_account_id',
        'destination_account_id',
        'created_by',
        'name',
        'state',
        'payment_type',
        'partner_type',
        'memo',
        'payment_reference',
        'date',
        'amount',
        'amount_company_currency_signed',
        'is_reconciled',
        'is_matched',
        'is_sent',
        'payment_transaction_id',
        'source_payment_id',
        'payment_token_id',
    ];

    protected array $logAttributes = [
        'name',
        'move.name'          => 'Move',
        'company.name'       => 'Company',
        'partner.name'       => 'Partner',
        'partner_type'       => 'Partner Type',
        'paymentMethod.name' => 'Payment Method',
        'currency.name'      => 'Currency',
        'paymentToken',
        'sourcePayment.name'      => 'Source Payment',
        'paymentTransaction.name' => 'Payment Transaction',
        'destinationAccount.name' => 'Destination Account',
        'outstandingAccount.name' => 'Outstanding Account',
        'is_sent'                 => 'Is Sent',
        'state'                   => 'State',
    ];

    /**
     * Move
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function move()
    {
        return $this->belongsTo(Move::class, 'move_id');
    }

    /**
     * Journal
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function journal()
    {
        return $this->belongsTo(Journal::class, 'journal_id');
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
     * Partner Bank
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function partnerBank()
    {
        return $this->belongsTo(BankAccount::class, 'partner_bank_id')->withTrashed();
    }

    /**
     * Paired Internal Transfer Payment
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function pairedInternalTransferPayment()
    {
        return $this->belongsTo(self::class, 'paired_internal_transfer_payment_id');
    }

    /**
     * Payment Method Line
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function paymentMethodLine()
    {
        return $this->belongsTo(PaymentMethodLine::class, 'payment_method_line_id');
    }

    /**
     * Payment Method
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function paymentMethod()
    {
        return $this->belongsTo(PaymentMethod::class, 'payment_method_id');
    }

    /**
     * Currency
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function currency()
    {
        return $this->belongsTo(Currency::class, 'currency_id');
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

    /**
     * Outstanding Account
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function outstandingAccount()
    {
        return $this->belongsTo(Account::class, 'outstanding_account_id');
    }

    /**
     * Destination Account
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function destinationAccount()
    {
        return $this->belongsTo(Account::class, 'destination_account_id');
    }

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
     * Payment Transaction
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function paymentTransaction()
    {
        return $this->belongsTo(PaymentTransaction::class, 'payment_transaction_id');
    }

    /**
     * Source Payment
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function sourcePayment()
    {
        return $this->belongsTo(self::class, 'source_payment_id');
    }

    /**
     * Payment Token
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function paymentToken()
    {
        return $this->belongsTo(PaymentToken::class, 'payment_token_id');
    }

    /**
     * Account Move Payment
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function accountMovePayment()
    {
        return $this->belongsToMany(Move::class, 'accounts_accounts_move_payment', 'payment_id', 'invoice_id');
    }
}
