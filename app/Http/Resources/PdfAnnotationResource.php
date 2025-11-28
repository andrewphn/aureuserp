<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Pdf Annotation Resource Filament resource
 *
 */
class PdfAnnotationResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     * @return array
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type' => 'pdf_annotation',
            'attributes' => [
                'document_id' => $this->document_id,
                'page_number' => $this->page_number,
                'annotation_type' => $this->annotation_type,
                'annotation_data' => $this->annotation_data,
                'author_id' => $this->author_id,
                'author_name' => $this->author_name,
                'created_at' => $this->created_at?->toIso8601String(),
                'updated_at' => $this->updated_at?->toIso8601String(),
            ],
            'relationships' => [
                'document' => [
                    'data' => [
                        'id' => $this->document_id,
                        'type' => 'pdf_document',
                    ],
                ],
                'author' => [
                    'data' => $this->when($this->author, [
                        'id' => $this->author_id,
                        'type' => 'user',
                    ]),
                ],
            ],
            'links' => [
                'self' => route('api.pdf.annotations.show', ['annotationId' => $this->id]),
            ],
        ];
    }

    /**
     * Get additional data that should be returned with the resource array.
     *
     * @param Request $request
     * @return array
     */
    public function with(Request $request): array
    {
        return [
            'meta' => [
                'version' => '1.0.0',
                'format' => 'https://pspdfkit.com/instant-json/v1',
            ],
        ];
    }
}
