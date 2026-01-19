<?php

namespace App\Http\Controllers\Api\V1;

use App\Services\ProductionEstimatorService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Webkul\Partner\Models\Partner;
use Webkul\Project\Models\Project;
use Webkul\Project\Models\ProjectStage;
use Webkul\Project\Models\Room;
use Webkul\Project\Models\RoomLocation;
use Webkul\Project\Models\CabinetRun;
use Webkul\Project\Models\Cabinet;
use Webkul\Project\Services\TcsPricingService;
use Webkul\Support\Models\Company;

class ProjectController extends BaseResourceController
{
    protected string $modelClass = Project::class;

    protected array $searchableFields = [
        'name',
        'project_number',
        'draft_number',
        'description',
    ];

    protected array $filterableFields = [
        'id',
        'is_active',
        'is_converted',
        'stage_id',
        'partner_id',
        'company_id',
        'user_id',
        'creator_id',
        'project_type',
        'visibility',
        'current_production_stage',
    ];

    protected array $sortableFields = [
        'id',
        'name',
        'project_number',
        'created_at',
        'updated_at',
        'start_date',
        'end_date',
        'estimated_linear_feet',
    ];

    protected array $includableRelations = [
        'rooms',
        'cabinets',
        'partner',
        'creator',
        'user',
        'stage',
        'company',
        'tags',
        'milestones',
        'tasks',
        'addresses',
    ];

    protected function validateStore(): array
    {
        return [
            // Core fields - name can be auto-generated
            'name' => 'nullable|string|max:255',
            'project_number' => 'nullable|string|max:100',

            // Relationships
            'partner_id' => 'nullable|integer|exists:partners_partners,id',
            'company_id' => 'nullable|integer|exists:supports_companies,id',
            'user_id' => 'nullable|integer|exists:users,id',
            'stage_id' => 'nullable|integer|exists:projects_project_stages,id',

            // Project details
            'project_type' => 'nullable|string|max:50',
            'lead_source' => 'nullable|string|max:50',
            'budget_range' => 'nullable|string|max:50',
            'complexity_score' => 'nullable|numeric|min:0|max:100',
            'description' => 'nullable|string',

            // Dates
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'desired_completion_date' => 'nullable|date',

            // Estimates
            'allocated_hours' => 'nullable|numeric|min:0',
            'estimated_linear_feet' => 'nullable|numeric|min:0',

            // Settings
            'visibility' => 'nullable|string|in:internal,public,private',
            'is_active' => 'nullable|boolean',
            'allow_timesheets' => 'nullable|boolean',
            'allow_milestones' => 'nullable|boolean',

            // Address (optional)
            'address' => 'nullable|array',
            'address.street1' => 'nullable|string|max:255',
            'address.street2' => 'nullable|string|max:255',
            'address.city' => 'nullable|string|max:100',
            'address.state' => 'nullable|string|max:100',
            'address.zip' => 'nullable|string|max:20',
            'address.country' => 'nullable|string|max:100',

            // Tags (array of tag IDs)
            'tags' => 'nullable|array',
            'tags.*' => 'integer|exists:supports_tags,id',

            // Rooms for batch creation
            'rooms' => 'nullable|array',
            'rooms.*.name' => 'required_with:rooms|string|max:255',
            'rooms.*.room_type' => 'nullable|string|max:50',
            'rooms.*.linear_feet' => 'nullable|numeric|min:0',
            'rooms.*.cabinet_level' => 'nullable|string|max:10',
            'rooms.*.material_category' => 'nullable|string|max:50',
            'rooms.*.finish_option' => 'nullable|string|max:50',
            'rooms.*.locations' => 'nullable|array',

            // Auto-generation flags
            'auto_generate_number' => 'nullable|boolean',
            'auto_generate_name' => 'nullable|boolean',
            'calculate_production_estimate' => 'nullable|boolean',
        ];
    }

    protected function validateUpdate(): array
    {
        return [
            'name' => 'sometimes|string|max:255',
            'project_number' => 'nullable|string|max:100',
            'partner_id' => 'nullable|integer|exists:partners_partners,id',
            'company_id' => 'nullable|integer|exists:supports_companies,id',
            'user_id' => 'nullable|integer|exists:users,id',
            'stage_id' => 'nullable|integer|exists:projects_project_stages,id',
            'project_type' => 'nullable|string|max:50',
            'lead_source' => 'nullable|string|max:50',
            'budget_range' => 'nullable|string|max:50',
            'complexity_score' => 'nullable|numeric|min:0|max:100',
            'description' => 'nullable|string',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'desired_completion_date' => 'nullable|date',
            'allocated_hours' => 'nullable|numeric|min:0',
            'estimated_linear_feet' => 'nullable|numeric|min:0',
            'visibility' => 'nullable|string|in:internal,public,private',
            'is_active' => 'nullable|boolean',
            'allow_timesheets' => 'nullable|boolean',
            'allow_milestones' => 'nullable|boolean',
            'current_production_stage' => 'nullable|string|in:discovery,design,sourcing,material_reserved,material_issued,production,delivery',

            // Address
            'address' => 'nullable|array',
            'address.street1' => 'nullable|string|max:255',
            'address.street2' => 'nullable|string|max:255',
            'address.city' => 'nullable|string|max:100',
            'address.state' => 'nullable|string|max:100',
            'address.zip' => 'nullable|string|max:20',
            'address.country' => 'nullable|string|max:100',

            // Tags
            'tags' => 'nullable|array',
            'tags.*' => 'integer|exists:supports_tags,id',
        ];
    }

