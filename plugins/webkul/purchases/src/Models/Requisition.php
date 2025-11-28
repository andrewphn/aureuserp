<?php

namespace Webkul\Purchase\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Webkul\Chatter\Traits\HasChatter;
use Webkul\Chatter\Traits\HasLogActivity;
use Webkul\Field\Traits\HasCustomFields;
use Webkul\Partner\Models\Partner;
use Webkul\Purchase\Database\Factories\RequisitionFactory;
use Webkul\Purchase\Enums\RequisitionState;
use Webkul\Purchase\Enums\RequisitionType;
use Webkul\Security\Models\User;
use Webkul\Support\Models\Company;
use Webkul\Support\Models\Currency;

/**
 * Requisition Eloquent model
 *
 * @property int $id
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property \Carbon\Carbon|null $deleted_at
 * @property string|null $name
 * @property mixed $type
 * @property mixed $state
 * @property string|null $reference
 * @property \Carbon\Carbon|null $starts_at
 * @property \Carbon\Carbon|null $ends_at
 * @property string|null $description
 * @property int $currency_id
 * @property int $partner_id
 * @property int $user_id
 * @property int $company_id
 * @property int $creator_id
 * @property-read \Illuminate\Database\Eloquent\Collection $lines
 * @property-read \Illuminate\Database\Eloquent\Model|null $partner
 * @property-read \Illuminate\Database\Eloquent\Model|null $currency
 * @property-read \Illuminate\Database\Eloquent\Model|null $user
 * @property-read \Illuminate\Database\Eloquent\Model|null $company
 * @property-read \Illuminate\Database\Eloquent\Model|null $creator
 *
 */
class Requisition extends Model
{
    use HasChatter, HasCustomFields, HasFactory, HasLogActivity, SoftDeletes;

    /**
     * Table name.
     *
     * @var string
     */
    protected $table = 'purchases_requisitions';

    /**
     * Fillable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'type',
        'state',
        'reference',
        'starts_at',
        'ends_at',
        'description',
        'currency_id',
        'partner_id',
        'user_id',
        'company_id',
        'creator_id',
    ];

    /**
     * Table name.
     *
     * @var string
     */
    protected $casts = [
        'state' => RequisitionState::class,
        'type'  => RequisitionType::class,
    ];

    protected array $logAttributes = [
        'name',
        'type',
        'state',
        'reference',
        'starts_at',
        'ends_at',
        'description',
        'currency.name' => 'Currency',
        'partner.name'  => 'Partner',
        'user.name'     => 'Buyer',
        'company.name'  => 'Company',
        'creator.name'  => 'Creator',
    ];

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
        return $this->hasMany(RequisitionLine::class);
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
        if ($this->type == RequisitionType::BLANKET_ORDER) {
            $this->name = 'BO/'.$this->id;
        } else {
            $this->name = 'PT/'.$this->id;
        }
    }

    /**
     * New Factory
     *
     * @return RequisitionFactory
     */
    protected static function newFactory(): RequisitionFactory
    {
        return RequisitionFactory::new();
    }
}
