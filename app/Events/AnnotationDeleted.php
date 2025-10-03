<?php

namespace App\Events;

use App\Models\PdfAnnotation;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AnnotationDeleted implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public int $annotationId;
    public int $documentId;
    public int $authorId;

    /**
     * Create a new event instance.
     */
    public function __construct(int $annotationId, int $documentId, int $authorId)
    {
        $this->annotationId = $annotationId;
        $this->documentId = $documentId;
        $this->authorId = $authorId;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PresenceChannel('document.' . $this->documentId),
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'annotation.deleted';
    }

    /**
     * Get the data to broadcast.
     *
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'annotation_id' => $this->annotationId,
            'document_id' => $this->documentId,
            'deleted_by' => $this->authorId,
            'deleted_at' => now()->toIso8601String(),
        ];
    }
}
