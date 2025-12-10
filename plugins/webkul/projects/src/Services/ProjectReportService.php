<?php

namespace Webkul\Project\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\View;
use Webkul\Project\Models\Cabinet;
use Webkul\Project\Models\Project;
use Webkul\Product\Models\Product;

class ProjectReportService
{
    protected MaterialBomService $bomService;

    public function __construct(MaterialBomService $bomService)
    {
        $this->bomService = $bomService;
    }

    /**
     * Generate Purchase Requisition HTML Report
     * This report is designed for inventory managers to create Purchase Orders
     */
    public function generatePurchaseRequisitionHtml(Project $project): string
    {
        $template = file_get_contents(base_path('templates/project-reports/purchase-requisition.html'));

        // Get all cabinets for the project with their hardware products
        $cabinets = Cabinet::whereHas('room', fn($q) =>
            $q->where('project_id', $project->id)
        )->with([
            'hingeProduct.supplierInformation.partner',
            'slideProduct.supplierInformation.partner',
            'pulloutProduct.supplierInformation.partner',
            'lazySusanProduct.supplierInformation.partner',
        ])->get();

        // Generate materials requisition data
        $materialsReq = $this->generateMaterialsRequisition($cabinets, $project);

        // Generate hardware requisition data
        $hardwareReq = $this->generateHardwareRequisition($cabinets, $project);

        // Build tables
        $materialsTable = $this->buildMaterialsRequisitionTable($materialsReq);
        $hardwareTable = $this->buildHardwareRequisitionTable($hardwareReq);

        // Calculate totals
        $totalMaterials = count($materialsReq);
        $totalHardware = count($hardwareReq);
        $totalLineItems = $totalMaterials + $totalHardware;

        $itemsInStock = collect($materialsReq)->where('in_stock', true)->count()
            + collect($hardwareReq)->where('in_stock', true)->count();
        $itemsToOrder = collect($materialsReq)->where('qty_to_order', '>', 0)->count()
            + collect($hardwareReq)->where('qty_to_order', '>', 0)->count();

        $estimatedCost = collect($materialsReq)->sum('estimated_order_cost')
            + collect($hardwareReq)->sum('estimated_order_cost');

        // Replace placeholders
        $replacements = [
            '{{PROJECT_NAME}}' => htmlspecialchars($project->name),
            '{{PROJECT_NUMBER}}' => htmlspecialchars($project->project_number ?? 'N/A'),
            '{{CUSTOMER_NAME}}' => htmlspecialchars($project->partner?->name ?? 'N/A'),
            '{{GENERATED_DATE}}' => now()->format('F j, Y \a\t g:i A'),
            '{{MATERIALS_TABLE}}' => $materialsTable,
            '{{HARDWARE_TABLE}}' => $hardwareTable,
            '{{MATERIAL_COUNT}}' => $totalMaterials,
            '{{HARDWARE_COUNT}}' => $totalHardware,
            '{{TOTAL_LINE_ITEMS}}' => $totalLineItems,
            '{{ITEMS_IN_STOCK}}' => $itemsInStock,
            '{{ITEMS_TO_ORDER}}' => $itemsToOrder,
            '{{ESTIMATED_COST}}' => '$' . number_format($estimatedCost, 2),
        ];

        return str_replace(array_keys($replacements), array_values($replacements), $template);
    }

    /**
     * Generate materials requisition data with inventory check
     */
    protected function generateMaterialsRequisition($cabinets, Project $project): array
    {
        $materials = [];

        foreach ($cabinets as $cabinet) {
            $category = $cabinet->material_category;
            $finish = $cabinet->finish_option;
            $lf = floatval($cabinet->linear_feet ?? 0);
            $cabinetNumber = $cabinet->cabinet_number ?? $cabinet->id;

            if (!$category) continue;

            $materialName = $this->formatMaterialCategory($category);
            $finishName = $this->formatFinishOption($finish);
            $key = $category . '_' . $finish;

            if (!isset($materials[$key])) {
                $costPerLf = $this->getEstimatedCostPerLf($category);

                $materials[$key] = [
                    'category' => 'materials',
                    'item_name' => $materialName,
                    'description' => $finishName . ' finish',
                    'sku' => $this->generateMaterialSku($category, $finish),
                    'uom' => 'LF',
                    'qty_needed' => 0,
                    'qty_on_hand' => 0, // Would need to check inventory
                    'qty_to_order' => 0,
                    'unit_cost' => $costPerLf,
                    'estimated_order_cost' => 0,
                    'vendor_name' => null, // Materials typically from lumber yard
                    'lead_time' => null,
                    'in_stock' => false,
                    'cabinet_numbers' => [],
                    'job_code' => $project->project_number ?? $project->id,
                ];
            }

            $materials[$key]['qty_needed'] += $lf;
            $materials[$key]['cabinet_numbers'][] = $cabinetNumber;
        }

        // Calculate order quantities and costs
        foreach ($materials as &$item) {
            // Add 10% waste factor
            $item['qty_needed'] = round($item['qty_needed'] * 1.10, 2);
            $item['qty_to_order'] = max(0, $item['qty_needed'] - $item['qty_on_hand']);
            $item['estimated_order_cost'] = $item['qty_to_order'] * $item['unit_cost'];
            $item['in_stock'] = $item['qty_to_order'] <= 0;
        }

        return array_values($materials);
    }

