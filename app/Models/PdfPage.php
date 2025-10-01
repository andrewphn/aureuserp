<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * PdfPage Model
 *
 * Represents a single page within a PDF document, including metadata,
 * thumbnail paths, and extracted text content.
 *
 * @property int $id
 * @property int $document_id
 * @property int $page_number
 * @property string|null $thumbnail_path
 * @property string|null $extracted_text
 * @property array|null $page_metadata
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class PdfPage extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'document_id',
        'page_number',
        'thumbnail_path',
        'extracted_text',
        'page_metadata',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'page_metadata' => 'array',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    /**
     * Get the document that owns this page.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function document(): BelongsTo
    {
        return $this->belongsTo(PdfDocument::class, 'document_id');
    }

    /**
     * Get all annotations for this page.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function annotations(): HasMany
    {
        return $this->hasMany(PdfAnnotation::class, 'document_id')
                    ->where('page_number', $this->page_number);
    }

    /**
     * Get the full URL for the thumbnail image.
     *
     * @return string|null
     */
    public function getThumbnailUrlAttribute(): ?string
    {
        if (!$this->thumbnail_path) {
            return null;
        }

        // If the path is already a full URL, return it
        if (str_starts_with($this->thumbnail_path, 'http')) {
            return $this->thumbnail_path;
        }

        // Otherwise, generate a storage URL
        return asset('storage/' . $this->thumbnail_path);
    }

    /**
     * Check if this page has extracted text.
     *
     * @return bool
     */
    public function hasExtractedText(): bool
    {
        return !empty($this->extracted_text);
    }

    /**
     * Get a preview of the extracted text (first N characters).
     *
     * @param int $length
     * @return string|null
     */
    public function getTextPreview(int $length = 200): ?string
    {
        if (!$this->extracted_text) {
            return null;
        }

        return strlen($this->extracted_text) > $length
            ? substr($this->extracted_text, 0, $length) . '...'
            : $this->extracted_text;
    }
}
