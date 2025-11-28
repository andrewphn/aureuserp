<?php

namespace Webkul\TableViews\Models;

use Illuminate\Database\Eloquent\Model;
use Webkul\Security\Models\User;

/**
 * Table View Eloquent model
 *
 * @property int $id
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property string|null $name
 * @property string|null $icon
 * @property string|null $color
 * @property bool $is_public
 * @property array $filters
 * @property string|null $filterable_type
 * @property int $user_id
 * @property-read \Illuminate\Database\Eloquent\Model|null $user
 *
 */
class TableView extends Model
{
    /**
     * Fillable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'icon',
        'color',
        'is_public',
        'filters',
        'filterable_type',
        'user_id',
    ];

    /**
     * Table name.
     *
     * @var string
     */
    protected $casts = [
        'filters' => 'array',
    ];

    /**
     * Get the user that owns the saved filter.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
