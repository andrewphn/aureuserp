<?php

namespace Webkul\Product\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\EloquentSortable\Sortable;
use Spatie\EloquentSortable\SortableTrait;
use Webkul\Product\Database\Factories\AttributeFactory;
use Webkul\Product\Enums\AttributeType;
use Webkul\Security\Models\User;

/**
 * Attribute Eloquent model
 *
 * @property int $id
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property \Carbon\Carbon|null $deleted_at
 * @property string|null $name
 * @property AttributeType $type
 * @property string|null $sort
 * @property string|null $unit_symbol
 * @property string|null $unit_label
 * @property float|null $min_value
 * @property float|null $max_value
 * @property int $decimal_places
 * @property int $creator_id
 * @property-read \Illuminate\Database\Eloquent\Collection $options
 * @property-read \Illuminate\Database\Eloquent\Model|null $creator
 *
 */
class Attribute extends Model implements Sortable
{
    use HasFactory, SoftDeletes, SortableTrait;

    /**
     * Table name.
     *
     * @var string
     */
    protected $table = 'products_attributes';

    /**
     * Fillable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'type',
        'category',
        'sort',
        'creator_id',
        'unit_symbol',
        'unit_label',
        'min_value',
        'max_value',
        'decimal_places',
        'is_constant',
        'default_value',
    ];

    /**
     * Attribute casts.
     *
     * @var array
     */
    protected $casts = [
        'type'           => AttributeType::class,
        'min_value'      => 'decimal:4',
        'max_value'      => 'decimal:4',
        'decimal_places' => 'integer',
        'is_constant'    => 'boolean',
        'default_value'  => 'decimal:4',
    ];

    public $sortable = [
        'order_column_name'  => 'sort',
        'sort_when_creating' => true,
    ];

    /**
     * Options
     *
     * @return HasMany
     */
    public function options(): HasMany
    {
        return $this->hasMany(AttributeOption::class);
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
     * @return AttributeFactory
     */
    protected static function newFactory(): AttributeFactory
    {
        return AttributeFactory::new();
    }

    /**
     * Check if this attribute stores numeric values (NUMBER or DIMENSION type)
     */
    public function isNumeric(): bool
    {
        return $this->type->isNumeric();
    }

    /**
     * Check if this attribute requires predefined options (RADIO, SELECT, COLOR)
     */
    public function requiresOptions(): bool
    {
        return $this->type->requiresOptions();
    }

    /**
     * Format a numeric value with the attribute's unit symbol
     *
     * @param float|null $value The numeric value to format
     * @return string The formatted value with unit (e.g., "21.5 in")
     */
    public function formatValue(?float $value): string
    {
        if ($value === null) {
            return '';
        }

        $formatted = number_format($value, $this->decimal_places ?? 2);

        return $this->unit_symbol
            ? "{$formatted} {$this->unit_symbol}"
            : $formatted;
    }

    /**
     * Get the label with unit for form fields
     *
     * @return string The attribute name with unit (e.g., "Slide Length (in)")
     */
    public function getLabelWithUnit(): string
    {
        return $this->unit_symbol
            ? "{$this->name} ({$this->unit_symbol})"
            : $this->name;
    }
}
