<?php

namespace Webkul\Account\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\EloquentSortable\Sortable;
use Spatie\EloquentSortable\SortableTrait;
use Webkul\Security\Models\User;
use Webkul\Support\Models\Company;
use Webkul\Support\Models\Country;

/**
 * Fiscal Position Eloquent model
 *
 * @property int $id
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property string|null $sort
 * @property int $company_id
 * @property int $country_id
 * @property int $country_group_id
 * @property int $creator_id
 * @property string|null $zip_from
 * @property string|null $zip_to
 * @property string|null $foreign_vat
 * @property string|null $name
 * @property string|null $notes
 * @property string|null $auto_reply
 * @property string|null $vat_required
 * @property-read \Illuminate\Database\Eloquent\Collection $fiscalPositionTaxes
 * @property-read \Illuminate\Database\Eloquent\Model|null $company
 * @property-read \Illuminate\Database\Eloquent\Model|null $country
 * @property-read \Illuminate\Database\Eloquent\Model|null $countryGroup
 * @property-read \Illuminate\Database\Eloquent\Model|null $createdBy
 *
 */
class FiscalPosition extends Model implements Sortable
{
    use HasFactory, SortableTrait;

    protected $table = 'accounts_fiscal_positions';

    protected $fillable = [
        'sort',
        'company_id',
        'country_id',
        'country_group_id',
        'creator_id',
        'zip_from',
        'zip_to',
        'foreign_vat',
        'name',
        'notes',
        'auto_reply',
        'vat_required',
    ];

    public $sortable = [
        'order_column_name'  => 'sort',
        'sort_when_creating' => true,
    ];

    /**
     * Company
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function company()
    {
        return $this->belongsTo(Company::class, 'company_id');
    }

    public function country()
    {
        return $this->belongsTo(Country::class, 'country_id');
    }

    /**
     * Country Group
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function countryGroup()
    {
        return $this->belongsTo(Country::class, 'country_group_id');
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
     * Fiscal Position Taxes
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function fiscalPositionTaxes()
    {
        return $this->hasMany(FiscalPositionTax::class, 'fiscal_position_id');
    }
}
