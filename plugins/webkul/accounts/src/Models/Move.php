<?php

namespace Webkul\Account\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\EloquentSortable\Sortable;
use Spatie\EloquentSortable\SortableTrait;
use Webkul\Account\Enums\JournalType;
use Webkul\Account\Enums\MoveState;
use Webkul\Account\Enums\MoveType;
use Webkul\Account\Enums\PaymentState;
use Webkul\Chatter\Traits\HasChatter;
use Webkul\Chatter\Traits\HasLogActivity;
use Webkul\Field\Traits\HasCustomFields;
use Webkul\Partner\Models\BankAccount;
use Webkul\Partner\Models\Partner;
use Webkul\Security\Models\User;
use Webkul\Support\Models\Company;
use Webkul\Support\Models\Currency;
use Webkul\Support\Models\UtmCampaign;
use Webkul\Support\Models\UTMMedium;
use Webkul\Support\Models\UTMSource;

/**
 * Move Eloquent model
 *
 * @property int $id
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property string|null $sort
 * @property int $journal_id
 * @property int $company_id
 * @property int $campaign_id
 * @property int $tax_cash_basis_origin_move_id
 * @property int $auto_post_origin_id
 * @property string|null $secure_sequence_number
 * @property int $invoice_payment_term_id
 * @property int $partner_id
 * @property int $commercial_partner_id
 * @property int $partner_shipping_id
 * @property int $partner_bank_id
 * @property int $fiscal_position_id
 * @property int $currency_id
 * @property int $reversed_entry_id
 * @property int $invoice_user_id
 * @property int $invoice_incoterm_id
 * @property int $invoice_cash_rounding_id
 * @property int $preferred_payment_method_line_id
 * @property int $creator_id
 * @property string|null $sequence_prefix
 * @property string|null $access_token
 * @property string|null $name
 * @property string|null $reference
 * @property mixed $state
 * @property mixed $move_type
 * @property string|null $auto_post
 * @property string|null $inalterable_hash
 * @property string|null $payment_reference
 * @property string|null $qr_code_method
 * @property mixed $payment_state
 * @property string|null $invoice_source_email
 * @property string|null $invoice_partner_display_name
 * @property string|null $invoice_origin
 * @property string|null $incoterm_location
 * @property \Carbon\Carbon|null $date
 * @property string|null $auto_post_until
 * @property \Carbon\Carbon|null $invoice_date
 * @property \Carbon\Carbon|null $invoice_date_due
 * @property \Carbon\Carbon|null $delivery_date
 * @property array|null $sending_data
 * @property string|null $narration
 * @property string|null $invoice_currency_rate
 * @property float $amount_untaxed
 * @property float $amount_tax
 * @property float $amount_total
 * @property float $amount_residual
 * @property float $amount_untaxed_signed
 * @property float $amount_untaxed_in_currency_signed
 * @property float $amount_tax_signed
 * @property float $amount_total_signed
 * @property float $amount_total_in_currency_signed
 * @property float $amount_residual_signed
 * @property float $quick_edit_total_amount
 * @property bool $is_storno
 * @property string|null $always_tax_exigible
 * @property string|null $checked
 * @property string|null $posted_before
 * @property string|null $made_sequence_gap
 * @property bool $is_manually_modified
 * @property bool $is_move_sent
 * @property int $source_id
 * @property int $medium_id
 * @property-read \Illuminate\Database\Eloquent\Model|null $paymentTermLine
 * @property-read \Illuminate\Database\Eloquent\Collection $lines
 * @property-read \Illuminate\Database\Eloquent\Collection $allLines
 * @property-read \Illuminate\Database\Eloquent\Collection $taxLines
 * @property-read \Illuminate\Database\Eloquent\Model|null $campaign
 * @property-read \Illuminate\Database\Eloquent\Model|null $journal
 * @property-read \Illuminate\Database\Eloquent\Model|null $company
 * @property-read \Illuminate\Database\Eloquent\Model|null $taxCashBasisOriginMove
 * @property-read \Illuminate\Database\Eloquent\Model|null $autoPostOrigin
 * @property-read \Illuminate\Database\Eloquent\Model|null $invoicePaymentTerm
 * @property-read \Illuminate\Database\Eloquent\Model|null $partner
 * @property-read \Illuminate\Database\Eloquent\Model|null $commercialPartner
 * @property-read \Illuminate\Database\Eloquent\Model|null $partnerShipping
 * @property-read \Illuminate\Database\Eloquent\Model|null $partnerBank
 * @property-read \Illuminate\Database\Eloquent\Model|null $fiscalPosition
 * @property-read \Illuminate\Database\Eloquent\Model|null $currency
 * @property-read \Illuminate\Database\Eloquent\Model|null $reversedEntry
 * @property-read \Illuminate\Database\Eloquent\Model|null $invoiceUser
 * @property-read \Illuminate\Database\Eloquent\Model|null $invoiceIncoterm
 * @property-read \Illuminate\Database\Eloquent\Model|null $invoiceCashRounding
 * @property-read \Illuminate\Database\Eloquent\Model|null $createdBy
 * @property-read \Illuminate\Database\Eloquent\Model|null $source
 * @property-read \Illuminate\Database\Eloquent\Model|null $medium
 * @property-read \Illuminate\Database\Eloquent\Model|null $paymentMethodLine
 *
 */
