<?php

namespace Webkul\Product\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Webkul\Product\Database\Factories\TagFactory;
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
 * @property int $creator_id
 * @property-read \Illuminate\Database\Eloquent\Model|null $creator
 *
 */
class Tag extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * Table name.
     *
     * @var string
     */
    protected $table = 'products_tags';

    /**
     * Fillable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'color',
        'creator_id',
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
