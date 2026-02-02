<?php

namespace Webkul\Project\Services;

use Illuminate\Support\Collection;
use Webkul\Product\Models\Product;
use Webkul\Project\Models\CncMaterialProduct;
use Webkul\Project\Models\CncMaterialUsage;
use Webkul\Project\Models\CncProgram;
use Webkul\Project\Models\Project;

/**
 * CNC Material Service
 *
 * Manages CNC material to inventory product connections for:
 * - Material usage tracking
 * - Stock level visibility
 * - Purchase order generation
 * - Material requirements planning
 */
class CncMaterialService
{
    /**
     * Get all material needs for a project
     */
    public function getProjectMaterialNeeds(Project $project): Collection
    {
        return CncMaterialUsage::forProject($project->id)
            ->needsMaterial()
            ->with(['product', 'cncProgram'])
            ->get()
            ->groupBy('product_id')
            ->map(function ($usages) {
                $product = $usages->first()->product;
                return [
                    'product' => $product,
                    'product_name' => $product->name ?? 'Unknown',
                    'total_sheets' => $usages->sum('sheets_required'),
                    'total_sqft' => $usages->sum('sqft_required'),
                    'total_cost' => $usages->sum('estimated_cost'),
                    'programs' => $usages->map(fn ($u) => [
                        'id' => $u->cnc_program_id,
                        'name' => $u->cncProgram->name ?? 'Unknown',
                        'sheets' => $u->sheets_required,
                    ]),
                    'on_hand' => $product->on_hand_quantity ?? 0,
                    'need_to_order' => max(0, $usages->sum('sheets_required') - ($product->on_hand_quantity ?? 0)),
                ];
            });
    }

    /**
     * Get all pending material needs across all projects
     */
    public function getAllPendingMaterialNeeds(): Collection
    {
        return CncMaterialUsage::pending()
            ->with(['product', 'cncProgram.project'])
            ->get()
            ->groupBy('product_id')
            ->map(function ($usages) {
                $product = $usages->first()->product;
                return [
                    'product' => $product,
                    'product_name' => $product->name ?? 'Unknown',
                    'total_sheets' => $usages->sum('sheets_required'),
                    'total_sqft' => $usages->sum('sqft_required'),
                    'estimated_cost' => $usages->sum('estimated_cost'),
                    'projects' => $usages->pluck('cncProgram.project.name')->unique()->values(),
                    'programs_count' => $usages->count(),
                    'on_hand' => $product->on_hand_quantity ?? 0,
                    'shortage' => max(0, $usages->sum('sheets_required') - ($product->on_hand_quantity ?? 0)),
                ];
            })
            ->sortByDesc('shortage');
    }

    /**
     * Get materials that need to be ordered
     */
    public function getMaterialsToOrder(): Collection
    {
        return $this->getAllPendingMaterialNeeds()
            ->filter(fn ($item) => $item['shortage'] > 0);
    }

    /**
     * Create material usage records for a CNC program
     */
    public function createUsageForProgram(CncProgram $program): ?CncMaterialUsage
    {
        // Check if usage already exists
        $existing = $program->materialUsage()->first();
        if ($existing) {
            return $existing;
        }

        return $program->createMaterialUsage();
    }

    /**
     * Create material usage records for all programs in a project
     */
    public function createUsageForProject(Project $project): Collection
    {
        $created = collect();

        foreach ($project->cncPrograms as $program) {
            $usage = $this->createUsageForProgram($program);
            if ($usage) {
                $created->push($usage);
            }
        }

        return $created;
    }

    /**
     * Update all material usages after nesting
     */
    public function updateUsagesAfterNesting(CncProgram $program): void
    {
        $program->updateMaterialUsage();
    }

    /**
     * Reserve materials for a project
     */
    public function reserveMaterialsForProject(Project $project): int
    {
        $reserved = 0;

        $usages = CncMaterialUsage::forProject($project->id)
            ->pending()
            ->get();

        foreach ($usages as $usage) {
            if ($usage->reserve()) {
                $reserved++;
            }
        }

        return $reserved;
    }

