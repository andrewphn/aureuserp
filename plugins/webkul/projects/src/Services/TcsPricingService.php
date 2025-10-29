<?php

namespace Webkul\Project\Services;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Webkul\Project\Models\Room;
use Webkul\Project\Models\RoomLocation;
use Webkul\Project\Models\CabinetRun;
use Webkul\Project\Models\CabinetSpecification;
use Webkul\Project\Models\Project;
use Webkul\Product\Models\Product;
use Webkul\Product\Models\Attribute;
use Webkul\Product\Models\AttributeOption;
use Webkul\Sale\Models\OrderLine;
use Webkul\Support\Models\Company;

/**
 * TCS Pricing Service
 *
 * Handles TCS's 3-tier configurable pricing structure loaded from product attributes:
 * Base Cabinet Level + Material Category + Finish Option = Total Unit Price/LF
 *
 * Features:
 * - Hierarchical inheritance: Room → Location → Cabinet Run → Cabinet
 * - Company-aware pricing based on shop capacity and capabilities
 * - Automatic lead time calculation based on shop capacity
 * - Capability-based option filtering (e.g., custom work requires capable shop)
 * - Dynamic pricing from products_attribute_options table
 *
 * Each level can override parent pricing, or inherit if not set.
 */
class TcsPricingService
{
    protected ?Company $company = null;

    /** Cache for pricing attributes loaded from database */
    protected static ?array $pricingCache = null;

    /**
     * Set the company context for pricing calculations
     *
     * @param Company|int|null $company Company model, ID, or null to load from project
     * @return self
     */
    public function forCompany($company = null): self
    {
        if ($company instanceof Company) {
            $this->company = $company;
        } elseif (is_int($company)) {
            $this->company = Company::find($company);
        }

        return $this;
    }

    /**
     * Load company from project context
     *
     * @param Project $project
     * @return self
     */
    public function forProject(Project $project): self
    {
        $this->company = $project->company;
        return $this;
    }

    /**
     * Load TCS pricing data from product attributes
     *
     * Caches the pricing structure from:
     * - products_attributes (Pricing Level, Material Category, Finish Option)
     * - products_attribute_options (actual pricing values in extra_price column)
     *
     * @return array Pricing structure with levels, materials, and finishes
     */
    protected function loadPricingFromDatabase(): array
    {
        if (static::$pricingCache !== null) {
            return static::$pricingCache;
        }

        // Load Pricing Level attribute and options
        $pricingLevelAttr = DB::table('products_attributes')
            ->where('name', 'Pricing Level')
            ->first();

        $cabinetLevels = [];
        if ($pricingLevelAttr) {
            $levels = DB::table('products_attribute_options')
                ->where('attribute_id', $pricingLevelAttr->id)
                ->orderBy('sort')
                ->get();

            foreach ($levels as $level) {
                // Extract level number from name (e.g., "Level 1 - Basic ($138/LF)" -> "1")
                if (preg_match('/Level (\d+)/', $level->name, $matches)) {
                    $levelNum = $matches[1];
                    // The base price should be calculated from Level 2 base ($168) + extra_price
                    // But the migration stores absolute prices, so we calculate base price
                    $basePrice = 168 + $level->extra_price; // Level 2 is base at $168
                    $cabinetLevels[$levelNum] = $basePrice;
                }
            }
        }

        // Load Material Category attribute and options
        $materialAttr = DB::table('products_attributes')
            ->where('name', 'Material Category')
            ->first();

        $materials = [];
        if ($materialAttr) {
            $options = DB::table('products_attribute_options')
                ->where('attribute_id', $materialAttr->id)
                ->orderBy('sort')
                ->get();

            foreach ($options as $option) {
                // Create slug from name for consistent keys
                $slug = $this->createSlug($option->name);
                $materials[$slug] = [
                    'name' => $option->name,
                    'price' => (float) $option->extra_price,
                ];
            }
        }

        // Load Finish Option attribute and options
        $finishAttr = DB::table('products_attributes')
            ->where('name', 'Finish Option')
            ->first();

        $finishes = [];
        if ($finishAttr) {
            $options = DB::table('products_attribute_options')
                ->where('attribute_id', $finishAttr->id)
                ->orderBy('sort')
                ->get();

            foreach ($options as $option) {
                // Create slug from name for consistent keys
                $slug = $this->createSlug($option->name);
                $finishes[$slug] = [
                    'name' => $option->name,
                    'price' => (float) $option->extra_price,
                ];
            }
        }

        static::$pricingCache = [
            'cabinet_levels' => $cabinetLevels,
            'materials' => $materials,
            'finishes' => $finishes,
        ];

        return static::$pricingCache;
    }

