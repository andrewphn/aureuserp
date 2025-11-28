<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Meeting Action Item Eloquent model
 *
 * @property int $id
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property int $meeting_transcript_id
 * @property string|null $assignee
 * @property string|null $task
 * @property \Carbon\Carbon|null $due_date
 * @property string|null $priority
 * @property string|null $status
 * @property string|null $mentioned_at_time
 * @property string|null $context
 * @property-read \Illuminate\Database\Eloquent\Model|null $meetingTranscript
 *
 */
class MeetingActionItem extends Model
{
    protected $fillable = [
        'meeting_transcript_id',
        'assignee',
        'task',
        'due_date',
        'priority',
        'status',
        'mentioned_at_time',
        'context',
    ];

    protected $casts = [
        'due_date' => 'date',
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
