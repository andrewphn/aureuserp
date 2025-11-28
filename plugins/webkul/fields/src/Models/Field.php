<?php

namespace Webkul\Field\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\EloquentSortable\Sortable;
use Spatie\EloquentSortable\SortableTrait;

/**
 * Field Eloquent model
 *
 * @property int $id
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property \Carbon\Carbon|null $deleted_at
 * @property string|null $code
 * @property string|null $name
 * @property string|null $type
 * @property string|null $input_type
 * @property bool $is_multiselect
 * @property string|null $datalist
 * @property array $options
 * @property array $form_settings
 * @property string|null $use_in_table
 * @property array $table_settings
 * @property array $infolist_settings
 * @property string|null $sort
 * @property string|null $customizable_type
 *
 */
class Field extends Model implements Sortable
{
    use SoftDeletes, SortableTrait;

    /**
     * Table name.
     *
     * @var string
     */
    protected $table = 'custom_fields';

    /**
     * Table name.
     *
     * @var string
     */
    protected $casts = [
        'is_multiselect'    => 'boolean',
        'options'           => 'array',
        'form_settings'     => 'array',
        'table_settings'    => 'array',
        'infolist_settings' => 'array',
    ];

    /**
     * Fillable.
     *
     * @var array
     */
    protected $fillable = [
        'code',
        'name',
        'type',
        'input_type',
        'is_multiselect',
        'datalist',
        'options',
        'form_settings',
        'use_in_table',
        'table_settings',
        'infolist_settings',
        'sort',
        'customizable_type',
    ];

    public $sortable = [
        'order_column_name'  => 'sort',
        'sort_when_creating' => true,
    ];
}