    protected function beforeStore(array $data, $request): array
    {
        // Set creator to current user if not provided
        if (!isset($data['creator_id'])) {
            $data['creator_id'] = $request->user()->id;
        }

        // Set default company if not provided
        if (empty($data['company_id'])) {
            $defaultCompany = Company::where('is_default', true)->first();
            $data['company_id'] = $defaultCompany?->id;
        }

        // Set default stage if not provided
        if (empty($data['stage_id'])) {
            $initialStage = ProjectStage::orderBy('sort')->first();
            $data['stage_id'] = $initialStage?->id;
        }

        // Auto-generate project number
        if (($data['auto_generate_number'] ?? true) && empty($data['project_number'])) {
            $data['project_number'] = $this->generateProjectNumber($data);
        }

        // Auto-generate project name
        if (($data['auto_generate_name'] ?? false) && empty($data['name'])) {
            $data['name'] = $this->generateProjectName($data);
        }

        // Ensure name is set (fallback)
        if (empty($data['name'])) {
            $data['name'] = $data['project_number'] ?? 'New Project';
        }

        // Set default visibility
        if (empty($data['visibility'])) {
            $data['visibility'] = 'internal';
        }

        // Calculate linear feet from rooms if provided
        if (!empty($data['rooms']) && empty($data['estimated_linear_feet'])) {
            $totalLf = 0;
            foreach ($data['rooms'] as $room) {
                $totalLf += (float) ($room['linear_feet'] ?? 0);
            }
            $data['estimated_linear_feet'] = $totalLf;
        }

        return $data;
    }

    protected function afterStore($model, $request): void
    {
        $data = $request->all();

        // Handle address creation
        if (!empty($data['address'])) {
            $this->createOrUpdateAddress($model, $data['address']);
        }

        // Sync tags
        if (!empty($data['tags'])) {
            $model->tags()->sync($data['tags']);
        }

        // Create rooms with hierarchy
        if (!empty($data['rooms'])) {
            $this->createRoomsWithHierarchy($model, $data['rooms']);
        }

        // Calculate production estimate
        if (($data['calculate_production_estimate'] ?? false) &&
            !empty($model->estimated_linear_feet) &&
            !empty($model->company_id)) {
            $this->createProductionEstimate($model);
        }
    }

    protected function afterUpdate($model, $request): void
    {
        $data = $request->all();

        // Handle address update
        if (isset($data['address'])) {
            $this->createOrUpdateAddress($model, $data['address']);
        }

        // Sync tags if provided
        if (isset($data['tags'])) {
            $model->tags()->sync($data['tags']);
        }
    }

    /**
     * Generate a unique project number
     */
    protected function generateProjectNumber(array $data): string
    {
        $companyAcronym = 'TCS';
        if (!empty($data['company_id'])) {
            $company = Company::find($data['company_id']);
            $companyAcronym = $company?->acronym ?? strtoupper(substr($company?->name ?? 'TCS', 0, 3));
        }

        $startNumber = 1;
        if (!empty($data['company_id'])) {
            $company = Company::find($data['company_id']);
            $startNumber = $company?->project_number_start ?? 1;
        }

        $lastProject = Project::where('company_id', $data['company_id'] ?? null)
            ->where('project_number', 'like', "{$companyAcronym}-%")
            ->orderBy('id', 'desc')
            ->first();

        $sequentialNumber = $startNumber;
        if ($lastProject && $lastProject->project_number) {
            preg_match('/-(\d+)/', $lastProject->project_number, $matches);
            if (!empty($matches[1])) {
                $sequentialNumber = max(intval($matches[1]) + 1, $startNumber);
            }
        }

        $streetAbbr = '';
        if (!empty($data['address']['street1'])) {
            $street = preg_replace('/[^a-zA-Z0-9]/', '', $data['address']['street1']);
            $streetAbbr = substr($street, 0, 20);
        }

        $projectNumber = sprintf(
            '%s-%03d%s',
            $companyAcronym,
            $sequentialNumber,
            $streetAbbr ? "-{$streetAbbr}" : ''
        );

        // Ensure uniqueness
        $counter = 0;
        $originalNumber = $projectNumber;
        while (Project::where('project_number', $projectNumber)->exists()) {
            $counter++;
            $projectNumber = "{$originalNumber}-{$counter}";
        }

        return $projectNumber;
    }

