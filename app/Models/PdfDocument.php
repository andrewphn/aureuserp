<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Webkul\Chatter\Traits\HasChatter;
use Webkul\Chatter\Traits\HasLogActivity;

/**
 * PdfDocument Model
 *
 * Represents a PDF document uploaded to the system and associated with various modules
 * (projects, quotations, etc.) through polymorphic relationships.
 *
 * @property int $id
 * @property string $module_type
 * @property int $module_id
 * @property string $file_name
 * @property string $file_path
 * @property int $file_size
 * @property string $mime_type
 * @property int $page_count
 * @property int $uploaded_by
 * @property array|null $tags
 * @property array|null $metadata
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property \Carbon\Carbon|null $deleted_at
 */
class PdfDocument extends Model
{
    use HasFactory, SoftDeletes, HasChatter, HasLogActivity;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'module_type',
        'module_id',
        'file_name',
        'file_path',
        'file_size',
        'mime_type',
        'page_count',
        'document_type',
        'notes',
        'uploaded_by',
        'tags',
        'metadata',
        'processing_status',
        'processing_error',
        'processed_at',
        'extracted_metadata',
        'metadata_reviewed',
        'extracted_at',
    ];

    /**
     * Attributes to log for activity tracking.
     *
     * @var array
     */
    protected array $logAttributes = [
        'file_name',
        'file_size',
        'document_type',
        'uploader.name' => 'Uploaded By',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'tags' => 'array',
            'metadata' => 'array',
            'extracted_metadata' => 'array',
            'metadata_reviewed' => 'boolean',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'deleted_at' => 'datetime',
            'processed_at' => 'datetime',
            'extracted_at' => 'datetime',
        ];
    }

    /**
     * Get all pages for this document.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function pages(): HasMany
    {
        return $this->hasMany(PdfPage::class, 'document_id');
    }

    /**
     * Get all annotations for this document.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function annotations(): HasMany
    {
        return $this->hasMany(PdfAnnotation::class, 'document_id');
    }

    /**
     * Get all activities for this document.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function activities(): HasMany
    {
        return $this->hasMany(PdfDocumentActivity::class, 'document_id');
    }

    /**
     * Get the user who uploaded this document.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    /**
     * Get the parent module (polymorphic relationship).
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphTo
     */
    public function module(): MorphTo
    {
        return $this->morphTo(__FUNCTION__, 'module_type', 'module_id');
    }

    /**
     * Scope a query to only include documents for a specific module.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $type
     * @param int $id
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeForModule($query, string $type, int $id)
    {
        return $query->where('module_type', $type)
                     ->where('module_id', $id);
    }

    /**
     * Scope a query to only include documents uploaded by a specific user.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int $userId
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByUploader($query, int $userId)
    {
        return $query->where('uploaded_by', $userId);
    }

    /**
     * Scope a query to get recently uploaded documents.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int $limit
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeRecent($query, int $limit = 10)
    {
        return $query->orderBy('created_at', 'desc')->limit($limit);
    }

    /**
     * Get the file size in a human-readable format.
     *
     * @return string
     */
    public function getFormattedFileSizeAttribute(): string
    {
        $bytes = $this->file_size;
        $units = ['B', 'KB', 'MB', 'GB'];

        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, 2) . ' ' . $units[$i];
    }

    /**
     * Check if document is processed
     *
     * @return bool
     */
    public function isProcessed(): bool
    {
        return $this->processing_status === 'completed';
    }

    /**
     * Check if document needs reprocessing
     *
     * @return bool
     */
    public function needsReprocessing(): bool
    {
        return in_array($this->processing_status, ['pending', 'failed']);
    }

    /**
     * Mark document as processing
     *
     * @return void
     */
    public function markAsProcessing(): void
    {
        $this->update([
            'processing_status' => 'processing',
            'processing_error' => null,
        ]);
    }

    /**
     * Mark document as completed
     *
     * @return void
     */
    public function markAsCompleted(): void
    {
        $this->update([
            'processing_status' => 'completed',
            'processing_error' => null,
            'processed_at' => now(),
        ]);
    }

    /**
     * Mark document as failed
     *
     * @param string $error
     * @return void
     */
    public function markAsFailed(string $error): void
    {
        $this->update([
            'processing_status' => 'failed',
            'processing_error' => $error,
        ]);
    }

    /**
     * Scope to get processed documents
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeProcessed($query)
    {
        return $query->where('processing_status', 'completed');
    }

    /**
     * Scope to get pending documents
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopePending($query)
    {
        return $query->where('processing_status', 'pending');
    }

    /**
     * Scope to get failed documents
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeFailed($query)
    {
        return $query->where('processing_status', 'failed');
    }
}
