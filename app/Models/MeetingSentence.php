<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Meeting Sentence Eloquent model
 *
 * @property int $id
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property int $meeting_transcript_id
 * @property int $meeting_segment_id
 * @property string|null $speaker_name
 * @property int $speaker_id
 * @property string|null $start_time
 * @property string|null $end_time
 * @property string|null $sentence
 * @property string|null $sequence_number
 * @property-read \Illuminate\Database\Eloquent\Model|null $meetingTranscript
 * @property-read \Illuminate\Database\Eloquent\Model|null $meetingSegment
 *
 */
class MeetingSentence extends Model
{
    protected $fillable = [
        'meeting_transcript_id',
        'meeting_segment_id',
        'speaker_name',
        'speaker_id',
        'start_time',
        'end_time',
        'sentence',
        'sequence_number',
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
     * Meeting Segment
     *
     * @return BelongsTo
     */
    public function meetingSegment(): BelongsTo
    {
        return $this->belongsTo(MeetingSegment::class);
    }
}
