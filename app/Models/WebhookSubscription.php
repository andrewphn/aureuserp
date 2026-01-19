<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;
use Webkul\Security\Models\User;

/**
 * Webhook Subscription model for n8n and external integrations
 *
 * @property int $id
 * @property int $user_id
 * @property string|null $name
 * @property string $url
 * @property array $events
 * @property string|null $secret
 * @property bool $is_active
 * @property \Carbon\Carbon|null $last_triggered_at
 * @property int $success_count
 * @property int $failure_count
 * @property string|null $last_error
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class WebhookSubscription extends Model
{
    protected $fillable = [
        'user_id',
        'name',
        'url',
        'events',
        'secret',
        'is_active',
        'last_triggered_at',
        'success_count',
        'failure_count',
        'last_error',
    ];

    protected $casts = [
        'events' => 'array',
        'is_active' => 'boolean',
        'last_triggered_at' => 'datetime',
        'success_count' => 'integer',
        'failure_count' => 'integer',
    ];

    /**
     * Available webhook events
     */
    public const EVENTS = [
        // Projects
        'project.created',
        'project.updated',
        'project.deleted',
        'project.stage_changed',

        // Rooms
        'room.created',
        'room.updated',
        'room.deleted',

        // Cabinets
        'cabinet.created',
        'cabinet.updated',
        'cabinet.deleted',

        // Cabinet Runs
        'cabinet_run.created',
        'cabinet_run.updated',
        'cabinet_run.deleted',

        // Drawers
        'drawer.created',
        'drawer.updated',
        'drawer.deleted',

        // Doors
        'door.created',
        'door.updated',
        'door.deleted',

        // Tasks
        'task.created',
        'task.updated',
        'task.deleted',
        'task.status_changed',

        // Employees
        'employee.created',
        'employee.updated',
        'employee.deleted',

        // Products
        'product.created',
        'product.updated',
        'product.deleted',

        // Partners
        'partner.created',
        'partner.updated',
        'partner.deleted',

        // Rhino Extraction
        'rhino.extraction_started',
        'rhino.extraction_completed',
        'rhino.extraction_failed',
        'rhino.review_required',
        'rhino.review_approved',
        'rhino.review_rejected',

        // Rhino Sync
        'rhino.sync_push',
        'rhino.sync_pull',
        'rhino.sync_conflict',
        'rhino.sync_resolved',
    ];

    /**
     * User who owns this subscription
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Delivery logs for this subscription
     */
    public function deliveries(): HasMany
    {
        return $this->hasMany(WebhookDelivery::class);
    }

    /**
     * Check if this subscription should receive the given event
     */
    public function shouldReceive(string $event): bool
    {
        if (!$this->is_active) {
            return false;
        }

        // Check for exact match or wildcard
        foreach ($this->events as $subscribedEvent) {
            if ($subscribedEvent === $event) {
                return true;
            }

            // Support wildcard events like 'project.*' or '*'
            if (Str::endsWith($subscribedEvent, '.*')) {
                $prefix = Str::beforeLast($subscribedEvent, '.*');
                if (Str::startsWith($event, $prefix . '.')) {
                    return true;
                }
            }

            if ($subscribedEvent === '*') {
                return true;
            }
        }

        return false;
    }

    /**
     * Generate signature for payload verification
     */
    public function generateSignature(string $payload): string
    {
        return hash_hmac('sha256', $payload, $this->secret ?? '');
    }

    /**
     * Mark a successful delivery
     */
    public function markSuccess(): void
    {
        $this->increment('success_count');
        $this->update([
            'last_triggered_at' => now(),
            'last_error' => null,
        ]);
    }

    /**
     * Mark a failed delivery
     */
    public function markFailure(string $error): void
    {
        $this->increment('failure_count');
        $this->update([
            'last_triggered_at' => now(),
            'last_error' => $error,
        ]);
    }

    /**
     * Boot the model
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($subscription) {
            // Generate a secret if not provided
            if (empty($subscription->secret)) {
                $subscription->secret = Str::random(64);
            }
        });
    }

    /**
     * Scope: Active subscriptions
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope: Subscriptions for a specific event
     */
    public function scopeForEvent($query, string $event)
    {
        return $query->where('is_active', true)
            ->where(function ($q) use ($event) {
                $q->whereJsonContains('events', $event)
                  ->orWhereJsonContains('events', '*')
                  ->orWhereJsonContains('events', Str::before($event, '.') . '.*');
            });
    }
}