    /**
     * Create a slug from an attribute option name
     *
     * Examples:
     * - "Paint Grade (Hard Maple/Poplar)" -> "paint_grade"
     * - "Stain + Clear" -> "stain_clear"
     * - "Two-tone" -> "two_tone"
     */
    protected function createSlug(string $name): string
    {
        // Extract the first meaningful part before parenthesis or price
        $slug = strtolower($name);
        $slug = preg_replace('/\s*\([^)]*\)/', '', $slug); // Remove (...)
        $slug = preg_replace('/\s*\+\s*/', '_', $slug); // Replace + with _
        $slug = preg_replace('/[^a-z0-9_]+/', '_', $slug); // Replace non-alphanumeric
        $slug = trim($slug, '_'); // Trim underscores from ends

        return $slug;
    }

    /**
     * Get cabinet level options from database
     *
     * @return array [level_number => price]
     */
    public function getCabinetLevels(): array
    {
        $pricing = $this->loadPricingFromDatabase();
        return $pricing['cabinet_levels'];
    }

    /**
     * Get material category options from database
     *
     * @return array [slug => ['name' => ..., 'price' => ...]]
     */
    public function getMaterials(): array
    {
        $pricing = $this->loadPricingFromDatabase();
        return $pricing['materials'];
    }

    /**
     * Get finish option options from database
     *
     * @return array [slug => ['name' => ..., 'price' => ...]]
     */
    public function getFinishes(): array
    {
        $pricing = $this->loadPricingFromDatabase();
        return $pricing['finishes'];
    }

    /**
     * Get cabinet level options formatted for Filament Select field
     *
     * @return array [level_number => "Level N - Description ($X/LF)"]
     */
    public function getCabinetLevelOptions(): array
    {
        $levels = $this->getCabinetLevels();
        $options = [];

        // Get full names from database
        $pricingLevelAttr = DB::table('products_attributes')
            ->where('name', 'Pricing Level')
            ->first();

        if ($pricingLevelAttr) {
            $dbOptions = DB::table('products_attribute_options')
                ->where('attribute_id', $pricingLevelAttr->id)
                ->orderBy('sort')
                ->get();

            foreach ($dbOptions as $option) {
                if (preg_match('/Level (\d+)/', $option->name, $matches)) {
                    $levelNum = $matches[1];
                    $options[$levelNum] = $option->name;
                }
            }
        }

        return $options;
    }

    /**
     * Get material category options formatted for Filament Select field
     *
     * @return array [slug => "Name +$X/LF"]
     */
    public function getMaterialCategoryOptions(): array
    {
        $materials = $this->getMaterials();
        $options = [];

        foreach ($materials as $slug => $data) {
            $price = $data['price'];
            $name = $data['name'];
            $options[$slug] = $price > 0
                ? "{$name} +\${$price}/LF"
                : $name;
        }

        return $options;
    }

    /**
     * Get finish option options formatted for Filament Select field
     *
     * @return array [slug => "Name +$X/LF"]
     */
    public function getFinishOptions(): array
    {
        $finishes = $this->getFinishes();
        $options = [];

        foreach ($finishes as $slug => $data) {
            $price = $data['price'];
            $name = $data['name'];
            $options[$slug] = $price > 0
                ? "{$name} +\${$price}/LF"
                : $name;
        }

        return $options;
    }

