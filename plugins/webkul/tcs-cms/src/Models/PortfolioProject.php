<?php

namespace Webkul\TcsCms\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;
use Webkul\Security\Models\User;

class PortfolioProject extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'tcs_portfolio_projects';

    protected $fillable = [
        'title',
        'slug',
        'summary',
        'description',
        'category',
        'project_type',
        'style_classification',
        'client_name',
        'client_type',
        'location',
        'materials',
        'techniques',
        'dimensions',
        'hardware_specifications',
        'square_footage',
        'weight',
        'overview',
        'discovery',
        'design',
        'production',
        'installation',
        'client_testimonial',
        'featured_image',
        'cover_image',
        'gallery',
        'process_photos',
        'before_after_images',
        'featured',
        'is_published',
        'published_at',
        'status',
        'completion_date',
        'portfolio_order',
        'timeline',
        'meta_title',
        'meta_description',
        'seo_tags',
        'creator_id',
    ];

    protected $casts = [
        'materials' => 'array',
        'techniques' => 'array',
        'dimensions' => 'array',
        'hardware_specifications' => 'array',
        'gallery' => 'array',
        'process_photos' => 'array',
        'before_after_images' => 'array',
        'seo_tags' => 'array',
        'featured' => 'boolean',
        'is_published' => 'boolean',
        'published_at' => 'datetime',
        'completion_date' => 'date',
        'square_footage' => 'decimal:2',
        'weight' => 'decimal:2',
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

        return Storage::url($this->featured_image);
    }

    public function getCoverImageUrlAttribute(): ?string
    {
        if (! $this->cover_image) {
            return null;
        }

        // If path starts with images/ or /images/, it's in public folder
        $path = ltrim($this->cover_image, '/');
        if (str_starts_with($path, 'images/')) {
            return '/' . $path;
        }

        return Storage::url($this->cover_image);
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
        return $query->orderBy('portfolio_order')->orderBy('created_at', 'desc');
    }
}
