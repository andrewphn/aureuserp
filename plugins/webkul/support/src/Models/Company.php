<?php

namespace Webkul\Support\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;
use Spatie\EloquentSortable\Sortable;
use Spatie\EloquentSortable\SortableTrait;
use Webkul\Chatter\Traits\HasChatter;
use Webkul\Field\Traits\HasCustomFields;
use Webkul\Partner\Models\Partner;
use Webkul\Security\Models\User;
use Webkul\Support\Database\Factories\CompanyFactory;

/**
 * Company Eloquent model
 *
 * @property int $id
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property \Carbon\Carbon|null $deleted_at
 * @property string|null $sort
 * @property string|null $name
 * @property int $company_id
 * @property int $parent_id
 * @property int $tax_id
 * @property string|null $registration_number
 * @property string|null $email
 * @property string|null $phone
 * @property string|null $mobile
 * @property string|null $street1
 * @property string|null $street2
 * @property string|null $city
 * @property string|null $zip
 * @property int $state_id
 * @property int $country_id
 * @property string|null $logo
 * @property string|null $shop_capacity_per_day
 * @property string|null $shop_capacity_per_month
 * @property string|null $shop_capacity_per_hour
 * @property float $working_hours_per_day
 * @property string|null $working_days_per_month
 * @property string|null $color
 * @property bool $is_active
 * @property \Carbon\Carbon|null $founded_date
 * @property int $creator_id
 * @property int $currency_id
 * @property int $partner_id
 * @property string|null $website
 * @property string|null $acronym
 * @property int|null $project_number_start
 * @property-read \Illuminate\Database\Eloquent\Collection $branches
 * @property-read \Illuminate\Database\Eloquent\Model|null $country
 * @property-read \Illuminate\Database\Eloquent\Model|null $state
 * @property-read \Illuminate\Database\Eloquent\Model|null $createdBy
 * @property-read \Illuminate\Database\Eloquent\Model|null $parent
 * @property-read \Illuminate\Database\Eloquent\Model|null $currency
 * @property-read \Illuminate\Database\Eloquent\Model|null $partner
 *
 */
