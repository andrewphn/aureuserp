<?php

namespace Webkul\Project\Services;

use App\Models\PdfPage;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Webkul\Project\Models\Cabinet;
use Webkul\Project\Models\CabinetRun;
use Webkul\Project\Models\PdfDocument;
use Webkul\Project\Models\Project;
use Webkul\Project\Models\Room;
use Webkul\Project\Models\RoomLocation;

/**
 * PdfPageEntityService
 *
 * Handles the flow from PDF page classification to entity creation.
 * This is the bridge between the PDF review wizard and the entity hierarchy.
 *
 * Design Philosophy:
 * - Pages are classified → Entities are created/linked → Data flows to entities
 * - The draft stores only wizard state, not business data
 * - Progressive creation: entities created as user moves through wizard
 */
class PdfPageEntityService
{
    /**
     * Initialize PDF pages for a document
     * Called when a PDF is first loaded in the wizard
     *
     * @param int $documentId
     * @param int $projectId
     * @param int $pageCount
     * @return \Illuminate\Support\Collection<PdfPage>
     */
    public function initializePagesForDocument(int $documentId, int $projectId, int $pageCount): \Illuminate\Support\Collection
    {
        // Check if pages already exist
        $existingPages = PdfPage::where('document_id', $documentId)->get();

        if ($existingPages->count() === $pageCount) {
            return $existingPages;
        }

        // Create missing pages
        return DB::transaction(function () use ($documentId, $projectId, $pageCount, $existingPages) {
            $existingPageNumbers = $existingPages->pluck('page_number')->toArray();

            for ($i = 1; $i <= $pageCount; $i++) {
                if (!in_array($i, $existingPageNumbers)) {
                    PdfPage::create([
                        'document_id' => $documentId,
                        'project_id' => $projectId,
                        'page_number' => $i,
                        'creator_id' => auth()->id(),
                    ]);
                }
            }

            return PdfPage::where('document_id', $documentId)->orderBy('page_number')->get();
        });
    }

    /**
     * Classify a page and optionally create/link entity
     *
     * @param PdfPage $page
     * @param string $pageType
     * @param array $entityData Optional data to create/link entity
     * @return PdfPage
     */
    public function classifyPage(PdfPage $page, string $pageType, array $entityData = []): PdfPage
    {
        return DB::transaction(function () use ($page, $pageType, $entityData) {
            // Update page classification
            $page->classify($pageType, $entityData['page_label'] ?? null);

            // If entity data provided, create/link entity
            if (!empty($entityData) && $this->shouldCreateEntity($pageType, $entityData)) {
                $entity = $this->createOrLinkEntity($page, $pageType, $entityData);
                if ($entity) {
                    $page->linkToEntity($entity);
                }
            }

            return $page->fresh();
        });
    }

    /**
     * Create or link an entity based on page type
     *
     * Uses PRIMARY_PURPOSES keys from PdfPage model:
     * - cover, floor_plan, elevations, countertops, reference, other
     *
     * @param PdfPage $page
     * @param string $pageType
     * @param array $data
     * @return Model|null
     */
    public function createOrLinkEntity(PdfPage $page, string $pageType, array $data): ?Model
    {
        return match ($pageType) {
            'cover' => $this->handleCoverPageEntity($page, $data),
            'floor_plan' => $this->handleFloorPlanEntity($page, $data),
            'elevations' => $this->handleElevationsEntity($page, $data),
            'countertops' => $this->handleCountertopsEntity($page, $data),
            default => null,
        };
    }

    /**
     * Handle Cover Page - links to Project, updates project metadata
     */
    protected function handleCoverPageEntity(PdfPage $page, array $data): ?Model
    {
        $project = $page->project;

        if (!$project) {
            return null;
        }

        // Update project with cover page data
        $updateData = [];

        // Address info → Project address
        if (!empty($data['address'])) {
            $updateData['project_address'] = $this->formatAddress($data['address']);
        }

        // Designer info → Custom fields or JSON
        if (!empty($data['designer'])) {
            // Store in project's flexible field or a related table
            // For now, we'll use description or a JSON column if available
            $updateData['description'] = $this->appendToDescription(
                $project->description,
                'Designer: ' . ($data['designer']['company'] ?? '') .
                ($data['designer']['drawn_by'] ? ' (Drawn by: ' . $data['designer']['drawn_by'] . ')' : '')
            );
        }

        if (!empty($updateData)) {
            $project->update($updateData);
        }

        return $project;
    }

