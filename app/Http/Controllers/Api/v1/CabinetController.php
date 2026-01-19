<?php

namespace App\Http\Controllers\Api\V1;

use App\Services\CabinetCalculatorService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Webkul\Project\Models\Cabinet;

class CabinetController extends BaseResourceController
{
    protected string $modelClass = Cabinet::class;

    protected array $searchableFields = [
        'cabinet_number',
        'full_code',
        'shop_notes',
        'hardware_notes',
    ];

    protected array $filterableFields = [
        'id',
        'project_id',
        'room_id',
        'cabinet_run_id',
        'cabinet_level',
        'material_category',
        'finish_option',
        'door_style',
        'door_mounting',
        'construction_type',
    ];

    protected array $sortableFields = [
        'id',
        'cabinet_number',
        'position_in_run',
        'length_inches',
        'width_inches',
        'depth_inches',
        'height_inches',
        'linear_feet',
        'total_price',
        'created_at',
        'updated_at',
    ];

    protected array $includableRelations = [
        'project',
        'room',
        'cabinetRun',
        'sections',
        'stretchers',
        'creator',
    ];

    protected function getBaseQuery(): Builder
    {
        $query = parent::getBaseQuery();

        // If nested under cabinet_run, scope to that run
        if ($cabinetRunId = request()->route('cabinet_run')) {
            $query->where('cabinet_run_id', $cabinetRunId);
        }

        return $query;
    }

    protected function validateStore(): array
    {
        return [
            'cabinet_run_id' => 'required|integer|exists:projects_cabinet_runs,id',
            'project_id' => 'nullable|integer|exists:projects_projects,id',
            'room_id' => 'nullable|integer|exists:projects_rooms,id',
            'cabinet_number' => 'nullable|string|max:50',
            'position_in_run' => 'nullable|integer|min:0',
            'wall_position_start_inches' => 'nullable|numeric|min:0',
            'length_inches' => 'required|numeric|min:1',
            'width_inches' => 'nullable|numeric|min:0',
            'depth_inches' => 'required|numeric|min:1',
            'height_inches' => 'required|numeric|min:1',
            'quantity' => 'nullable|integer|min:1',
            'cabinet_level' => 'nullable|string|max:50',
            'material_category' => 'nullable|string|max:50',
            'finish_option' => 'nullable|string|max:50',
            'door_style' => 'nullable|string|max:100',
            'door_mounting' => 'nullable|string|max:50',
            'door_count' => 'nullable|integer|min:0',
            'drawer_count' => 'nullable|integer|min:0',
            'shop_notes' => 'nullable|string',
            'hardware_notes' => 'nullable|string',
            'custom_modifications' => 'nullable|string',
        ];
    }

    protected function validateUpdate(): array
    {
        return [
            'cabinet_number' => 'sometimes|string|max:50',
            'position_in_run' => 'nullable|integer|min:0',
            'wall_position_start_inches' => 'nullable|numeric|min:0',
            'length_inches' => 'sometimes|numeric|min:1',
            'width_inches' => 'nullable|numeric|min:0',
            'depth_inches' => 'sometimes|numeric|min:1',
            'height_inches' => 'sometimes|numeric|min:1',
            'quantity' => 'nullable|integer|min:1',
            'cabinet_level' => 'nullable|string|max:50',
            'material_category' => 'nullable|string|max:50',
            'finish_option' => 'nullable|string|max:50',
            'door_style' => 'nullable|string|max:100',
            'door_mounting' => 'nullable|string|max:50',
            'door_count' => 'nullable|integer|min:0',
            'drawer_count' => 'nullable|integer|min:0',
            'shop_notes' => 'nullable|string',
            'hardware_notes' => 'nullable|string',
            'custom_modifications' => 'nullable|string',
        ];
    }

    protected function beforeStore(array $data, Request $request): array
    {
        if ($cabinetRunId = $request->route('cabinet_run')) {
            $data['cabinet_run_id'] = $cabinetRunId;

            // Auto-populate project_id and room_id from cabinet run
            $cabinetRun = \Webkul\Project\Models\CabinetRun::with('roomLocation.room')->find($cabinetRunId);
            if ($cabinetRun && $cabinetRun->roomLocation) {
                $data['room_id'] = $cabinetRun->roomLocation->room_id;
                $data['project_id'] = $cabinetRun->roomLocation->room->project_id ?? null;
            }
        }

        if (!isset($data['creator_id'])) {
            $data['creator_id'] = $request->user()->id;
        }

        if (!isset($data['quantity'])) {
            $data['quantity'] = 1;
        }

        return $data;
    }

