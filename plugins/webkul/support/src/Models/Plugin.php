<?php

namespace Webkul\Support\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Spatie\EloquentSortable\Sortable;
use Spatie\EloquentSortable\SortableTrait;

/**
 * Plugin Eloquent model
 *
 * @property int $id
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property string|null $name
 * @property string|null $author
 * @property string|null $summary
 * @property string|null $description
 * @property string|null $latest_version
 * @property string|null $license
 * @property bool $is_active
 * @property bool $is_installed
 * @property string|null $sort
 * @property-read \Illuminate\Database\Eloquent\Collection $dependencies
 * @property-read \Illuminate\Database\Eloquent\Collection $dependents
 *
 */
class Plugin extends Model implements Sortable
{
    use SortableTrait;

    protected $fillable = [
        'name',
        'author',
        'summary',
        'description',
        'latest_version',
        'license',
        'is_active',
        'is_installed',
        'sort',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public $sortable = [
        'order_column_name'  => 'sort',
        'sort_when_creating' => true,
    ];

    /**
     * Dependencies
     *
     * @return BelongsToMany
     */
    public function dependencies(): BelongsToMany
    {
        return $this->belongsToMany(
            Plugin::class,
            'plugin_dependencies',
            'plugin_id',
            'dependency_id'
        );
    }

    /**
     * Dependents
     *
     * @return BelongsToMany
     */
    public function dependents(): BelongsToMany
    {
        return $this->belongsToMany(
            Plugin::class,
            'plugin_dependencies',
            'dependency_id',
            'plugin_id'
        );
    }
}
