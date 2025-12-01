<?php

namespace Webkul\Project\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\EloquentSortable\Sortable;
use Spatie\EloquentSortable\SortableTrait;
use Webkul\Chatter\Traits\HasLogActivity;
use Webkul\Project\Database\Factories\ProjectInspirationImageFactory;
use Webkul\Security\Models\User;

/**
 * ProjectInspirationImage Model
 *
 * Represents inspiration images uploaded for projects.
 * Images can be annotated using Nutrient similar to PDF documents.
 *
 * @property int $id
 * @property int $project_id
 * @property string $file_name
 * @property string $file_path
 * @property int $file_size
 * @property string $mime_type
 * @property int|null $width
 * @property int|null $height
 * @property int $uploaded_by
 * @property string|null $description
 * @property array|null $tags
 * @property array|null $metadata
 * @property int $sort_order
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property \Carbon\Carbon|null $deleted_at
 */
class ProjectInspirationImage extends Model implements Sortable
{
    use HasFactory, HasLogActivity, SoftDeletes, SortableTrait;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'project_inspiration_images';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'project_id',
        'room_id',
        'file_name',
        'title',
        'file_path',
        'file_size',
        'mime_type',
        'width',
        'height',
        'uploaded_by',
        'description',
        'tags',
        'metadata',
        'sort_order',
    ];

    /**
     * Attributes to log for activity tracking.
     *
     * @var array
     */
    protected array $logAttributes = [
        'file_name',
        'description',
        'file_size',
        'uploader.name' => 'Uploaded By',
    ];

    /**
     * Sortable configuration.
     *
     * @var array
     */
    public $sortable = [
        'order_column_name'  => 'sort_order',
        'sort_when_creating' => true,
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
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'deleted_at' => 'datetime',
        ];
    }

    /**
     * Get the project that owns this inspiration image.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * Get the room this image is associated with (optional).
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class, 'room_id');
    }

    /**
     * Get the user who uploaded this image.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    /**
     * Scope a query to only include images for a specific project.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int $projectId
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeForProject($query, int $projectId)
    {
        return $query->where('project_id', $projectId);
    }

    /**
     * Scope a query to filter images by room.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int|null $roomId
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeForRoom($query, ?int $roomId)
    {
        if ($roomId === null) {
            return $query;
        }

        return $query->where('room_id', $roomId);
    }

    /**
     * Scope a query to only include images without a room.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeUnassigned($query)
    {
        return $query->whereNull('room_id');
    }

    /**
     * Scope a query to only include images uploaded by a specific user.
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
     * Scope a query to get recently uploaded images.
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
     * Get the dimensions string (e.g., "1920x1080").
     *
     * @return string|null
     */
    public function getDimensionsAttribute(): ?string
    {
        if ($this->width && $this->height) {
            return "{$this->width}x{$this->height}";
        }

        return null;
    }

    /**
     * Build group for sortable trait.
     */
    public function buildSortQuery()
    {
        return static::query()->where('project_id', $this->project_id);
    }

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): ProjectInspirationImageFactory
    {
        return ProjectInspirationImageFactory::new();
    }
}
