<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Update Annotation Request form request
 *
 */
class UpdateAnnotationRequest extends FormRequest
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
            'type' => 'required|string|in:pspdfkit/ink,pspdfkit/highlight,pspdfkit/text,pspdfkit/note,pspdfkit/arrow,pspdfkit/line,pspdfkit/rectangle,pspdfkit/ellipse,pspdfkit/stamp,pspdfkit/image,pspdfkit/link',
            'data' => 'required|array',
            'data.pageIndex' => 'nullable|integer|min:0',
            'data.page' => 'nullable|integer|min:1',

            // Geometry fields
            'data.boundingBox' => 'nullable|array',
            'data.boundingBox.width' => 'required_with:data.boundingBox|numeric|min:0',
            'data.boundingBox.height' => 'required_with:data.boundingBox|numeric|min:0',
            'data.boundingBox.left' => 'required_with:data.boundingBox|numeric',
            'data.boundingBox.top' => 'required_with:data.boundingBox|numeric',

            // Rectangles array
            'data.rects' => 'nullable|array',
            'data.rects.*' => 'array',
            'data.rects.*.width' => 'required_with:data.rects.*|numeric|min:0',
            'data.rects.*.height' => 'required_with:data.rects.*|numeric|min:0',
            'data.rects.*.left' => 'required_with:data.rects.*|numeric',
            'data.rects.*.top' => 'required_with:data.rects.*|numeric',

            // Line/Arrow fields
            'data.startPoint' => 'nullable|array',
            'data.startPoint.x' => 'required_with:data.startPoint|numeric',
            'data.startPoint.y' => 'required_with:data.startPoint|numeric',
            'data.endPoint' => 'nullable|array',
            'data.endPoint.x' => 'required_with:data.endPoint|numeric',
            'data.endPoint.y' => 'required_with:data.endPoint|numeric',

            // Ink annotation fields
            'data.lines' => 'nullable|array',
            'data.lines.*' => 'array',
            'data.lines.*.points' => 'required_with:data.lines.*|array',
            'data.lines.*.points.*' => 'array',
            'data.lines.*.points.*.x' => 'required_with:data.lines.*.points.*|numeric',
            'data.lines.*.points.*.y' => 'required_with:data.lines.*.points.*|numeric',

            // Text content
            'data.text' => 'nullable|string',
            'data.contents' => 'nullable|string',

            // Style fields
            'data.strokeColor' => 'nullable',
            'data.fillColor' => 'nullable',
            'data.strokeWidth' => 'nullable|numeric|min:0|max:100',
            'data.opacity' => 'nullable|numeric|min:0|max:1',

            // Font fields
            'data.fontSize' => 'nullable|numeric|min:1|max:144',
            'data.fontFamily' => 'nullable|string|max:100',
            'data.fontStyle' => 'nullable|array',

            // Metadata
            'data.name' => 'nullable|string|max:255',
            'data.subject' => 'nullable|string|max:255',
            'data.customData' => 'nullable|array',
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
            'type.required' => 'Annotation type is required.',
            'type.in' => 'Invalid annotation type. Must be a valid Nutrient annotation type.',
            'data.required' => 'Annotation data is required.',
            'data.boundingBox.required_with' => 'Bounding box dimensions are required.',
            'data.startPoint.required_with' => 'Start point coordinates are required for line/arrow annotations.',
            'data.endPoint.required_with' => 'End point coordinates are required for line/arrow annotations.',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Ensure data.type matches the top-level type
        if ($this->has('data') && $this->has('type')) {
            $data = $this->data;
            $data['type'] = $this->type;

            // Convert pageIndex to page if provided
            if (isset($data['pageIndex']) && !isset($data['page'])) {
                $data['page'] = $data['pageIndex'] + 1;
            }

            $this->merge(['data' => $data]);
        }
    }
}
