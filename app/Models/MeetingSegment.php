<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Meeting Segment Eloquent model
 *
 * @property int $id
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property int $meeting_transcript_id
 * @property string|null $start_time
 * @property string|null $end_time
 * @property string|null $topic
 * @property string|null $summary
 * @property array $speakers
 * @property float $sentence_count
 * @property-read \Illuminate\Database\Eloquent\Collection $sentences
 * @property-read \Illuminate\Database\Eloquent\Model|null $meetingTranscript
 *
 */
class MeetingSegment extends Model
{
    protected $fillable = [
        'meeting_transcript_id',
        'start_time',
        'end_time',
        'topic',
        'summary',
        'speakers',
        'sentence_count',
    ];

    protected $casts = [
        'speakers' => 'array',
    ];

    /**
     * Meeting Transcript
     *
     * @return BelongsTo
     */
    public function meetingTranscript(): BelongsTo
    {
        return $this->belongsTo(MeetingTranscript::class);
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