    /**
     * Resolve effective pricing for a cabinet by checking inheritance chain
     *
     * Check order: Cabinet → Cabinet Run → Location → Room
     *
     * @param CabinetSpecification $cabinet The cabinet to price
     * @return array Resolved pricing configuration ['cabinet_level', 'material_category', 'finish_option']
     */
    public function resolveEffectivePricing(CabinetSpecification $cabinet): array
    {
        $cabinetRun = $cabinet->cabinetRun;
        $location = $cabinetRun?->roomLocation;
        $room = $location?->room;

        return [
            'cabinet_level'     => $cabinet->cabinet_level
                                ?? $cabinetRun?->cabinet_level
                                ?? $location?->cabinet_level
                                ?? $room?->cabinet_level
                                ?? '3',  // Default to Level 3

            'material_category' => $cabinet->material_category
                                ?? $cabinetRun?->material_category
                                ?? $location?->material_category
                                ?? $room?->material_category
                                ?? 'stain_grade',  // Default to stain grade

            'finish_option'     => $cabinet->finish_option
                                ?? $cabinetRun?->finish_option
                                ?? $location?->finish_option
                                ?? $room?->finish_option
                                ?? 'unfinished',  // Default to unfinished
        ];
    }

    /**
     * Resolve effective pricing for any entity type in the hierarchy
     *
     * @param Model $entity The entity (Room, Location, Run, or Cabinet)
     * @param string $annotationType The type ('room', 'location', 'cabinet_run', 'cabinet')
     * @return array Resolved pricing configuration
     */
    public function resolveEffectivePricingForEntity(Model $entity, string $annotationType): array
    {
        return match ($annotationType) {
            'room' => $this->resolveForRoom($entity),
            'location' => $this->resolveForLocation($entity),
            'cabinet_run' => $this->resolveForCabinetRun($entity),
            'cabinet' => $this->resolveEffectivePricing($entity),
            default => $this->getDefaultPricing(),
        };
    }

    /**
     * Resolve pricing for a room
     */
    protected function resolveForRoom(Room $room): array
    {
        return [
            'cabinet_level'     => $room->cabinet_level ?? '3',
            'material_category' => $room->material_category ?? 'stain_grade',
            'finish_option'     => $room->finish_option ?? 'unfinished',
        ];
    }

    /**
     * Resolve pricing for a location (checks parent room)
     */
    protected function resolveForLocation(RoomLocation $location): array
    {
        $room = $location->room;

        return [
            'cabinet_level'     => $location->cabinet_level ?? $room?->cabinet_level ?? '3',
            'material_category' => $location->material_category ?? $room?->material_category ?? 'stain_grade',
            'finish_option'     => $location->finish_option ?? $room?->finish_option ?? 'unfinished',
        ];
    }

    /**
     * Resolve pricing for a cabinet run (checks parent location and room)
     */
    protected function resolveForCabinetRun(CabinetRun $run): array
    {
        $location = $run->roomLocation;
        $room = $location?->room;

        return [
            'cabinet_level'     => $run->cabinet_level
                                ?? $location?->cabinet_level
                                ?? $room?->cabinet_level
                                ?? '3',

            'material_category' => $run->material_category
                                ?? $location?->material_category
                                ?? $room?->material_category
                                ?? 'stain_grade',

            'finish_option'     => $run->finish_option
                                ?? $location?->finish_option
                                ?? $room?->finish_option
                                ?? 'unfinished',
        ];
    }

    /**
     * Get default pricing configuration
     */
    protected function getDefaultPricing(): array
    {
        return [
            'cabinet_level'     => '3',
            'material_category' => 'stain_grade',
            'finish_option'     => 'unfinished',
        ];
    }

    /**
     * Calculate total unit price per linear foot from pricing configuration
     *
     * @param string $cabinetLevel The cabinet construction level (1-5)
     * @param string $materialCategory The material category slug
     * @param string $finishOption The finish option slug
     * @return float Total unit price per LF
     */
    public function calculateUnitPrice(
        string $cabinetLevel,
        string $materialCategory,
        string $finishOption
    ): float {
        $levels = $this->getCabinetLevels();
        $materials = $this->getMaterials();
        $finishes = $this->getFinishes();

        $basePrice = $levels[$cabinetLevel] ?? 192.00;  // Default to Level 3
        $materialPrice = $materials[$materialCategory]['price'] ?? 156.00;  // Default to stain grade
        $finishPrice = $finishes[$finishOption]['price'] ?? 0.00;  // Default to unfinished

        return $basePrice + $materialPrice + $finishPrice;
    }

