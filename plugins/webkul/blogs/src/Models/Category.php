<?php

namespace Webkul\Blog\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Webkul\Security\Models\User;
use Illuminate\Database\Eloquent\SoftDeletes;

class Category extends Model
{
    use SoftDeletes;

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
        return Storage::url($this->image);
    }

    public function posts(): HasMany
    {
        return $this->hasMany(Post::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
