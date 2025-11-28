<?php

namespace Webkul\Project\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Webkul\Inventory\Models\Move;
use Webkul\Inventory\Models\ProductQuantity;
use Webkul\Inventory\Models\Warehouse;
use Webkul\Product\Models\Product;
use Webkul\Project\Models\CabinetMaterialsBom;
use Webkul\Project\Models\MaterialReservation;
use Webkul\Project\Models\Project;

/**
 * Inventory Reservation Service
 *
 * Handles material reservations for projects, including:
 * - Creating reservations from BOM items
 * - Checking material availability
 * - Issuing materials (creating inventory moves)
 * - Releasing/cancelling reservations
 */
class InventoryReservationService
{
    /**
     * Reserve all materials for a project based on its BOM
     *
     * @param Project $project
     * @param int|null $warehouseId Override project's default warehouse
     * @return array ['success' => bool, 'reservations' => Collection, 'errors' => array]
     */
    public function reserveMaterialsForProject(Project $project, ?int $warehouseId = null): array
    {
        $warehouseId = $warehouseId ?? $project->warehouse_id;

        if (!$warehouseId) {
            return [
                'success' => false,
                'reservations' => collect(),
                'errors' => ['No warehouse assigned to project. Please assign a warehouse first.'],
            ];
        }

        $warehouse = Warehouse::find($warehouseId);
        if (!$warehouse) {
            return [
                'success' => false,
                'reservations' => collect(),
                'errors' => ['Warehouse not found.'],
            ];
        }

        // Get all BOM items for the project
        $bomItems = CabinetMaterialsBom::whereHas('cabinetSpecification', function ($query) use ($project) {
            $query->where('project_id', $project->id);
        })
            ->whereNotNull('product_id')
            ->where('material_allocated', false)
            ->get();

        if ($bomItems->isEmpty()) {
            return [
                'success' => true,
                'reservations' => collect(),
                'errors' => [],
                'message' => 'No pending BOM items to reserve.',
            ];
        }

        $reservations = collect();
        $errors = [];

        DB::beginTransaction();

        try {
            foreach ($bomItems as $bomItem) {
                $result = $this->reserveBomItem($bomItem, $warehouse);

                if ($result['success']) {
                    $reservations->push($result['reservation']);
                } else {
                    $errors[] = $result['error'];
                }
            }

            DB::commit();

            return [
                'success' => empty($errors),
                'reservations' => $reservations,
                'errors' => $errors,
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to reserve materials for project', [
                'project_id' => $project->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'reservations' => collect(),
                'errors' => ['Failed to reserve materials: ' . $e->getMessage()],
            ];
        }
    }

    /**
     * Reserve a single BOM item
     *
     * @param CabinetMaterialsBom $bom
     * @param Warehouse $warehouse
     * @return array ['success' => bool, 'reservation' => MaterialReservation|null, 'error' => string|null]
     */
    public function reserveBomItem(CabinetMaterialsBom $bom, Warehouse $warehouse): array
    {
        if (!$bom->product_id) {
            return [
                'success' => false,
                'reservation' => null,
                'error' => "BOM item '{$bom->component_name}' has no product assigned.",
            ];
        }

        $quantity = $bom->quantity_with_waste ?? $bom->quantity_required;
        $unit = $bom->unit_of_measure ?? 'unit';

        // Check availability
        if (!$this->checkAvailability($bom->product_id, $warehouse->id, $quantity)) {
            $product = Product::find($bom->product_id);
            return [
                'success' => false,
                'reservation' => null,
                'error' => "Insufficient inventory for '" . ($product?->name ?? 'Unknown') . "'. Required: {$quantity} {$unit}.",
            ];
        }

        // Get project from cabinet specification
        $project = $bom->cabinetSpecification?->project;
        if (!$project) {
            return [
                'success' => false,
                'reservation' => null,
                'error' => "BOM item '{$bom->component_name}' is not linked to a project.",
            ];
        }

        // Create reservation
        $reservation = MaterialReservation::create([
            'project_id' => $project->id,
            'bom_id' => $bom->id,
            'product_id' => $bom->product_id,
            'warehouse_id' => $warehouse->id,
            'location_id' => $warehouse->lot_stock_location_id,
            'quantity_reserved' => $quantity,
            'unit_of_measure' => $unit,
            'status' => MaterialReservation::STATUS_RESERVED,
            'reserved_by' => auth()->id(),
            'reserved_at' => now(),
            'expires_at' => now()->addDays(30), // Default 30-day reservation
        ]);

        // Update ProductQuantity reserved amount
        $this->incrementReservedQuantity($bom->product_id, $warehouse->id, $quantity);

        // Mark BOM as allocated
        $bom->update([
            'material_allocated' => true,
            'material_allocated_at' => now(),
        ]);

        return [
            'success' => true,
            'reservation' => $reservation,
            'error' => null,
        ];
    }

