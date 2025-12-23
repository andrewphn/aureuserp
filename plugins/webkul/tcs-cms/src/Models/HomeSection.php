<?php

namespace Webkul\TcsCms\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class HomeSection extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'tcs_home_sections';

    protected $fillable = [
        'section_key',
        'section_type',
        'title',
        'subtitle',
        'content',
        'cta_text',
        'cta_link',
        'image',
        'background_image',
        'layout_style',
        'additional_images',
        'service_items',
        'testimonial_items',
        'author_info',
        'process_steps',
        'settings',
        'is_active',
        'position',
    ];

    protected $casts = [
        'additional_images' => 'array',
        'service_items' => 'array',
        'testimonial_items' => 'array',
        'author_info' => 'array',
        'process_steps' => 'array',
        'settings' => 'array',
        'is_active' => 'boolean',
    ];

    public const SECTION_TYPES = [
        'hero' => 'Hero Section',
        'projects' => 'Featured Projects',
        'journal' => 'Journal/Blog Feed',
        'process' => 'Our Process',
        'services' => 'Services Showcase',
        'testimonials' => 'Testimonials',
        'owner_note' => 'Owner\'s Note',
        'contact' => 'Contact CTA',
        'custom' => 'Custom Content',
    ];

    public const LAYOUT_STYLES = [
        'default' => 'Default',
        'centered' => 'Centered',
        'full_width' => 'Full Width',
        'split' => 'Split Layout',
    ];

    public function getImageUrlAttribute(): ?string
    {
        if (! $this->image) {
            return null;
        }

        return Storage::url($this->image);
    }

    public function getBackgroundImageUrlAttribute(): ?string
    {
        if (! $this->background_image) {
            return null;
        }

        return Storage::url($this->background_image);
    }

    public function getSectionTypeLabelAttribute(): string
    {
        return self::SECTION_TYPES[$this->section_type] ?? $this->section_type ?? 'Custom';
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('position');
    }

    public function scopeByKey($query, string $key)
    {
        return $query->where('section_key', $key);
    }
}