    /**
     * Generate hardware requisition data with inventory and vendor check
     */
    protected function generateHardwareRequisition($cabinets, Project $project): array
    {
        $hardware = [];

        foreach ($cabinets as $cabinet) {
            $cabinetNumber = $cabinet->cabinet_number ?? $cabinet->id;

            // Hinges
            $doorCount = intval($cabinet->door_count ?? 0);
            if ($doorCount > 0) {
                $hingesNeeded = $cabinet->hinge_quantity ?: ($doorCount * 2);
                $hingeProduct = $cabinet->hingeProduct;
                $this->addHardwareRequisitionItem($hardware, [
                    'product' => $hingeProduct,
                    'type' => 'Hinges',
                    'description' => $hingeProduct?->name ?? 'Concealed Soft-Close Hinges',
                    'quantity' => $hingesNeeded,
                    'cabinet_number' => $cabinetNumber,
                    'project' => $project,
                ]);
            }

            // Drawer Slides
            $drawerCount = intval($cabinet->drawer_count ?? 0);
            if ($drawerCount > 0) {
                $slideProduct = $cabinet->slideProduct;
                $slidesNeeded = $cabinet->slide_quantity ?: $drawerCount;
                $this->addHardwareRequisitionItem($hardware, [
                    'product' => $slideProduct,
                    'type' => 'Drawer Slides',
                    'description' => $slideProduct?->name ?? 'Soft-Close Drawer Slides',
                    'quantity' => $slidesNeeded,
                    'cabinet_number' => $cabinetNumber,
                    'project' => $project,
                ]);
            }

            // Pullouts
            if ($cabinet->pullout_product_id) {
                $pulloutProduct = $cabinet->pulloutProduct;
                $this->addHardwareRequisitionItem($hardware, [
                    'product' => $pulloutProduct,
                    'type' => 'Pullouts',
                    'description' => $pulloutProduct?->name ?? 'Pull-out Accessory',
                    'quantity' => 1,
                    'cabinet_number' => $cabinetNumber,
                    'project' => $project,
                ]);
            }

            // Lazy Susan
            if ($cabinet->lazy_susan_product_id) {
                $lazySusanProduct = $cabinet->lazySusanProduct;
                $this->addHardwareRequisitionItem($hardware, [
                    'product' => $lazySusanProduct,
                    'type' => 'Lazy Susan',
                    'description' => $lazySusanProduct?->name ?? 'Lazy Susan',
                    'quantity' => 1,
                    'cabinet_number' => $cabinetNumber,
                    'project' => $project,
                ]);
            }
        }

        return array_values($hardware);
    }

    /**
     * Add hardware item to requisition with inventory and vendor lookup
     */
    protected function addHardwareRequisitionItem(array &$hardware, array $data): void
    {
        $product = $data['product'];
        $key = $data['type'] . '_' . ($product?->id ?? 'default') . '_' . $data['description'];

        if (isset($hardware[$key])) {
            $hardware[$key]['qty_needed'] += $data['quantity'];
            $hardware[$key]['cabinet_numbers'][] = $data['cabinet_number'];
        } else {
            // Get vendor info from product suppliers
            $vendorName = null;
            $leadTime = null;
            $unitCost = $product?->cost ?? $this->getDefaultHardwareCost($data['type']);

            if ($product && $product->relationLoaded('supplierInformation')) {
                $primarySupplier = $product->supplierInformation->first();
                if ($primarySupplier) {
                    $vendorName = $primarySupplier->partner?->name;
                    $leadTime = $primarySupplier->delay; // days
                    $unitCost = $primarySupplier->price ?? $unitCost;
                }
            }

            // Check inventory
            $qtyOnHand = $this->getProductInventoryQuantity($product?->id);

            $hardware[$key] = [
                'category' => 'hardware',
                'item_name' => $data['type'],
                'description' => $data['description'],
                'sku' => $product?->reference ?? $product?->barcode ?? 'N/A',
                'product_id' => $product?->id,
                'uom' => 'EA',
                'qty_needed' => $data['quantity'],
                'qty_on_hand' => $qtyOnHand,
                'qty_to_order' => 0,
                'unit_cost' => $unitCost,
                'estimated_order_cost' => 0,
                'vendor_name' => $vendorName,
                'lead_time' => $leadTime,
                'in_stock' => false,
                'cabinet_numbers' => [$data['cabinet_number']],
                'job_code' => $data['project']->project_number ?? $data['project']->id,
            ];
        }

        // Recalculate order quantity
        $hardware[$key]['qty_to_order'] = max(0, $hardware[$key]['qty_needed'] - $hardware[$key]['qty_on_hand']);
        $hardware[$key]['estimated_order_cost'] = $hardware[$key]['qty_to_order'] * $hardware[$key]['unit_cost'];
        $hardware[$key]['in_stock'] = $hardware[$key]['qty_to_order'] <= 0;
    }

    /**
     * Get product inventory quantity from inventories_product_quantities
     */
    protected function getProductInventoryQuantity(?int $productId): float
    {
        if (!$productId) return 0;

        $quantity = DB::table('inventories_product_quantities')
            ->where('product_id', $productId)
            ->sum(DB::raw('quantity - reserved_quantity'));

        return max(0, floatval($quantity));
    }

    /**
     * Get default hardware cost by type
     */
    protected function getDefaultHardwareCost(string $type): float
    {
        return match (strtolower($type)) {
            'hinges' => 8.50,
            'drawer slides' => 35.00,
            'pullouts' => 125.00,
            'lazy susan' => 175.00,
            default => 25.00,
        };
    }

    /**
     * Generate a SKU for materials
     */
    protected function generateMaterialSku(string $category, ?string $finish): string
    {
        $catCode = match (strtolower($category)) {
            'paint_grade' => 'PG',
            'stain_grade' => 'SG',
            'premium' => 'PM',
            'custom_exotic' => 'CE',
            default => 'OT',
        };

        $finishCode = match (strtolower($finish ?? '')) {
            'unfinished' => 'UF',
            'natural_stain' => 'NS',
            'custom_stain' => 'CS',
            'paint_finish' => 'PF',
            'clear_coat' => 'CC',
            default => 'ST',
        };

        return "MAT-{$catCode}-{$finishCode}";
    }

