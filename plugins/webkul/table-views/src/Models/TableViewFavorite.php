<?php

namespace Webkul\TableViews\Models;

use Illuminate\Database\Eloquent\Model;
use Webkul\Security\Models\User;

/**
 * Table View Favorite Eloquent model
 *
 * @property int $id
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property bool $is_favorite
 * @property string|null $view_type
 * @property string|null $view_key
 * @property string|null $filterable_type
 * @property int $user_id
 * @property-read \Illuminate\Database\Eloquent\Model|null $user
 *
 */
class TableViewFavorite extends Model
{
    /**
     * Fillable.
     *
     * @var array
     */
    protected $fillable = [
        'is_favorite',
        'view_type',
        'view_key',
        'filterable_type',
        'user_id',
    ];

    /**
     * Get the user that owns the saved filter.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