    /**
     * Generate a project name from partner and address
     */
    protected function generateProjectName(array $data): string
    {
        $parts = [];

        // Add street address
        if (!empty($data['address']['street1'])) {
            $parts[] = $data['address']['street1'];
        }

        // Add partner name
        if (!empty($data['partner_id'])) {
            $partner = Partner::find($data['partner_id']);
            if ($partner) {
                $parts[] = $partner->name;
            }
        }

        // Add project type
        if (!empty($data['project_type'])) {
            $parts[] = ucfirst($data['project_type']);
        }

        if (empty($parts)) {
            return 'New Project - ' . now()->format('M j, Y');
        }

        return implode(' - ', $parts);
    }

    /**
     * Create or update project address
     */
    protected function createOrUpdateAddress(Project $project, array $addressData): void
    {
        if (empty($addressData['street1']) && empty($addressData['city'])) {
            return;
        }

        $address = $project->addresses()->where('is_primary', true)->first();

        $payload = [
            'type' => 'project',
            'street1' => $addressData['street1'] ?? null,
            'street2' => $addressData['street2'] ?? null,
            'city' => $addressData['city'] ?? null,
            'zip' => $addressData['zip'] ?? null,
            'is_primary' => true,
        ];

        if ($address) {
            $address->update($payload);
        } else {
            $project->addresses()->create($payload);
        }
    }

    /**
     * Create rooms with full hierarchy (locations, runs, cabinets)
     */
    protected function createRoomsWithHierarchy(Project $project, array $roomsData): void
    {
        $pricingService = app(TcsPricingService::class);
        $roomSort = 1;

        foreach ($roomsData as $roomData) {
            $linearFeet = (float) ($roomData['linear_feet'] ?? 0);
            $cabinetLevel = $roomData['cabinet_level'] ?? '2';
            $materialCategory = $roomData['material_category'] ?? 'stain_grade';
            $finishOption = $roomData['finish_option'] ?? 'unfinished';

            // Calculate estimated value
            $unitPrice = $pricingService->calculateUnitPrice($cabinetLevel, $materialCategory, $finishOption);
            $estimatedValue = $linearFeet * $unitPrice;

            // Map cabinet level to tier column
            $tierColumn = "total_linear_feet_tier_{$cabinetLevel}";

            $room = $project->rooms()->create([
                'name' => $roomData['name'] ?? 'Room ' . $roomSort,
                'room_type' => $roomData['room_type'] ?? 'other',
                'sort_order' => $roomSort++,
                'cabinet_level' => $cabinetLevel,
                'material_category' => $materialCategory,
                'finish_option' => $finishOption,
                $tierColumn => $linearFeet,
                'estimated_cabinet_value' => $estimatedValue,
            ]);

            // Create locations if provided
            if (!empty($roomData['locations'])) {
                $this->createLocationsWithHierarchy($project, $room, $roomData['locations']);
            }
        }
    }

    /**
     * Create locations with cabinet runs and cabinets
     */
    protected function createLocationsWithHierarchy(Project $project, Room $room, array $locationsData): void
    {
        $locSort = 1;

        foreach ($locationsData as $locData) {
            $location = $room->locations()->create([
                'project_id' => $project->id,
                'name' => $locData['name'] ?? 'Location ' . $locSort,
                'location_type' => $locData['location_type'] ?? 'wall',
                'cabinet_level' => $locData['cabinet_level'] ?? $room->cabinet_level,
                'sort_order' => $locSort++,
            ]);

            // Create cabinet runs if provided
            if (!empty($locData['cabinet_runs'])) {
                $this->createCabinetRunsWithCabinets($project, $room, $location, $locData['cabinet_runs']);
            }
        }
    }