    /**
     * Check if a product has sufficient available inventory
     *
     * @param int $productId
     * @param int $warehouseId
     * @param float $quantity
     * @return bool
     */
    public function checkAvailability(int $productId, int $warehouseId, float $quantity): bool
    {
        $warehouse = Warehouse::find($warehouseId);
        if (!$warehouse || !$warehouse->lot_stock_location_id) {
            return false;
        }

        $productQuantity = ProductQuantity::where('product_id', $productId)
            ->where('location_id', $warehouse->lot_stock_location_id)
            ->first();

        if (!$productQuantity) {
            return false;
        }

        // Available = Quantity - Reserved
        $available = $productQuantity->quantity - ($productQuantity->reserved_quantity ?? 0);

        return $available >= $quantity;
    }

    /**
     * Get available quantity for a product in a warehouse
     *
     * @param int $productId
     * @param int $warehouseId
     * @return float
     */
    public function getAvailableQuantity(int $productId, int $warehouseId): float
    {
        $warehouse = Warehouse::find($warehouseId);
        if (!$warehouse || !$warehouse->lot_stock_location_id) {
            return 0;
        }

        $productQuantity = ProductQuantity::where('product_id', $productId)
            ->where('location_id', $warehouse->lot_stock_location_id)
            ->first();

        if (!$productQuantity) {
            return 0;
        }

        return max(0, $productQuantity->quantity - ($productQuantity->reserved_quantity ?? 0));
    }

