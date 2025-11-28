<?php

namespace Webkul\Sale\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Webkul\Account\Models\FiscalPosition;
use Webkul\Account\Models\Journal;
use Webkul\Account\Models\Move;
use Webkul\Account\Models\PaymentTerm;
use App\Traits\HasPdfDocuments;
use Webkul\Chatter\Traits\HasChatter;
use Webkul\Chatter\Traits\HasLogActivity;
use Webkul\Field\Traits\HasCustomFields;
use Webkul\Inventory\Models\Operation;
use Webkul\Inventory\Models\Warehouse;
use Webkul\Partner\Models\Partner;
use Webkul\Sale\Enums\InvoiceStatus;
use Webkul\Sale\Enums\OrderState;
use Webkul\Security\Models\User;
use Webkul\Support\Models\Company;
use Webkul\Support\Models\Currency;
use Webkul\Support\Models\UtmCampaign;
use Webkul\Support\Models\UTMMedium;
use Webkul\Support\Models\UTMSource;

/**
 * Order Eloquent model
 *
 * @property int $id
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property \Carbon\Carbon|null $deleted_at
 * @property int $utm_source_id
 * @property int $medium_id
 * @property int $company_id
 * @property int $partner_id
 * @property int $project_id
 * @property int $journal_id
 * @property int $partner_invoice_id
 * @property int $partner_shipping_id
 * @property int $fiscal_position_id
 * @property int $sale_order_template_id
 * @property int $document_template_id
 * @property int $payment_term_id
 * @property int $currency_id
 * @property int $user_id
 * @property int $team_id
 * @property int $creator_id
 * @property int $campaign_id
 * @property string|null $access_token
 * @property string|null $name
 * @property mixed $state
 * @property string|null $client_order_ref
 * @property string|null $origin
 * @property string|null $reference
 * @property string|null $signed_by
 * @property mixed $invoice_status
 * @property \Carbon\Carbon|null $validity_date
 * @property string|null $note
 * @property string|null $locked
 * @property \Carbon\Carbon|null $commitment_date
 * @property string|null $date_order
 * @property string|null $signed_on
 * @property string|null $prepayment_percent
 * @property string|null $require_signature
 * @property string|null $require_payment
 * @property string|null $currency_rate
 * @property float $amount_untaxed
 * @property float $amount_tax
 * @property float $amount_total
 * @property int $warehouse_id
 * @property-read \Illuminate\Database\Eloquent\Collection $lines
 * @property-read \Illuminate\Database\Eloquent\Collection $optionalLines
 * @property-read \Illuminate\Database\Eloquent\Collection $operations
 * @property-read \Illuminate\Database\Eloquent\Collection $lineItems
 * @property-read \Illuminate\Database\Eloquent\Model|null $company
 * @property-read \Illuminate\Database\Eloquent\Model|null $partner
 * @property-read \Illuminate\Database\Eloquent\Model|null $project
 * @property-read \Illuminate\Database\Eloquent\Model|null $campaign
 * @property-read \Illuminate\Database\Eloquent\Model|null $journal
 * @property-read \Illuminate\Database\Eloquent\Model|null $partnerInvoice
 * @property-read \Illuminate\Database\Eloquent\Model|null $partnerShipping
 * @property-read \Illuminate\Database\Eloquent\Model|null $fiscalPosition
 * @property-read \Illuminate\Database\Eloquent\Model|null $paymentTerm
 * @property-read \Illuminate\Database\Eloquent\Model|null $currency
 * @property-read \Illuminate\Database\Eloquent\Model|null $user
 * @property-read \Illuminate\Database\Eloquent\Model|null $team
 * @property-read \Illuminate\Database\Eloquent\Model|null $createdBy
 * @property-read \Illuminate\Database\Eloquent\Model|null $utmSource
 * @property-read \Illuminate\Database\Eloquent\Model|null $medium
 * @property-read \Illuminate\Database\Eloquent\Model|null $quotationTemplate
 * @property-read \Illuminate\Database\Eloquent\Model|null $documentTemplate
 * @property-read \Illuminate\Database\Eloquent\Model|null $warehouse
 * @property-read \Illuminate\Database\Eloquent\Collection $accountMoves
 * @property-read \Illuminate\Database\Eloquent\Collection $tags
 *
 */
class Order extends Model
{
    use HasChatter, HasCustomFields, HasFactory, HasLogActivity, HasPdfDocuments, SoftDeletes;

