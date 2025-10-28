<?php

namespace App\Events;

use App\Models\PdfPageAnnotation;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AnnotationCreated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public PdfPageAnnotation $annotation;

    /**
     * Create a new event instance.
     */
    public function __construct(PdfPageAnnotation $annotation)
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
            new PresenceChannel('document.' . $this->annotation->pdfPage->document_id),
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'annotation.created';
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
                'document_id' => $this->annotation->pdfPage->document_id,
                'page_number' => $this->annotation->pdfPage->page_number,
                'type' => $this->annotation->annotation_type,
                'label' => $this->annotation->label,
                'position' => [
                    'x' => $this->annotation->x,
                    'y' => $this->annotation->y,
                    'width' => $this->annotation->width,
                    'height' => $this->annotation->height,
                ],
                'visual_properties' => $this->annotation->visual_properties,
                'creator' => [
                    'id' => $this->annotation->creator_id,
                    'name' => $this->annotation->creator?->name ?? 'Unknown',
                ],
                'created_at' => $this->annotation->created_at->toIso8601String(),
            ],
        ];
    }
}
