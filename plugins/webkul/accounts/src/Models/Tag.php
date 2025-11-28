<?php

namespace Webkul\Account\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Webkul\Security\Models\User;
use Webkul\Support\Models\Country;

/**
 * Tag Eloquent model
 *
 * @property int $id
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property string|null $color
 * @property int $country_id
 * @property int $creator_id
 * @property string|null $applicability
 * @property string|null $name
 * @property string|null $tax_negate
 * @property-read \Illuminate\Database\Eloquent\Model|null $country
 * @property-read \Illuminate\Database\Eloquent\Model|null $createdBy
 *
 */
class Tag extends Model
{
    use HasFactory;

    protected $table = 'accounts_account_tags';

    protected $fillable = [
        'color',
        'country_id',
        'creator_id',
        'applicability',
        'name',
        'tax_negate',
    ];

    /**
     * Country
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function country()
    {
        return $this->belongsTo(Country::class);
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