    /**
     * Build materials requisition table HTML
     */
    protected function buildMaterialsRequisitionTable(array $materials): string
    {
        if (empty($materials)) {
            return '<div class="empty-state">No materials required for this project.</div>';
        }

        $html = '<table class="data-table">
            <thead>
                <tr>
                    <th>Material / SKU</th>
                    <th>Description</th>
                    <th class="text-center">Qty Needed</th>
                    <th class="text-center">On Hand</th>
                    <th class="text-center">To Order</th>
                    <th class="text-right">Unit Cost</th>
                    <th class="text-right">Order Cost</th>
                    <th>Vendor</th>
                </tr>
            </thead>
            <tbody>';

        $totalCost = 0;

        foreach ($materials as $item) {
            $totalCost += $item['estimated_order_cost'];

            $stockClass = $item['in_stock'] ? 'stock-in' : ($item['qty_on_hand'] > 0 ? 'stock-low' : 'stock-out');
            $qtyToOrderClass = $item['qty_to_order'] > 0 ? 'qty-to-order' : 'qty-to-order qty-zero';

            $cabinetList = implode(', ', array_slice($item['cabinet_numbers'], 0, 5));
            if (count($item['cabinet_numbers']) > 5) {
                $cabinetList .= '...';
            }

            $html .= '<tr>
                <td>
                    <div class="font-bold">' . htmlspecialchars($item['item_name']) . '</div>
                    <div class="font-mono text-muted" style="font-size: 8px;">' . htmlspecialchars($item['sku']) . '</div>
                </td>
                <td>
                    ' . htmlspecialchars($item['description']) . '
                    <div class="job-ref">Cabinets: <code>' . htmlspecialchars($cabinetList) . '</code></div>
                </td>
                <td class="text-center qty-needed">' . number_format($item['qty_needed'], 2) . ' ' . $item['uom'] . '</td>
                <td class="text-center">
                    <span class="stock-badge ' . $stockClass . '">' . number_format($item['qty_on_hand'], 2) . '</span>
                </td>
                <td class="text-center">
                    <span class="' . $qtyToOrderClass . '">' . number_format($item['qty_to_order'], 2) . '</span>
                </td>
                <td class="text-right cost-cell">$' . number_format($item['unit_cost'], 2) . '/' . $item['uom'] . '</td>
                <td class="text-right cost-cell total">$' . number_format($item['estimated_order_cost'], 2) . '</td>
                <td class="vendor-cell">
                    <span class="vendor-not-set">Assign Vendor</span>
                </td>
            </tr>';
        }

        // Total row
        $html .= '<tr class="total-row">
            <td colspan="6" class="text-right">Total Materials Order Cost</td>
            <td class="text-right">$' . number_format($totalCost, 2) . '</td>
            <td></td>
        </tr>';

        $html .= '</tbody></table>';
        return $html;
    }

    /**
     * Build hardware requisition table HTML
     */
    protected function buildHardwareRequisitionTable(array $hardware): string
    {
        if (empty($hardware)) {
            return '<div class="empty-state">No hardware required for this project.</div>';
        }

        $html = '<table class="data-table">
            <thead>
                <tr>
                    <th>Item / SKU</th>
                    <th>Description</th>
                    <th class="text-center">Qty Needed</th>
                    <th class="text-center">On Hand</th>
                    <th class="text-center">To Order</th>
                    <th class="text-right">Unit Cost</th>
                    <th class="text-right">Order Cost</th>
                    <th>Vendor / Lead Time</th>
                </tr>
            </thead>
            <tbody>';

        $totalCost = 0;

        foreach ($hardware as $item) {
            $totalCost += $item['estimated_order_cost'];

            $stockClass = $item['in_stock'] ? 'stock-in' : ($item['qty_on_hand'] > 0 ? 'stock-low' : 'stock-out');
            $qtyToOrderClass = $item['qty_to_order'] > 0 ? 'qty-to-order' : 'qty-to-order qty-zero';
            $hardwareType = $this->getHardwareBadgeClass($item['item_name']);

            $cabinetList = implode(', ', array_unique(array_slice($item['cabinet_numbers'], 0, 5)));
            if (count($item['cabinet_numbers']) > 5) {
                $cabinetList .= '...';
            }

            $vendorHtml = $item['vendor_name']
                ? '<span class="vendor-name">' . htmlspecialchars($item['vendor_name']) . '</span>'
                  . ($item['lead_time'] ? '<br><span class="text-muted" style="font-size: 8px;">' . $item['lead_time'] . ' days lead time</span>' : '')
                : '<span class="vendor-not-set">Not Set</span>';

            $html .= '<tr>
                <td>
                    <span class="hardware-badge hardware-' . $hardwareType . '">' . htmlspecialchars($item['item_name']) . '</span>
                    <div class="font-mono text-muted" style="font-size: 8px;">' . htmlspecialchars($item['sku']) . '</div>
                </td>
                <td>
                    ' . htmlspecialchars($item['description']) . '
                    <div class="job-ref">Cabinets: <code>' . htmlspecialchars($cabinetList) . '</code></div>
                </td>
                <td class="text-center qty-needed">' . number_format($item['qty_needed']) . ' ' . $item['uom'] . '</td>
                <td class="text-center">
                    <span class="stock-badge ' . $stockClass . '">' . number_format($item['qty_on_hand']) . '</span>
                </td>
                <td class="text-center">
                    <span class="' . $qtyToOrderClass . '">' . number_format($item['qty_to_order']) . '</span>
                </td>
                <td class="text-right cost-cell">$' . number_format($item['unit_cost'], 2) . '</td>
                <td class="text-right cost-cell total">$' . number_format($item['estimated_order_cost'], 2) . '</td>
                <td class="vendor-cell">' . $vendorHtml . '</td>
            </tr>';
        }

        // Total row
        $html .= '<tr class="total-row">
            <td colspan="6" class="text-right">Total Hardware Order Cost</td>
            <td class="text-right">$' . number_format($totalCost, 2) . '</td>
            <td></td>
        </tr>';

        $html .= '</tbody></table>';
        return $html;
    }

