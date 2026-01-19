<?php

namespace App\Http\Controllers\Api\V1;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Webkul\Project\Models\Bom;

/**
 * Bill of Materials (BOM) Controller for V1 API
 *
 * Handles Bill of Materials for projects.
 * BOMs list all materials required for cabinet construction.
 */
class BomController extends BaseResourceController
{
    protected string $modelClass = Bom::class;

    protected array $searchableFields = [
        'name',
        'description',
    ];

    protected array $filterableFields = [
        'id',
        'project_id',
        'cabinet_id',
        'product_id',
        'material_type',
        'status',
        'company_id',
    ];

    protected array $sortableFields = [
        'id',
        'name',
        'quantity',
        'unit_cost',
        'total_cost',
        'created_at',
        'updated_at',
    ];

    protected array $includableRelations = [
        'project',
        'cabinet',
        'product',
        'company',
    ];

    protected function validateStore(): array
    {
        return [
            'project_id' => 'required|integer|exists:projects_projects,id',
            'cabinet_id' => 'nullable|integer|exists:projects_cabinets,id',
            'product_id' => 'nullable|integer|exists:products_products,id',
            'company_id' => 'nullable|integer|exists:companies,id',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'material_type' => 'nullable|string|in:sheet_good,hardware,edge_banding,finish,other',
            'quantity' => 'required|numeric|min:0',
            'uom' => 'nullable|string|max:50',
            'unit_cost' => 'nullable|numeric|min:0',
            'status' => 'nullable|string|in:pending,ordered,received,issued',
        ];
    }

    protected function validateUpdate(): array
    {
        return [
            'cabinet_id' => 'nullable|integer|exists:projects_cabinets,id',
            'product_id' => 'nullable|integer|exists:products_products,id',
            'name' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'material_type' => 'nullable|string|in:sheet_good,hardware,edge_banding,finish,other',
            'quantity' => 'sometimes|numeric|min:0',
            'uom' => 'nullable|string|max:50',
            'unit_cost' => 'nullable|numeric|min:0',
            'status' => 'nullable|string|in:pending,ordered,received,issued',
        ];
    }

    protected function beforeStore(array $data, Request $request): array
    {
        if (!isset($data['creator_id'])) {
            $data['creator_id'] = $request->user()->id;
        }

        // Calculate total cost
        $quantity = $data['quantity'] ?? 0;
        $unitCost = $data['unit_cost'] ?? 0;
        $data['total_cost'] = $quantity * $unitCost;

        if (!isset($data['status'])) {
            $data['status'] = 'pending';
        }

        return $data;
    }

    protected function beforeUpdate(array $data, Model $model, Request $request): array
    {
        // Recalculate total cost if quantity or unit_cost changed
        $quantity = $data['quantity'] ?? $model->quantity ?? 0;
        $unitCost = $data['unit_cost'] ?? $model->unit_cost ?? 0;
        $data['total_cost'] = $quantity * $unitCost;

        return $data;
    }

    protected function transformModel(Model $model): array
    {
        $data = $model->toArray();

        // Add computed fields
        $data['is_ordered'] = in_array($model->status, ['ordered', 'received', 'issued']);
        $data['is_received'] = in_array($model->status, ['received', 'issued']);
        $data['is_issued'] = $model->status === 'issued';

        return $data;
    }