    protected $table = 'sales_orders';

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory()
    {
        return \Webkul\Sale\Database\Factories\OrderFactory::new();
    }

    protected $fillable = [
        'utm_source_id',
        'medium_id',
        'company_id',
        'partner_id',
        'project_id',
        'journal_id',
        'partner_invoice_id',
        'partner_shipping_id',
        'fiscal_position_id',
        'sale_order_template_id',
        'document_template_id',
        'payment_term_id',
        'currency_id',
        'user_id',
        'team_id',
        'creator_id',
        'campaign_id',
        'access_token',
        'name',
        'state',
        'client_order_ref',
        'origin',
        'reference',
        'signed_by',
        'invoice_status',
        'validity_date',
        'note',
        'locked',
        'commitment_date',
        'date_order',
        'signed_on',
        'prepayment_percent',
        'require_signature',
        'require_payment',
        'currency_rate',
        'amount_untaxed',
        'amount_tax',
        'amount_total',
        'warehouse_id',
        'source_quote_id',
        'converted_from_quote_at',
    ];

    protected array $logAttributes = [
        'medium.name'          => 'Medium',
        'utmSource.name'       => 'UTM Source',
        'partner.name'         => 'Customer',
        'project.name'         => 'Project',
        'partnerInvoice.name'  => 'Invoice Address',
        'partnerShipping.name' => 'Shipping Address',
        'fiscalPosition.name'  => 'Fiscal Position',
        'paymentTerm.name'     => 'Payment Term',
        'currency.name'        => 'Currency',
        'user.name'            => 'Salesperson',
        'team.name'            => 'Sales Team',
        'creator.name'         => 'Created By',
        'company.name'         => 'Company',
        'name'                 => 'Order Reference',
        'state'                => 'Order Status',
        'client_order_ref'     => 'Customer Reference',
        'origin'               => 'Source Document',
        'reference'            => 'Reference',
        'signed_by'            => 'Signed By',
        'invoice_status'       => 'Invoice Status',
        'validity_date'        => 'Validity Date',
        'note'                 => 'Terms and Conditions',
        'currency_rate'        => 'Currency Rate',
        'amount_untaxed'       => 'Subtotal',
        'amount_tax'           => 'Tax',
        'amount_total'         => 'Total',
        'locked'               => 'Locked',
        'require_signature'    => 'Require Signature',
        'require_payment'      => 'Require Payment',
        'commitment_date'      => 'Commitment Date',
        'date_order'           => 'Order Date',
        'signed_on'            => 'Signed On',
        'prepayment_percent'   => 'Prepayment Percentage',
    ];

    protected $casts = [
        'state'                   => OrderState::class,
        'invoice_status'          => InvoiceStatus::class,
        'converted_from_quote_at' => 'datetime',
    ];