    /**
     * Handle Floor Plan - creates Room entities
     */
    protected function handleFloorPlanEntity(PdfPage $page, array $data): ?Model
    {
        $project = $page->project;

        if (!$project) {
            return null;
        }

        // If specific room is specified, create/find it
        if (!empty($data['room_name'])) {
            return $this->findOrCreateRoom($project, $data['room_name'], $page, $data);
        }

        // If multiple rooms mentioned, create them all and link page to first
        if (!empty($data['rooms']) && is_array($data['rooms'])) {
            $firstRoom = null;
            foreach ($data['rooms'] as $roomName) {
                $room = $this->findOrCreateRoom($project, $roomName, $page, $data);
                if (!$firstRoom) {
                    $firstRoom = $room;
                } else {
                    // Add as additional entity link
                    $page->addAdditionalEntityLink($room);
                }
            }
            return $firstRoom;
        }

        // Create room from page label
        if (!empty($data['page_label'])) {
            return $this->findOrCreateRoom($project, $data['page_label'], $page, $data);
        }

        return null;
    }

    /**
     * Handle Elevations - creates RoomLocation entities
     */
    protected function handleElevationsEntity(PdfPage $page, array $data): ?Model
    {
        $project = $page->project;

        if (!$project) {
            return null;
        }

        // Need a room to create a location
        $room = null;

        // If room specified, find/create it
        if (!empty($data['room_id'])) {
            $room = Room::find($data['room_id']);
        } elseif (!empty($data['room_name'])) {
            $room = $this->findOrCreateRoom($project, $data['room_name'], null, []);
        }

        if (!$room) {
            // Try to infer room from page label (e.g., "Kitchen - Sink Wall")
            $roomName = $this->extractRoomFromLabel($data['page_label'] ?? '');
            if ($roomName) {
                $room = $this->findOrCreateRoom($project, $roomName, null, []);
            }
        }

        if (!$room) {
            Log::warning('PdfPageEntityService: Cannot create RoomLocation without Room', [
                'page_id' => $page->id,
                'data' => $data,
            ]);
            return null;
        }

        // Create/find location
        $locationName = $data['location_name'] ?? $data['page_label'] ?? 'Unnamed Location';
        return $this->findOrCreateRoomLocation($room, $locationName, $page, $data);
    }

    /**
     * Handle Countertops - creates RoomLocation with countertop data
     */
    protected function handleCountertopsEntity(PdfPage $page, array $data): ?Model
    {
        // Countertops are typically associated with a room location
        // Reuse elevation logic but mark as countertop-focused
        $location = $this->handleElevationsEntity($page, array_merge($data, [
            'location_type' => 'countertop',
        ]));

        if ($location instanceof RoomLocation) {
            // Update with countertop-specific data
            $location->update([
                'countertop_type' => $data['countertop_type'] ?? null,
                'backsplash_sqft' => $data['backsplash_sqft'] ?? null,
            ]);
        }

        return $location;
    }

    /**
     * Find or create a Room entity
     */
    protected function findOrCreateRoom(Project $project, string $name, ?PdfPage $page, array $data): Room
    {
        // Try to find existing room
        $room = Room::where('project_id', $project->id)
            ->where('name', $name)
            ->first();

        if ($room) {
            return $room;
        }

        // Create new room
        return Room::create([
            'project_id' => $project->id,
            'name' => $name,
            'room_type' => $data['room_type'] ?? $this->inferRoomType($name),
            'floor_number' => $data['floor_number'] ?? $this->inferFloorNumber($name),
            'pdf_page_number' => $page?->page_number,
            'pdf_room_label' => $data['page_label'] ?? $name,
            'creator_id' => auth()->id(),
        ]);
    }

    /**
     * Find or create a RoomLocation entity
     */
    protected function findOrCreateRoomLocation(Room $room, string $name, ?PdfPage $page, array $data): RoomLocation
    {
        // Try to find existing location
        $location = RoomLocation::where('room_id', $room->id)
            ->where('name', $name)
            ->first();

        if ($location) {
            // Update with new data if provided
            if (!empty($data['linear_feet'])) {
                $location->update(['overall_width_inches' => ($data['linear_feet'] ?? 0) * 12]);
            }
            return $location;
        }

        // Create new location
        return RoomLocation::create([
            'room_id' => $room->id,
            'name' => $name,
            'location_type' => $data['location_type'] ?? $this->inferLocationType($name),
            'elevation_reference' => $page?->page_number ? "PDF Page {$page->page_number}" : null,
            'overall_width_inches' => ($data['linear_feet'] ?? 0) * 12,
            'cabinet_level' => $data['pricing_tier'] ?? $room->cabinet_level,
            'material_category' => $data['material_category'] ?? $room->material_category,
            'finish_option' => $data['finish_option'] ?? $room->finish_option,
            'creator_id' => auth()->id(),
        ]);
    }

