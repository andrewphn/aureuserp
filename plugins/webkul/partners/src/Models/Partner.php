<?php

namespace Webkul\Partner\Models;

use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Storage;
use Webkul\Chatter\Traits\HasChatter;
use Webkul\Chatter\Traits\HasLogActivity;
use Webkul\Partner\Database\Factories\PartnerFactory;
use Webkul\Partner\Enums\AccountType;
use Webkul\Security\Models\User;
use Webkul\Support\Models\Company;
use Webkul\Support\Models\Country;
use Webkul\Support\Models\State;

/**
 * Partner Eloquent model
 *
 * @property int $id
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property \Carbon\Carbon|null $deleted_at
 * @property mixed $account_type
 * @property string|null $sub_type
 * @property string|null $name
 * @property string|null $avatar
 * @property string|null $email
 * @property string|null $job_title
 * @property string|null $website
 * @property int $tax_id
 * @property string|null $phone
 * @property string|null $mobile
 * @property string|null $color
 * @property string|null $company_registry
 * @property string|null $reference
 * @property string|null $street1
 * @property string|null $street2
 * @property string|null $city
 * @property string|null $zip
 * @property int $state_id
 * @property int $country_id
 * @property int $parent_id
 * @property int $creator_id
 * @property int $user_id
 * @property int $title_id
 * @property int $company_id
 * @property int $industry_id
 * @property-read \Illuminate\Database\Eloquent\Collection $addresses
 * @property-read \Illuminate\Database\Eloquent\Collection $contacts
 * @property-read \Illuminate\Database\Eloquent\Collection $bankAccounts
 * @property-read \Illuminate\Database\Eloquent\Model|null $country
 * @property-read \Illuminate\Database\Eloquent\Model|null $state
 * @property-read \Illuminate\Database\Eloquent\Model|null $parent
 * @property-read \Illuminate\Database\Eloquent\Model|null $creator
 * @property-read \Illuminate\Database\Eloquent\Model|null $user
 * @property-read \Illuminate\Database\Eloquent\Model|null $title
 * @property-read \Illuminate\Database\Eloquent\Model|null $company
 * @property-read \Illuminate\Database\Eloquent\Model|null $industry
 * @property-read \Illuminate\Database\Eloquent\Collection $tags
 *
 */
class Partner extends Authenticatable implements FilamentUser
{
    use HasChatter, HasFactory, HasLogActivity, Notifiable, SoftDeletes;

    /**
     * Table name.
     *
     * @var string
     */
    protected $table = 'partners_partners';

    /**
     * Fillable.
     *
     * @var array
     */
    protected $fillable = [
        'account_type',
        'sub_type',
        'name',
        'avatar',
        'email',
        'job_title',
        'website',
        'tax_id',
        'phone',
        'mobile',
        'color',
        'company_registry',
        'reference',
        'street1',
        'street2',
        'city',
        'zip',
        'state_id',
        'country_id',
        'parent_id',
        'creator_id',
        'user_id',
        'title_id',
        'company_id',
        'industry_id',
    ];

    /**
     * Table name.
     *
     * @var string
     */
    protected $casts = [
        'account_type' => AccountType::class,
        'is_active'    => 'boolean',
    ];

    /**
     * Determine if the user can access the Filament panel.
     */
    public function canAccessPanel(Panel $panel): bool
    {
        return true;
    }

    /**
     * Get image url for the product image.
     *
     * @return string
     */
    public function getAvatarUrlAttribute()
    {
        if (! $this->avatar) {
            return;
        }

        return Storage::url($this->avatar);
    }

    /**
     * Country
     *
     * @return BelongsTo
     */
    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }

    /**
     * State
     *
     * @return BelongsTo
     */
    public function state(): BelongsTo
    {
        return $this->belongsTo(State::class);
    }

    /**
     * Parent
     *
     * @return BelongsTo
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class);
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
     * User
     *
     * @return BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Title
     *
     * @return BelongsTo
     */
    public function title(): BelongsTo
    {
        return $this->belongsTo(Title::class);
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
     * Industry
     *
     * @return BelongsTo
     */
    public function industry(): BelongsTo
    {
        return $this->belongsTo(Industry::class);
    }

    /**
     * Addresses
     *
     * @return HasMany
     */
    public function addresses(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')
            ->where('account_type', AccountType::ADDRESS);
    }

    /**
     * Contacts
     *
     * @return HasMany
     */
    public function contacts(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')
            ->where('account_type', '!=', AccountType::ADDRESS);
    }

    /**
     * Bank Accounts
     *
     * @return HasMany
     */
    public function bankAccounts(): HasMany
    {
        return $this->hasMany(BankAccount::class);
    }

    /**
     * Tags
     *
     * @return BelongsToMany
     */
    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class, 'partners_partner_tag', 'partner_id', 'tag_id');
    }

    /**
     * New Factory
     *
     * @return PartnerFactory
     */
    protected static function newFactory(): PartnerFactory
    {
        return PartnerFactory::new();
    }
}
