<?php

namespace Webkul\Support\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;

/**
 * Activity Log Eloquent model
 *
 * @property int $id
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property string|null $log_name
 * @property string|null $description
 * @property string|null $subject_type
 * @property string|null $event
 * @property int $subject_id
 * @property string|null $causer_type
 * @property int $causer_id
 * @property array $properties
 * @property-read \Illuminate\Database\Eloquent\Model|null $subject
 * @property-read \Illuminate\Database\Eloquent\Model|null $causer
 *
 */
class ActivityLog extends Model
{
    protected $table = 'activity_logs';

    protected $fillable = [
        'log_name',
        'description',
        'subject_type',
        'event',
        'subject_id',
        'causer_type',
        'causer_id',
        'properties',
    ];

    protected $casts = [
        'properties' => 'collection',
    ];

    /**
     * Subject
     *
     * @return MorphTo
     */
    public function subject(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Causer
     *
     * @return MorphTo
     */
    public function causer(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Get Extra Property
     *
     * @param string $propertyName
     * @param mixed $defaultValue
     * @return mixed
     */
    public function getExtraProperty(string $propertyName, mixed $defaultValue = null): mixed
    {
        return Arr::get($this->properties->toArray(), $propertyName, $defaultValue);
    }

    /**
     * Changes
     *
     * @return Collection
     */
    public function changes(): Collection
    {
        if (! $this->properties instanceof Collection) {
            return new Collection;
        }

        return $this->properties->only(['attributes', 'old']);
    }

    public function getChangesAttribute(): Collection
    {
        return $this->changes();
    }

    /**
     * Scope query to In Log
     *
     * @param Builder $query The search query
     * @return Builder
     */
    public function scopeInLog(Builder $query, ...$logNames): Builder
    {
        if (is_array($logNames[0])) {
            $logNames = $logNames[0];
        }

        return $query->whereIn('log_name', $logNames);
    }

    /**
     * Scope query to Caused By
     *
     * @param Builder $query The search query
     * @param Model $causer
     * @return Builder
     */
    public function scopeCausedBy(Builder $query, Model $causer): Builder
    {
        return $query
            ->where('causer_type', $causer->getMorphClass())
            ->where('causer_id', $causer->getKey());
    }

    public function scopeForSubject(Builder $query, Model $subject): Builder
    {
        return $query->where('subject_type', $subject->getMorphClass())->where('subject_id', $subject->getKey());
    }

    /**
     * Scope query to For Event
     *
     * @param Builder $query The search query
     * @param string $event
     * @return Builder
     */
    public function scopeForEvent(Builder $query, string $event): Builder
    {
        return $query->where('event', $event);
    }

    /**
     * Scope query to Has Batch
     *
     * @param Builder $query The search query
     * @return Builder
     */
    public function scopeHasBatch(Builder $query): Builder
    {
        return $query->whereNotNull('batch_uuid');
    }

    /**
     * Scope query to For Batch
     *
     * @param Builder $query The search query
     * @param string $batchUuid
     * @return Builder
     */
    public function scopeForBatch(Builder $query, string $batchUuid): Builder
    {
        return $query->where('batch_uuid', $batchUuid);
    }
}
