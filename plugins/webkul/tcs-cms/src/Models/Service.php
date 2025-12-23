<?php

namespace Webkul\TcsCms\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;
use Webkul\Security\Models\User;

class Service extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'tcs_services';

    protected $fillable = [
        'title',
        'slug',
        'summary',
        'description',
        'icon',
        'category',
        'features',
        'price_range',
        'timeline',
        'featured_image',
        'gallery',
        'process_steps',
        'faq_content',
        'is_published',
        'published_at',
        'featured',
        'status',
        'position',
        'meta_title',
        'meta_description',
        'creator_id',
    ];

    protected $casts = [
        'features' => 'array',
        'gallery' => 'array',
        'process_steps' => 'array',
        'is_published' => 'boolean',
        'published_at' => 'datetime',
        'featured' => 'boolean',
    ];

    public function getFeaturedImageUrlAttribute(): ?string
    {
        if (! $this->featured_image) {
            return null;
        }

        return Storage::url($this->featured_image);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creator_id');
    }

    public function scopePublished($query)
    {
        return $query->where('is_published', true);
    }

    public function scopeFeatured($query)
    {
        return $query->where('featured', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('position')->orderBy('title');
    }
}