    /**
     * Check if we should create an entity for this page type
     */
    protected function shouldCreateEntity(string $pageType, array $data): bool
    {
        // Always create for these types if data provided
        if (in_array($pageType, ['floor_plan', 'elevations'])) {
            return !empty($data['room_name']) || !empty($data['rooms']) || !empty($data['page_label']);
        }

        // Cover updates project, always process
        if ($pageType === 'cover') {
            return !empty($data['address']) || !empty($data['designer']);
        }

        return false;
    }

    /**
     * Format address array into string
     */
    protected function formatAddress(array $address): string
    {
        $parts = array_filter([
            $address['street'] ?? null,
            $address['city'] ?? null,
            $address['state'] ?? null,
            $address['zip'] ?? null,
        ]);

        return implode(', ', $parts);
    }

    /**
     * Append text to description without duplicating
     */
    protected function appendToDescription(?string $existing, string $new): string
    {
        if (empty($existing)) {
            return $new;
        }

        if (str_contains($existing, $new)) {
            return $existing;
        }

        return $existing . "\n" . $new;
    }

    /**
     * Extract room name from a page label
     * e.g., "Kitchen - Sink Wall" → "Kitchen"
     */
    protected function extractRoomFromLabel(string $label): ?string
    {
        // Common patterns
        $patterns = [
            '/^([^-–—]+)\s*[-–—]/', // "Kitchen - Sink Wall" → "Kitchen"
            '/^(.+?)\s+(?:Elevation|Wall|Island|Cabinet)/', // "Kitchen Elevation" → "Kitchen"
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $label, $matches)) {
                return trim($matches[1]);
            }
        }

        return null;
    }

    /**
     * Infer room type from name
     */
    protected function inferRoomType(string $name): string
    {
        $name = strtolower($name);

        $types = [
            'kitchen' => 'kitchen',
            'bath' => 'bathroom',
            'laundry' => 'laundry',
            'mudroom' => 'mudroom',
            'pantry' => 'pantry',
            'closet' => 'closet',
            'office' => 'office',
            'living' => 'living_room',
            'family' => 'family_room',
            'dining' => 'dining_room',
            'bedroom' => 'bedroom',
            'garage' => 'garage',
            'basement' => 'basement',
        ];

        foreach ($types as $keyword => $type) {
            if (str_contains($name, $keyword)) {
                return $type;
            }
        }

        return 'other';
    }

    /**
     * Infer floor number from name
     */
    protected function inferFloorNumber(string $name): ?int
    {
        $name = strtolower($name);

        if (str_contains($name, 'basement') || str_contains($name, 'lower')) {
            return 0;
        }
        if (str_contains($name, 'first') || str_contains($name, '1st') || str_contains($name, 'ground')) {
            return 1;
        }
        if (str_contains($name, 'second') || str_contains($name, '2nd')) {
            return 2;
        }
        if (str_contains($name, 'third') || str_contains($name, '3rd')) {
            return 3;
        }

        // Try to extract number
        if (preg_match('/(\d+)(?:st|nd|rd|th)?\s*floor/i', $name, $matches)) {
            return (int) $matches[1];
        }

        return null;
    }

    /**
     * Infer location type from name
     */
    protected function inferLocationType(string $name): string
    {
        $name = strtolower($name);

        $types = [
            'sink' => 'sink_wall',
            'range' => 'range_wall',
            'stove' => 'range_wall',
            'cooktop' => 'range_wall',
            'island' => 'island',
            'peninsula' => 'peninsula',
            'pantry' => 'pantry',
            'refrigerator' => 'refrigerator_wall',
            'fridge' => 'refrigerator_wall',
            'upper' => 'upper_cabinets',
            'base' => 'base_cabinets',
            'tall' => 'tall_cabinets',
        ];

        foreach ($types as $keyword => $type) {
            if (str_contains($name, $keyword)) {
                return $type;
            }
        }

        return 'general';
    }

    /**
     * Get summary of entities created/linked for a document
     */
    public function getDocumentEntitySummary(int $documentId): array
    {
        $pages = PdfPage::where('document_id', $documentId)
            ->with('linkedEntity')
            ->orderBy('page_number')
            ->get();

        return [
            'total_pages' => $pages->count(),
            'classified' => $pages->whereNotNull('primary_purpose')->count(),
            'entity_linked' => $pages->whereNotNull('linked_entity_id')->count(),
            'by_type' => $pages->groupBy('primary_purpose')->map->count(),
            'entities' => $pages->filter(fn($p) => $p->linkedEntity)->map(fn($p) => [
                'page' => $p->page_number,
                'type' => $p->entity_type_short_name,
                'name' => $p->linked_entity_name,
            ])->values(),
        ];
    }
}
