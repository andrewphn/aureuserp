<?php

namespace Webkul\Project\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Webkul\Project\Models\CabinetSpecification;
use Webkul\Project\Models\Project;
use Webkul\Project\Models\Room;
use Webkul\Sale\Enums\OrderState;
use Webkul\Sale\Models\Order;
use Webkul\Sale\Models\OrderLine;
use Webkul\Support\Models\Currency;
use Webkul\Support\Models\UOM;

/**
 * Project to Order Service
 *
 * Generates Sales Order lines from project specifications including:
 * - Cabinet specifications with linear foot pricing
 * - Room-level charges
 * - Material costs from BOM
 * - Labor estimates from milestones
 */
class ProjectToOrderService
{
    /**
     * Generate order lines from a project's specifications
     *
     * @param Project $project The source project
     * @param Order $order The target order to populate
     * @param array $options Configuration options
     * @return array ['success' => bool, 'lines' => Collection, 'errors' => array]
     */
    public function generateOrderLinesFromProject(Project $project, Order $order, array $options = []): array
    {
        $options = array_merge([
            'include_cabinets' => true,
            'include_rooms' => true,
            'include_materials' => false,
            'include_labor' => false,
            'group_by_room' => true,
        ], $options);

        $lines = collect();
        $errors = [];

        DB::beginTransaction();

        try {
            $sortOrder = 1;

            // Add room section headers and cabinets
            if ($options['include_rooms'] || $options['include_cabinets']) {
                $rooms = $project->rooms()->with(['locations.cabinetRuns.cabinets'])->get();

                foreach ($rooms as $room) {
                    // Add room section header if grouping by room
                    if ($options['group_by_room']) {
                        $sectionLine = $this->createSectionLine($order, $room->name, $sortOrder++);
                        $lines->push($sectionLine);
                    }

                    // Add cabinet lines
                    if ($options['include_cabinets']) {
                        $cabinetLines = $this->generateCabinetLines($project, $order, $room, $sortOrder);
                        foreach ($cabinetLines as $line) {
                            $lines->push($line);
                            $sortOrder++;
                        }
                    }

                    // Add room-level pricing if configured
                    if ($options['include_rooms'] && ($room->quoted_price ?? 0) > 0) {
                        $roomLine = $this->createRoomPriceLine($order, $room, $sortOrder++);
                        $lines->push($roomLine);
                    }
                }
            }

            // Add standalone cabinets (not associated with rooms)
            if ($options['include_cabinets']) {
                $standaloneCabinets = $project->cabinetSpecifications()
                    ->whereNull('room_id')
                    ->get();

                if ($standaloneCabinets->isNotEmpty()) {
                    // Section header for unassigned cabinets
                    $sectionLine = $this->createSectionLine($order, 'Unassigned Cabinets', $sortOrder++);
                    $lines->push($sectionLine);

                    foreach ($standaloneCabinets as $cabinet) {
                        $line = $this->createCabinetLine($order, $cabinet, $sortOrder++);
                        $lines->push($line);
                    }
                }
            }

            // Add BOM materials as line items
            if ($options['include_materials']) {
                $materialLines = $this->generateMaterialLines($project, $order, $sortOrder);
                foreach ($materialLines as $line) {
                    $lines->push($line);
                    $sortOrder++;
                }
            }

            // Update order totals
            $this->updateOrderTotals($order, $lines);

            DB::commit();

            return [
                'success' => true,
                'lines' => $lines,
                'errors' => $errors,
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to generate order lines from project', [
                'project_id' => $project->id,
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'lines' => collect(),
                'errors' => [$e->getMessage()],
            ];
        }
    }

