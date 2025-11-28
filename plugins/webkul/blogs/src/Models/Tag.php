<?php

namespace Webkul\Blog\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\EloquentSortable\Sortable;
use Spatie\EloquentSortable\SortableTrait;
use Webkul\Blog\Database\Factories\TagFactory;
use Webkul\Security\Models\User;

/**
 * Tag Eloquent model
 *
 * @property int $id
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property \Carbon\Carbon|null $deleted_at
 * @property string|null $name
 * @property string|null $color
 * @property string|null $sort
 * @property int $creator_id
 * @property-read \Illuminate\Database\Eloquent\Model|null $creator
 *
 */
class Tag extends Model implements Sortable
{
    use HasFactory, SoftDeletes, SortableTrait;

    /**
     * Table name.
     *
     * @var string
     */
    protected $table = 'blogs_tags';

    /**
     * Fillable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'color',
        'sort',
        'creator_id',
    ];

    public $sortable = [
        'order_column_name'  => 'sort',
        'sort_when_creating' => true,
    ];

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
     * @return TagFactory
     */
    protected static function newFactory(): TagFactory
    {
        return TagFactory::new();
    }
}