    /**
     * Generate HTML BOM Report
     */
    public function generateBomHtml(Project $project): string
    {
        $template = file_get_contents(base_path('templates/project-reports/project-bom.html'));

        // Get all cabinets for the project with their hardware products
        $cabinets = Cabinet::whereHas('room', fn($q) =>
            $q->where('project_id', $project->id)
        )->with([
            'hingeProduct',
            'slideProduct',
            'pulloutProduct',
            'lazySusanProduct',
        ])->get();

        // Generate materials directly from cabinet data
        $materialsBom = $this->generateMaterialsFromCabinets($cabinets);

        // Generate hardware directly from cabinet data
        $hardwareBom = $this->generateHardwareFromCabinets($cabinets);

        // Build materials table
        $materialsTable = $this->buildMaterialsTableDirect($materialsBom);

        // Build hardware table
        $hardwareTable = $this->buildHardwareTableDirect($hardwareBom);

        // Calculate totals
        $totalMaterials = count($materialsBom);
        $totalHardware = collect($hardwareBom)->sum('quantity');
        $totalMaterialCost = collect($materialsBom)->sum('estimated_cost');
        $totalHardwareCost = collect($hardwareBom)->sum('total_cost');

        // Replace placeholders
        $replacements = [
            '{{PROJECT_NAME}}' => htmlspecialchars($project->name),
            '{{PROJECT_NUMBER}}' => htmlspecialchars($project->project_number ?? 'N/A'),
            '{{CUSTOMER_NAME}}' => htmlspecialchars($project->partner?->name ?? 'N/A'),
            '{{GENERATED_DATE}}' => now()->format('F j, Y \a\t g:i A'),
            '{{MATERIALS_TABLE}}' => $materialsTable,
            '{{HARDWARE_TABLE}}' => $hardwareTable,
            '{{MATERIAL_COUNT}}' => $totalMaterials,
            '{{HARDWARE_COUNT}}' => count($hardwareBom),
            '{{TOTAL_SHEET_GOODS}}' => $totalMaterials . ' items',
            '{{TOTAL_HARDWARE}}' => number_format($totalHardware) . ' pcs',
            '{{TOTAL_COST}}' => '$' . number_format($totalMaterialCost + $totalHardwareCost, 2),
        ];

        return str_replace(array_keys($replacements), array_values($replacements), $template);
    }

    /**
     * Generate materials BOM directly from cabinet properties
     */
    protected function generateMaterialsFromCabinets($cabinets): array
    {
        $materials = [];

        foreach ($cabinets as $cabinet) {
            $category = $cabinet->material_category;
            $finish = $cabinet->finish_option;
            $lf = floatval($cabinet->linear_feet ?? 0);
            $cabinetNumber = $cabinet->cabinet_number ?? $cabinet->id;

            if (!$category) continue;

            // Format the material category for display
            $materialName = $this->formatMaterialCategory($category);
            $finishName = $this->formatFinishOption($finish);

            // Create a composite key for aggregation
            $key = $category . '_' . $finish;

            if (!isset($materials[$key])) {
                // Estimate square feet based on linear feet (rough estimate: 6 sqft per LF for cabinets)
                $sqftPerLf = 6.0;

                $materials[$key] = [
                    'material_category' => $materialName,
                    'finish_option' => $finishName,
                    'material_type' => $this->getMaterialType($category),
                    'total_linear_feet' => 0,
                    'estimated_sqft' => 0,
                    'cabinet_count' => 0,
                    'cabinet_numbers' => [],
                    'estimated_cost' => 0,
                ];
            }

            $materials[$key]['total_linear_feet'] += $lf;
            $materials[$key]['estimated_sqft'] += $lf * 6.0; // Rough estimate
            $materials[$key]['cabinet_count']++;
            $materials[$key]['cabinet_numbers'][] = $cabinetNumber;

            // Estimate cost based on linear feet ($85/LF average for paint grade)
            $costPerLf = $this->getEstimatedCostPerLf($category);
            $materials[$key]['estimated_cost'] += $lf * $costPerLf;
        }

        return array_values($materials);
    }

