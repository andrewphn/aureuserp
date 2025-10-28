<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
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
        'version_number',
        'previous_version_id',
        'is_latest_version',
        'version_metadata',
        'document_type',
        'notes',
        'uploaded_by',
        'tags',
        'metadata',
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
            'version_metadata' => 'array',
            'is_latest_version' => 'boolean',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'deleted_at' => 'datetime',
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
     * Get all tags for this document.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function documentTags()
    {
        return $this->belongsToMany(\Webkul\Project\Models\Tag::class, 'pdf_document_tag', 'pdf_document_id', 'tag_id')
                    ->withTimestamps();
    }

    /**
     * Get all annotations for this document through its pages.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasManyThrough
     */
    public function annotations(): HasManyThrough
    {
        return $this->hasManyThrough(
            PdfPageAnnotation::class,  // Final model
            PdfPage::class,             // Intermediate model
            'document_id',              // FK on pdf_pages table
            'pdf_page_id',              // FK on pdf_page_annotations table
            'id',                       // PK on pdf_documents table
            'id'                        // PK on pdf_pages table
        );
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
     * Get the previous version of this document.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function previousVersion(): BelongsTo
    {
        return $this->belongsTo(PdfDocument::class, 'previous_version_id');
    }

    /**
     * Get the next version(s) of this document.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function nextVersions(): HasMany
    {
        return $this->hasMany(PdfDocument::class, 'previous_version_id');
    }

    /**
     * Get all versions of this document (including itself).
     *
     * @return \Illuminate\Support\Collection
     */
    public function getAllVersions()
    {
        // Find the root (first) version
        $root = $this;
        while ($root->previous_version_id) {
            $root = $root->previousVersion;
        }

        // Build chain from root
        $versions = collect([$root]);
        $current = $root;

        while ($nextVersion = $current->nextVersions()->first()) {
            $versions->push($nextVersion);
            $current = $nextVersion;
        }

        return $versions;
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
}
