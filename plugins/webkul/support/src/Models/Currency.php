<?php

namespace Webkul\Support\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Currency Eloquent model
 *
 * @property int $id
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property string|null $code
 * @property string|null $name
 * @property string|null $symbol
 * @property string|null $iso_numeric
 * @property string|null $decimal_places
 * @property string|null $full_name
 * @property string|null $rounding
 * @property string|null $active
 *
 */
class Currency extends Model
{
    protected $fillable = [
        'code',
        'name',
        'symbol',
        'iso_numeric',
        'decimal_places',
        'full_name',
        'rounding',
        'active',
    ];
}