    /**
     * Generate hardware BOM directly from cabinet properties
     */
    protected function generateHardwareFromCabinets($cabinets): array
    {
        $hardware = [];

        foreach ($cabinets as $cabinet) {
            $cabinetNumber = $cabinet->cabinet_number ?? $cabinet->id;

            // Count doors and calculate hinges (2 hinges per door typically)
            $doorCount = intval($cabinet->door_count ?? 0);
            if ($doorCount > 0) {
                $hingesNeeded = $doorCount * 2; // 2 hinges per door
                $hingeProduct = $cabinet->hingeProduct;

                $this->addHardwareItem($hardware, [
                    'type' => 'Hinges',
                    'description' => $hingeProduct?->name ?? 'Concealed Soft-Close Hinges',
                    'product_id' => $cabinet->hinge_product_id,
                    'quantity' => $cabinet->hinge_quantity ?: $hingesNeeded,
                    'unit_cost' => $hingeProduct?->cost ?? 8.50,
                    'cabinet_numbers' => [$cabinetNumber],
                ]);
            }

            // Count drawers and calculate slides (1 pair per drawer)
            $drawerCount = intval($cabinet->drawer_count ?? 0);
            if ($drawerCount > 0) {
                $slideProduct = $cabinet->slideProduct;

                $this->addHardwareItem($hardware, [
                    'type' => 'Drawer Slides',
                    'description' => $slideProduct?->name ?? 'Soft-Close Drawer Slides',
                    'product_id' => $cabinet->slide_product_id,
                    'quantity' => $cabinet->slide_quantity ?: $drawerCount, // 1 pair per drawer
                    'unit_cost' => $slideProduct?->cost ?? 35.00,
                    'cabinet_numbers' => [$cabinetNumber],
                ]);
            }

            // Check for pullout product
            if ($cabinet->pullout_product_id) {
                $pulloutProduct = $cabinet->pulloutProduct;
                $this->addHardwareItem($hardware, [
                    'type' => 'Pullouts',
                    'description' => $pulloutProduct?->name ?? 'Pull-out Accessory',
                    'product_id' => $cabinet->pullout_product_id,
                    'quantity' => 1,
                    'unit_cost' => $pulloutProduct?->cost ?? 125.00,
                    'cabinet_numbers' => [$cabinetNumber],
                ]);
            }

            // Check for lazy susan
            if ($cabinet->lazy_susan_product_id) {
                $lazySusanProduct = $cabinet->lazySusanProduct;
                $this->addHardwareItem($hardware, [
                    'type' => 'Lazy Susan',
                    'description' => $lazySusanProduct?->name ?? 'Lazy Susan',
                    'product_id' => $cabinet->lazy_susan_product_id,
                    'quantity' => 1,
                    'unit_cost' => $lazySusanProduct?->cost ?? 175.00,
                    'cabinet_numbers' => [$cabinetNumber],
                ]);
            }
        }

        return array_values($hardware);
    }

    /**
     * Add hardware item to the aggregated list
     */
    protected function addHardwareItem(array &$hardware, array $item): void
    {
        $key = $item['type'] . '_' . ($item['product_id'] ?? 'default') . '_' . $item['description'];

        if (isset($hardware[$key])) {
            $hardware[$key]['quantity'] += $item['quantity'];
            $hardware[$key]['total_cost'] = $hardware[$key]['quantity'] * $hardware[$key]['unit_cost'];
            $hardware[$key]['cabinet_numbers'] = array_merge(
                $hardware[$key]['cabinet_numbers'],
                $item['cabinet_numbers']
            );
        } else {
            $item['total_cost'] = $item['quantity'] * $item['unit_cost'];
            $hardware[$key] = $item;
        }
    }

    /**
     * Format material category for display
     */
    protected function formatMaterialCategory(string $category): string
    {
        return ucwords(str_replace(['_', '-'], ' ', $category));
    }

    /**
     * Format finish option for display
     */
    protected function formatFinishOption(?string $finish): string
    {
        if (!$finish) return 'Standard';
        return ucwords(str_replace(['_', '-'], ' ', $finish));
    }

    /**
     * Get material type for badge styling
     */
    protected function getMaterialType(string $category): string
    {
        $category = strtolower($category);
        if (str_contains($category, 'paint')) return 'plywood';
        if (str_contains($category, 'stain')) return 'hardwood';
        if (str_contains($category, 'premium')) return 'hardwood';
        if (str_contains($category, 'custom') || str_contains($category, 'exotic')) return 'hardwood';
        return 'other';
    }

    /**
     * Get estimated cost per linear foot for a material category
     */
    protected function getEstimatedCostPerLf(string $category): float
    {
        $category = strtolower($category);

        return match (true) {
            str_contains($category, 'paint_grade') => 85.00,
            str_contains($category, 'stain_grade') => 110.00,
            str_contains($category, 'premium') => 150.00,
            str_contains($category, 'custom') || str_contains($category, 'exotic') => 200.00,
            default => 100.00,
        };
    }

    /**
     * Build materials table HTML from direct cabinet data
     */
    protected function buildMaterialsTableDirect(array $materials): string
    {
        if (empty($materials)) {
            return '<div class="empty-state">No materials data available for this project.</div>';
        }

        $html = '<table class="data-table">
            <thead>
                <tr>
                    <th>Material</th>
                    <th>Finish</th>
                    <th class="text-center">Linear Feet</th>
                    <th class="text-center">Est. Sq Ft</th>
                    <th class="text-center">Cabinets</th>
                    <th class="text-right">Est. Cost</th>
                </tr>
            </thead>
            <tbody>';

        $totalLf = 0;
        $totalSqft = 0;
        $totalCost = 0;

        foreach ($materials as $item) {
            $totalLf += $item['total_linear_feet'];
            $totalSqft += $item['estimated_sqft'];
            $totalCost += $item['estimated_cost'];

            $materialType = $item['material_type'];
            $cabinetList = implode(', ', array_slice($item['cabinet_numbers'], 0, 5));
            if (count($item['cabinet_numbers']) > 5) {
                $cabinetList .= '...';
            }

            $html .= '<tr>
                <td>
                    <span class="material-badge material-' . $materialType . '">' . htmlspecialchars($item['material_category']) . '</span>
                </td>
                <td>' . htmlspecialchars($item['finish_option']) . '</td>
                <td class="text-center font-bold">' . number_format($item['total_linear_feet'], 2) . ' LF</td>
                <td class="text-center">' . number_format($item['estimated_sqft'], 1) . ' sqft</td>
                <td class="text-center">
                    <span class="qty-cell">' . $item['cabinet_count'] . '</span>
                    <div class="cabinet-ref"><code>' . htmlspecialchars($cabinetList) . '</code></div>
                </td>
                <td class="text-right cost-cell positive">$' . number_format($item['estimated_cost'], 2) . '</td>
            </tr>';
        }

        // Totals row
        $html .= '<tr class="total-row">
            <td colspan="2" class="text-right">Totals</td>
            <td class="text-center">' . number_format($totalLf, 2) . ' LF</td>
            <td class="text-center">' . number_format($totalSqft, 1) . ' sqft</td>
            <td></td>
            <td class="text-right">$' . number_format($totalCost, 2) . '</td>
        </tr>';

        $html .= '</tbody></table>';
        return $html;
    }