class Move extends Model implements Sortable
{
    use HasChatter, HasCustomFields, HasFactory, HasLogActivity, SortableTrait;

    protected $table = 'accounts_account_moves';

    protected $fillable = [
        'sort',
        'journal_id',
        'company_id',
        'campaign_id',
        'tax_cash_basis_origin_move_id',
        'auto_post_origin_id',
        'secure_sequence_number',
        'invoice_payment_term_id',
        'partner_id',
        'commercial_partner_id',
        'partner_shipping_id',
        'partner_bank_id',
        'fiscal_position_id',
        'currency_id',
        'reversed_entry_id',
        'invoice_user_id',
        'invoice_incoterm_id',
        'invoice_cash_rounding_id',
        'preferred_payment_method_line_id',
        'creator_id',
        'sequence_prefix',
        'access_token',
        'name',
        'reference',
        'state',
        'move_type',
        'auto_post',
        'inalterable_hash',
        'payment_reference',
        'qr_code_method',
        'payment_state',
        'invoice_source_email',
        'invoice_partner_display_name',
        'invoice_origin',
        'incoterm_location',
        'date',
        'auto_post_until',
        'invoice_date',
        'invoice_date_due',
        'delivery_date',
        'sending_data',
        'narration',
        'invoice_currency_rate',
        'amount_untaxed',
        'amount_tax',
        'amount_total',
        'amount_residual',
        'amount_untaxed_signed',
        'amount_untaxed_in_currency_signed',
        'amount_tax_signed',
        'amount_total_signed',
        'amount_total_in_currency_signed',
        'amount_residual_signed',
        'quick_edit_total_amount',
        'is_storno',
        'always_tax_exigible',
        'checked',
        'posted_before',
        'made_sequence_gap',
        'is_manually_modified',
        'is_move_sent',
        'source_id',
        'medium_id',
    ];

    protected array $logAttributes = [
        'medium.name'                       => 'Medium',
        'source.name'                       => 'UTM Source',
        'partner.name'                      => 'Customer',
        'commercialPartner.name'            => 'Commercial Partner',
        'partnerShipping.name'              => 'Shipping Address',
        'partnerBank.name'                  => 'Bank Account',
        'fiscalPosition.name'               => 'Fiscal Position',
        'currency.name'                     => 'Currency',
        'reversedEntry.name'                => 'Reversed Entry',
        'invoiceUser.name'                  => 'Invoice User',
        'invoiceIncoterm.name'              => 'Invoice Incoterm',
        'invoiceCashRounding.name'          => 'Invoice Cash Rounding',
        'createdBy.name'                    => 'Created By',
        'name'                              => 'Invoice Reference',
        'state'                             => 'Invoice Status',
        'reference'                         => 'Reference',
        'invoiceSourceEmail'                => 'Source Email',
        'invoicePartnerDisplayName'         => 'Partner Display Name',
        'invoiceOrigin'                     => 'Invoice Origin',
        'incotermLocation'                  => 'Incoterm Location',
        'date'                              => 'Invoice Date',
        'invoice_date'                      => 'Invoice Date',
        'invoice_date_due'                  => 'Due Date',
        'delivery_date'                     => 'Delivery Date',
        'narration'                         => 'Notes',
        'amount_untaxed'                    => 'Subtotal',
        'amount_tax'                        => 'Tax',
        'amount_total'                      => 'Total',
        'amount_residual'                   => 'Residual',
        'amount_untaxed_signed'             => 'Subtotal (Signed)',
        'amount_untaxed_in_currency_signed' => 'Subtotal (In Currency) (Signed)',
        'amount_tax_signed'                 => 'Tax (Signed)',
        'amount_total_signed'               => 'Total (Signed)',
        'amount_total_in_currency_signed'   => 'Total (In Currency) (Signed)',
        'amount_residual_signed'            => 'Residual (Signed)',
        'quick_edit_total_amount'           => 'Quick Edit Total Amount',
        'is_storno'                         => 'Is Storno',
        'always_tax_exigible'               => 'Always Tax Exigible',
        'checked'                           => 'Checked',
        'posted_before'                     => 'Posted Before',
        'made_sequence_gap'                 => 'Made Sequence Gap',
        'is_manually_modified'              => 'Is Manually Modified',
        'is_move_sent'                      => 'Is Move Sent',
    ];