    /**
     * Company
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function company()
    {
        return $this->belongsTo(Company::class)->withTrashed();
    }

    /**
     * Partner (Customer)
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function partner()
    {
        return $this->belongsTo(Partner::class);
    }

    /**
     * Project
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function project()
    {
        return $this->belongsTo(\Webkul\Project\Models\Project::class);
    }

    /**
     * Get the quantity to invoice attribute
     *
     * @return float
     */
    public function getQtyToInvoiceAttribute()
    {
        return $this->lines->sum('qty_to_invoice');
    }

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
        return $this->belongsTo(Journal::class);
    }

    /**
     * Account Moves
     *
     * @return BelongsToMany
     */
    public function accountMoves(): BelongsToMany
    {
        return $this->belongsToMany(Move::class, 'sales_order_invoices', 'order_id', 'move_id');
    }

    /**
     * Partner Invoice
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function partnerInvoice()
    {
        return $this->belongsTo(Partner::class, 'partner_invoice_id');
    }

    /**
     * Tags
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function tags()
    {
        return $this->belongsToMany(Tag::class, 'sales_order_tags', 'order_id', 'tag_id');
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
     * Fiscal Position
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function fiscalPosition()
    {
        return $this->belongsTo(FiscalPosition::class);
    }

    /**
     * Payment Term
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function paymentTerm()
    {
        return $this->belongsTo(PaymentTerm::class);
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
     * User
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Team
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function team()
    {
        return $this->belongsTo(Team::class);
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
     * Utm Source
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function utmSource()
    {
        return $this->belongsTo(UTMSource::class, 'utm_source_id');
    }

    /**
     * Medium
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function medium()
    {
        return $this->belongsTo(UTMMedium::class);
    }

    /**
     * Lines
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function lines()
    {
        return $this->hasMany(OrderLine::class);
    }

    /**
     * Optional Lines
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function optionalLines()
    {
        return $this->hasMany(OrderOption::class);
    }

    /**
     * Quotation Template
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function quotationTemplate()
    {
        return $this->belongsTo(OrderTemplate::class, 'sale_order_template_id');
    }

    /**
     * Document Template
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function documentTemplate()
    {
        return $this->belongsTo(DocumentTemplate::class, 'document_template_id');
    }

    /**
     * Warehouse
     *
     * @return BelongsTo
     */
    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'warehouse_id');
    }

    /**
     * Source Quote (the quote this order was converted from)
     *
     * @return BelongsTo
     */
    public function sourceQuote(): BelongsTo
    {
        return $this->belongsTo(self::class, 'source_quote_id');
    }

    /**
     * Derived Orders (orders converted from this quote)
     *
     * @return HasMany
     */
    public function derivedOrders(): HasMany
    {
        return $this->hasMany(self::class, 'source_quote_id');
    }

    /**
     * Operations
     *
     * @return HasMany
     */
    public function operations(): HasMany
    {
        return $this->hasMany(Operation::class, 'sale_order_id');
    }

    /**
     * Line Items
     *
     * @return HasMany
     */
    public function lineItems(): HasMany
    {
        return $this->hasMany(SalesOrderLineItem::class, 'sales_order_id');
    }

    /**
     * Boot
     *
     * @return void
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($order) {
            // Set initial name based on state when first created
            if (empty($order->name)) {
                $order->updateName();
            }
        });

        static::created(function ($order) {
            // Update name with actual ID after creation
            if (empty($order->name) || $order->name === 'SO/') {
                static::withoutEvents(function () use ($order) {
                    $order->update(['name' => $order->generateName()]);
                });
            }
        });
    }

    /**
     * Update the name based on the state without trigger any additional events.
     * Only used on initial creation.
     */
    /**
     * Update Name
     *
     */
    public function updateName()
    {
        $this->name = $this->generateName();
    }

    /**
     * Generate order number based on state and project
     */
    /**
     * Generate Name
     *
     * @return string
     */
    protected function generateName(): string
    {
        $settings = app(\Webkul\Sale\Settings\QuotationAndOrderSettings::class);

        // Check if this order is linked to a project
        if ($this->project_id && $this->project) {
            // Get project number (which already includes street address: TCS-001-MapleAve)
            $projectNumber = $this->project->project_number;

            if ($projectNumber) {
                // Base prefix based on order state
                $basePrefix = match($this->state) {
                    OrderState::DRAFT, OrderState::SENT => $settings->quotation_prefix ?? 'Q',
                    OrderState::SALE => $settings->sales_order_prefix ?? 'SO',
                    OrderState::CANCEL => $settings->sales_order_prefix ?? 'SO',
                    default => $settings->sales_order_prefix ?? 'SO',
                };

                // Count existing orders for this project with same state prefix
                $existingCount = static::where('project_id', $this->project_id)
                    ->where('id', '!=', $this->id)
                    ->where('name', 'LIKE', "{$projectNumber}-{$basePrefix}%")
                    ->count();

                $orderNumber = $existingCount + 1;

                // Format: TCS-001-MapleAve-Q1, TCS-002-FriendshipLane-SO1
                return "{$projectNumber}-{$basePrefix}{$orderNumber}";
            }
        }

        // Fallback to company-based numbering if no project
        $companyAcronym = $this->company?->acronym ?? '';

        // Base prefix based on order state
        $basePrefix = match($this->state) {
            OrderState::DRAFT, OrderState::SENT => $settings->quotation_prefix ?? 'Q',
            OrderState::SALE => $settings->sales_order_prefix ?? 'SO',
            OrderState::CANCEL => $settings->sales_order_prefix ?? 'SO',
            default => $settings->sales_order_prefix ?? 'SO',
        };

        // If company has acronym, use it as prefix
        if (!empty($companyAcronym)) {
            $prefix = $companyAcronym . '-' . $basePrefix;
        } else {
            $prefix = $basePrefix;
        }

        return $prefix . '/' . $this->id;
    }
}