    /**
     * Build hardware table HTML from direct cabinet data
     */
    protected function buildHardwareTableDirect(array $hardware): string
    {
        if (empty($hardware)) {
            return '<div class="empty-state">No hardware data available for this project.</div>';
        }

        $html = '<table class="data-table">
            <thead>
                <tr>
                    <th>Type</th>
                    <th>Description</th>
                    <th class="text-center">Qty</th>
                    <th class="text-right">Unit Cost</th>
                    <th class="text-right">Total Cost</th>
                    <th>Cabinets</th>
                </tr>
            </thead>
            <tbody>';

        $totalQty = 0;
        $totalCost = 0;

        foreach ($hardware as $item) {
            $totalQty += $item['quantity'];
            $totalCost += $item['total_cost'];

            $hardwareType = $this->getHardwareBadgeClass($item['type']);
            $cabinetList = implode(', ', array_unique(array_slice($item['cabinet_numbers'], 0, 5)));
            if (count($item['cabinet_numbers']) > 5) {
                $cabinetList .= '...';
            }

            $html .= '<tr>
                <td><span class="hardware-badge hardware-' . $hardwareType . '">' . htmlspecialchars($item['type']) . '</span></td>
                <td>' . htmlspecialchars($item['description']) . '</td>
                <td class="text-center qty-cell">' . number_format($item['quantity']) . '</td>
                <td class="text-right cost-cell">$' . number_format($item['unit_cost'], 2) . '</td>
                <td class="text-right cost-cell positive">$' . number_format($item['total_cost'], 2) . '</td>
                <td><div class="cabinet-ref"><code>' . htmlspecialchars($cabinetList) . '</code></div></td>
            </tr>';
        }

        // Totals row
        $html .= '<tr class="total-row">
            <td colspan="2" class="text-right">Totals</td>
            <td class="text-center">' . number_format($totalQty) . '</td>
            <td></td>
            <td class="text-right">$' . number_format($totalCost, 2) . '</td>
            <td></td>
        </tr>';

        $html .= '</tbody></table>';
        return $html;
    }

    /**
     * Generate HTML Project Summary Report
     */
    public function generateSummaryHtml(Project $project, array $config = []): string
    {
        $template = file_get_contents(base_path('templates/project-reports/project-summary.html'));

        $sections = $config['sections'] ?? ['project_info', 'project_totals', 'room_breakdown', 'cabinet_detail'];
        $includePricing = $config['include_pricing'] ?? true;
        $includeDimensions = $config['include_dimensions'] ?? true;

        // Load relationships
        $project->load([
            'partner',
            'rooms.locations.cabinetRuns.cabinets',
            'tags',
        ]);

        // Calculate totals
        $allCabinets = $project->rooms->flatMap(fn($room) =>
            $room->locations->flatMap(fn($loc) =>
                $loc->cabinetRuns->flatMap(fn($run) => $run->cabinets)
            )
        );

        $totalLinearFeet = $allCabinets->sum('linear_feet');
        $estimatedValue = $allCabinets->sum('total_price');

        // Build room rows
        $roomRows = $this->buildRoomRows($project->rooms, $includePricing);

        // Build cabinet table
        $cabinetTable = $this->buildCabinetTable($project->rooms, $includePricing, $includeDimensions);

        // Determine status class
        $statusClass = match (strtolower($project->current_production_stage ?? 'discovery')) {
            'discovery', 'quoting' => 'discovery',
            'production', 'fabrication', 'finishing' => 'in-progress',
            'delivered', 'completed', 'closed' => 'completed',
            default => 'discovery',
        };

        // Replace placeholders
        $replacements = [
            '{{PROJECT_NAME}}' => htmlspecialchars($project->name),
            '{{PROJECT_NUMBER}}' => htmlspecialchars($project->project_number ?? 'N/A'),
            '{{CUSTOMER_NAME}}' => htmlspecialchars($project->partner?->name ?? 'N/A'),
            '{{STATUS}}' => ucfirst(str_replace('_', ' ', $project->current_production_stage ?? 'Discovery')),
            '{{STATUS_CLASS}}' => $statusClass,
            '{{START_DATE}}' => $project->start_date?->format('M j, Y') ?? 'Not Set',
            '{{TARGET_DATE}}' => $project->desired_completion_date?->format('M j, Y') ?? 'Not Set',
            '{{GENERATED_DATE}}' => now()->format('F j, Y \a\t g:i A'),
            '{{TOTAL_ROOMS}}' => $project->rooms->count(),
            '{{TOTAL_CABINETS}}' => $allCabinets->count(),
            '{{TOTAL_LINEAR_FEET}}' => number_format($totalLinearFeet, 1) . ' LF',
            '{{ESTIMATED_VALUE}}' => $includePricing ? '$' . number_format($estimatedValue, 2) : '—',
            '{{ROOM_ROWS}}' => $roomRows,
            '{{CABINET_TABLE}}' => $cabinetTable,
        ];

        return str_replace(array_keys($replacements), array_values($replacements), $template);
    }

