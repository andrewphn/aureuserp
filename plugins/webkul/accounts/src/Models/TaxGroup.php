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
 * Tax Group Eloquent model
 *
 * @property int $id
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property string|null $sort
 * @property int $company_id
 * @property int $country_id
 * @property int $creator_id
 * @property string|null $name
 * @property float $preceding_subtotal
 * @property-read \Illuminate\Database\Eloquent\Model|null $company
 * @property-read \Illuminate\Database\Eloquent\Model|null $country
 * @property-read \Illuminate\Database\Eloquent\Model|null $createdBy
 *
 */
class TaxGroup extends Model implements Sortable
{
    use HasFactory, SortableTrait;

    protected $table = 'accounts_tax_groups';

    protected $fillable = [
        'sort',
        'company_id',
        'country_id',
        'creator_id',
        'name',
        'preceding_subtotal',
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
     * Created By
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function createdBy()
    {
        return $this->belongsTo(User::class, 'creator_id');
    }
}
