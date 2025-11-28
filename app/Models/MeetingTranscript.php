<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Meeting Transcript Eloquent model
 *
 * @property int $id
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property string|null $file_path
 * @property string|null $title
 * @property \Carbon\Carbon|null $meeting_date
 * @property string|null $duration_minutes
 * @property array $participants
 * @property string|null $status
 * @property float $total_sentences
 * @property string|null $summary
 * @property-read \Illuminate\Database\Eloquent\Collection $segments
 * @property-read \Illuminate\Database\Eloquent\Collection $topics
 * @property-read \Illuminate\Database\Eloquent\Collection $actionItems
 * @property-read \Illuminate\Database\Eloquent\Collection $entities
 * @property-read \Illuminate\Database\Eloquent\Collection $sentences
 *
 */
class MeetingTranscript extends Model
{
    protected $fillable = [
        'file_path',
        'title',
        'meeting_date',
        'duration_minutes',
        'participants',
        'status',
        'total_sentences',
        'summary',
    ];

    protected $casts = [
        'participants' => 'array',
        'meeting_date' => 'date',
    ];

    /**
     * Segments
     *
     * @return HasMany
     */
    public function segments(): HasMany
    {
        return $this->hasMany(MeetingSegment::class);
    }

    /**
     * Topics
     *
     * @return HasMany
     */
    public function topics(): HasMany
    {
        return $this->hasMany(MeetingTopic::class);
    }

    /**
     * Action Items
     *
     * @return HasMany
     */
    public function actionItems(): HasMany
    {
        return $this->hasMany(MeetingActionItem::class);
    }

    /**
     * Entities
     *
     * @return HasMany
     */
    public function entities(): HasMany
    {
        return $this->hasMany(MeetingEntity::class);
    }

    /**
     * Sentences
     *
     * @return HasMany
     */
    public function sentences(): HasMany
    {
        return $this->hasMany(MeetingSentence::class);
    }
}
