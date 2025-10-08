<?php

namespace App\Services;

use App\Models\PdfPageAnnotation;
use Webkul\Project\Models\Room;
use Webkul\Project\Models\RoomLocation;
use Webkul\Project\Models\CabinetRun;
use Webkul\Project\Models\CabinetSpecification;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AnnotationEntityService
{
    /**
     * Create or link entity from annotation based on annotation type and context
     *
     * @param PdfPageAnnotation $annotation
     * @param array $context Context data (project_id, selected_room_id, etc.)
     * @return array Created/linked entity details
     */
    public function createOrLinkEntityFromAnnotation(PdfPageAnnotation $annotation, array $context): array
    {
        return match($annotation->annotation_type) {
            'room' => $this->createRoom($annotation, $context),
            'room_location' => $this->createRoomLocation($annotation, $context),
            'cabinet_run' => $this->createCabinetRun($annotation, $context),
            'cabinet' => $this->createCabinet($annotation, $context),
            default => ['success' => false, 'error' => 'Unknown annotation type']
        };
    }

    /**
     * Create Room record from floor plan annotation
     *
     * @param PdfPageAnnotation $annotation
     * @param array $context Must contain: project_id, page_number
     * @return array
     */
    protected function createRoom(PdfPageAnnotation $annotation, array $context): array
    {
        try {
            DB::beginTransaction();

            $room = Room::create([
                'project_id' => $context['project_id'],
                'name' => $annotation->label ?? $annotation->room_type ?? 'Untitled Room',
                'room_type' => $annotation->room_type,
                'pdf_page_number' => $context['page_number'],
                'pdf_room_label' => $annotation->label,
                'notes' => $annotation->notes,
                'creator_id' => Auth::id(),
            ]);

            // Link annotation to room
            $annotation->update(['room_id' => $room->id]);

            DB::commit();

            return [
                'success' => true,
                'entity_type' => 'room',
                'entity_id' => $room->id,
                'entity' => $room,
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to create Room from annotation', [
                'annotation_id' => $annotation->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => 'Failed to create room: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Create RoomLocation record from floor plan or elevation annotation
     *
     * @param PdfPageAnnotation $annotation
     * @param array $context Must contain: room_id
     * @return array
     */
    protected function createRoomLocation(PdfPageAnnotation $annotation, array $context): array
    {
        try {
            DB::beginTransaction();

            if (empty($context['room_id'])) {
                return [
                    'success' => false,
                    'error' => 'Room ID is required to create Room Location',
                ];
            }

            $roomLocation = RoomLocation::create([
                'room_id' => $context['room_id'],
                'name' => $annotation->label ?? 'Untitled Location',
                'location_type' => $context['location_type'] ?? 'wall',
                'sequence' => $context['sequence'] ?? 0,
                'notes' => $annotation->notes,
                'creator_id' => Auth::id(),
            ]);

            // Link annotation to room location
            $annotation->update([
                'room_id' => $context['room_id'],
                'metadata' => array_merge($annotation->metadata ?? [], [
                    'room_location_id' => $roomLocation->id,
                ]),
            ]);

            DB::commit();

            return [
                'success' => true,
                'entity_type' => 'room_location',
                'entity_id' => $roomLocation->id,
                'entity' => $roomLocation,
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to create RoomLocation from annotation', [
                'annotation_id' => $annotation->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => 'Failed to create room location: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Create CabinetRun record from elevation annotation
     *
     * @param PdfPageAnnotation $annotation
     * @param array $context Must contain: room_location_id
     * @return array
     */
    protected function createCabinetRun(PdfPageAnnotation $annotation, array $context): array
    {
        try {
            DB::beginTransaction();

            if (empty($context['room_location_id'])) {
                return [
                    'success' => false,
                    'error' => 'Room Location ID is required to create Cabinet Run',
                ];
            }

            $cabinetRun = CabinetRun::create([
                'room_location_id' => $context['room_location_id'],
                'name' => $annotation->label ?? 'Untitled Run',
                'run_type' => $context['run_type'] ?? 'base',
                'notes' => $annotation->notes,
                'creator_id' => Auth::id(),
            ]);

            // Link annotation to cabinet run
            $annotation->update(['cabinet_run_id' => $cabinetRun->id]);

            DB::commit();

            return [
                'success' => true,
                'entity_type' => 'cabinet_run',
                'entity_id' => $cabinetRun->id,
                'entity' => $cabinetRun,
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to create CabinetRun from annotation', [
                'annotation_id' => $annotation->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => 'Failed to create cabinet run: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Create CabinetSpecification record from elevation/detail annotation
     *
     * @param PdfPageAnnotation $annotation
     * @param array $context Must contain: cabinet_run_id, project_id
     * @return array
     */
    protected function createCabinet(PdfPageAnnotation $annotation, array $context): array
    {
        try {
            DB::beginTransaction();

            if (empty($context['cabinet_run_id'])) {
                return [
                    'success' => false,
                    'error' => 'Cabinet Run ID is required to create Cabinet',
                ];
            }

            if (empty($context['project_id'])) {
                return [
                    'success' => false,
                    'error' => 'Project ID is required to create Cabinet',
                ];
            }

            // Default product variant (should be passed from context in real implementation)
            $productVariantId = $context['product_variant_id'] ?? 1;

            $cabinet = CabinetSpecification::create([
                'cabinet_run_id' => $context['cabinet_run_id'],
                'project_id' => $context['project_id'],
                'product_variant_id' => $productVariantId,
                'cabinet_number' => $annotation->label ?? null,
                'position_in_run' => $context['position_in_run'] ?? 0,
                'length_inches' => $context['length_inches'] ?? 0,
                'width_inches' => $context['width_inches'] ?? 0,
                'depth_inches' => $context['depth_inches'] ?? 0,
                'height_inches' => $context['height_inches'] ?? 0,
                'linear_feet' => isset($context['length_inches']) ? ($context['length_inches'] / 12) : 0,
                'quantity' => $context['quantity'] ?? 1,
                'unit_price_per_lf' => $context['unit_price_per_lf'] ?? 0,
                'total_price' => 0, // Will be calculated
                'hardware_notes' => $annotation->notes,
                'creator_id' => Auth::id(),
            ]);

            // Link annotation to cabinet
            $annotation->update(['cabinet_specification_id' => $cabinet->id]);

            DB::commit();

            return [
                'success' => true,
                'entity_type' => 'cabinet',
                'entity_id' => $cabinet->id,
                'entity' => $cabinet,
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to create Cabinet from annotation', [
                'annotation_id' => $annotation->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => 'Failed to create cabinet: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Update existing cabinet with dimensions/hardware from detail annotation
     *
     * @param int $cabinetId
     * @param array $data
     * @return array
     */
    public function updateCabinetSpecs(int $cabinetId, array $data): array
    {
        try {
            $cabinet = CabinetSpecification::findOrFail($cabinetId);

            $updateData = [];

            if (isset($data['length_inches'])) {
                $updateData['length_inches'] = $data['length_inches'];
                $updateData['linear_feet'] = $data['length_inches'] / 12;
            }

            if (isset($data['width_inches'])) {
                $updateData['width_inches'] = $data['width_inches'];
            }

            if (isset($data['depth_inches'])) {
                $updateData['depth_inches'] = $data['depth_inches'];
            }

            if (isset($data['height_inches'])) {
                $updateData['height_inches'] = $data['height_inches'];
            }

            if (isset($data['hardware_notes'])) {
                $updateData['hardware_notes'] = $data['hardware_notes'];
            }

            if (isset($data['custom_modifications'])) {
                $updateData['custom_modifications'] = $data['custom_modifications'];
            }

            if (isset($data['shop_notes'])) {
                $updateData['shop_notes'] = $data['shop_notes'];
            }

            $cabinet->update($updateData);

            return [
                'success' => true,
                'entity_type' => 'cabinet',
                'entity_id' => $cabinet->id,
                'entity' => $cabinet->fresh(),
            ];

        } catch (\Exception $e) {
            Log::error('Failed to update Cabinet specs', [
                'cabinet_id' => $cabinetId,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => 'Failed to update cabinet specs: ' . $e->getMessage(),
            ];
        }
    }
}
