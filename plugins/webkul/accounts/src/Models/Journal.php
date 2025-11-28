<?php

namespace Webkul\Account\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Spatie\EloquentSortable\Sortable;
use Spatie\EloquentSortable\SortableTrait;
use Webkul\Partner\Models\BankAccount;
use Webkul\Security\Models\User;
use Webkul\Support\Models\Company;
use Webkul\Support\Models\Currency;

/**
 * Journal Eloquent model
 *
 * @property int $id
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property int $default_account_id
 * @property int $suspense_account_id
 * @property string|null $sort
 * @property int $currency_id
 * @property int $company_id
 * @property int $profit_account_id
 * @property int $loss_account_id
 * @property int $bank_account_id
 * @property int $creator_id
 * @property string|null $color
 * @property string|null $access_token
 * @property string|null $code
 * @property string|null $type
 * @property string|null $invoice_reference_type
 * @property string|null $invoice_reference_model
 * @property string|null $bank_statements_source
 * @property string|null $name
 * @property string|null $order_override_regex
 * @property string|null $auto_check_on_post
 * @property string|null $restrict_mode_hash_table
 * @property string|null $refund_order
 * @property string|null $payment_order
 * @property string|null $show_on_dashboard
 * @property-read \Illuminate\Database\Eloquent\Collection $inboundPaymentMethodLines
 * @property-read \Illuminate\Database\Eloquent\Collection $outboundPaymentMethodLines
 * @property-read \Illuminate\Database\Eloquent\Model|null $bankAccount
 * @property-read \Illuminate\Database\Eloquent\Model|null $company
 * @property-read \Illuminate\Database\Eloquent\Model|null $creator
 * @property-read \Illuminate\Database\Eloquent\Model|null $currency
 * @property-read \Illuminate\Database\Eloquent\Model|null $defaultAccount
 * @property-read \Illuminate\Database\Eloquent\Model|null $lossAccount
 * @property-read \Illuminate\Database\Eloquent\Model|null $profitAccount
 * @property-read \Illuminate\Database\Eloquent\Model|null $suspenseAccount
 * @property-read \Illuminate\Database\Eloquent\Collection $allowedAccounts
 *
 */
class Journal extends Model implements Sortable
{
    use HasFactory, SortableTrait;

    protected $table = 'accounts_journals';

    protected $fillable = [
        'default_account_id',
        'suspense_account_id',
        'sort',
        'currency_id',
        'company_id',
        'profit_account_id',
        'loss_account_id',
        'bank_account_id',
        'creator_id',
        'color',
        'access_token',
        'code',
        'type',
        'invoice_reference_type',
        'invoice_reference_model',
        'bank_statements_source',
        'name',
        'order_override_regex',
        'auto_check_on_post',
        'restrict_mode_hash_table',
        'refund_order',
        'payment_order',
        'show_on_dashboard',
    ];

    public $sortable = [
        'order_column_name'  => 'sort',
        'sort_when_creating' => true,
    ];

    /**
     * Bank Account
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function bankAccount()
    {
        return $this->belongsTo(BankAccount::class);
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
     * Creator
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'creator_id');
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
     * Default Account
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function defaultAccount()
    {
        return $this->belongsTo(Account::class, 'default_account_id');
    }

    public function lossAccount()
    {
        return $this->belongsTo(Account::class, 'loss_account_id');
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
     * Suspense Account
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function suspenseAccount()
    {
        return $this->belongsTo(Account::class, 'suspense_account_id');
    }

    /**
     * Allowed Accounts
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function allowedAccounts()
    {
        return $this->belongsToMany(Account::class, 'accounts_journal_accounts', 'journal_id', 'account_id');
    }

    /**
     * Get Available Payment Method Lines
     *
     * @param string $paymentType
     * @return mixed
     */
    public function getAvailablePaymentMethodLines(string $paymentType): mixed
    {
        if (! $this->exists) {
            return PaymentMethodLine::query()->whereNull('id')->get();
        }

        return match ($paymentType) {
            'inbound'  => $this->inboundPaymentMethodLines,
            'outbound' => $this->outboundPaymentMethodLines,
            default    => throw new InvalidArgumentException('Invalid payment type'),
        };
    }

    /**
     * Inbound Payment Method Lines
     *
     * @return HasMany
     */
    public function inboundPaymentMethodLines(): HasMany
    {
        return $this->hasMany(PaymentMethodLine::class)->where('type', 'inbound');
    }

    /**
     * Outbound Payment Method Lines
     *
     * @return HasMany
     */
    public function outboundPaymentMethodLines(): HasMany
    {
        return $this->hasMany(PaymentMethodLine::class)->where('type', 'outbound');
    }

    /**
     * Compute Inbound Payment Method Lines
     *
     * @return void
     */
    public function computeInboundPaymentMethodLines(): void
    {
        if (! in_array($this->type, ['bank', 'cash', 'credit'])) {
            $this->inboundPaymentMethodLines()->delete();

            return;
        }

        DB::transaction(function () {
            $this->inboundPaymentMethodLines()->delete();

            $defaultMethods = $this->getDefaultInboundPaymentMethods();

            foreach ($defaultMethods as $method) {
                $this->inboundPaymentMethodLines()->create([
                    'name'              => $method->name,
                    'payment_method_id' => $method->id,
                    'type'              => 'inbound',
                ]);
            }
        });
    }

    protected function getDefaultInboundPaymentMethods(): mixed
    {
        return PaymentMethod::where('type', 'inbound')
            ->where('active', true)
            ->get();
    }
}