    /**
     * Create a new order for a project
     *
     * @param Project $project The source project
     * @param array $orderData Additional order data
     * @return Order
     */
    public function createOrderForProject(Project $project, array $orderData = []): Order
    {
        // Get default currency - first look for USD, then any active currency
        $currency = Currency::where('code', 'USD')->first()
            ?? Currency::where('active', true)->first()
            ?? Currency::first();
        $currencyId = $currency?->id;

        $orderData = array_merge([
            'project_id' => $project->id,
            'partner_id' => $project->partner_id,
            'partner_invoice_id' => $project->partner_id,
            'partner_shipping_id' => $project->partner_id,
            'company_id' => $project->company_id,
            'warehouse_id' => $project->warehouse_id,
            'state' => OrderState::DRAFT,
            'date_order' => now(),
            'currency_id' => $currencyId,
            'user_id' => auth()->id(),
            'creator_id' => auth()->id(),
            'source_quote_id' => $project->source_quote_id,
        ], $orderData);

        return Order::create($orderData);
    }

    /**
     * Import project specifications into an existing order
     *
     * @param Order $order The target order
     * @param Project $project The source project
     * @param array $options Configuration options
     * @return array
     */
    public function importFromProject(Order $order, Project $project, array $options = []): array
    {
        // Clear existing lines if requested
        if ($options['clear_existing'] ?? false) {
            $order->lines()->delete();
        }

        return $this->generateOrderLinesFromProject($project, $order, $options);
    }

    // =========================================================================
    // Line Generation Methods
    // =========================================================================

    /**
     * Generate cabinet order lines from a room
     */
    protected function generateCabinetLines(Project $project, Order $order, Room $room, int &$sortOrder): Collection
    {
        $lines = collect();

        // Get cabinets through room locations and runs
        foreach ($room->locations as $location) {
            foreach ($location->cabinetRuns as $run) {
                foreach ($run->cabinets as $cabinet) {
                    $line = $this->createCabinetLine($order, $cabinet, $sortOrder++);
                    $lines->push($line);
                }
            }
        }

        // Also get cabinets directly on the room
        $directCabinets = CabinetSpecification::where('room_id', $room->id)
            ->whereNull('cabinet_run_id')
            ->get();

        foreach ($directCabinets as $cabinet) {
            $line = $this->createCabinetLine($order, $cabinet, $sortOrder++);
            $lines->push($line);
        }

        return $lines;
    }

    /**
     * Generate material order lines from BOM
     */
    protected function generateMaterialLines(Project $project, Order $order, int &$sortOrder): Collection
    {
        $lines = collect();

        // Section header
        $lines->push($this->createSectionLine($order, 'Materials', $sortOrder++));

        // Get BOM items with products
        $bomItems = $project->cabinetSpecifications()
            ->with('bom.product')
            ->get()
            ->flatMap(function ($cabinet) {
                return $cabinet->bom ?? collect();
            })
            ->filter(fn($bom) => $bom->product_id)
            ->groupBy('product_id');

        foreach ($bomItems as $productId => $items) {
            $firstItem = $items->first();
            $totalQuantity = $items->sum('quantity_required');
            $product = $firstItem->product;

            if (!$product) {
                continue;
            }

            $line = OrderLine::create([
                'order_id' => $order->id,
                'company_id' => $order->company_id,
                'currency_id' => $order->currency_id,
                'order_partner_id' => $order->partner_id,
                'product_id' => $product->id,
                'product_uom_id' => $product->uom_id,
                'name' => $product->name,
                'product_uom_qty' => $totalQuantity,
                'price_unit' => $product->list_price ?? 0,
                'price_subtotal' => $totalQuantity * ($product->list_price ?? 0),
                'price_total' => $totalQuantity * ($product->list_price ?? 0),
                'state' => 'draft',
                'sort' => $sortOrder++,
                'creator_id' => auth()->id(),
            ]);

            $lines->push($line);
        }

        return $lines;
    }

    /**
     * Create a section header line
     */
    protected function createSectionLine(Order $order, string $name, int $sortOrder): OrderLine
    {
        return OrderLine::create([
            'order_id' => $order->id,
            'company_id' => $order->company_id,
            'currency_id' => $order->currency_id,
            'display_type' => 'line_section',
            'name' => $name,
            'sort' => $sortOrder,
            'product_uom_qty' => 0,
            'price_unit' => 0,
            'price_subtotal' => 0,
            'price_total' => 0,
            'creator_id' => auth()->id(),
        ]);
    }

