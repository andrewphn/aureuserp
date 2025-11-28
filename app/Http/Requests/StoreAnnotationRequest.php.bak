<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreAnnotationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Authorization is handled by policy in controller
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'annotations' => 'required|array|min:1',
            'annotations.*.type' => 'required|string|in:pspdfkit/ink,pspdfkit/highlight,pspdfkit/text,pspdfkit/note,pspdfkit/arrow,pspdfkit/line,pspdfkit/rectangle,pspdfkit/ellipse,pspdfkit/stamp,pspdfkit/image,pspdfkit/link',
            'annotations.*.id' => 'nullable|string|max:255',
            'annotations.*.pageIndex' => 'nullable|integer|min:0',
            'annotations.*.page' => 'nullable|integer|min:1',

            // Geometry fields (required for most annotation types)
            'annotations.*.boundingBox' => 'nullable|array',
            'annotations.*.boundingBox.width' => 'required_with:annotations.*.boundingBox|numeric|min:0',
            'annotations.*.boundingBox.height' => 'required_with:annotations.*.boundingBox|numeric|min:0',
            'annotations.*.boundingBox.left' => 'required_with:annotations.*.boundingBox|numeric',
            'annotations.*.boundingBox.top' => 'required_with:annotations.*.boundingBox|numeric',

            // Rectangles array (for highlight annotations)
            'annotations.*.rects' => 'nullable|array',
            'annotations.*.rects.*' => 'array',
            'annotations.*.rects.*.width' => 'required_with:annotations.*.rects.*|numeric|min:0',
            'annotations.*.rects.*.height' => 'required_with:annotations.*.rects.*|numeric|min:0',
            'annotations.*.rects.*.left' => 'required_with:annotations.*.rects.*|numeric',
            'annotations.*.rects.*.top' => 'required_with:annotations.*.rects.*|numeric',

            // Line/Arrow specific fields
            'annotations.*.startPoint' => 'nullable|array',
            'annotations.*.startPoint.x' => 'required_with:annotations.*.startPoint|numeric',
            'annotations.*.startPoint.y' => 'required_with:annotations.*.startPoint|numeric',
            'annotations.*.endPoint' => 'nullable|array',
            'annotations.*.endPoint.x' => 'required_with:annotations.*.endPoint|numeric',
            'annotations.*.endPoint.y' => 'required_with:annotations.*.endPoint|numeric',

            // Ink annotation specific fields
            'annotations.*.lines' => 'nullable|array',
            'annotations.*.lines.*' => 'array',
            'annotations.*.lines.*.points' => 'required_with:annotations.*.lines.*|array',
            'annotations.*.lines.*.points.*' => 'array',
            'annotations.*.lines.*.points.*.x' => 'required_with:annotations.*.lines.*.points.*|numeric',
            'annotations.*.lines.*.points.*.y' => 'required_with:annotations.*.lines.*.points.*|numeric',

            // Text content fields
            'annotations.*.text' => 'nullable|string',
            'annotations.*.contents' => 'nullable|string',

            // Style fields
            'annotations.*.strokeColor' => 'nullable', // Validated separately in AnnotationService
            'annotations.*.fillColor' => 'nullable', // Validated separately in AnnotationService
            'annotations.*.strokeWidth' => 'nullable|numeric|min:0|max:100',
            'annotations.*.opacity' => 'nullable|numeric|min:0|max:1',

            // Font fields for text annotations
            'annotations.*.fontSize' => 'nullable|numeric|min:1|max:144',
            'annotations.*.fontFamily' => 'nullable|string|max:100',
            'annotations.*.fontStyle' => 'nullable|array',

            // Metadata fields
            'annotations.*.name' => 'nullable|string|max:255',
            'annotations.*.subject' => 'nullable|string|max:255',
            'annotations.*.createdAt' => 'nullable|date',
            'annotations.*.updatedAt' => 'nullable|date',
            'annotations.*.customData' => 'nullable|array',
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'annotations.required' => 'At least one annotation is required.',
            'annotations.*.type.required' => 'Annotation type is required.',
            'annotations.*.type.in' => 'Invalid annotation type. Must be a valid Nutrient annotation type.',
            'annotations.*.boundingBox.required_with' => 'Bounding box dimensions are required.',
            'annotations.*.startPoint.required_with' => 'Start point coordinates are required for line/arrow annotations.',
            'annotations.*.endPoint.required_with' => 'End point coordinates are required for line/arrow annotations.',
            'annotations.*.lines.*.points.required_with' => 'Ink annotation must have point data.',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Convert pageIndex to 1-based page number if provided
        if ($this->has('annotations')) {
            $annotations = $this->annotations;

            foreach ($annotations as $index => $annotation) {
                // Ensure either pageIndex or page is present
                if (isset($annotation['pageIndex']) && !isset($annotation['page'])) {
                    $annotations[$index]['page'] = $annotation['pageIndex'] + 1;
                }
            }

            $this->merge(['annotations' => $annotations]);
        }
    }
}
