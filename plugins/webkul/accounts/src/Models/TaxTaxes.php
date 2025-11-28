<?php

namespace Webkul\Account\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Tax Taxes Eloquent model
 *
 * @property int $id
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property int $parent_tax_id
 * @property int $child_tax_id
 * @property-read \Illuminate\Database\Eloquent\Model|null $parentTax
 * @property-read \Illuminate\Database\Eloquent\Model|null $childTax
 *
 */
class TaxTaxes extends Model
{
    protected $table = 'accounts_tax_taxes';

    protected $fillable = ['parent_tax_id', 'child_tax_id'];

    public $timestamps = false;

    /**
     * Parent Tax
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function parentTax()
    {
        return $this->belongsTo(Tax::class, 'parent_tax_id');
    }

    public function childTax()
    {
        return $this->belongsTo(Tax::class, 'child_tax_id');
    }
}
