<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Meeting Entity Eloquent model
 *
 * @property int $id
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property int $meeting_transcript_id
 * @property string|null $entity_type
 * @property string|null $entity_name
 * @property float $mentions_count
 * @property array $context_snippets
 * @property-read \Illuminate\Database\Eloquent\Model|null $meetingTranscript
 *
 */
class MeetingEntity extends Model
{
    protected $fillable = [
        'meeting_transcript_id',
        'entity_type',
        'entity_name',
        'mentions_count',
        'context_snippets',
    ];

    protected $casts = [
        'context_snippets' => 'array',
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