    protected $casts = [
        'invoice_date_due' => 'datetime',
        'state'            => MoveState::class,
        'payment_state'    => PaymentState::class,
        'move_type'        => MoveType::class,
    ];

    public $sortable = [
        'order_column_name'  => 'sort',
        'sort_when_creating' => true,
    ];

    /**
     * Campaign
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function campaign()
    {
        return $this->belongsTo(UtmCampaign::class, 'campaign_id');
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

    public function taxCashBasisOriginMove()
    {
        return $this->belongsTo(Move::class, 'tax_cash_basis_origin_move_id');
    }

    /**
     * Auto Post Origin
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function autoPostOrigin()
    {
        return $this->belongsTo(Move::class, 'auto_post_origin_id');
    }

    /**
     * Invoice Payment Term
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function invoicePaymentTerm()
    {
        return $this->belongsTo(PaymentTerm::class, 'invoice_payment_term_id')->withTrashed();
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
     * Commercial Partner
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function commercialPartner()
    {
        return $this->belongsTo(Partner::class, 'commercial_partner_id');
    }

    /**
     * Partner Shipping
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function partnerShipping()
    {
        return $this->belongsTo(Partner::class, 'partner_shipping_id');
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
     * Fiscal Position
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function fiscalPosition()
    {
        return $this->belongsTo(FiscalPosition::class, 'fiscal_position_id');
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
     * Reversed Entry
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function reversedEntry()
    {
        return $this->belongsTo(self::class, 'reversed_entry_id');
    }

    /**
     * Invoice User
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function invoiceUser()
    {
        return $this->belongsTo(User::class, 'invoice_user_id');
    }

    /**
     * Invoice Incoterm
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function invoiceIncoterm()
    {
        return $this->belongsTo(Incoterm::class, 'invoice_incoterm_id');
    }

    /**
     * Invoice Cash Rounding
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function invoiceCashRounding()
    {
        return $this->belongsTo(CashRounding::class, 'invoice_cash_rounding_id');
    }

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
     * Source
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function source()
    {
        return $this->belongsTo(UTMSource::class, 'source_id');
    }

    /**
     * Medium
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function medium()
    {
        return $this->belongsTo(UTMMedium::class, 'medium_id');
    }

    /**
     * Payment Method Line
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function paymentMethodLine()
    {
        return $this->belongsTo(PaymentMethodLine::class, 'preferred_payment_method_line_id');
    }

    public function getTotalDiscountAttribute()
    {
        return $this->lines()
            ->where('display_type', 'product')
            ->sum('discount');
    }

    /**
     * Is Inbound
     *
     * @param mixed $includeReceipts
     * @return bool
     */
    public function isInbound($includeReceipts = true)
    {
        return in_array($this->move_type, $this->getInboundTypes($includeReceipts));
    }

    /**
     * Get Inbound Types
     *
     * @param mixed $includeReceipts
     * @return array
     */
    public function getInboundTypes($includeReceipts = true): array
    {
        $types = [MoveType::OUT_INVOICE, MoveType::IN_REFUND];

        if ($includeReceipts) {
            $types[] = MoveType::OUT_RECEIPT;
        }

        return $types;
    }

