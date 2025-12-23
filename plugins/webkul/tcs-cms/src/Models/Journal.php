<?php

namespace Webkul\TcsCms\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;
use Webkul\Security\Models\User;

class Journal extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'tcs_journals';

    protected $fillable = [
        'title',
        'slug',
        'excerpt',
        'content',
        'blocks',
        'featured_image',
        'gallery',
        'category',
        'tags',
        'is_published',
        'published_at',
        'status',
        'read_time',
        'view_count',
        'meta_title',
        'meta_description',
        'author_id',
    ];

    protected $casts = [
        'blocks' => 'array',
        'gallery' => 'array',
        'tags' => 'array',
        'is_published' => 'boolean',
        'published_at' => 'datetime',
    ];

    public function getFeaturedImageUrlAttribute(): ?string
    {
        if (! $this->featured_image) {
            return null;
        }

        // If path starts with images/ or /images/, it's in public folder
        $path = ltrim($this->featured_image, '/');
        if (str_starts_with($path, 'images/')) {
            return '/' . $path;
        }

        // Otherwise use storage
        return Storage::url($this->featured_image);
    }

    public function getReadingTimeAttribute(): string
    {
        $wordCount = str_word_count(strip_tags($this->content ?? ''));
        $minutes = max(1, ceil($wordCount / 200));

        return $minutes.' min read';
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    public function scopePublished($query)
    {
        return $query->where('is_published', true)
            ->where('published_at', '<=', now());
    }

    public function scopeRecent($query)
    {
        return $query->orderBy('published_at', 'desc');
    }

    public function incrementViewCount(): void
    {
        $this->increment('view_count');
    }
}