    /**
     * Issue materials for a CNC program (mark as used)
     */
    public function issueMaterials(CncProgram $program): bool
    {
        $usage = $program->materialUsage()->first();

        if (!$usage) {
            return false;
        }

        return $usage->issue(
            $program->sheets_actual,
            $program->sqft_actual
        );
    }

    /**
     * Get material stock status for all CNC material codes
     */
    public function getMaterialStockStatus(): Collection
    {
        $materialCodes = CncProgram::getMaterialCodes();
        $status = collect();

        foreach ($materialCodes as $code => $name) {
            $mapping = CncMaterialProduct::getDefaultForCode($code);

            $status->push([
                'code' => $code,
                'name' => $name,
                'product' => $mapping?->product,
                'product_name' => $mapping?->product?->name ?? 'Not Configured',
                'is_configured' => $mapping !== null,
                'stock_sheets' => $mapping?->current_stock_sheets ?? 0,
                'min_stock' => $mapping?->min_stock_sheets ?? 0,
                'is_low_stock' => $mapping?->is_low_stock ?? false,
                'sheets_needed' => $mapping?->sheets_needed ?? 0,
                'cost_per_sheet' => $mapping?->cost_per_sheet,
                'preferred_vendor' => $mapping?->preferredVendor?->name,
            ]);
        }

        return $status;
    }

    /**
     * Get unconfigured material codes (codes without product mappings)
     */
    public function getUnconfiguredMaterials(): array
    {
        $materialCodes = array_keys(CncProgram::getMaterialCodes());
        $configured = CncMaterialProduct::active()
            ->default()
            ->pluck('material_code')
            ->toArray();

        return array_diff($materialCodes, $configured);
    }

    /**
     * Auto-link CNC programs to products based on material code
     */
    public function autoLinkProgramsToProducts(): int
    {
        $linked = 0;

        $programs = CncProgram::whereNull('material_product_id')
            ->whereNotNull('material_code')
            ->get();

        foreach ($programs as $program) {
            $mapping = CncMaterialProduct::getDefaultForCode($program->material_code);

            if ($mapping) {
                $program->material_product_id = $mapping->product_id;
                $program->save();
                $linked++;
            }
        }

        return $linked;
    }

    /**
     * Calculate total material cost for a project
     */
    public function calculateProjectMaterialCost(Project $project): array
    {
        $programs = $project->cncPrograms()->with('materialUsage')->get();

        $estimated = 0;
        $actual = 0;

        foreach ($programs as $program) {
            foreach ($program->materialUsage as $usage) {
                $estimated += $usage->estimated_cost ?? 0;
                $actual += $usage->actual_cost ?? 0;
            }
        }

        return [
            'estimated' => $estimated,
            'actual' => $actual,
            'variance' => $actual - $estimated,
            'variance_pct' => $estimated > 0 ? round((($actual - $estimated) / $estimated) * 100, 1) : 0,
        ];
    }

    /**
     * Get suggested products for a material code based on name matching
     */
    public function getSuggestedProducts(string $materialCode): Collection
    {
        $materialName = CncProgram::getMaterialCodes()[$materialCode] ?? $materialCode;

        // Search for products with matching keywords
        $keywords = explode(' ', strtolower($materialName));
        $keywords = array_filter($keywords, fn ($k) => strlen($k) > 2);

        return Product::query()
            ->where(function ($query) use ($keywords, $materialCode) {
                foreach ($keywords as $keyword) {
                    $query->orWhere('name', 'like', "%{$keyword}%");
                }
                // Also search by material code directly
                $query->orWhere('name', 'like', "%{$materialCode}%");
                $query->orWhere('reference', 'like', "%{$materialCode}%");
            })
            ->limit(10)
            ->get();
    }
}