    /**
     * Is Outbound
     *
     * @param mixed $includeReceipts
     * @return bool
     */
    public function isOutbound($includeReceipts = true)
    {
        return in_array($this->move_type, $this->getOutboundTypes($includeReceipts));
    }

    /**
     * Get Outbound Types
     *
     * @param mixed $includeReceipts
     * @return array
     */
    public function getOutboundTypes($includeReceipts = true): array
    {
        $types = [MoveType::IN_INVOICE, MoveType::OUT_REFUND];

        if ($includeReceipts) {
            $types[] = MoveType::IN_RECEIPT;
        }

        return $types;
    }

    /**
     * Lines
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function lines()
    {
        return $this->hasMany(MoveLine::class, 'move_id')
            ->where('display_type', 'product');
    }

    /**
     * All Lines
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function allLines()
    {
        return $this->hasMany(MoveLine::class, 'move_id');
    }

    /**
     * Tax Lines
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function taxLines()
    {
        return $this->hasMany(MoveLine::class, 'move_id')
            ->where('display_type', 'tax');
    }

    /**
     * Payment Term Line
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function paymentTermLine()
    {
        return $this->hasOne(MoveLine::class, 'move_id')
            ->where('display_type', 'payment_term');
    }

    /**
     * Is Invoice
     *
     * @param mixed $includeReceipts
     * @return bool
     */
    public function isInvoice($includeReceipts = false)
    {
        return $this->isSaleDocument($includeReceipts) || $this->isPurchaseDocument($includeReceipts);
    }

    /**
     * Is Entry
     *
     * @return bool
     */
    public function isEntry()
    {
        return $this->move_type === MoveType::ENTRY;
    }

    /**
     * Get Sale Types
     *
     * @param mixed $includeReceipts
     */
    public function getSaleTypes($includeReceipts = false)
    {
        return $includeReceipts
            ? [MoveType::OUT_INVOICE, MoveType::OUT_REFUND, MoveType::OUT_RECEIPT]
            : [MoveType::OUT_INVOICE, MoveType::OUT_REFUND];
    }

    /**
     * Is Sale Document
     *
     * @param mixed $includeReceipts
     * @return bool
     */
    public function isSaleDocument($includeReceipts = false)
    {
        return in_array($this->move_type, $this->getSaleTypes($includeReceipts));
    }

    /**
     * Is Purchase Document
     *
     * @param mixed $includeReceipts
     * @return bool
     */
    public function isPurchaseDocument($includeReceipts = false)
    {
        return in_array($this->move_type, $includeReceipts ? [
            MoveType::IN_INVOICE,
            MoveType::IN_REFUND,
            MoveType::IN_RECEIPT,
        ] : [MoveType::IN_INVOICE, MoveType::IN_REFUND]);
    }

    public function getValidJournalTypes()
    {
        if ($this->isSaleDocument(true)) {
            return [JournalType::SALE];
        } elseif ($this->isPurchaseDocument(true)) {
            return [JournalType::PURCHASE];
        } elseif ($this->origin_payment_id || $this->statement_line_id) {
            return [JournalType::BANK, JournalType::CASH, JournalType::CREDIT_CARD];
        } else {
            return [JournalType::GENERAL];
        }
    }

    /**
     * Bootstrap any application services.
     */
    /**
     * Boot
     *
     * @return void
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $model->creator_id = auth()->id();
        });

        static::created(function ($model) {
            $model->updateSequencePrefix();

            $model->updateQuietly([
                'name' => $model->sequence_prefix.'/'.$model->id,
            ]);
        });
    }

    /**
     * Update the full name without triggering additional events
     */
    /**
     * Update Sequence Prefix
     *
     */
    public function updateSequencePrefix()
    {
        $suffix = date('Y').'/'.date('m');

        switch ($this->move_type) {
            case MoveType::OUT_INVOICE:
                $this->sequence_prefix = 'INV/'.$suffix;

                break;
            case MoveType::OUT_REFUND:
                $this->sequence_prefix = 'RINV/'.$suffix;

                break;
            case MoveType::IN_INVOICE:
                $this->sequence_prefix = 'BILL/'.$suffix;

                break;
            case MoveType::IN_REFUND:
                $this->sequence_prefix = 'RBILL/'.$suffix;

                break;
            default:
                $this->sequence_prefix = $suffix;

                break;
        }
    }
}
