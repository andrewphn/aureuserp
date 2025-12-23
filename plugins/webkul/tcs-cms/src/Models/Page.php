<?php

namespace Webkul\TcsCms\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;
use Webkul\Security\Models\User;
use Webkul\TcsCms\Filament\Blocks\PageBlocks;

class Page extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'tcs_cms_pages';

    protected $fillable = [
        'title',
        'slug',
        'content',
        'featured_image',
        'gallery',
        'blocks',
        'layout',
        'is_published',
        'status',
        'template',
        'show_in_navigation',
        'position',
        'meta_title',
        'meta_description',
        'creator_id',
    ];

    protected $casts = [
        'blocks' => 'array',
        'gallery' => 'array',
        'is_published' => 'boolean',
        'show_in_navigation' => 'boolean',
        'position' => 'integer',
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

    public function scopeInNavigation($query)
    {
        return $query->where('show_in_navigation', true)
            ->orderBy('position');
    }

    public function scopeByLayout($query, string $layout)
    {
        return $query->where('layout', $layout);
    }

    /**
     * Get mutated blocks data ready for frontend rendering.
     */
    public function getMutatedBlocksAttribute(): array
    {
        if (empty($this->blocks)) {
            return [];
        }

        return collect($this->blocks)->map(function ($block) {
            $type = $block['type'] ?? '';
            $data = $block['data'] ?? [];

            return [
                'type' => $type,
                'data' => PageBlocks::mutateData($type, $data),
            ];
        })->all();
    }

    /**
     * Find page by slug.
     */
    public static function findBySlug(string $slug): ?self
    {
        return static::where('slug', $slug)->first();
    }

    /**
     * Get the homepage.
     */
    public static function homepage(): ?self
    {
        return static::where('slug', 'home')
            ->orWhere('slug', 'homepage')
            ->orWhere('layout', 'homepage')
            ->first();
    }
}
