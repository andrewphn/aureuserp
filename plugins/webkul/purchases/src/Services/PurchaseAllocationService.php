<?php

namespace Webkul\Purchase\Services;

use Webkul\Inventory\Models\Move;
use Webkul\Purchase\Models\OrderLine;
use Webkul\Project\Models\MaterialReservation;
use Webkul\Project\Models\CabinetMaterialsBom;
use Webkul\Project\Models\HardwareRequirement;

/**
 * Purchase Allocation Service
 *
 * Handles automatic allocation of purchased goods to projects when inventory is received.
 * This service is called when an inventory move is validated (goods received).
 *
 * Flow:
 * 1. Purchase Order Line created with project_id, bom_id, or hardware_requirement_id
 * 2. Order confirmed → Inventory Receipt created
 * 3. Receipt validated (goods received) → This service is triggered
 * 4. Service creates MaterialReservation records and updates BOM/Hardware allocation flags
 */
class PurchaseAllocationService
{
    /**
     * Handle goods received event for a purchase order line
     *
     * Called when an inventory move linked to a purchase order line is validated (state = DONE)
     *
     * @param Move $move The validated inventory move
     * @return void
     */
    public function onGoodsReceived(Move $move): void
    {
        // Load the order line with relationships
        $orderLine = $move->purchaseOrderLine;

        if (!$orderLine) {
            return;
        }

        // Load relationships for processing
        $orderLine->load(['project', 'bom', 'hardwareRequirement', 'order', 'uom']);

        // 1. Create MaterialReservation if project is linked
        if ($orderLine->project_id) {
            $this->createMaterialReservation($move, $orderLine);
        }

        // 2. Update BOM allocation if linked
        if ($orderLine->bom_id && $orderLine->bom) {
            $this->updateBomAllocation($orderLine);
        }

        // 3. Update Hardware allocation if linked
        if ($orderLine->hardware_requirement_id && $orderLine->hardwareRequirement) {
            $this->updateHardwareAllocation($orderLine, $move);
        }
    }

    /**
     * Create a MaterialReservation record for the received goods
     *
     * @param Move $move The inventory move
     * @param OrderLine $orderLine The purchase order line
     * @return MaterialReservation
     */
    protected function createMaterialReservation(Move $move, OrderLine $orderLine): MaterialReservation
    {
        return MaterialReservation::create([
            'project_id' => $orderLine->project_id,
            'bom_id' => $orderLine->bom_id,
            'product_id' => $orderLine->product_id,
            'warehouse_id' => $move->warehouse_id,
            'location_id' => $move->destination_location_id,
            'quantity_reserved' => $move->quantity,
            'unit_of_measure' => $orderLine->uom?->name ?? 'EA',
            'status' => MaterialReservation::STATUS_RESERVED,
            'reserved_by' => auth()->id(),
            'reserved_at' => now(),
            'move_id' => $move->id,
            'notes' => $this->buildReservationNotes($orderLine),
        ]);
    }

    /**
     * Build descriptive notes for the reservation
     *
     * @param OrderLine $orderLine
     * @return string
     */
    protected function buildReservationNotes(OrderLine $orderLine): string
    {
        $notes = "Auto-allocated from PO: {$orderLine->order?->name}";

        if ($orderLine->bom) {
            $notes .= " | BOM: {$orderLine->bom->component_name}";
        }

        if ($orderLine->hardwareRequirement) {
            $hw = $orderLine->hardwareRequirement;
            $notes .= " | Hardware: {$hw->hardware_type} - {$hw->model_number}";
        }

        return $notes;
    }

    /**
     * Update BOM entry allocation flags
     *
     * @param OrderLine $orderLine
     * @return void
     */
    protected function updateBomAllocation(OrderLine $orderLine): void
    {
        $orderLine->bom->update([
            'material_allocated' => true,
            'material_allocated_at' => now(),
        ]);
    }

    /**
     * Update Hardware Requirement allocation flags and create reservation if needed
     *
     * @param OrderLine $orderLine
     * @param Move $move
     * @return void
     */
    protected function updateHardwareAllocation(OrderLine $orderLine, Move $move): void
    {
        $hardware = $orderLine->hardwareRequirement;

        // Create reservation if not exists
        if (!$hardware->material_reservation_id) {
            // Get project from hardware requirement's cabinet hierarchy
            $project = $hardware->getProject();

            $reservation = MaterialReservation::create([
                'project_id' => $project?->id ?? $orderLine->project_id,
                'product_id' => $hardware->product_id,
                'warehouse_id' => $move->warehouse_id,
                'location_id' => $move->destination_location_id,
                'quantity_reserved' => $hardware->quantity_required,
                'unit_of_measure' => $hardware->unit_of_measure ?? 'EA',
                'status' => MaterialReservation::STATUS_RESERVED,
                'reserved_by' => auth()->id(),
                'reserved_at' => now(),
                'move_id' => $move->id,
                'notes' => "Auto-allocated hardware from PO: {$orderLine->order?->name}",
            ]);

            $hardware->update([
                'material_reservation_id' => $reservation->id,
                'hardware_allocated' => true,
                'hardware_allocated_at' => now(),
            ]);
        } else {
            // Reservation exists, just update allocation flag
            $hardware->update([
                'hardware_allocated' => true,
                'hardware_allocated_at' => now(),
            ]);
        }
    }

    /**
     * Check if an order line has any project-related allocations
     *
     * @param OrderLine $orderLine
     * @return bool
     */
    public function hasProjectAllocation(OrderLine $orderLine): bool
    {
        return $orderLine->project_id
            || $orderLine->bom_id
            || $orderLine->hardware_requirement_id;
    }

    /**
     * Get allocation summary for an order line
     *
     * @param OrderLine $orderLine
     * @return array
     */
    public function getAllocationSummary(OrderLine $orderLine): array
    {
        $orderLine->load(['project', 'bom', 'hardwareRequirement']);

        return [
            'has_allocation' => $this->hasProjectAllocation($orderLine),
            'project' => $orderLine->project?->only(['id', 'name', 'project_number']),
            'bom' => $orderLine->bom?->only(['id', 'component_name', 'material_allocated']),
            'hardware' => $orderLine->hardwareRequirement?->only([
                'id',
                'hardware_type',
                'model_number',
                'hardware_allocated',
            ]),
        ];
    }
}