    /**
     * Build materials table HTML
     */
    protected function buildMaterialsTable(iterable $materials): string
    {
        $materials = collect($materials)->toArray();
        if (empty($materials)) {
            return '<div class="empty-state">No materials data available for this project.</div>';
        }

        $html = '<table class="data-table">
            <thead>
                <tr>
                    <th>Material</th>
                    <th>Product</th>
                    <th class="text-center">Quantity</th>
                    <th>Usage</th>
                    <th>Cabinets</th>
                </tr>
            </thead>
            <tbody>';

        foreach ($materials as $item) {
            $materialType = $this->getMaterialBadgeClass($item['wood_species'] ?? '');
            $html .= '<tr>
                <td>
                    <span class="material-badge material-' . $materialType . '">' . htmlspecialchars($item['wood_species'] ?? 'N/A') . '</span>
                </td>
                <td>
                    <div class="font-bold">' . htmlspecialchars($item['product_name'] ?? 'N/A') . '</div>
                    <div class="text-muted font-mono">' . htmlspecialchars($item['product_sku'] ?? '') . '</div>
                </td>
                <td class="text-center">
                    <span class="qty-cell">' . number_format($item['quantity'] ?? 0, 2) . '</span>
                    <span class="qty-unit">' . htmlspecialchars($item['unit'] ?? 'ea') . '</span>
                </td>
                <td>' . htmlspecialchars($item['usage'] ?? 'N/A') . '</td>
                <td>
                    <div class="cabinet-ref">
                        <code>' . htmlspecialchars($item['cabinet_numbers'] ?? 'N/A') . '</code>
                    </div>
                </td>
            </tr>';
        }

        $html .= '</tbody></table>';
        return $html;
    }

    /**
     * Build hardware table HTML
     */
    protected function buildHardwareTable($hardwareItems): string
    {
        if (empty($hardwareItems) || (is_countable($hardwareItems) && count($hardwareItems) === 0)) {
            return '<div class="empty-state">No hardware data available for this project.</div>';
        }

        $html = '<table class="data-table">
            <thead>
                <tr>
                    <th>Component</th>
                    <th>Product ID</th>
                    <th class="text-center">Qty</th>
                    <th class="text-center">UOM</th>
                    <th class="text-right">Unit Cost</th>
                    <th class="text-right">Total Cost</th>
                </tr>
            </thead>
            <tbody>';

        $totalCost = 0;
        foreach ($hardwareItems as $item) {
            $itemTotal = floatval($item->total_material_cost ?? 0);
            $totalCost += $itemTotal;

            $hardwareType = $this->getHardwareBadgeClass($item->component_name ?? '');
            $html .= '<tr>
                <td>
                    <span class="hardware-badge hardware-' . $hardwareType . '">' . htmlspecialchars($item->component_name ?? 'N/A') . '</span>
                </td>
                <td class="font-mono text-muted">' . htmlspecialchars($item->product_id ?? 'N/A') . '</td>
                <td class="text-center qty-cell">' . number_format($item->quantity_required ?? 0) . '</td>
                <td class="text-center text-muted">' . htmlspecialchars($item->unit_of_measure ?? 'EA') . '</td>
                <td class="text-right cost-cell">$' . number_format($item->unit_cost ?? 0, 2) . '</td>
                <td class="text-right cost-cell positive">$' . number_format($itemTotal, 2) . '</td>
            </tr>';
        }

        // Total row
        $html .= '<tr class="total-row">
            <td colspan="5" class="text-right">Total Hardware Cost</td>
            <td class="text-right">$' . number_format($totalCost, 2) . '</td>
        </tr>';

        $html .= '</tbody></table>';
        return $html;
    }

    /**
     * Build room rows HTML
     */
    protected function buildRoomRows($rooms, bool $includePricing): string
    {
        if ($rooms->isEmpty()) {
            return '<tr><td colspan="5" class="empty-state">No rooms defined for this project.</td></tr>';
        }

        $html = '';
        $totalCabinets = 0;
        $totalLF = 0;
        $totalValue = 0;

        foreach ($rooms as $room) {
            $roomLocations = $room->locations;
            $roomRuns = $roomLocations->flatMap(fn($loc) => $loc->cabinetRuns);
            $roomCabinets = $roomRuns->flatMap(fn($run) => $run->cabinets);

            $cabinetCount = $roomCabinets->count();
            $linearFeet = $roomCabinets->sum('linear_feet');
            $value = $roomCabinets->sum('total_price');

            $totalCabinets += $cabinetCount;
            $totalLF += $linearFeet;
            $totalValue += $value;

            $roomClass = $this->getRoomBadgeClass($room->room_type ?? '');

            $html .= '<tr>
                <td class="font-bold">' . htmlspecialchars($room->name) . '</td>
                <td><span class="room-badge room-' . $roomClass . '">' . ucfirst($room->room_type ?? 'Other') . '</span></td>
                <td class="text-center">' . $cabinetCount . '</td>
                <td class="text-right font-mono">' . number_format($linearFeet, 2) . ' LF</td>';

            if ($includePricing) {
                $html .= '<td class="text-right font-mono">$' . number_format($value, 2) . '</td>';
            }

            $html .= '</tr>';
        }

        // Totals row
        $html .= '<tr class="total-row">
            <td colspan="2" class="text-right">Totals</td>
            <td class="text-center">' . $totalCabinets . '</td>
            <td class="text-right font-mono">' . number_format($totalLF, 2) . ' LF</td>';

        if ($includePricing) {
            $html .= '<td class="text-right font-mono">$' . number_format($totalValue, 2) . '</td>';
        }

        $html .= '</tr>';

        return $html;
    }

