<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Webkul\Project\Models\Project;
use Webkul\Project\Services\FormFiller\UniversalFormFillerService;
use Webkul\Sale\Models\DocumentTemplate;

/**
 * Form Filler API Controller
 *
 * Provides API endpoints for the Universal Form Filler,
 * enabling AI agents and external tools to fill documents.
 */
class FormFillerController extends Controller
{
    public function __construct(
        protected UniversalFormFillerService $formFillerService
    ) {}

    /**
     * List available document templates
     */
    public function listTemplates(Request $request): JsonResponse
    {
        $type = $request->query('type');

        $query = DocumentTemplate::query();

        if ($type) {
            $query->where('type', $type);
        }

        $templates = $query->get(['id', 'name', 'type', 'description', 'is_default']);

        return response()->json([
            'success' => true,
            'templates' => $templates,
            'types' => DocumentTemplate::getTypes(),
        ]);
    }

    /**
     * Get template details with available fields
     */
    public function getTemplate(int $id): JsonResponse
    {
        $template = DocumentTemplate::find($id);

        if (!$template) {
            return response()->json([
                'success' => false,
                'error' => 'Template not found',
            ], 404);
        }

        $content = $template->getContent();
        $fields = $this->formFillerService->parseEditableFields($content ?? '');

        return response()->json([
            'success' => true,
            'template' => [
                'id' => $template->id,
                'name' => $template->name,
                'type' => $template->type,
                'description' => $template->description,
            ],
            'available_fields' => array_keys($fields),
            'field_descriptions' => UniversalFormFillerService::getAvailableVariables($template->type),
        ]);
    }

    /**
     * Fill document from project data
     */
    public function fillFromProject(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'template_id' => 'required|integer|exists:document_templates,id',
            'project_id' => 'required|integer|exists:projects_projects,id',
            'additional_fields' => 'array',
        ]);

        $template = DocumentTemplate::find($validated['template_id']);
        $project = Project::with(['partner', 'orders'])->find($validated['project_id']);

        $content = $template->getContent();
        $fields = $this->formFillerService->parseEditableFields($content ?? '');

        // Fill from project
        $filledFields = $this->formFillerService->fillFromProject($fields, $project);

        // Merge any additional fields provided
        if (!empty($validated['additional_fields'])) {
            $filledFields = array_merge($filledFields, $validated['additional_fields']);
        }

        // Apply to template
        $renderedContent = $this->formFillerService->applyFields($content, $filledFields);

        return response()->json([
            'success' => true,
            'fields' => $filledFields,
            'rendered_html' => $renderedContent,
            'project' => [
                'id' => $project->id,
                'name' => $project->name,
                'client' => $project->partner?->name,
            ],
        ]);
    }

    /**
     * Fill document using AI prompt
     */
    public function fillWithAi(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'template_id' => 'required|integer|exists:document_templates,id',
            'prompt' => 'required|string|max:2000',
            'project_id' => 'nullable|integer|exists:projects_projects,id',
            'current_fields' => 'array',
        ]);

        $template = DocumentTemplate::find($validated['template_id']);
        $project = isset($validated['project_id'])
            ? Project::with(['partner'])->find($validated['project_id'])
            : null;

        $content = $template->getContent();
        $fields = $validated['current_fields'] ?? $this->formFillerService->parseEditableFields($content ?? '');

        // Process AI prompt
        $result = $this->formFillerService->processAiPrompt(
            $validated['prompt'],
            $fields,
            $project
        );

        if (!$result['success']) {
            return response()->json([
                'success' => false,
                'error' => $result['error'],
            ], 400);
        }

        // Merge AI results with current fields
        $updatedFields = array_merge($fields, $result['fields']);

        // Apply to template
        $renderedContent = $this->formFillerService->applyFields($content, $updatedFields);

        return response()->json([
            'success' => true,
            'fields' => $updatedFields,
            'updated_fields' => $result['fields'],
            'message' => $result['message'],
            'rendered_html' => $renderedContent,
        ]);
    }

    /**
     * Render document with provided fields
     */
    public function renderDocument(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'template_id' => 'required|integer|exists:document_templates,id',
            'fields' => 'required|array',
        ]);

        $template = DocumentTemplate::find($validated['template_id']);
        $content = $template->getContent();

        if (!$content) {
            return response()->json([
                'success' => false,
                'error' => 'Template has no content',
            ], 400);
        }

        $renderedContent = $this->formFillerService->applyFields($content, $validated['fields']);

        return response()->json([
            'success' => true,
            'rendered_html' => $renderedContent,
        ]);
    }

    /**
     * Get available variables for a template type
     */
    public function getVariables(string $type): JsonResponse
    {
        $variables = UniversalFormFillerService::getAvailableVariables($type);

        return response()->json([
            'success' => true,
            'type' => $type,
            'variables' => $variables,
        ]);
    }
}