    /**
     * Get price breakdown for display
     *
     * @param string $cabinetLevel The cabinet construction level
     * @param string $materialCategory The material category slug
     * @param string $finishOption The finish option slug
     * @return array Price breakdown with labels and values
     */
    public function getPriceBreakdown(
        string $cabinetLevel,
        string $materialCategory,
        string $finishOption
    ): array {
        $levels = $this->getCabinetLevels();
        $materials = $this->getMaterials();
        $finishes = $this->getFinishes();

        $basePrice = $levels[$cabinetLevel] ?? 192.00;
        $materialPrice = $materials[$materialCategory]['price'] ?? 156.00;
        $finishPrice = $finishes[$finishOption]['price'] ?? 0.00;
        $totalPrice = $basePrice + $materialPrice + $finishPrice;

        return [
            'base' => [
                'label' => $this->getCabinetLevelLabel($cabinetLevel),
                'price' => $basePrice,
            ],
            'material' => [
                'label' => $this->getMaterialCategoryLabel($materialCategory),
                'price' => $materialPrice,
            ],
            'finish' => [
                'label' => $this->getFinishOptionLabel($finishOption),
                'price' => $finishPrice,
            ],
            'total' => [
                'label' => 'Total per Linear Foot',
                'price' => $totalPrice,
            ],
        ];
    }

    /**
     * Get formatted HTML price breakdown for UI display
     */
    public function getFormattedPriceBreakdown(
        string $cabinetLevel,
        string $materialCategory,
        string $finishOption
    ): string {
        $breakdown = $this->getPriceBreakdown($cabinetLevel, $materialCategory, $finishOption);

        return sprintf(
            '<div class="space-y-2 text-sm">
                <div class="flex justify-between">
                    <span>%s</span>
                    <span class="font-medium">$%s</span>
                </div>
                <div class="flex justify-between text-primary-600">
                    <span>%s</span>
                    <span class="font-medium">+$%s</span>
                </div>
                <div class="flex justify-between text-primary-600">
                    <span>%s</span>
                    <span class="font-medium">+$%s</span>
                </div>
                <div class="border-t pt-2 flex justify-between font-bold text-base">
                    <span>%s</span>
                    <span class="text-success-600">$%s/LF</span>
                </div>
            </div>',
            $breakdown['base']['label'],
            number_format($breakdown['base']['price'], 2),
            $breakdown['material']['label'],
            number_format($breakdown['material']['price'], 2),
            $breakdown['finish']['label'],
            number_format($breakdown['finish']['price'], 2),
            $breakdown['total']['label'],
            number_format($breakdown['total']['price'], 2)
        );
    }

    /**
     * Get label for cabinet level from database
     */
    protected function getCabinetLevelLabel(string $level): string
    {
        $levels = $this->getCabinetLevels();
        $price = $levels[$level] ?? 192.00;

        // Get full label from database
        $pricingLevelAttr = DB::table('products_attributes')
            ->where('name', 'Pricing Level')
            ->first();

        if ($pricingLevelAttr) {
            $option = DB::table('products_attribute_options')
                ->where('attribute_id', $pricingLevelAttr->id)
                ->where('name', 'LIKE', "Level {$level}%")
                ->first();

            if ($option) {
                return preg_replace('/\s*\$\d+\/LF\s*/', '', $option->name); // Remove price from label
            }
        }

        return "Level {$level} - \${$price}/LF";
    }

    /**
     * Get label for material category from database
     */
    protected function getMaterialCategoryLabel(string $categorySlug): string
    {
        $materials = $this->getMaterials();
        return $materials[$categorySlug]['name'] ?? 'Stain Grade';
    }

    /**
     * Get label for finish option from database
     */
    protected function getFinishOptionLabel(string $optionSlug): string
    {
        $finishes = $this->getFinishes();
        return $finishes[$optionSlug]['name'] ?? 'Unfinished';
    }

    /**
     * Check if pricing is inherited from parent
     *
     * @param Model $entity The entity to check
     * @param string $field The pricing field ('cabinet_level', 'material_category', 'finish_option')
     * @return bool True if inherited from parent, false if explicitly set
     */
    public function isInherited(Model $entity, string $field): bool
    {
        return empty($entity->$field);
    }

