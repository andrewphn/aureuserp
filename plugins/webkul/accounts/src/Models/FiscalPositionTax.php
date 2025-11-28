<?php

namespace Webkul\Account\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Webkul\Security\Models\User;
use Webkul\Support\Models\Company;

/**
 * Fiscal Position Tax Eloquent model
 *
 * @property int $id
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property int $fiscal_position_id
 * @property int $company_id
 * @property int $tax_source_id
 * @property int $tax_destination_id
 * @property int $creator_id
 * @property-read \Illuminate\Database\Eloquent\Model|null $fiscalPosition
 * @property-read \Illuminate\Database\Eloquent\Model|null $company
 * @property-read \Illuminate\Database\Eloquent\Model|null $taxSource
 * @property-read \Illuminate\Database\Eloquent\Model|null $taxDestination
 * @property-read \Illuminate\Database\Eloquent\Model|null $creator
 *
 */
class FiscalPositionTax extends Model
{
    use HasFactory;

    protected $table = 'accounts_fiscal_position_taxes';

    protected $fillable = [
        'fiscal_position_id',
        'company_id',
        'tax_source_id',
        'tax_destination_id',
        'creator_id',
    ];

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
     * Company
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Tax Source
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function taxSource()
    {
        return $this->belongsTo(Tax::class, 'tax_source_id');
    }

    /**
     * Tax Destination
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function taxDestination()
    {
        return $this->belongsTo(Tax::class, 'tax_destination_id');
    }

    /**
     * Creator
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'creator_id');
    }
}