class Company extends Model implements Sortable
{
    use HasChatter, HasCustomFields, HasFactory, SoftDeletes, SortableTrait;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'sort',
        'name',
        'company_id',
        'parent_id',
        'tax_id',
        'registration_number',
        'email',
        'phone',
        'mobile',
        'street1',
        'street2',
        'city',
        'zip',
        'state_id',
        'country_id',
        'logo',
        'shop_capacity_per_day',
        'shop_capacity_per_month',
        'shop_capacity_per_hour',
        'working_hours_per_day',
        'working_days_per_month',
        'color',
        'is_active',
        'founded_date',
        'creator_id',
        'currency_id',
        'partner_id',
        'website',
        'acronym',
        'project_number_start',
        // Department production rates (LF per day)
        'design_concepts_lf_per_day',
        'design_revisions_lf_per_day',
        'shop_drawings_lf_per_day',
        'cut_list_bom_lf_per_day',
        'rough_mill_lf_per_day',
        'cabinet_assembly_lf_per_day',
        'doors_drawers_lf_per_day',
        'sanding_prep_lf_per_day',
        'finishing_lf_per_day',
        'hardware_install_lf_per_day',
        'installation_lf_per_day',
    ];

    public $sortable = [
        'order_column_name'  => 'sort',
        'sort_when_creating' => true,
    ];

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
     * Get the creator of the company
     */
    /**
     * Created By
     *
     * @return BelongsTo
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creator_id');
    }

    /**
     * Get the parent company
     */
    /**
     * Parent
     *
     * @return BelongsTo
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'parent_id');
    }

    /**
     * Get the branches (child companies)
     */
    /**
     * Branches
     *
     * @return HasMany
     */
    public function branches(): HasMany
    {
        return $this->hasMany(Company::class, 'parent_id');
    }

    /**
     * Scope a query to only include parent companies
     */
    /**
     * Scope query to Parents
     *
     * @param mixed $query The search query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeParents($query)
    {
        return $query->whereNull('parent_id');
    }

    /**
     * Check if company is a branch
     */
    /**
     * Is Branch
     *
     * @return bool
     */
    public function isBranch(): bool
    {
        return ! is_null($this->parent_id);
    }

    /**
     * Check if company is a parent
     */
    /**
     * Is Parent
     *
     * @return bool
     */
    public function isParent(): bool
    {
        return is_null($this->parent_id);
    }

    /**
     * Get the currency associated with the company.
     */
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
     * Partner
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function partner()
    {
        return $this->belongsTo(Partner::class, 'partner_id');
    }

    /**
     * Scope a query to only include active companies.
     */
    /**
     * Scope query to Active
     *
     * @param mixed $query The search query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * New Factory
     *
     * @return CompanyFactory
     */
    protected static function newFactory(): CompanyFactory
    {
        return CompanyFactory::new();
    }

    /**
     * Calculate shop capacity values based on changes
     */
    /**
     * Calculate Capacities
     *
     * @param mixed $company
     */
    protected static function calculateCapacities($company)
    {
        // Default working schedule: 4 days/week, 8 hours/day (Monday-Thursday, 8am-5pm with 1hr lunch)
        $defaultWorkingHoursPerDay = 8;
        $defaultWorkingDaysPerMonth = 17; // ~4 weeks × 4 days + 1 extra day

        // Set defaults if not provided
        if (! $company->working_hours_per_day) {
            $company->working_hours_per_day = $defaultWorkingHoursPerDay;
        }
        if (! $company->working_days_per_month) {
            $company->working_days_per_month = $defaultWorkingDaysPerMonth;
        }

        // Auto-calculate based on what was changed
        if ($company->isDirty('shop_capacity_per_day') && $company->shop_capacity_per_day) {
            // User entered daily capacity → calculate hourly and monthly
            $company->shop_capacity_per_hour = round($company->shop_capacity_per_day / $company->working_hours_per_day, 2);
            $company->shop_capacity_per_month = round($company->shop_capacity_per_day * $company->working_days_per_month, 2);
        } elseif ($company->isDirty('shop_capacity_per_month') && $company->shop_capacity_per_month) {
            // User entered monthly capacity → calculate daily and hourly
            $company->shop_capacity_per_day = round($company->shop_capacity_per_month / $company->working_days_per_month, 2);
            $company->shop_capacity_per_hour = round($company->shop_capacity_per_day / $company->working_hours_per_day, 2);
        } elseif ($company->isDirty('shop_capacity_per_hour') && $company->shop_capacity_per_hour) {
            // User entered hourly capacity → calculate daily and monthly
            $company->shop_capacity_per_day = round($company->shop_capacity_per_hour * $company->working_hours_per_day, 2);
            $company->shop_capacity_per_month = round($company->shop_capacity_per_day * $company->working_days_per_month, 2);
        }
    }

    /**
     * Bootstrap the model and its traits.
     */
    /**
     * Boot
     *
     * @return void
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($company) {
            static::calculateCapacities($company);

            if (! $company->partner_id) {
                $partner = Partner::create([
                    'creator_id'       => $company->creator_id ?? Auth::id(),
                    'sub_type'         => 'company',
                    'company_registry' => $company->registration_number,
                    'name'             => $company->name,
                    'email'            => $company->email,
                    'website'          => $company->website,
                    'tax_id'           => $company->tax_id,
                    'phone'            => $company->phone,
                    'mobile'           => $company->mobile,
                    'color'            => $company->color,
                    'street1'          => $company->street1,
                    'street2'          => $company->street2,
                    'city'             => $company->city,
                    'zip'              => $company->zip,
                    'state_id'         => $company->state_id,
                    'country_id'       => $company->country_id,
                    'parent_id'        => $company->parent_id,
                    'company_id'       => $company->id,
                ]);

                $company->partner_id = $partner->id;
            }
        });

        static::updating(function ($company) {
            static::calculateCapacities($company);
        });

        static::saved(function ($company) {
            Partner::updateOrCreate(
                [
                    'id' => $company->partner_id,
                ], [
                    'creator_id'       => $company->creator_id ?? Auth::id(),
                    'sub_type'         => 'company',
                    'company_registry' => $company->registration_number,
                    'name'             => $company->name,
                    'email'            => $company->email,
                    'website'          => $company->website,
                    'tax_id'           => $company->tax_id,
                    'phone'            => $company->phone,
                    'mobile'           => $company->mobile,
                    'color'            => $company->color,
                    'street1'          => $company->street1,
                    'street2'          => $company->street2,
                    'city'             => $company->city,
                    'zip'              => $company->zip,
                    'state_id'         => $company->state_id,
                    'country_id'       => $company->country_id,
                    'parent_id'        => $company->parent_id,
                    'company_id'       => $company->id,
                ]
            );
        });
    }
}
