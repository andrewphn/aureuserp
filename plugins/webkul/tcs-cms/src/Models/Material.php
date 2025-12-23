<?php

namespace Webkul\TcsCms\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class Material extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'tcs_materials';

    protected $fillable = [
        'name',
        'slug',
        'scientific_name',
        'common_names',
        'type',
        'description',
        'characteristics',
        'featured_image',
        'gallery',
        'content',
        'properties',
        'origin',
        'hardness',
        'density',
        'grain_pattern',
        'color_range',
        'workability',
        'durability',
        'sustainability_rating',
        'sustainability',
        'applications',
        'best_uses',
        'finish_recommendations',
        'is_published',
        'featured',
        'position',
        'meta_title',
        'meta_description',
    ];

    protected $casts = [
        'gallery' => 'array',
        'properties' => 'array',
        'applications' => 'array',
        'best_uses' => 'array',
        'finish_recommendations' => 'array',
        'is_published' => 'boolean',
        'featured' => 'boolean',
    ];

    public function getFeaturedImageUrlAttribute(): ?string
    {
        if (! $this->featured_image) {
            return null;
        }

        return Storage::url($this->featured_image);
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
        return $query->orderBy('position')->orderBy('name');
    }

    public function scopeWoodType($query)
    {
        return $query->where('type', 'wood');
    }
}
