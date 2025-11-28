<?php

namespace Webkul\Purchase\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Webkul\Account\Models\FiscalPosition;
use Webkul\Account\Models\Incoterm;
use Webkul\Account\Models\Partner;
use Webkul\Account\Models\PaymentTerm;
use Webkul\Chatter\Models\Message;
use Webkul\Chatter\Traits\HasChatter;
use Webkul\Chatter\Traits\HasLogActivity;
use Webkul\Field\Traits\HasCustomFields;
use Webkul\Inventory\Models\Operation;
use Webkul\Inventory\Models\OperationType;
use Webkul\Purchase\Database\Factories\OrderFactory;
use Webkul\Purchase\Enums\OrderInvoiceStatus;
use Webkul\Purchase\Enums\OrderReceiptStatus;
use Webkul\Purchase\Enums\OrderState;
use Webkul\Security\Models\User;
use Webkul\Support\Models\Company;
use Webkul\Support\Models\Currency;

/**
 * Order Eloquent model
 *
 * @property int $id
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property string|null $name
 * @property string|null $description
 * @property string|null $priority
 * @property string|null $origin
 * @property string|null $partner_reference
 * @property mixed $state
 * @property mixed $invoice_status
 * @property mixed $receipt_status
 * @property float $untaxed_amount
 * @property float $tax_amount
 * @property float $total_amount
 * @property float $total_cc_amount
 * @property string|null $currency_rate
 * @property bool $mail_reminder_confirmed
 * @property bool $mail_reception_confirmed
 * @property bool $mail_reception_declined
 * @property float $invoice_count
 * @property \Carbon\Carbon|null $ordered_at
 * @property \Carbon\Carbon|null $approved_at
 * @property \Carbon\Carbon|null $planned_at
 * @property \Carbon\Carbon|null $calendar_start_at
 * @property string|null $incoterm_location
 * @property \Carbon\Carbon|null $effective_date
 * @property bool $report_grids
 * @property int $requisition_id
 * @property int $purchases_group_id
 * @property int $partner_id
 * @property int $currency_id
 * @property int $fiscal_position_id
 * @property int $payment_term_id
 * @property int $incoterm_id
 * @property int $user_id
 * @property int $company_id
 * @property int $creator_id
 * @property int $operation_type_id
 * @property-read \Illuminate\Database\Eloquent\Collection $lines
 * @property-read \Illuminate\Database\Eloquent\Model|null $requisition
 * @property-read \Illuminate\Database\Eloquent\Model|null $group
 * @property-read \Illuminate\Database\Eloquent\Model|null $partner
 * @property-read \Illuminate\Database\Eloquent\Model|null $fiscalPosition
 * @property-read \Illuminate\Database\Eloquent\Model|null $paymentTerm
 * @property-read \Illuminate\Database\Eloquent\Model|null $incoterm
 * @property-read \Illuminate\Database\Eloquent\Model|null $currency
 * @property-read \Illuminate\Database\Eloquent\Model|null $user
 * @property-read \Illuminate\Database\Eloquent\Model|null $company
 * @property-read \Illuminate\Database\Eloquent\Model|null $creator
 * @property-read \Illuminate\Database\Eloquent\Model|null $operationType
 * @property-read \Illuminate\Database\Eloquent\Collection $accountMoves
 * @property-read \Illuminate\Database\Eloquent\Collection $operations
 *
 */
class Order extends Model
{
    use HasChatter, HasCustomFields, HasFactory, HasLogActivity;

    /**
     * Table name.
     *
     * @var string
     */
    protected $table = 'purchases_orders';

    /**
     * Fillable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'description',
        'priority',
        'origin',
        'partner_reference',
        'state',
        'invoice_status',
        'receipt_status',
        'untaxed_amount',
        'tax_amount',
        'total_amount',
        'total_cc_amount',
        'currency_rate',
        'mail_reminder_confirmed',
        'mail_reception_confirmed',
        'mail_reception_declined',
        'invoice_count',
        'ordered_at',
        'approved_at',
        'planned_at',
        'calendar_start_at',
        'incoterm_location',
        'effective_date',
        'report_grids',
        'requisition_id',
        'purchases_group_id',
        'partner_id',
        'currency_id',
        'fiscal_position_id',
        'payment_term_id',
        'incoterm_id',
        'user_id',
        'company_id',
        'creator_id',
        'operation_type_id',
    ];

    /**
     * Table name.
     *
     * @var string
     */
    protected $casts = [
        'state'                    => OrderState::class,
        'invoice_status'           => OrderInvoiceStatus::class,
        'receipt_status'           => OrderReceiptStatus::class,
        'mail_reminder_confirmed'  => 'boolean',
        'mail_reception_confirmed' => 'boolean',
        'mail_reception_declined'  => 'boolean',
        'report_grids'             => 'boolean',
        'ordered_at'               => 'datetime',
        'approved_at'              => 'datetime',
        'planned_at'               => 'datetime',
        'calendar_start_at'        => 'datetime',
        'effective_date'           => 'datetime',
    ];

