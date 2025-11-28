<?php

namespace Webkul\Blog\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;
use Webkul\Blog\Database\Factories\CategoryFactory;
use Webkul\Security\Models\User;

/**
 * Category Eloquent model
 *
 * @property int $id
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property \Carbon\Carbon|null $deleted_at
 * @property string|null $name
 * @property string|null $sub_title
 * @property string|null $slug
 * @property string|null $image
 * @property string|null $meta_title
 * @property string|null $meta_keywords
 * @property string|null $meta_description
 * @property int $creator_id
 * @property-read \Illuminate\Database\Eloquent\Collection $posts
 * @property-read \Illuminate\Database\Eloquent\Model|null $creator
 *
 */
class Category extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * Table name.
     *
     * @var string
     */
    protected $table = 'blogs_categories';

    /**
     * Fillable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'sub_title',
        'slug',
        'image',
        'meta_title',
        'meta_keywords',
        'meta_description',
        'creator_id',
    ];

    /**
     * Get image url for the product image.
     *
     * @return string
     */
    public function getImageUrlAttribute()
    {
        if (! $this->image) {
            return null;
        }

        return Storage::url($this->image);
    }

    /**
     * Posts
     *
     * @return HasMany
     */
    public function posts(): HasMany
    {
        return $this->hasMany(Post::class);
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
     * @return CategoryFactory
     */
    protected static function newFactory(): CategoryFactory
    {
        return CategoryFactory::new();
    }
}
