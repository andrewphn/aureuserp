<?php

namespace Webkul\Support\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Webkul\Security\Models\User;
use Webkul\Support\Database\Factories\BankFactory;

/**
 * Bank Eloquent model
 *
 * @property int $id
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property \Carbon\Carbon|null $deleted_at
 * @property string|null $name
 * @property string|null $code
 * @property string|null $email
 * @property string|null $phone
 * @property string|null $street1
 * @property string|null $street2
 * @property string|null $city
 * @property string|null $zip
 * @property int $state_id
 * @property int $country_id
 * @property int $creator_id
 * @property-read \Illuminate\Database\Eloquent\Model|null $country
 * @property-read \Illuminate\Database\Eloquent\Model|null $state
 * @property-read \Illuminate\Database\Eloquent\Model|null $creator
 *
 */
class Bank extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * Fillable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'code',
        'email',
        'phone',
        'street1',
        'street2',
        'city',
        'zip',
        'state_id',
        'country_id',
        'creator_id',
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
     * Creator
     *
     * @return BelongsTo
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * New Factory
     *
     * @return BankFactory
     */
    protected static function newFactory(): BankFactory
    {
        return BankFactory::new();
    }
}
