<?php

namespace App\Http\Controllers;

use App\Models\PdfPage;
use App\Models\PdfPageAnnotation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Pdf Annotation Controller controller
 *
 */
class PdfAnnotationController extends Controller
{
    /**
     * Get all annotations for a PDF page in Nutrient Instant JSON format
     */
    public function getPageAnnotations(int $pdfPageId)
    {
        $pdfPage = PdfPage::findOrFail($pdfPageId);

        $annotations = PdfPageAnnotation::where('pdf_page_id', $pdfPageId)
            ->with(['cabinetRun', 'cabinet', 'childAnnotations'])
            ->get();

        // Convert to Nutrient Instant JSON format
        $instantJson = [
            'format' => 'https://pspdfkit.com/instant-json/v1',
            'pdfId' => $pdfPage->pdf_document_id,
            'annotations' => $annotations->map(fn($ann) => $ann->toNutrientAnnotation())->values()->all(),
        ];

        return response()->json($instantJson);
    }

    /**
     * Save annotations from Nutrient (bulk save from Instant JSON)
     */
    public function savePageAnnotations(Request $request, int $pdfPageId)
    {
        $validated = $request->validate([
            'annotations' => 'required|array',
            'annotations.*.id' => 'required|string',
            'annotations.*.type' => 'required|string',
            'annotations.*.bbox' => 'required|array|size:4',
            'annotations.*.customData' => 'sometimes|array',
        ]);

        $pdfPage = PdfPage::findOrFail($pdfPageId);
        $userId = Auth::id();

        $savedAnnotations = [];

        foreach ($validated['annotations'] as $annotationData) {
            // Check if annotation already exists by nutrient_annotation_id
            $existing = PdfPageAnnotation::where('nutrient_annotation_id', $annotationData['id'])
                ->where('pdf_page_id', $pdfPageId)
                ->first();

            if ($existing) {
                // Update existing annotation
                $existing->update([
                    'x' => $annotationData['bbox'][0],
                    'y' => $annotationData['bbox'][1],
                    'width' => $annotationData['bbox'][2],
                    'height' => $annotationData['bbox'][3],
                    'visual_properties' => [
                        'strokeColor' => $annotationData['strokeColor'] ?? '#FF0000',
                        'strokeWidth' => $annotationData['strokeWidth'] ?? 2,
                        'opacity' => $annotationData['opacity'] ?? 1,
                    ],
                    'nutrient_data' => $annotationData,
                    'label' => $annotationData['customData']['label'] ?? $existing->label,
                ]);
                $savedAnnotations[] = $existing;
            } else {
                // Create new annotation
                $annotation = PdfPageAnnotation::createFromNutrient(
                    $pdfPageId,
                    $annotationData,
                    $userId
                );
                $savedAnnotations[] = $annotation;
            }
        }

        return response()->json([
            'success' => true,
            'saved_count' => count($savedAnnotations),
            'annotations' => $savedAnnotations,
        ]);
    }

    /**
     * Create a single annotation (for manual creation via UI)
     */
    public function createAnnotation(Request $request, int $pdfPageId)
    {
        $validated = $request->validate([
            'annotation_type' => 'required|in:cabinet_run,cabinet,note',
            'label' => 'nullable|string',
            'parent_annotation_id' => 'nullable|exists:pdf_page_annotations,id',
            'x' => 'required|numeric',
            'y' => 'required|numeric',
            'width' => 'required|numeric',
            'height' => 'required|numeric',
            'cabinet_run_id' => 'nullable|exists:projects_cabinet_runs,id',
            'cabinet_id' => 'nullable|exists:projects_cabinets,id',
        ]);

        $pdfPage = PdfPage::findOrFail($pdfPageId);

        $annotation = PdfPageAnnotation::create(array_merge($validated, [
            'pdf_page_id' => $pdfPageId,
            'creator_id' => Auth::id(),
            'nutrient_annotation_id' => 'annotation_' . uniqid(),
        ]));

        return response()->json([
            'success' => true,
            'annotation' => $annotation->load(['cabinetRun', 'cabinet']),
            'nutrient_annotation' => $annotation->toNutrientAnnotation(),
        ]);
    }

    /**
     * Link annotation to cabinet run or cabinet specification
     */
    public function linkAnnotation(Request $request, int $annotationId)
    {
        $validated = $request->validate([
            'cabinet_run_id' => 'nullable|exists:projects_cabinet_runs,id',
            'cabinet_id' => 'nullable|exists:projects_cabinets,id',
            'label' => 'nullable|string',
        ]);

        $annotation = PdfPageAnnotation::findOrFail($annotationId);
        $annotation->update($validated);

        return response()->json([
            'success' => true,
            'annotation' => $annotation->load(['cabinetRun', 'cabinet']),
        ]);
    }

    /**
     * Delete annotation
     */
    public function deleteAnnotation(int $annotationId)
    {
        $annotation = PdfPageAnnotation::findOrFail($annotationId);

        // Get nutrient ID before deleting
        $nutrientId = $annotation->nutrient_annotation_id;

        // Soft delete (will cascade to children via model events if needed)
        $annotation->delete();

        return response()->json([
            'success' => true,
            'nutrient_annotation_id' => $nutrientId,
        ]);
    }

    /**
     * Get available cabinet runs for linking (within the same project)
     */
    public function getAvailableCabinetRuns(int $pdfPageId)
    {
        $pdfPage = PdfPage::with('pdfDocument.module')->findOrFail($pdfPageId);
        $project = $pdfPage->pdfDocument->module ?? null;

        if (!$project || !($project instanceof \Webkul\Project\Models\Project)) {
            return response()->json(['cabinet_runs' => []]);
        }

        $projectId = $project->id;

        $cabinetRuns = \Webkul\Project\Models\CabinetRun::whereHas('roomLocation.room', function($query) use ($projectId) {
            $query->where('project_id', $projectId);
        })
        ->with(['roomLocation.room', 'cabinets'])
        ->get();

        return response()->json([
            'cabinet_runs' => $cabinetRuns->map(function($run) {
                return [
                    'id' => $run->id,
                    'name' => $run->name,
                    'run_type' => $run->run_type,
                    'room_name' => $run->roomLocation->room->name ?? 'Unknown',
                    'cabinet_count' => $run->cabinets->count(),
                ];
            }),
        ]);
    }

    /**
     * Get cabinets within a specific cabinet run for nested linking
     */
    public function getCabinetsInRun(int $cabinetRunId)
    {
        $cabinetRun = \Webkul\Project\Models\CabinetRun::with('cabinets')->findOrFail($cabinetRunId);

        return response()->json([
            'cabinet_run' => [
                'id' => $cabinetRun->id,
                'name' => $cabinetRun->name,
            ],
            'cabinets' => $cabinetRun->cabinets->map(function($cabinet) {
                return [
                    'id' => $cabinet->id,
                    'cabinet_number' => $cabinet->cabinet_number,
                    'length_inches' => $cabinet->length_inches,
                    'linear_feet' => $cabinet->linear_feet,
                    'position_in_run' => $cabinet->position_in_run,
                ];
            }),
        ]);
    }
}
