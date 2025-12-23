<?php

namespace Webkul\TcsCms\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Faq extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'tcs_faqs';

    protected $fillable = [
        'question',
        'answer',
        'category',
        'status',
        'is_published',
        'view_count',
        'helpful_count',
        'position',
    ];

    protected $casts = [
        'is_published' => 'boolean',
    ];

    public const CATEGORIES = [
        'general' => 'General Questions',
        'process' => 'Process & Timeline',
        'materials' => 'Materials & Quality',
        'pricing' => 'Pricing & Estimates',
        'maintenance' => 'Maintenance & Care',
        'technical' => 'Technical Questions',
        'installation' => 'Installation',
        'warranty' => 'Warranty & Service',
        'design' => 'Design & Customization',
        'project' => 'Project-Specific',
    ];

    public function getCategoryLabelAttribute(): string
    {
        return self::CATEGORIES[$this->category] ?? $this->category ?? 'General';
    }

    public function scopePublished($query)
    {
        return $query->where('is_published', true);
    }

    public function scopeByCategory($query, string $category)
    {
        return $query->where('category', $category);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('position')->orderBy('created_at');
    }

    public function incrementViewCount(): void
    {
        $this->increment('view_count');
    }

    public function incrementHelpfulCount(): void
    {
        $this->increment('helpful_count');
    }
}
