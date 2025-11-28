<?php

namespace Webkul\Website\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Webkul\Security\Models\User;
use Webkul\Website\Database\Factories\PageFactory;

/**
 * Page Eloquent model
 *
 * @property int $id
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property \Carbon\Carbon|null $deleted_at
 * @property string|null $title
 * @property string|null $content
 * @property string|null $slug
 * @property bool $is_published
 * @property \Carbon\Carbon|null $published_at
 * @property bool $is_header_visible
 * @property bool $is_footer_visible
 * @property string|null $meta_title
 * @property string|null $meta_keywords
 * @property string|null $meta_description
 * @property int $creator_id
 * @property-read \Illuminate\Database\Eloquent\Model|null $creator
 *
 */
class Page extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * Table name.
     *
     * @var string
     */
    protected $table = 'website_pages';

    /**
     * Fillable.
     *
     * @var array
     */
    protected $fillable = [
        'title',
        'content',
        'slug',
        'is_published',
        'published_at',
        'is_header_visible',
        'is_footer_visible',
        'meta_title',
        'meta_keywords',
        'meta_description',
        'creator_id',
    ];

    /**
     * Table name.
     *
     * @var string
     */
    protected $casts = [
        'is_published'      => 'boolean',
        'is_header_visible' => 'boolean',
        'is_footer_visible' => 'boolean',
        'published_at'      => 'datetime',
    ];

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
     * @return PageFactory
     */
    protected static function newFactory(): PageFactory
    {
        return PageFactory::new();
    }
}
