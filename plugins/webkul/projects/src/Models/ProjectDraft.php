<?php

namespace Webkul\Project\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\User;
use Carbon\Carbon;

class ProjectDraft extends Model
{
    protected $table = 'projects_project_drafts';

    protected $fillable = [
        'user_id',
        'session_id',
        'current_step',
        'form_data',
        'expires_at',
    ];

    protected $casts = [
        'form_data' => 'array',
        'expires_at' => 'datetime',
    ];

    /**
     * Get the user that owns the draft.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope to get active drafts (not expired).
     */
    public function scopeActive($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('expires_at')
              ->orWhere('expires_at', '>', now());
        });
    }

    /**
     * Scope to get drafts for current session.
     */
    public function scopeForCurrentSession($query)
    {
        return $query->where('session_id', session()->getId());
    }

    /**
     * Set expiration date (default 7 days from now).
     */
    public function setExpiration(int $days = 7): void
    {
        $this->expires_at = Carbon::now()->addDays($days);
        $this->save();
    }

    /**
     * Check if draft is expired.
     */
    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    /**
     * Delete expired drafts (can be run as scheduled task).
     */
    public static function deleteExpired(): int
    {
        return static::where('expires_at', '<', now())->delete();
    }
}