    /**
     * Create cabinet runs with cabinets
     */
    protected function createCabinetRunsWithCabinets(Project $project, Room $room, RoomLocation $location, array $runsData): void
    {
        $runSort = 1;

        foreach ($runsData as $runData) {
            $run = $location->cabinetRuns()->create([
                'project_id' => $project->id,
                'room_id' => $room->id,
                'name' => $runData['name'] ?? 'Run ' . $runSort,
                'run_type' => $runData['run_type'] ?? 'base',
                'sort_order' => $runSort++,
            ]);

            // Create cabinets if provided
            if (!empty($runData['cabinets'])) {
                $cabSort = 1;
                foreach ($runData['cabinets'] as $cabData) {
                    $run->cabinets()->create([
                        'project_id' => $project->id,
                        'room_id' => $room->id,
                        'cabinet_number' => $cabData['cabinet_number'] ?? 'C' . $cabSort,
                        'length_inches' => $cabData['length_inches'] ?? 36,
                        'height_inches' => $cabData['height_inches'] ?? 34.5,
                        'depth_inches' => $cabData['depth_inches'] ?? 24,
                        'drawer_count' => $cabData['drawer_count'] ?? 0,
                        'door_count' => $cabData['door_count'] ?? 2,
                        'has_face_frame' => $cabData['has_face_frame'] ?? true,
                        'position_in_run' => $cabSort++,
                    ]);
                }
            }
        }
    }

    /**
     * Create production estimate for the project
     */
    protected function createProductionEstimate(Project $project): void
    {
        try {
            $estimate = ProductionEstimatorService::calculate(
                $project->estimated_linear_feet,
                $project->company_id
            );

            if ($estimate) {
                \App\Models\ProductionEstimate::createFromEstimate(
                    $project->id,
                    $project->company_id,
                    $project->estimated_linear_feet,
                    $estimate
                );
            }
        } catch (\Exception $e) {
            \Log::warning('Failed to create production estimate', [
                'project_id' => $project->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * GET /projects/{id}/tree - Get full project hierarchy
     */
    public function tree(int $id): JsonResponse
    {
        $project = Project::with([
            'rooms' => fn($q) => $q->orderBy('sort_order'),
            'rooms.locations' => fn($q) => $q->orderBy('sort_order'),
            'rooms.locations.cabinetRuns' => fn($q) => $q->orderBy('sort_order'),
            'rooms.locations.cabinetRuns.cabinets' => fn($q) => $q->orderBy('position_in_run'),
            'partner',
            'addresses',
            'tags',
        ])->find($id);

        if (!$project) {
            return $this->notFound('Project not found');
        }

        return $this->success($project, 'Project tree retrieved');
    }

    /**
     * POST /projects/{id}/change-stage - Change project stage with validation
     */
    public function changeStage(Request $request, int $id): JsonResponse
    {
        $project = Project::find($id);

        if (!$project) {
            return $this->notFound('Project not found');
        }

        $validated = $request->validate([
            'stage' => 'required|string|in:discovery,design,sourcing,material_reserved,material_issued,production,delivery',
        ]);

        $oldStage = $project->current_production_stage;
        $newStage = $validated['stage'];

        // Update the stage
        $project->update([
            'current_production_stage' => $newStage,
        ]);

        // Dispatch webhook event with stage change data
        $webhookData = $this->transformModel($project);
        $webhookData['old_stage'] = $oldStage;
        $webhookData['new_stage'] = $newStage;
        $this->dispatchWebhookEventWithData($webhookData, 'stage_changed');

        return $this->success([
            'project' => $project->fresh(),
            'old_stage' => $oldStage,
            'new_stage' => $newStage,
        ], "Project stage changed from {$oldStage} to {$newStage}");
    }

    /**
     * GET /projects/{id}/calculate - Calculate project totals and pricing
     */
    public function calculate(int $id): JsonResponse
    {
        $project = Project::with([
            'rooms.locations.cabinetRuns.cabinets',
        ])->find($id);

        if (!$project) {
            return $this->notFound('Project not found');
        }

        $pricingService = app(TcsPricingService::class);

        $totalLinearFeet = 0;
        $totalCabinets = 0;
        $totalDoors = 0;
        $totalDrawers = 0;
        $estimatedValue = 0;

        foreach ($project->rooms as $room) {
            foreach ($room->locations as $location) {
                foreach ($location->cabinetRuns as $run) {
                    foreach ($run->cabinets as $cabinet) {
                        $totalCabinets++;
                        $totalLinearFeet += ($cabinet->length_inches / 12);
                        $totalDoors += $cabinet->door_count ?? 0;
                        $totalDrawers += $cabinet->drawer_count ?? 0;
                        $estimatedValue += $cabinet->total_price ?? 0;
                    }
                }
            }
        }

        // Update project totals
        $project->update([
            'estimated_linear_feet' => round($totalLinearFeet, 2),
            'total_cabinet_count' => $totalCabinets,
            'total_door_count' => $totalDoors,
            'total_drawer_count' => $totalDrawers,
        ]);

        return $this->success([
            'project_id' => $project->id,
            'total_linear_feet' => round($totalLinearFeet, 2),
            'total_cabinets' => $totalCabinets,
            'total_doors' => $totalDoors,
            'total_drawers' => $totalDrawers,
            'estimated_value' => round($estimatedValue, 2),
        ], 'Project calculations completed');
    }
}
