<?php

namespace App\Events;

use App\Models\PdfAnnotation;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AnnotationUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public PdfAnnotation $annotation;

    /**
     * Create a new event instance.
     */
    public function __construct(PdfAnnotation $annotation)
    {
        $this->annotation = $annotation;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PresenceChannel('document.' . $this->annotation->document_id),
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'annotation.updated';
    }

    /**
     * Get the data to broadcast.
     *
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'annotation' => [
                'id' => $this->annotation->id,
                'document_id' => $this->annotation->document_id,
                'page_number' => $this->annotation->page_number,
                'type' => $this->annotation->annotation_type,
                'data' => $this->annotation->annotation_data,
                'author' => [
                    'id' => $this->annotation->author_id,
                    'name' => $this->annotation->author_name,
                ],
                'updated_at' => $this->annotation->updated_at->toIso8601String(),
            ],
        ];
    }
}
