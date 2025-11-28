<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Meeting Topic Eloquent model
 *
 * @property int $id
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property int $meeting_transcript_id
 * @property string|null $topic_name
 * @property string|null $description
 * @property string|null $first_mention_time
 * @property array $keywords
 * @property float $mention_count
 * @property-read \Illuminate\Database\Eloquent\Model|null $meetingTranscript
 *
 */
class MeetingTopic extends Model
{
    protected $fillable = [
        'meeting_transcript_id',
        'topic_name',
        'description',
        'first_mention_time',
        'keywords',
        'mention_count',
    ];

    protected $casts = [
        'keywords' => 'array',
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
}
