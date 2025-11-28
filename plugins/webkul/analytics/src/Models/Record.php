<?php

namespace Webkul\Analytic\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Webkul\Partner\Models\Partner;
use Webkul\Security\Models\User;
use Webkul\Support\Models\Company;

/**
 * Record Eloquent model
 *
 * @property int $id
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property string|null $type
 * @property string|null $name
 * @property \Carbon\Carbon|null $date
 * @property float $amount
 * @property float $unit_amount
 * @property int $partner_id
 * @property int $company_id
 * @property int $user_id
 * @property int $creator_id
 * @property-read \Illuminate\Database\Eloquent\Model|null $partner
 * @property-read \Illuminate\Database\Eloquent\Model|null $creator
 * @property-read \Illuminate\Database\Eloquent\Model|null $user
 * @property-read \Illuminate\Database\Eloquent\Model|null $company
 *
 */
class Record extends Model
{
    /**
     * Table name.
     *
     * @var string
     */
    protected $table = 'analytic_records';

    /**
     * Fillable.
     *
     * @var array
     */
    protected $fillable = [
        'type',
        'name',
        'date',
        'amount',
        'unit_amount',
        'partner_id',
        'company_id',
        'user_id',
        'creator_id',
    ];

    /**
     * Table name.
     *
     * @var string
     */
    protected $casts = [
        'date' => 'date',
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
     * Company
     *
     * @return BelongsTo
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }
}
