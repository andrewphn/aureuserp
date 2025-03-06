<?php

namespace Webkul\Blog\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Webkul\Security\Models\User;

class Category extends Model
{
    /**
     * Table name.
     *
     * @var string
     */
    protected $table = 'blogs_categories';

    public function blogs(): HasMany
    {
        return $this->hasMany(Blog::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
