<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Webkul\Project\Models\Project;
use Webkul\Project\Models\Room;
use Webkul\Project\Models\RoomLocation;
use Webkul\Project\Models\CabinetRun;
use Webkul\Project\Models\Cabinet;
use Webkul\Project\Models\CabinetSection;
use Webkul\Project\Models\Drawer;
use Webkul\Project\Models\Door;
use Webkul\Project\Models\Shelf;
use Webkul\Project\Models\Stretcher;
use Webkul\Project\Models\FalseFront;

/**
 * DrawingAnalysisPersistenceService
 *
 * Maps the 10-step drawing analysis pipeline JSON output to Eloquent models.
 * Follows the database write order for referential integrity:
 *
 * 1. projects_projects
 * 2. projects_rooms
 * 3. projects_room_locations
 * 4. projects_cabinet_runs
 * 5. projects_cabinets
 * 6. projects_cabinet_sections
 * 7. projects_drawers / projects_doors / projects_shelves
 */
class DrawingAnalysisPersistenceService
{
    // Mapping of extracted entity IDs to database IDs
    protected array $idMap = [];

    // Tracking for rollback
    protected array $createdRecords = [];

    // Pipeline data from all steps
    protected array $pipelineData = [];

    // Current session/transaction ID
    protected ?string $sessionId = null;

