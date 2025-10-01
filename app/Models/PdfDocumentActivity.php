<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * PdfDocumentActivity Model
 *
 * Tracks user activities on PDF documents, such as viewing, downloading,
 * annotating, and other interactions for audit and analytics purposes.
 *
 * @property int $id
 * @property int $document_id
 * @property int $user_id
 * @property string $action_type
 * @property array|null $action_details
 * @property \Carbon\Carbon $created_at
 */
class PdfDocumentActivity extends Model
{
    use HasFactory;

    /**
     * Activity types.
     */
    const ACTION_VIEWED = 'viewed';
    const ACTION_DOWNLOADED = 'downloaded';
    const ACTION_ANNOTATED = 'annotated';
    const ACTION_UPLOADED = 'uploaded';
    const ACTION_DELETED = 'deleted';
    const ACTION_SHARED = 'shared';
    const ACTION_PRINTED = 'printed';

    /**
     * Indicates if the model should be timestamped.
     * Only uses created_at, no updated_at.
     *
     * @var bool
     */
    public $timestamps = false;

    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    protected $dates = ['created_at'];

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'document_id',
        'user_id',
        'action_type',
        'action_details',
        'created_at',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'action_details' => 'array',
            'created_at' => 'datetime',
        ];
    }

    /**
     * Boot the model and set created_at on creating.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (!$model->created_at) {
                $model->created_at = now();
            }
        });
    }

    /**
     * Get the document that this activity belongs to.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function document(): BelongsTo
    {
        return $this->belongsTo(PdfDocument::class, 'document_id');
    }

    /**
     * Get the user who performed this activity.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Scope a query to only include activities of a specific type.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $type
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByAction($query, string $type)
    {
        return $query->where('action_type', $type);
    }

    /**
     * Scope a query to get recent activities.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int $limit
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeRecentActivity($query, int $limit = 20)
    {
        return $query->orderBy('created_at', 'desc')->limit($limit);
    }

    /**
     * Scope a query to get activities within a date range.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param \Carbon\Carbon $startDate
     * @param \Carbon\Carbon $endDate
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeBetweenDates($query, $startDate, $endDate)
    {
        return $query->whereBetween('created_at', [$startDate, $endDate]);
    }

    /**
     * Create a new activity log entry.
     *
     * @param int $documentId
     * @param int $userId
     * @param string $actionType
     * @param array|null $details
     * @return self
     */
    public static function log(int $documentId, int $userId, string $actionType, ?array $details = null): self
    {
        return self::create([
            'document_id' => $documentId,
            'user_id' => $userId,
            'action_type' => $actionType,
            'action_details' => $details,
        ]);
    }
}