    /**
     * Build cabinet table HTML
     */
    protected function buildCabinetTable($rooms, bool $includePricing, bool $includeDimensions): string
    {
        $allCabinets = $rooms->flatMap(fn($room) =>
            $room->locations->flatMap(fn($loc) =>
                $loc->cabinetRuns->flatMap(fn($run) =>
                    $run->cabinets->map(fn($cab) => [
                        'room' => $room->name,
                        'location' => $loc->name,
                        'run' => $run->name,
                        'run_type' => $run->run_type,
                        'cabinet' => $cab,
                    ])
                )
            )
        );

        if ($allCabinets->isEmpty()) {
            return '<div class="empty-state">No cabinets defined for this project.</div>';
        }

        // Build header
        $html = '<table class="data-table"><thead><tr>
            <th>Room / Location</th>
            <th>Run</th>
            <th>Cabinet #</th>';

        if ($includeDimensions) {
            $html .= '<th class="text-center">Dimensions</th>';
        }

        $html .= '<th class="text-right">Linear Feet</th>
            <th>Material / Finish</th>';

        if ($includePricing) {
            $html .= '<th class="text-right">Price</th>';
        }

        $html .= '</tr></thead><tbody>';

        $totalLF = 0;
        $totalPrice = 0;

        foreach ($allCabinets as $data) {
            $cab = $data['cabinet'];
            $lf = floatval($cab->linear_feet ?? 0);
            $price = floatval($cab->total_price ?? 0);
            $totalLF += $lf;
            $totalPrice += $price;

            $runTypeClass = match(strtolower($data['run_type'] ?? '')) {
                'base' => 'cabinet-base',
                'wall', 'upper' => 'cabinet-wall',
                'tall' => 'cabinet-tall',
                default => 'cabinet-base',
            };

            $html .= '<tr>
                <td>
                    <div class="font-bold">' . htmlspecialchars($data['room']) . '</div>
                    <div class="text-muted" style="font-size: 9px;">' . htmlspecialchars($data['location']) . '</div>
                </td>
                <td>
                    <span class="cabinet-badge ' . $runTypeClass . '">' . ucfirst($data['run_type'] ?? 'N/A') . '</span>
                    <div class="text-muted" style="font-size: 9px;">' . htmlspecialchars($data['run']) . '</div>
                </td>
                <td class="font-bold">' . htmlspecialchars($cab->cabinet_number ?? 'N/A') . '</td>';

            if ($includeDimensions) {
                $dims = [];
                if ($cab->length_inches) $dims[] = $cab->length_inches . '"W';
                if ($cab->depth_inches) $dims[] = $cab->depth_inches . '"D';
                if ($cab->height_inches) $dims[] = $cab->height_inches . '"H';

                $html .= '<td class="text-center dimensions">' . (empty($dims) ? '—' : implode(' x ', $dims)) . '</td>';
            }

            $html .= '<td class="text-right font-mono font-bold">' . number_format($lf, 2) . ' LF</td>
                <td>
                    <div>' . htmlspecialchars(str_replace('_', ' ', ucwords($cab->material_category ?? 'N/A'))) . '</div>
                    <div class="text-muted" style="font-size: 9px;">' . htmlspecialchars(str_replace('_', ' ', ucwords($cab->finish_option ?? ''))) . '</div>
                </td>';

            if ($includePricing) {
                $html .= '<td class="text-right font-mono">$' . number_format($price, 2) . '</td>';
            }

            $html .= '</tr>';
        }

        // Totals row
        $colspan = $includeDimensions ? 4 : 3;
        $html .= '<tr class="total-row">
            <td colspan="' . $colspan . '" class="text-right">Totals</td>
            <td class="text-right font-mono">' . number_format($totalLF, 2) . ' LF</td>
            <td></td>';

        if ($includePricing) {
            $html .= '<td class="text-right font-mono">$' . number_format($totalPrice, 2) . '</td>';
        }

        $html .= '</tr></tbody></table>';

        return $html;
    }

    /**
     * Get material badge CSS class
     */
    protected function getMaterialBadgeClass(string $material): string
    {
        $material = strtolower($material);
        if (str_contains($material, 'plywood') || str_contains($material, 'ply')) return 'plywood';
        if (str_contains($material, 'hardwood') || str_contains($material, 'maple') || str_contains($material, 'oak')) return 'hardwood';
        if (str_contains($material, 'mdf')) return 'mdf';
        if (str_contains($material, 'melamine') || str_contains($material, 'laminate')) return 'melamine';
        return 'other';
    }

    /**
     * Get hardware badge CSS class
     */
    protected function getHardwareBadgeClass(string $component): string
    {
        $component = strtolower($component);
        if (str_contains($component, 'hinge')) return 'hinge';
        if (str_contains($component, 'slide') || str_contains($component, 'drawer')) return 'slide';
        if (str_contains($component, 'pull')) return 'pull';
        if (str_contains($component, 'knob')) return 'knob';
        return 'other';
    }

    /**
     * Get room badge CSS class
     */
    protected function getRoomBadgeClass(string $roomType): string
    {
        return match(strtolower($roomType)) {
            'kitchen' => 'kitchen',
            'bathroom' => 'bathroom',
            'bedroom' => 'bedroom',
            'laundry' => 'laundry',
            default => 'other',
        };
    }
}