    /**
     * Persist all extracted data from the drawing analysis pipeline
     *
     * @param array $pipelineOutput - Complete output from DrawingAnalysisOrchestrator
     * @param array $options - Persistence options (dry_run, update_existing, etc.)
     * @return array Persistence result with created record IDs
     */
    public function persist(array $pipelineOutput, array $options = []): array
    {
        $this->sessionId = $pipelineOutput['session_id'] ?? uniqid('persist_', true);
        $dryRun = $options['dry_run'] ?? false;
        $updateExisting = $options['update_existing'] ?? false;

        $this->pipelineData = $pipelineOutput['extracted_data'] ?? $pipelineOutput;
        $this->idMap = [];
        $this->createdRecords = [];

        Log::info("Starting drawing analysis persistence", [
            'session_id' => $this->sessionId,
            'dry_run' => $dryRun,
        ]);

        try {
            if ($dryRun) {
                return $this->generateDryRunReport();
            }

            DB::beginTransaction();

            // Step 1: Persist or find Project
            $project = $this->persistProject($updateExisting);

            // Step 2: Persist Rooms
            $rooms = $this->persistRooms($project, $updateExisting);

            // Step 3: Persist Locations
            $locations = $this->persistLocations($rooms, $updateExisting);

            // Step 4: Persist Cabinet Runs
            $runs = $this->persistCabinetRuns($locations, $updateExisting);

            // Step 5: Persist Cabinets
            $cabinets = $this->persistCabinets($runs, $project, $updateExisting);

            // Step 6: Persist Sections
            $sections = $this->persistSections($cabinets, $updateExisting);

            // Step 7: Persist Components (drawers, doors, shelves, stretchers)
            $components = $this->persistComponents($sections, $cabinets, $updateExisting);

            DB::commit();

            Log::info("Drawing analysis persistence completed", [
                'session_id' => $this->sessionId,
                'records_created' => count($this->createdRecords),
            ]);

            return [
                'success' => true,
                'session_id' => $this->sessionId,
                'id_map' => $this->idMap,
                'created_records' => $this->createdRecords,
                'summary' => $this->buildPersistenceSummary(),
            ];

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error("Drawing analysis persistence failed", [
                'session_id' => $this->sessionId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'success' => false,
                'session_id' => $this->sessionId,
                'error' => $e->getMessage(),
                'partial_records' => $this->createdRecords,
            ];
        }
    }

    /**
     * Persist or find the Project
     */
    protected function persistProject(bool $updateExisting): Project
    {
        $entities = $this->getEntities();
        $projectData = $entities['project'] ?? null;

        if (!$projectData) {
            throw new \RuntimeException("No project entity found in extraction data");
        }

        $notes = $this->getNotes();
        $titleBlock = $notes['title_block'] ?? [];

        // Try to find existing project
        $project = null;
        if ($updateExisting && isset($projectData['name'])) {
            $project = Project::where('name', $projectData['name'])->first();
        }

        if ($project) {
            Log::info("Found existing project", ['id' => $project->id, 'name' => $project->name]);
        } else {
            $project = Project::create([
                'name' => $projectData['name'] ?? $titleBlock['project_name'] ?? 'Imported Project',
                'description' => $projectData['description'] ?? "Imported from drawing {$titleBlock['drawing_number'] ?? 'unknown'}",
                'notes' => $this->formatProjectNotes(),
            ]);

            $this->trackCreated('project', $project);
        }

        $this->idMap[$projectData['id'] ?? 'PRJ-001'] = $project->id;
        $this->idMap['project'] = $project->id;

        return $project;
    }

    /**
     * Persist Rooms
     */
    protected function persistRooms(Project $project, bool $updateExisting): array
    {
        $entities = $this->getEntities();
        $roomsData = $entities['rooms'] ?? [];
        $rooms = [];

        foreach ($roomsData as $roomData) {
            $room = null;

            if ($updateExisting && isset($roomData['name'])) {
                $room = Room::where('project_id', $project->id)
                    ->where('name', $roomData['name'])
                    ->first();
            }

            if (!$room) {
                $room = Room::create([
                    'project_id' => $project->id,
                    'name' => $roomData['name'] ?? 'Room',
                    'room_type' => $this->mapRoomType($roomData['type'] ?? 'other'),
                    'floor_number' => $roomData['floor'] ?? '1',
                    'notes' => $roomData['notes'] ?? null,
                ]);

                $this->trackCreated('room', $room);
            }

            $this->idMap[$roomData['id']] = $room->id;
            $rooms[$roomData['id']] = $room;
        }

        return $rooms;
    }

    /**
     * Persist Room Locations
     */
    protected function persistLocations(array $rooms, bool $updateExisting): array
    {
        $entities = $this->getEntities();
        $locationsData = $entities['locations'] ?? [];
        $locations = [];

        foreach ($locationsData as $locationData) {
            $roomId = $this->idMap[$locationData['parent_id']] ?? null;

            if (!$roomId) {
                Log::warning("Room not found for location", ['location' => $locationData['id']]);
                continue;
            }

            $location = null;

            if ($updateExisting && isset($locationData['name'])) {
                $location = RoomLocation::where('room_id', $roomId)
                    ->where('name', $locationData['name'])
                    ->first();
            }

            if (!$location) {
                // Get hardware specs from notes
                $hardwareSpecs = $this->extractHardwareSpecs();

                $location = RoomLocation::create([
                    'room_id' => $roomId,
                    'name' => $locationData['name'] ?? 'Location',
                    'location_type' => $locationData['type'] ?? 'wall',
                    'sequence' => $locationData['sequence'] ?? 1,
                    // Material specs from notes
                    'material_type' => $this->extractMaterialType(),
                    'wood_species' => $this->extractWoodSpecies(),
                    // Hardware specs
                    'soft_close_doors' => $hardwareSpecs['soft_close_doors'] ?? true,
                    'soft_close_drawers' => $hardwareSpecs['soft_close_drawers'] ?? true,
                    'notes' => $locationData['notes'] ?? null,
                ]);

                $this->trackCreated('location', $location);
            }

            $this->idMap[$locationData['id']] = $location->id;
            $locations[$locationData['id']] = $location;
        }

        return $locations;
    }

    /**
     * Persist Cabinet Runs
     */
    protected function persistCabinetRuns(array $locations, bool $updateExisting): array
    {
        $entities = $this->getEntities();
        $runsData = $entities['cabinet_runs'] ?? [];
        $runs = [];

        foreach ($runsData as $runData) {
            $locationId = $this->idMap[$runData['parent_id']] ?? null;

            if (!$locationId) {
                Log::warning("Location not found for run", ['run' => $runData['id']]);
                continue;
            }

            $run = null;

            if ($updateExisting && isset($runData['name'])) {
                $run = CabinetRun::where('room_location_id', $locationId)
                    ->where('name', $runData['name'])
                    ->first();
            }

            if (!$run) {
                $run = CabinetRun::create([
                    'room_location_id' => $locationId,
                    'name' => $runData['name'] ?? 'Cabinet Run',
                    'run_type' => $this->mapRunType($runData['type'] ?? 'base'),
                    'total_linear_feet' => $runData['linear_feet'] ?? null,
                    'notes' => $runData['notes'] ?? null,
                ]);

                $this->trackCreated('cabinet_run', $run);
            }

            $this->idMap[$runData['id']] = $run->id;
            $runs[$runData['id']] = $run;
        }

        return $runs;
    }

    /**
     * Persist Cabinets
     */
    protected function persistCabinets(array $runs, Project $project, bool $updateExisting): array
    {
        $entities = $this->getEntities();
        $cabinetsData = $entities['cabinets'] ?? [];
        $verification = $this->getVerification();
        $constraints = $this->getConstraints();
        $cabinets = [];

        foreach ($cabinetsData as $cabinetData) {
            $runId = $this->idMap[$cabinetData['parent_id']] ?? null;
            $run = $runs[$cabinetData['parent_id']] ?? null;

            if (!$runId) {
                Log::warning("Run not found for cabinet", ['cabinet' => $cabinetData['id']]);
                continue;
            }

            $cabinet = null;

            if ($updateExisting && isset($cabinetData['name'])) {
                $cabinet = Cabinet::where('cabinet_run_id', $runId)
                    ->where('name', $cabinetData['name'])
                    ->first();
            }

            if (!$cabinet) {
                // Get dimensions from bounding geometry
                $geometry = $cabinetData['bounding_geometry'] ?? [];
                $width = $this->extractNumericValue($geometry['width'] ?? null);
                $height = $this->extractNumericValue($geometry['height'] ?? null);
                $depth = $this->extractNumericValue($geometry['depth'] ?? null);

                // Get verification data for this cabinet
                $cabinetVerification = $this->findCabinetVerification($cabinetData['id']);

                // Get constraint values
                $gapConstraint = $this->findConstraint('gap_standard');
                $materialConstraint = $this->findConstraint('material_thickness');

                $cabinet = Cabinet::create([
                    'cabinet_run_id' => $runId,
                    'project_id' => $project->id,
                    'room_id' => $run?->roomLocation?->room_id,
                    'name' => $cabinetData['name'] ?? 'Cabinet',
                    'position_in_run' => $cabinetData['position_in_run'] ?? 1,
                    // Dimensions
                    'length_inches' => $width,
                    'height_inches' => $height,
                    'depth_inches' => $depth,
                    'linear_feet' => $width ? round($width / 12, 2) : null,
                    // Construction details from verification
                    'toe_kick_height' => $this->extractToeKickHeight($cabinetVerification),
                    'face_frame_stile_width_inches' => $this->extractStileWidth($cabinetVerification),
                    'face_frame_rail_width_inches' => $this->extractRailWidth($cabinetVerification),
                    'face_frame_door_gap_inches' => $gapConstraint['value'] ?? Cabinet::STANDARD_FACE_FRAME_DOOR_GAP,
                    // Material
                    'box_thickness' => $materialConstraint['value'] ?? Cabinet::DEFAULT_BOX_THICKNESS,
                    // Counts
                    'drawer_count' => $this->countDrawersForCabinet($cabinetData['id']),
                    'door_count' => $this->countDoorsForCabinet($cabinetData['id']),
                    // Construction type
                    'top_construction_type' => 'stretchers',
                    'stretcher_height_inches' => Cabinet::STANDARD_STRETCHER_HEIGHT,
                    // Notes
                    'shop_notes' => $this->formatCabinetNotes($cabinetData),
                ]);

                $this->trackCreated('cabinet', $cabinet);
            }

            $this->idMap[$cabinetData['id']] = $cabinet->id;
            $cabinets[$cabinetData['id']] = $cabinet;
        }

        return $cabinets;
    }

    /**
     * Persist Cabinet Sections
     */
    protected function persistSections(array $cabinets, bool $updateExisting): array
    {
        $entities = $this->getEntities();
        $sectionsData = $entities['sections'] ?? [];
        $sections = [];

        foreach ($sectionsData as $sectionData) {
            $cabinetId = $this->idMap[$sectionData['parent_id']] ?? null;
            $cabinet = $cabinets[$sectionData['parent_id']] ?? null;

            if (!$cabinetId) {
                Log::warning("Cabinet not found for section", ['section' => $sectionData['id']]);
                continue;
            }

            $section = null;

            if ($updateExisting && isset($sectionData['name'])) {
                $section = CabinetSection::where('cabinet_id', $cabinetId)
                    ->where('name', $sectionData['name'])
                    ->first();
            }

            if (!$section) {
                // Get section dimensions from db_mapping if available
                $dbMapping = $sectionData['_db_mapping']['columns'] ?? [];

                $section = CabinetSection::create([
                    'cabinet_id' => $cabinetId,
                    'name' => $sectionData['name'] ?? 'Section',
                    'section_type' => $this->mapSectionType($sectionData['type'] ?? 'opening'),
                    'section_number' => $sectionData['sequence'] ?? 1,
                    'width_inches' => $dbMapping['width_inches'] ?? null,
                    'height_inches' => $dbMapping['height_inches'] ?? null,
                    'opening_width_inches' => $dbMapping['opening_width_inches'] ?? null,
                    'opening_height_inches' => $dbMapping['opening_height_inches'] ?? null,
                ]);

                $this->trackCreated('section', $section);
            }

            $this->idMap[$sectionData['id']] = $section->id;
            $sections[$sectionData['id']] = $section;
        }

        return $sections;
    }

    /**
     * Persist Components (drawers, doors, shelves, stretchers)
     */
    protected function persistComponents(array $sections, array $cabinets, bool $updateExisting): array
    {
        $componentData = $this->getComponents();
        $components = [];

        foreach ($componentData as $comp) {
            $type = $comp['type'] ?? 'unknown';

            switch ($type) {
                case 'drawer':
                    $drawer = $this->persistDrawer($comp, $sections, $cabinets, $updateExisting);
                    if ($drawer) {
                        $components['drawers'][] = $drawer;
                        $this->idMap[$comp['id']] = $drawer->id;
                    }
                    break;

                case 'false_front':
                    $falseFront = $this->persistFalseFront($comp, $sections, $cabinets, $updateExisting);
                    if ($falseFront) {
                        $components['false_fronts'][] = $falseFront;
                        $this->idMap[$comp['id']] = $falseFront->id;
                    }
                    break;

                case 'door':
                    $door = $this->persistDoor($comp, $sections, $updateExisting);
                    if ($door) {
                        $components['doors'][] = $door;
                        $this->idMap[$comp['id']] = $door->id;
                    }
                    break;

                case 'shelf':
                    $shelf = $this->persistShelf($comp, $sections, $updateExisting);
                    if ($shelf) {
                        $components['shelves'][] = $shelf;
                        $this->idMap[$comp['id']] = $shelf->id;
                    }
                    break;

                case 'stretcher':
                    $stretcher = $this->persistStretcher($comp, $cabinets, $updateExisting);
                    if ($stretcher) {
                        $components['stretchers'][] = $stretcher;
                        $this->idMap[$comp['id']] = $stretcher->id;
                    }
                    break;
            }
        }

        return $components;
    }

    /**
     * Persist a single Drawer
     */
    protected function persistDrawer(array $data, array $sections, array $cabinets, bool $updateExisting): ?Drawer
    {
        $sectionId = $this->idMap[$data['parent_section_id'] ?? $data['parent_id']] ?? null;
        $cabinetId = $this->idMap[$data['parent_id']] ?? null;

        // Find cabinet from parent chain
        $cabinet = null;
        foreach ($cabinets as $extractedId => $cab) {
            if ($this->idMap[$extractedId] == $cabinetId || $extractedId == $data['parent_id']) {
                $cabinet = $cab;
                break;
            }
        }

        if (!$sectionId && !$cabinetId) {
            Log::warning("No parent found for drawer", ['drawer' => $data['id']]);
            return null;
        }

        $dims = $data['dimensions'] ?? [];
        $boxDims = $data['box_dimensions'] ?? [];
        $slideSpecs = $data['slide_specs'] ?? [];
        $dbMapping = $data['_db_mapping']['columns'] ?? [];

        $drawer = Drawer::create([
            'cabinet_id' => $cabinetId,
            'section_id' => $sectionId,
            'drawer_number' => $dbMapping['drawer_number'] ?? $data['position_number'] ?? 1,
            'drawer_name' => $dbMapping['drawer_name'] ?? $data['name'] ?? 'DR',
            'drawer_position' => $data['position'] ?? 'top',
            // Front dimensions
            'front_width_inches' => $this->extractDimensionValue($dims['width'] ?? null) ?? $dbMapping['front_width_inches'] ?? null,
            'front_height_inches' => $this->extractDimensionValue($dims['height'] ?? null) ?? $dbMapping['front_height_inches'] ?? null,
            'front_thickness_inches' => $this->extractDimensionValue($dims['thickness'] ?? null) ?? $dbMapping['front_thickness_inches'] ?? 0.75,
            // Box dimensions
            'box_width_inches' => $this->extractDimensionValue($boxDims['width'] ?? null) ?? $dbMapping['box_width_inches'] ?? null,
            'box_depth_inches' => $this->extractDimensionValue($boxDims['depth'] ?? null) ?? $dbMapping['box_depth_inches'] ?? null,
            'box_height_inches' => $this->extractDimensionValue($boxDims['height'] ?? null) ?? $dbMapping['box_height_inches'] ?? null,
            'box_material' => $dbMapping['box_material'] ?? 'maple',
            'box_thickness' => $dbMapping['box_thickness'] ?? 0.5,
            'joinery_method' => $dbMapping['joinery_method'] ?? 'dovetail',
            // Slides
            'slide_type' => $slideSpecs['type'] ?? $dbMapping['slide_type'] ?? 'undermount',
            'slide_model' => $slideSpecs['model'] ?? $dbMapping['slide_model'] ?? null,
            'slide_length_inches' => $slideSpecs['length'] ?? $dbMapping['slide_length_inches'] ?? null,
            'soft_close' => $slideSpecs['soft_close'] ?? $dbMapping['soft_close'] ?? true,
            // Profile
            'profile_type' => $dbMapping['profile_type'] ?? 'shaker',
            'fabrication_method' => $dbMapping['fabrication_method'] ?? 'cnc',
            // Notes
            'notes' => $data['derivation_summary'] ?? null,
        ]);

        $this->trackCreated('drawer', $drawer);

        return $drawer;
    }

    /**
     * Persist a False Front (stored as drawer without box)
     */
    protected function persistFalseFront(array $data, array $sections, array $cabinets, bool $updateExisting): ?FalseFront
    {
        $sectionId = $this->idMap[$data['parent_section_id'] ?? $data['parent_id']] ?? null;
        $cabinetId = $this->idMap[$data['parent_id']] ?? null;

        if (!$cabinetId) {
            Log::warning("No cabinet found for false front", ['false_front' => $data['id']]);
            return null;
        }

        $dims = $data['dimensions'] ?? [];
        $dbMapping = $data['_db_mapping']['columns'] ?? [];

        $falseFront = FalseFront::create([
            'cabinet_id' => $cabinetId,
            'section_id' => $sectionId,
            'false_front_number' => $dbMapping['drawer_number'] ?? 1,
            'false_front_name' => 'FF',
            'position' => $data['position'] ?? 'top',
            'width_inches' => $this->extractDimensionValue($dims['width'] ?? null) ?? $dbMapping['front_width_inches'] ?? null,
            'height_inches' => $this->extractDimensionValue($dims['height'] ?? null) ?? $dbMapping['front_height_inches'] ?? null,
            'thickness_inches' => $this->extractDimensionValue($dims['thickness'] ?? null) ?? 0.75,
            'profile_type' => $dbMapping['profile_type'] ?? 'shaker',
            'notes' => $data['derivation_summary'] ?? 'False front - no drawer box',
        ]);

        $this->trackCreated('false_front', $falseFront);

        return $falseFront;
    }

    /**
     * Persist a Door
     */
    protected function persistDoor(array $data, array $sections, bool $updateExisting): ?Door
    {
        $sectionId = $this->idMap[$data['parent_id']] ?? null;

        if (!$sectionId) {
            Log::warning("No section found for door", ['door' => $data['id']]);
            return null;
        }

        $dims = $data['dimensions'] ?? [];

        $door = Door::create([
            'section_id' => $sectionId,
            'door_number' => $data['position_number'] ?? 1,
            'door_name' => $data['name'] ?? 'DR',
            'width_inches' => $this->extractDimensionValue($dims['width'] ?? null),
            'height_inches' => $this->extractDimensionValue($dims['height'] ?? null),
            'hinge_side' => $data['hinge_side'] ?? 'left',
            'profile_type' => 'shaker',
            'notes' => $data['derivation_summary'] ?? null,
        ]);

        $this->trackCreated('door', $door);

        return $door;
    }

    /**
     * Persist a Shelf
     */
    protected function persistShelf(array $data, array $sections, bool $updateExisting): ?Shelf
    {
        $sectionId = $this->idMap[$data['parent_id']] ?? null;

        if (!$sectionId) {
            Log::warning("No section found for shelf", ['shelf' => $data['id']]);
            return null;
        }

        $dims = $data['dimensions'] ?? [];

        $shelf = Shelf::create([
            'section_id' => $sectionId,
            'shelf_number' => $data['position_number'] ?? 1,
            'width_inches' => $this->extractDimensionValue($dims['width'] ?? null),
            'depth_inches' => $this->extractDimensionValue($dims['depth'] ?? null),
            'shelf_type' => $data['shelf_type'] ?? 'adjustable',
            'notes' => $data['derivation_summary'] ?? null,
        ]);

        $this->trackCreated('shelf', $shelf);

        return $shelf;
    }

    /**
     * Persist a Stretcher
     */
    protected function persistStretcher(array $data, array $cabinets, bool $updateExisting): ?Stretcher
    {
        $cabinetId = $this->idMap[$data['parent_id']] ?? null;

        if (!$cabinetId) {
            Log::warning("No cabinet found for stretcher", ['stretcher' => $data['id']]);
            return null;
        }

        $dims = $data['dimensions'] ?? [];
        $stretcherDetails = $data['stretcher_details'] ?? [];

        $stretcher = Stretcher::create([
            'cabinet_id' => $cabinetId,
            'stretcher_type' => $stretcherDetails['purpose'] ?? 'drawer_support',
            'width_inches' => $this->extractDimensionValue($dims['width'] ?? null),
            'height_inches' => $this->extractDimensionValue($dims['height'] ?? null) ?? 3.0,
            'depth_inches' => $this->extractDimensionValue($dims['depth'] ?? null) ?? 3.5,
            'position_from_bottom_inches' => $stretcherDetails['vertical_position']['value'] ?? null,
            'notes' => $data['derivation_summary'] ?? null,
        ]);

        $this->trackCreated('stretcher', $stretcher);

        return $stretcher;
    }

    // =========================================================================
    // Helper Methods
    // =========================================================================

    protected function getEntities(): array
    {
        return $this->pipelineData['entities']['entities'] ?? $this->pipelineData['step_5_entity_extraction']['entities'] ?? [];
    }

    protected function getNotes(): array
    {
        return $this->pipelineData['notes'] ?? $this->pipelineData['step_3_notes_extraction'] ?? [];
    }

    protected function getVerification(): array
    {
        return $this->pipelineData['verification'] ?? $this->pipelineData['step_6_verification'] ?? [];
    }

    protected function getConstraints(): array
    {
        return $this->pipelineData['constraints'] ?? $this->pipelineData['step_8_constraints'] ?? [];
    }

    protected function getComponents(): array
    {
        return $this->pipelineData['components']['components'] ?? $this->pipelineData['step_9_components']['components'] ?? [];
    }

    protected function extractNumericValue(?array $data): ?float
    {
        if (!$data) return null;
        return $data['numeric'] ?? $data['value'] ?? null;
    }

    protected function extractDimensionValue(?array $data): ?float
    {
        if (!$data) return null;
        return $data['value'] ?? null;
    }

    protected function mapRoomType(string $type): string
    {
        return match(strtolower($type)) {
            'kitchen' => 'kitchen',
            'bathroom', 'bath' => 'bathroom',
            'laundry' => 'laundry',
            'office' => 'office',
            'pantry' => 'pantry',
            'mudroom' => 'mudroom',
            'closet' => 'closet',
            default => 'other',
        };
    }

    protected function mapRunType(string $type): string
    {
        return match(strtolower($type)) {
            'base', 'base_run' => 'base',
            'wall', 'upper', 'wall_run' => 'wall',
            'tall', 'tall_run' => 'tall',
            default => 'base',
        };
    }

    protected function mapSectionType(string $type): string
    {
        return match(strtolower($type)) {
            'drawer_stack', 'drawer_bank' => 'drawer_bank',
            'door_opening' => 'door_opening',
            'shelf_section' => 'shelf_section',
            default => 'opening',
        };
    }

    protected function findCabinetVerification(string $cabinetId): ?array
    {
        $verification = $this->getVerification();
        $cabinetVerifications = $verification['cabinet_verifications'] ?? [];

        foreach ($cabinetVerifications as $v) {
            if (($v['cabinet_id'] ?? '') === $cabinetId) {
                return $v;
            }
        }

        return $cabinetVerifications[0] ?? null;
    }

    protected function findConstraint(string $type): ?array
    {
        $constraints = $this->getConstraints();
        $constraintList = $constraints['constraints'] ?? [];

        foreach ($constraintList as $c) {
            if (($c['type'] ?? '') === $type) {
                return $c;
            }
        }

        return null;
    }

    protected function extractToeKickHeight(?array $verification): ?float
    {
        if (!$verification) return Cabinet::STANDARD_TOE_KICK_HEIGHT;

        $fixedElements = $verification['fixed_elements']['vertical'] ?? [];
        foreach ($fixedElements as $element) {
            if (($element['type'] ?? '') === 'toe_kick') {
                return $element['value'] ?? Cabinet::STANDARD_TOE_KICK_HEIGHT;
            }
        }

        return Cabinet::STANDARD_TOE_KICK_HEIGHT;
    }

    protected function extractStileWidth(?array $verification): ?float
    {
        if (!$verification) return Cabinet::STANDARD_FACE_FRAME_WIDTH;

        $fixedElements = $verification['fixed_elements']['horizontal'] ?? [];
        foreach ($fixedElements as $element) {
            if (in_array($element['type'] ?? '', ['left_stile', 'right_stile'])) {
                return $element['value'] ?? Cabinet::STANDARD_FACE_FRAME_WIDTH;
            }
        }

        return Cabinet::STANDARD_FACE_FRAME_WIDTH;
    }

    protected function extractRailWidth(?array $verification): ?float
    {
        if (!$verification) return Cabinet::STANDARD_FACE_FRAME_WIDTH;

        $fixedElements = $verification['fixed_elements']['vertical'] ?? [];
        foreach ($fixedElements as $element) {
            if (in_array($element['type'] ?? '', ['top_rail', 'bottom_rail', 'intermediate_rail'])) {
                return $element['value'] ?? Cabinet::STANDARD_FACE_FRAME_WIDTH;
            }
        }

        return Cabinet::STANDARD_FACE_FRAME_WIDTH;
    }

    protected function extractHardwareSpecs(): array
    {
        $notes = $this->getNotes();
        $noteList = $notes['notes'] ?? [];

        $specs = [
            'soft_close_doors' => true,
            'soft_close_drawers' => true,
        ];

        foreach ($noteList as $note) {
            if (($note['type'] ?? '') === 'hardware_spec') {
                $text = strtolower($note['text']['exact'] ?? '');
                if (str_contains($text, 'soft close')) {
                    $specs['soft_close_doors'] = true;
                    $specs['soft_close_drawers'] = true;
                }
            }
        }

        return $specs;
    }

    protected function extractMaterialType(): ?string
    {
        $notes = $this->getNotes();
        $noteList = $notes['notes'] ?? [];

        foreach ($noteList as $note) {
            if (($note['type'] ?? '') === 'material_spec') {
                $text = strtolower($note['text']['exact'] ?? '');
                if (str_contains($text, 'stain')) return 'stain_grade';
                if (str_contains($text, 'paint')) return 'paint_grade';
            }
        }

        return 'stain_grade';
    }

    protected function extractWoodSpecies(): ?string
    {
        $notes = $this->getNotes();
        $noteList = $notes['notes'] ?? [];

        foreach ($noteList as $note) {
            if (($note['type'] ?? '') === 'material_spec') {
                $text = strtolower($note['text']['exact'] ?? '');
                if (str_contains($text, 'maple')) return 'maple';
                if (str_contains($text, 'cherry')) return 'cherry';
                if (str_contains($text, 'oak')) return 'oak';
                if (str_contains($text, 'walnut')) return 'walnut';
            }
        }

        return 'maple';
    }

    protected function countDrawersForCabinet(string $cabinetId): int
    {
        $components = $this->getComponents();
        $count = 0;

        foreach ($components as $comp) {
            if (($comp['parent_id'] ?? '') === $cabinetId && ($comp['type'] ?? '') === 'drawer') {
                $count++;
            }
        }

        return $count;
    }

    protected function countDoorsForCabinet(string $cabinetId): int
    {
        $components = $this->getComponents();
        $count = 0;

        foreach ($components as $comp) {
            if (($comp['parent_id'] ?? '') === $cabinetId && ($comp['type'] ?? '') === 'door') {
                $count++;
            }
        }

        return $count;
    }

    protected function formatProjectNotes(): ?string
    {
        $notes = $this->getNotes();
        $noteList = $notes['notes'] ?? [];
        $projectNotes = [];

        foreach ($noteList as $note) {
            if (($note['scope'] ?? '') === 'project' || ($note['scope'] ?? '') === 'drawing') {
                $projectNotes[] = $note['text']['exact'] ?? '';
            }
        }

        return !empty($projectNotes) ? implode("\n", $projectNotes) : null;
    }

    protected function formatCabinetNotes(array $cabinetData): ?string
    {
        $notes = [];

        if (isset($cabinetData['notes'])) {
            $notes[] = $cabinetData['notes'];
        }

        // Add derivation info
        $geometry = $cabinetData['bounding_geometry'] ?? [];
        foreach (['width', 'height', 'depth'] as $dim) {
            if (isset($geometry[$dim]['source']) && $geometry[$dim]['source'] !== 'labeled') {
                $notes[] = ucfirst($dim) . " source: " . $geometry[$dim]['source'];
            }
        }

        return !empty($notes) ? implode("\n", $notes) : null;
    }

    protected function trackCreated(string $type, $model): void
    {
        $this->createdRecords[] = [
            'type' => $type,
            'id' => $model->id,
            'model' => get_class($model),
        ];
    }

    protected function buildPersistenceSummary(): array
    {
        $summary = [];

        foreach ($this->createdRecords as $record) {
            $type = $record['type'];
            if (!isset($summary[$type])) {
                $summary[$type] = 0;
            }
            $summary[$type]++;
        }

        return $summary;
    }

    protected function generateDryRunReport(): array
    {
        return [
            'success' => true,
            'session_id' => $this->sessionId,
            'dry_run' => true,
            'would_create' => [
                'projects' => 1,
                'rooms' => count($this->getEntities()['rooms'] ?? []),
                'locations' => count($this->getEntities()['locations'] ?? []),
                'cabinet_runs' => count($this->getEntities()['cabinet_runs'] ?? []),
                'cabinets' => count($this->getEntities()['cabinets'] ?? []),
                'sections' => count($this->getEntities()['sections'] ?? []),
                'components' => count($this->getComponents()),
            ],
            'entities_preview' => $this->getEntities(),
            'components_preview' => $this->getComponents(),
        ];
    }
}
