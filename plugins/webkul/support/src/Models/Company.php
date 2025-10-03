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
    ];

    public $sortable = [
        'order_column_name'  => 'sort',
        'sort_when_creating' => true,
    ];

    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }

    public function state(): BelongsTo
    {
        return $this->belongsTo(State::class);
    }

    /**
     * Get the creator of the company
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creator_id');
    }

    /**
     * Get the parent company
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'parent_id');
    }

    /**
     * Get the branches (child companies)
     */
    public function branches(): HasMany
    {
        return $this->hasMany(Company::class, 'parent_id');
    }

    /**
     * Scope a query to only include parent companies
     */
    public function scopeParents($query)
    {
        return $query->whereNull('parent_id');
    }

    /**
     * Check if company is a branch
     */
    public function isBranch(): bool
    {
        return ! is_null($this->parent_id);
    }

    /**
     * Check if company is a parent
     */
    public function isParent(): bool
    {
        return is_null($this->parent_id);
    }

    /**
     * Get the currency associated with the company.
     */
    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class);
    }

    public function partner()
    {
        return $this->belongsTo(Partner::class, 'partner_id');
    }

    /**
     * Scope a query to only include active companies.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    protected static function newFactory(): CompanyFactory
    {
        return CompanyFactory::new();
    }

    /**
     * Calculate shop capacity values based on changes
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