    /**
     * GET /bom/by-project/{projectId} - Get all BOM items for a project
     */
    public function byProject(int $projectId): JsonResponse
    {
        $bom = Bom::with(['product', 'cabinet'])
            ->where('project_id', $projectId)
            ->orderBy('material_type')
            ->orderBy('name')
            ->get();

        // Group by material type
        $grouped = $bom->groupBy('material_type');

        // Calculate totals
        $totals = [
            'total_items' => $bom->count(),
            'total_cost' => $bom->sum('total_cost'),
            'by_type' => $grouped->map(fn($items) => [
                'count' => $items->count(),
                'total_cost' => $items->sum('total_cost'),
            ]),
            'by_status' => [
                'pending' => $bom->where('status', 'pending')->count(),
                'ordered' => $bom->where('status', 'ordered')->count(),
                'received' => $bom->where('status', 'received')->count(),
                'issued' => $bom->where('status', 'issued')->count(),
            ],
        ];

        return $this->success([
            'project_id' => $projectId,
            'items' => $bom->map(fn($item) => $this->transformModel($item)),
            'grouped' => $grouped->map(fn($items) => $items->map(fn($item) => $this->transformModel($item))),
            'totals' => $totals,
        ], 'Project BOM retrieved');
    }

    /**
     * GET /bom/by-cabinet/{cabinetId} - Get BOM for a specific cabinet
     */
    public function byCabinet(int $cabinetId): JsonResponse
    {
        $bom = Bom::with(['product'])
            ->where('cabinet_id', $cabinetId)
            ->orderBy('material_type')
            ->orderBy('name')
            ->get();

        return $this->success([
            'cabinet_id' => $cabinetId,
            'items' => $bom->map(fn($item) => $this->transformModel($item)),
            'total_cost' => $bom->sum('total_cost'),
        ], 'Cabinet BOM retrieved');
    }

    /**
     * POST /bom/generate/{projectId} - Generate BOM for a project
     *
     * Generates BOM items based on project cabinets.
     */
    public function generate(Request $request, int $projectId): JsonResponse
    {
        $project = \Webkul\Project\Models\Project::with([
            'rooms.locations.cabinetRuns.cabinets',
        ])->find($projectId);

        if (!$project) {
            return $this->notFound('Project not found');
        }

        $validated = $request->validate([
            'overwrite' => 'nullable|boolean',
        ]);

        // Check if BOM already exists
        $existingBom = Bom::where('project_id', $projectId)->exists();
        if ($existingBom && !($validated['overwrite'] ?? false)) {
            return $this->error('BOM already exists. Use overwrite=true to regenerate.', 422);
        }

        // Delete existing if overwriting
        if ($existingBom && ($validated['overwrite'] ?? false)) {
            Bom::where('project_id', $projectId)->delete();
        }

        $bomItems = [];
        $cabinetCount = 0;

        // Generate BOM from cabinets
        foreach ($project->rooms as $room) {
            foreach ($room->locations as $location) {
                foreach ($location->cabinetRuns as $run) {
                    foreach ($run->cabinets as $cabinet) {
                        $cabinetCount++;
                        $cabinetBom = $this->generateCabinetBom($project, $cabinet, $request->user()->id);
                        $bomItems = array_merge($bomItems, $cabinetBom);
                    }
                }
            }
        }

        // Consolidate similar items
        $consolidated = $this->consolidateBom($bomItems);

        // Save BOM items
        $savedItems = [];
        foreach ($consolidated as $item) {
            $item['project_id'] = $projectId;
            $item['creator_id'] = $request->user()->id;
            $savedItems[] = Bom::create($item);
        }

        return $this->success([
            'project_id' => $projectId,
            'cabinets_processed' => $cabinetCount,
            'items_created' => count($savedItems),
            'total_cost' => collect($savedItems)->sum('total_cost'),
        ], 'BOM generated successfully', 201);
    }