    /**
     * Calculate cabinet dimensions and pricing
     *
     * POST /api/v1/cabinets/{id}/calculate
     *
     * Uses the CabinetCalculatorService to run calculations
     * based on exterior dimensions and component specification
     */
    public function calculate(int $id, CabinetCalculatorService $calculator): JsonResponse
    {
        $cabinet = Cabinet::with(['sections.drawers', 'sections.doors', 'sections.shelves', 'stretchers'])->findOrFail($id);

        try {
            // Build input from cabinet data
            $components = [];

            // Add drawers from sections
            foreach ($cabinet->sections as $section) {
                foreach ($section->drawers as $drawer) {
                    $components[] = [
                        'type' => 'drawer',
                        'height' => $drawer->front_height_inches ?? 6.0,
                    ];
                }
                foreach ($section->doors as $door) {
                    $components[] = [
                        'type' => 'door',
                        'height' => $door->height_inches ?? 12.0,
                    ];
                }
            }

            $input = [
                'exterior' => [
                    'width' => (float) ($cabinet->width_inches ?? $cabinet->length_inches),
                    'height' => (float) $cabinet->height_inches,
                    'depth' => (float) $cabinet->depth_inches,
                ],
                'cabinet_type' => $cabinet->construction_type ?? 'base',
                'components' => $components,
                'template_id' => $cabinet->construction_template_id,
            ];

            $result = $calculator->calculateFromExterior($input);

            return $this->success([
                'cabinet_id' => $cabinet->id,
                'dimensions' => [
                    'width_inches' => $cabinet->width_inches ?? $cabinet->length_inches,
                    'height_inches' => $cabinet->height_inches,
                    'depth_inches' => $cabinet->depth_inches,
                    'linear_feet' => $cabinet->linear_feet,
                ],
                'pricing' => [
                    'unit_price_per_lf' => $cabinet->unit_price_per_lf,
                    'total_price' => $cabinet->total_price,
                    'adjustment_amount' => $cabinet->adjustment_amount,
                    'final_price' => $cabinet->final_price,
                ],
                'complexity' => [
                    'score' => $cabinet->complexity_score,
                    'breakdown' => $cabinet->complexity_breakdown,
                ],
                'calculation_result' => $result,
            ], 'Cabinet calculated successfully');
        } catch (\Exception $e) {
            return $this->error('Calculation failed: ' . $e->getMessage(), null, 500);
        }
    }

    /**
     * Get cabinet cut list for shop production
     *
     * GET /api/v1/cabinets/{id}/cut-list
     *
     * Uses the CabinetCalculatorService to generate a cut list
     */
    public function cutList(int $id, CabinetCalculatorService $calculator): JsonResponse
    {
        $cabinet = Cabinet::with([
            'sections.drawers',
            'sections.doors',
            'sections.shelves',
            'stretchers',
            'constructionTemplate',
        ])->findOrFail($id);

        try {
            // Build components list
            $components = [];
            foreach ($cabinet->sections as $section) {
                foreach ($section->drawers as $drawer) {
                    $components[] = [
                        'type' => 'drawer',
                        'height' => $drawer->front_height_inches ?? 6.0,
                    ];
                }
            }

            $input = [
                'exterior' => [
                    'width' => (float) ($cabinet->width_inches ?? $cabinet->length_inches),
                    'height' => (float) $cabinet->height_inches,
                    'depth' => (float) $cabinet->depth_inches,
                ],
                'cabinet_type' => $cabinet->construction_type ?? 'base',
                'components' => $components,
                'template_id' => $cabinet->construction_template_id,
            ];

            // Full calculation includes cut list
            $result = $calculator->calculateFromExterior($input);

            return $this->success([
                'cabinet_id' => $cabinet->id,
                'cabinet_number' => $cabinet->cabinet_number,
                'full_code' => $cabinet->full_code,
                'cut_list' => $result['cut_list'] ?? [],
                'box' => $result['box'] ?? null,
                'face_frame' => $result['face_frame'] ?? null,
                'stretchers' => $result['stretchers'] ?? null,
            ], 'Cut list generated');
        } catch (\Exception $e) {
            return $this->error('Cut list generation failed: ' . $e->getMessage(), null, 500);
        }
    }
}
