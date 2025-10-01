<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * PdfAnnotation Model
 *
 * Represents an annotation on a PDF page, such as highlights, text comments,
 * drawings, or other markup added by users.
 *
 * @property int $id
 * @property int $document_id
 * @property int $page_number
 * @property string $annotation_type
 * @property array $annotation_data
 * @property int $author_id
 * @property string $author_name
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property \Carbon\Carbon|null $deleted_at
 */
class PdfAnnotation extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * Supported annotation types.
     */
    const TYPE_HIGHLIGHT = 'highlight';
    const TYPE_TEXT = 'text';
    const TYPE_DRAWING = 'drawing';
    const TYPE_ARROW = 'arrow';
    const TYPE_RECTANGLE = 'rectangle';
    const TYPE_CIRCLE = 'circle';
    const TYPE_STAMP = 'stamp';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'document_id',
        'page_number',
        'annotation_type',
        'annotation_data',
        'author_id',
        'author_name',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'annotation_data' => 'array',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'deleted_at' => 'datetime',
        ];
    }

    /**
     * Get the document that this annotation belongs to.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function document(): BelongsTo
    {
        return $this->belongsTo(PdfDocument::class, 'document_id');
    }

    /**
     * Get the page that this annotation is on.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function page(): BelongsTo
    {
        return $this->belongsTo(PdfPage::class, 'document_id')
                    ->where('page_number', $this->page_number);
    }

    /**
     * Get the user who created this annotation.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    /**
     * Scope a query to only include annotations by a specific author.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int $userId
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByAuthor($query, int $userId)
    {
        return $query->where('author_id', $userId);
    }

    /**
     * Scope a query to only include annotations of a specific type.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $type
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByType($query, string $type)
    {
        return $query->where('annotation_type', $type);
    }

    /**
     * Scope a query to only include annotations for a specific page.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int $pageNumber
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeForPage($query, int $pageNumber)
    {
        return $query->where('page_number', $pageNumber);
    }

    /**
     * Check if this is a specific annotation type.
     *
     * @param string $type
     * @return bool
     */
    public function isType(string $type): bool
    {
        return $this->annotation_type === $type;
    }

    /**
     * Get the color from annotation data if available.
     *
     * @return string|null
     */
    public function getColor(): ?string
    {
        return $this->annotation_data['color'] ?? null;
    }

    /**
     * Get the position coordinates from annotation data.
     *
     * @return array|null
     */
    public function getPosition(): ?array
    {
        return $this->annotation_data['position'] ?? null;
    }

    /**
     * Get the text content from annotation data if available.
     *
     * @return string|null
     */
    public function getText(): ?string
    {
        return $this->annotation_data['text'] ?? null;
    }
}