    /**
     * Get inheritance source for a pricing field
     *
     * @param Model $entity The entity
     * @param string $field The pricing field
     * @return string|null The source ('parent', 'grandparent', 'default') or null if not inherited
     */
    public function getInheritanceSource(Model $entity, string $field): ?string
    {
        if (!empty($entity->$field)) {
            return null;  // Not inherited
        }

        // Check parent based on entity type
        if ($entity instanceof CabinetSpecification) {
            $run = $entity->cabinetRun;
            if ($run && !empty($run->$field)) {
                return 'cabinet_run';
            }
            $location = $run?->roomLocation;
            if ($location && !empty($location->$field)) {
                return 'location';
            }
            $room = $location?->room;
            if ($room && !empty($room->$field)) {
                return 'room';
            }
        } elseif ($entity instanceof CabinetRun) {
            $location = $entity->roomLocation;
            if ($location && !empty($location->$field)) {
                return 'location';
            }
            $room = $location?->room;
            if ($room && !empty($room->$field)) {
                return 'room';
            }
        } elseif ($entity instanceof RoomLocation) {
            $room = $entity->room;
            if ($room && !empty($room->$field)) {
                return 'room';
            }
        }

        return 'default';
    }

    /**
     * Check if company can produce a specific cabinet level
     * Higher levels (4-5) require more shop capacity and capabilities
     *
     * @param string $cabinetLevel The cabinet level to check
     * @return bool True if company can produce this level
     */
    public function companyCanProduceLevel(string $cabinetLevel): bool
    {
        if (!$this->company) {
            return true;  // No company context, allow all
        }

        // Level 5 (Custom work) requires high capacity
        if ($cabinetLevel === '5' && $this->company->shop_capacity_per_day < 20) {
            return false;
        }

        // Level 4 (Beaded frames, specialty) requires moderate capacity
        if ($cabinetLevel === '4' && $this->company->shop_capacity_per_day < 15) {
            return false;
        }

        return true;
    }

    /**
     * Get available cabinet levels for company
     *
     * @return array Available levels with pricing
     */
    public function getAvailableLevels(): array
    {
        $cabinetLevels = $this->getCabinetLevels();
        $levels = [];

        foreach ($cabinetLevels as $level => $price) {
            if ($this->companyCanProduceLevel($level)) {
                $levels[$level] = $this->getCabinetLevelLabel($level) . ' - $' . number_format($price, 0) . '/LF';
            }
        }
        return $levels;
    }

    /**
     * Estimate lead time for cabinet production based on shop capacity
     *
     * @param float $linearFeet Total linear feet to produce
     * @param string $cabinetLevel Cabinet level (affects production time)
     * @return int Estimated lead time in working days
     */
    public function estimateLeadTime(float $linearFeet, string $cabinetLevel): int
    {
        if (!$this->company || !$this->company->shop_capacity_per_day) {
            return ceil($linearFeet / 20); // Default: 20 LF per day
        }

        // Higher levels take longer per LF
        $complexityMultiplier = match ($cabinetLevel) {
            '1' => 0.8,  // Open boxes are faster
            '2' => 1.0,  // Standard
            '3' => 1.2,  // More detail
            '4' => 1.5,  // Specialty work
            '5' => 2.0,  // Custom work takes longest
            default => 1.0,
        };

        $adjustedCapacity = $this->company->shop_capacity_per_day / $complexityMultiplier;
        $workingDays = ceil($linearFeet / $adjustedCapacity);

        return max(1, $workingDays); // At least 1 day
    }

    /**
     * Get company shop capacity summary for display
     *
     * @return string|null HTML summary of shop capacity
     */
    public function getCapacitySummary(): ?string
    {
        if (!$this->company) {
            return null;
        }

        return sprintf(
            '<div class="text-xs text-gray-600 space-y-1">
                <div><strong>Shop:</strong> %s</div>
                <div><strong>Capacity:</strong> %s LF/day, %s LF/month</div>
                <div><strong>Hours:</strong> %s hrs/day, %s days/month</div>
            </div>',
            e($this->company->name),
            number_format($this->company->shop_capacity_per_day ?? 0, 1),
            number_format($this->company->shop_capacity_per_month ?? 0, 0),
            $this->company->working_hours_per_day ?? 8,
            $this->company->working_days_per_month ?? 17
        );
    }
}
