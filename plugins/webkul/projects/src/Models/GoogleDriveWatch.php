<?php

namespace Webkul\Project\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Google Drive Watch Model
 *
 * Stores active push notification channels for Google Drive folders.
 *
 * @property int $id
 * @property int $project_id
 * @property string $channel_id
 * @property string $resource_id
 * @property string|null $resource_uri
 * @property string $folder_id
 * @property \Carbon\Carbon $expires_at
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class GoogleDriveWatch extends Model
{
    protected $table = 'projects_google_drive_watches';

    protected $fillable = [
        'project_id',
        'channel_id',
        'resource_id',
        'resource_uri',
        'folder_id',
        'expires_at',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
    ];

    /**
     * Get the project this watch belongs to
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * Check if the watch is expired
     */
    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    /**
     * Check if the watch is expiring soon (within 24 hours)
     */
    public function isExpiringSoon(): bool
    {
        return $this->expires_at->isBefore(now()->addDay());
    }

    /**
     * Scope to get active (non-expired) watches
     */
    public function scopeActive($query)
    {
        return $query->where('expires_at', '>', now());
    }

    /**
     * Scope to get expiring soon watches
     */
    public function scopeExpiringSoon($query)
    {
        return $query->where('expires_at', '<=', now()->addDay())
                     ->where('expires_at', '>', now());
    }
}