    /**
     * Generate BOM items for a single cabinet
     */
    protected function generateCabinetBom($project, $cabinet, $userId): array
    {
        $items = [];

        // Sheet goods (plywood for box)
        $sqft = $this->calculateCabinetSheetGoods($cabinet);
        if ($sqft > 0) {
            $items[] = [
                'cabinet_id' => $cabinet->id,
                'name' => '3/4" Plywood - Cabinet Box',
                'material_type' => 'sheet_good',
                'quantity' => round($sqft, 2),
                'uom' => 'sqft',
                'unit_cost' => 3.50, // Default estimate
                'total_cost' => round($sqft * 3.50, 2),
                'status' => 'pending',
            ];
        }

        // Back panel
        $backSqft = ($cabinet->length_inches ?? 0) * ($cabinet->height_inches ?? 0) / 144;
        if ($backSqft > 0) {
            $items[] = [
                'cabinet_id' => $cabinet->id,
                'name' => '1/4" Plywood - Back Panel',
                'material_type' => 'sheet_good',
                'quantity' => round($backSqft, 2),
                'uom' => 'sqft',
                'unit_cost' => 1.50,
                'total_cost' => round($backSqft * 1.50, 2),
                'status' => 'pending',
            ];
        }

        // Drawer slides
        $drawerCount = $cabinet->drawer_count ?? 0;
        if ($drawerCount > 0) {
            $items[] = [
                'cabinet_id' => $cabinet->id,
                'name' => 'Drawer Slides (pair)',
                'material_type' => 'hardware',
                'quantity' => $drawerCount,
                'uom' => 'pair',
                'unit_cost' => 35.00,
                'total_cost' => $drawerCount * 35.00,
                'status' => 'pending',
            ];
        }

        // Door hinges
        $doorCount = $cabinet->door_count ?? 0;
        if ($doorCount > 0) {
            $hingesPerDoor = 2;
            $items[] = [
                'cabinet_id' => $cabinet->id,
                'name' => 'Concealed Hinges',
                'material_type' => 'hardware',
                'quantity' => $doorCount * $hingesPerDoor,
                'uom' => 'each',
                'unit_cost' => 5.00,
                'total_cost' => $doorCount * $hingesPerDoor * 5.00,
                'status' => 'pending',
            ];
        }

        // Edge banding
        $edgeBandingLf = ($cabinet->length_inches ?? 0) / 12 * 4; // Rough estimate
        if ($edgeBandingLf > 0) {
            $items[] = [
                'cabinet_id' => $cabinet->id,
                'name' => 'Edge Banding',
                'material_type' => 'edge_banding',
                'quantity' => round($edgeBandingLf, 1),
                'uom' => 'lf',
                'unit_cost' => 0.50,
                'total_cost' => round($edgeBandingLf * 0.50, 2),
                'status' => 'pending',
            ];
        }

        return $items;
    }

    /**
     * Calculate sheet goods needed for cabinet box
     */
    protected function calculateCabinetSheetGoods($cabinet): float
    {
        $length = $cabinet->length_inches ?? 0;
        $height = $cabinet->height_inches ?? 0;
        $depth = $cabinet->depth_inches ?? 0;

        // Two sides
        $sides = 2 * ($depth * $height) / 144;
        // Top and bottom
        $topBottom = 2 * ($length * $depth) / 144;

        return $sides + $topBottom;
    }

    /**
     * Consolidate similar BOM items
     */
    protected function consolidateBom(array $items): array
    {
        $consolidated = [];

        foreach ($items as $item) {
            $key = $item['name'] . '|' . ($item['material_type'] ?? '');

            if (isset($consolidated[$key])) {
                $consolidated[$key]['quantity'] += $item['quantity'];
                $consolidated[$key]['total_cost'] += $item['total_cost'];
                // Keep first cabinet_id as reference, null for consolidated
                $consolidated[$key]['cabinet_id'] = null;
            } else {
                $consolidated[$key] = $item;
            }
        }

        return array_values($consolidated);
    }

    /**
     * POST /bom/bulk-update-status - Update status of multiple BOM items
     */
    public function bulkUpdateStatus(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'integer|exists:projects_bom,id',
            'status' => 'required|string|in:pending,ordered,received,issued',
        ]);

        $updated = Bom::whereIn('id', $validated['ids'])
            ->update(['status' => $validated['status']]);

        return $this->success([
            'updated_count' => $updated,
            'new_status' => $validated['status'],
        ], "Updated {$updated} BOM items to {$validated['status']}");
    }
}