    /**
     * Create a cabinet order line
     */
    protected function createCabinetLine(Order $order, CabinetSpecification $cabinet, int $sortOrder): OrderLine
    {
        // Build description
        $description = $this->buildCabinetDescription($cabinet);

        // Calculate price (linear feet * rate)
        $linearFeet = $cabinet->linear_feet ?? 0;
        $pricePerLf = $cabinet->unit_price_per_lf ?? 0;
        $quantity = $cabinet->quantity ?? 1;
        $totalPrice = $cabinet->total_price ?? ($linearFeet * $pricePerLf * $quantity);

        // Get or create linear feet UOM
        $lfUom = UOM::where('name', 'LIKE', '%Linear F%')
            ->orWhere('name', 'LIKE', '%LF%')
            ->first();

        return OrderLine::create([
            'order_id' => $order->id,
            'company_id' => $order->company_id,
            'currency_id' => $order->currency_id,
            'order_partner_id' => $order->partner_id,
            'product_id' => $cabinet->product_variant_id,
            'product_uom_id' => $lfUom?->id,
            'name' => $description,
            'product_uom_qty' => $linearFeet * $quantity,
            'price_unit' => $pricePerLf,
            'price_subtotal' => $totalPrice,
            'price_total' => $totalPrice, // Add tax calculation if needed
            'state' => 'draft',
            'sort' => $sortOrder,
            'creator_id' => auth()->id(),
        ]);
    }

    /**
     * Create a room-level price line
     */
    protected function createRoomPriceLine(Order $order, Room $room, int $sortOrder): OrderLine
    {
        return OrderLine::create([
            'order_id' => $order->id,
            'company_id' => $order->company_id,
            'currency_id' => $order->currency_id,
            'order_partner_id' => $order->partner_id,
            'name' => "{$room->name} - Room Charges",
            'product_uom_qty' => 1,
            'price_unit' => $room->quoted_price ?? 0,
            'price_subtotal' => $room->quoted_price ?? 0,
            'price_total' => $room->quoted_price ?? 0,
            'state' => 'draft',
            'sort' => $sortOrder,
            'creator_id' => auth()->id(),
        ]);
    }

    /**
     * Build cabinet description for order line
     */
    protected function buildCabinetDescription(CabinetSpecification $cabinet): string
    {
        $parts = [];

        // Cabinet identifier
        if ($cabinet->cabinet_number) {
            $parts[] = "Cabinet #{$cabinet->cabinet_number}";
        }

        // Dimensions
        $dims = [];
        if ($cabinet->width_inches) {
            $dims[] = "{$cabinet->width_inches}\"W";
        }
        if ($cabinet->depth_inches) {
            $dims[] = "{$cabinet->depth_inches}\"D";
        }
        if ($cabinet->height_inches) {
            $dims[] = "{$cabinet->height_inches}\"H";
        }
        if (!empty($dims)) {
            $parts[] = implode(' x ', $dims);
        }

        // Level
        if ($cabinet->cabinet_level) {
            $parts[] = ucfirst($cabinet->cabinet_level);
        }

        // Material
        if ($cabinet->material_category) {
            $parts[] = $cabinet->material_category;
        }

        // Finish
        if ($cabinet->finish_option) {
            $parts[] = $cabinet->finish_option;
        }

        // Linear feet
        if ($cabinet->linear_feet) {
            $parts[] = "{$cabinet->linear_feet} LF";
        }

        return implode(' - ', $parts) ?: 'Cabinet';
    }

    /**
     * Update order totals based on lines
     */
    protected function updateOrderTotals(Order $order, Collection $lines): void
    {
        $subtotal = $lines->sum('price_subtotal');
        $tax = $lines->sum('price_tax') ?? 0;
        $total = $subtotal + $tax;

        $order->update([
            'amount_untaxed' => $subtotal,
            'amount_tax' => $tax,
            'amount_total' => $total,
        ]);
    }
}