    /**
     * Release a reservation (cancel it)
     *
     * @param MaterialReservation $reservation
     * @param string|null $reason
     * @return bool
     */
    public function releaseReservation(MaterialReservation $reservation, ?string $reason = null): bool
    {
        if (!$reservation->can_be_cancelled) {
            return false;
        }

        DB::beginTransaction();

        try {
            // Decrement reserved quantity
            $this->decrementReservedQuantity(
                $reservation->product_id,
                $reservation->warehouse_id,
                $reservation->quantity_reserved
            );

            // Mark BOM as not allocated (if linked)
            if ($reservation->bom) {
                $reservation->bom->update([
                    'material_allocated' => false,
                    'material_allocated_at' => null,
                ]);
            }

            // Cancel the reservation
            $reservation->markAsCancelled($reason);

            DB::commit();
            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to release reservation', [
                'reservation_id' => $reservation->id,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Issue material from reservation (create inventory move)
     *
     * @param MaterialReservation $reservation
     * @return Move|null
     */
    public function issueMaterial(MaterialReservation $reservation): ?Move
    {
        if (!$reservation->can_be_issued) {
            return null;
        }

        $warehouse = $reservation->warehouse;
        if (!$warehouse) {
            return null;
        }

        DB::beginTransaction();

        try {
            // Get product for UOM reference
            $product = $reservation->product;

            // Create inventory move from stock location to output
            $move = Move::create([
                'name' => 'Project Material Issue: ' . $reservation->project?->project_number,
                'product_id' => $reservation->product_id,
                'source_location_id' => $warehouse->lot_stock_location_id,
                'destination_location_id' => $warehouse->output_stock_location_id,
                'quantity' => $reservation->quantity_reserved,
                'state' => 'done',
                'origin' => 'Project: ' . $reservation->project?->project_number,
                'is_picked' => true,
                'scheduled_at' => now(),
                'warehouse_id' => $warehouse->id,
                'company_id' => $reservation->project?->company_id ?? $warehouse->company_id,
                'uom_id' => $product?->uom_id,
            ]);

            // Decrement reserved quantity (it's now moved)
            $this->decrementReservedQuantity(
                $reservation->product_id,
                $reservation->warehouse_id,
                $reservation->quantity_reserved
            );

            // Update ProductQuantity (reduce actual quantity)
            $this->decrementQuantity(
                $reservation->product_id,
                $warehouse->lot_stock_location_id,
                $reservation->quantity_reserved
            );

            // Mark BOM as issued
            if ($reservation->bom) {
                $reservation->bom->update([
                    'material_issued' => true,
                    'material_issued_at' => now(),
                ]);
            }

            // Mark reservation as issued
            $reservation->markAsIssued($move);

            DB::commit();
            return $move;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to issue material', [
                'reservation_id' => $reservation->id,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Issue all reserved materials for a project
     *
     * @param Project $project
     * @return array ['success' => bool, 'moves' => Collection, 'errors' => array]
     */
    public function issueAllMaterialsForProject(Project $project): array
    {
        $reservations = MaterialReservation::forProject($project->id)
            ->reserved()
            ->notExpired()
            ->get();

        if ($reservations->isEmpty()) {
            return [
                'success' => true,
                'moves' => collect(),
                'errors' => [],
                'message' => 'No reserved materials to issue.',
            ];
        }

        $moves = collect();
        $errors = [];

        foreach ($reservations as $reservation) {
            $move = $this->issueMaterial($reservation);

            if ($move) {
                $moves->push($move);
            } else {
                $errors[] = "Failed to issue reservation #{$reservation->id} for product: {$reservation->product?->name}";
            }
        }

        return [
            'success' => empty($errors),
            'moves' => $moves,
            'errors' => $errors,
        ];
    }

    /**
     * Release all reservations for a project
     *
     * @param Project $project
     * @param string|null $reason
     * @return int Number of reservations released
     */
    public function releaseAllReservationsForProject(Project $project, ?string $reason = null): int
    {
        $reservations = MaterialReservation::forProject($project->id)
            ->active()
            ->get();

        $released = 0;
        foreach ($reservations as $reservation) {
            if ($this->releaseReservation($reservation, $reason)) {
                $released++;
            }
        }

        return $released;
    }

    /**
     * Get reservation summary for a project
     *
     * @param Project $project
     * @return array
     */
    public function getProjectReservationSummary(Project $project): array
    {
        $reservations = MaterialReservation::forProject($project->id)->get();

        return [
            'total' => $reservations->count(),
            'pending' => $reservations->where('status', MaterialReservation::STATUS_PENDING)->count(),
            'reserved' => $reservations->where('status', MaterialReservation::STATUS_RESERVED)->count(),
            'issued' => $reservations->where('status', MaterialReservation::STATUS_ISSUED)->count(),
            'cancelled' => $reservations->where('status', MaterialReservation::STATUS_CANCELLED)->count(),
            'total_value' => $reservations->sum(function ($r) {
                return $r->quantity_reserved * ($r->product?->cost ?? 0);
            }),
        ];
    }

    // =========================================================================
    // Private Helper Methods
    // =========================================================================

    /**
     * Increment reserved quantity in ProductQuantity
     */
    private function incrementReservedQuantity(int $productId, int $warehouseId, float $quantity): void
    {
        $warehouse = Warehouse::find($warehouseId);
        if (!$warehouse || !$warehouse->lot_stock_location_id) {
            return;
        }

        ProductQuantity::where('product_id', $productId)
            ->where('location_id', $warehouse->lot_stock_location_id)
            ->increment('reserved_quantity', $quantity);
    }

    /**
     * Decrement reserved quantity in ProductQuantity
     */
    private function decrementReservedQuantity(int $productId, int $warehouseId, float $quantity): void
    {
        $warehouse = Warehouse::find($warehouseId);
        if (!$warehouse || !$warehouse->lot_stock_location_id) {
            return;
        }

        ProductQuantity::where('product_id', $productId)
            ->where('location_id', $warehouse->lot_stock_location_id)
            ->decrement('reserved_quantity', $quantity);
    }

    /**
     * Decrement actual quantity in ProductQuantity
     */
    private function decrementQuantity(int $productId, int $locationId, float $quantity): void
    {
        ProductQuantity::where('product_id', $productId)
            ->where('location_id', $locationId)
            ->decrement('quantity', $quantity);
    }
}