    protected array $logAttributes = [
        'name',
        'description',
        'priority',
        'origin',
        'partner_reference',
        'state',
        'invoice_status',
        'receipt_status',
        'untaxed_amount',
        'currency_rate',
        'ordered_at',
        'approved_at',
        'planned_at',
        'calendar_start_at',
        'incoterm_location',
        'effective_date',
        'requisition.name'    => 'Requisition',
        'partner.name'        => 'Vendor',
        'currency.name'       => 'Currency',
        'fiscalPosition'      => 'Fiscal Position',
        'paymentTerm.name'    => 'Payment Term',
        'incoterm.name'       => 'Buyer',
        'user.name'           => 'Buyer',
        'company.name'        => 'Company',
        'creator.name'        => 'Creator',
    ];

    /**
     * Checks if new invoice is allow or not
     */
    public function getQtyToInvoiceAttribute()
    {
        return $this->lines->sum('qty_to_invoice');
    }

    /**
     * Requisition
     *
     * @return BelongsTo
     */
    public function requisition(): BelongsTo
    {
        return $this->belongsTo(Requisition::class);
    }

    /**
     * Group
     *
     * @return BelongsTo
     */
    public function group(): BelongsTo
    {
        return $this->belongsTo(OrderGroup::class);
    }

    /**
     * Partner
     *
     * @return BelongsTo
     */
    public function partner(): BelongsTo
    {
        return $this->belongsTo(Partner::class);
    }

    /**
     * Fiscal Position
     *
     * @return BelongsTo
     */
    public function fiscalPosition(): BelongsTo
    {
        return $this->belongsTo(FiscalPosition::class);
    }

    /**
     * Payment Term
     *
     * @return BelongsTo
     */
    public function paymentTerm(): BelongsTo
    {
        return $this->belongsTo(PaymentTerm::class);
    }

    /**
     * Incoterm
     *
     * @return BelongsTo
     */
    public function incoterm(): BelongsTo
    {
        return $this->belongsTo(Incoterm::class);
    }

    /**
     * Currency
     *
     * @return BelongsTo
     */
    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class);
    }

    /**
     * User
     *
     * @return BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Company
     *
     * @return BelongsTo
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Creator
     *
     * @return BelongsTo
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Lines
     *
     * @return HasMany
     */
    public function lines(): HasMany
    {
        return $this->hasMany(OrderLine::class, 'order_id');
    }

    /**
     * Account Moves
     *
     * @return BelongsToMany
     */
    public function accountMoves(): BelongsToMany
    {
        return $this->belongsToMany(AccountMove::class, 'purchases_order_account_moves', 'order_id', 'move_id');
    }

    /**
     * Operation Type
     *
     * @return BelongsTo
     */
    public function operationType(): BelongsTo
    {
        return $this->belongsTo(OperationType::class, 'operation_type_id');
    }

    /**
     * Operations
     *
     * @return BelongsToMany
     */
    public function operations(): BelongsToMany
    {
        return $this->belongsToMany(Operation::class, 'purchases_order_operations', 'purchase_order_id', 'inventory_operation_id');
    }

    /**
     * Add a new message
     */
    /**
     * Add Message
     *
     * @param array $data The data array
     * @return Message
     */
    public function addMessage(array $data): Message
    {
        $message = new Message;

        $user = filament()->auth()->user();

        $message->fill(array_merge([
            'creator_id'       => $user?->id,
            'date_deadline'    => $data['date_deadline'] ?? now(),
            'company_id'       => $data['company_id'] ?? ($user->defaultCompany?->id ?? null),
            'messageable_type' => Order::class,
            'messageable_id'   => $this->id,
        ], $data));

        $message->save();

        return $message;
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

        static::saving(function ($order) {
            $order->updateName();
        });

        static::created(function ($order) {
            $order->update(['name' => $order->name]);
        });
    }

    /**
     * Update the full name without triggering additional events
     */
    /**
     * Update Name
     *
     */
    public function updateName()
    {
        $this->name = 'PO/'.$this->id;
    }

    /**
     * New Factory
     *
     * @return OrderFactory
     */
    protected static function newFactory(): OrderFactory
    {
        return OrderFactory::new();
    }
}
