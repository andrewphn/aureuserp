<?php

namespace Webkul\Support\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Measurement Unit Model
 *
 * Stores unit definitions and conversion factors for measurements.
 * All measurements are stored in inches, and this table provides
 * conversion factors to/from other units.
 *
 * @property int $id
 * @property string $unit_code Unique unit code (e.g., in, ft, yd, mm, cm, m)
 * @property string $unit_name Full unit name (e.g., inches, feet, yards)
 * @property string $unit_symbol Unit symbol for display (e.g., in, ft, yd)
 * @property string $unit_type Type: linear, area, volume, weight
 * @property float $conversion_factor Conversion factor to inches
 * @property bool $is_base_unit Whether this is the base unit (inches)
 * @property int $display_order Display order in selectors
 * @property bool $is_active Whether unit is active
 * @property string|null $description Unit description
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property \Carbon\Carbon|null $deleted_at
 */
class Measurement extends Model
{
    use SoftDeletes;

    protected $table = 'measurements';

    protected $fillable = [
        'unit_code',
        'unit_name',
        'unit_symbol',
        'unit_type',
        'conversion_factor',
        'is_base_unit',
        'display_order',
        'is_active',
        'description',
    ];

    protected $casts = [
        'conversion_factor' => 'float',
        'is_base_unit' => 'boolean',
        'display_order' => 'integer',
        'is_active' => 'boolean',
    ];

    /**
     * Get active units by type
     */
    public static function getActiveByType(string $type): \Illuminate\Database\Eloquent\Collection
    {
        return static::where('unit_type', $type)
            ->where('is_active', true)
            ->orderBy('display_order')
            ->get();
    }

    /**
     * Get all active linear units
     */
    public static function getActiveLinearUnits(): \Illuminate\Database\Eloquent\Collection
    {
        return static::getActiveByType('linear');
    }

    /**
     * Convert value from this unit to inches
     */
    public function toInches(float $value): float
    {
        if ($this->is_base_unit) {
            return $value;
        }

        return $value * $this->conversion_factor;
    }

    /**
     * Convert value from inches to this unit
     */
    public function fromInches(float $inches): float
    {
        if ($this->is_base_unit) {
            return $inches;
        }

        return $inches / $this->conversion_factor;
    }
}
