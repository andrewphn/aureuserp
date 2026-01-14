<?php

namespace App\Services;

use Webkul\Project\Models\CabinetSection;
use Webkul\Project\Models\Drawer;
use Webkul\Project\Models\Shelf;
use Webkul\Project\Models\Door;
use Webkul\Project\Models\Pullout;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

/**
 * Opening Configurator Service
 * 
 * Manages space allocation within cabinet section openings.
 * Calculates positions for components (drawers, shelves, doors, pullouts)
 * and tracks consumed/remaining space.
 * 
 * @see docs/OPENING_CONFIGURATOR_SYSTEM.md
 * @see docs/DATABASE_HIERARCHY.md (projects_cabinet_sections)
 */
class OpeningConfiguratorService
{
    // ===== SHOP STANDARD GAP CONSTANTS =====
    
    /** Default gap at top of opening (1/8") */
    public const GAP_TOP_REVEAL_INCHES = 0.125;
    
    /** Default gap at bottom of opening (1/8") */
    public const GAP_BOTTOM_REVEAL_INCHES = 0.125;
    
    /** Default gap between components (1/8") */
    public const GAP_BETWEEN_COMPONENTS_INCHES = 0.125;
    
    /** Side reveal for doors (1/16" per side) */
    public const GAP_DOOR_SIDE_REVEAL_INCHES = 0.0625;
    
    // ===== MINIMUM HEIGHTS =====
    
    /** Minimum opening height for a shelf position */
    public const MIN_SHELF_OPENING_HEIGHT_INCHES = 5.5;
    
    /** Minimum drawer front height */
    public const MIN_DRAWER_FRONT_HEIGHT_INCHES = 4.0;
    
    /**
     * Calculate all component positions and update the section's space tracking
     */
    public function calculateSectionLayout(CabinetSection $section): array
    {
        $section->load(['drawers', 'shelves', 'doors', 'pullouts']);
        
        $openingHeight = $section->opening_height_inches ?? 0;
        $openingWidth = $section->opening_width_inches ?? 0;
        
        // Get gap settings from section or use defaults
        $topReveal = $section->top_reveal_inches ?? self::GAP_TOP_REVEAL_INCHES;
        $bottomReveal = $section->bottom_reveal_inches ?? self::GAP_BOTTOM_REVEAL_INCHES;
        $componentGap = $section->component_gap_inches ?? self::GAP_BETWEEN_COMPONENTS_INCHES;
        
        // Get layout direction
        $layoutDirection = $section->layout_direction ?? 'vertical';
        
        // Collect all components
        $components = $this->collectAllComponents($section);
        
        // Calculate positions based on layout direction
        $result = match ($layoutDirection) {
            'horizontal' => $this->calculateHorizontalLayout($components, $openingWidth, $componentGap),
            'grid' => $this->calculateGridLayout($components, $openingWidth, $openingHeight, $componentGap),
            default => $this->calculateVerticalLayout($components, $openingHeight, $topReveal, $bottomReveal, $componentGap),
        };
        
        // Update section totals
        $section->total_consumed_height_inches = $result['consumed_height'];
        $section->total_consumed_width_inches = $result['consumed_width'];
        $section->remaining_height_inches = $openingHeight - $result['consumed_height'];
        $section->remaining_width_inches = $openingWidth - $result['consumed_width'];
        $section->save();
        
        return $result;
    }
    
    /**
     * Collect all components from a section, sorted by position
     */
    public function collectAllComponents(CabinetSection $section): Collection
    {
        $components = collect();
        
        foreach ($section->drawers as $drawer) {
            $components->push([
                'model' => $drawer,
                'type' => 'drawer',
                'height' => $drawer->front_height_inches ?? 0,
                'width' => $drawer->front_width_inches ?? $section->opening_width_inches ?? 0,
                'sort_order' => $drawer->sort_order ?? 0,
            ]);
        }
        
        foreach ($section->shelves as $shelf) {
            // For shelves, consumed height includes the minimum opening clearance
            $components->push([
                'model' => $shelf,
                'type' => 'shelf',
                'height' => self::MIN_SHELF_OPENING_HEIGHT_INCHES, // Opening space for shelf
                'width' => $shelf->width_inches ?? $section->opening_width_inches ?? 0,
                'sort_order' => $shelf->sort_order ?? 0,
            ]);
        }
        
        foreach ($section->doors as $door) {
            $components->push([
                'model' => $door,
                'type' => 'door',
                'height' => $door->height_inches ?? 0,
                'width' => $door->width_inches ?? 0,
                'sort_order' => $door->sort_order ?? 0,
            ]);
        }
        
        foreach ($section->pullouts as $pullout) {
            $components->push([
                'model' => $pullout,
                'type' => 'pullout',
                'height' => $pullout->height_inches ?? 0,
                'width' => $pullout->width_inches ?? 0,
                'sort_order' => $pullout->sort_order ?? 0,
            ]);
        }
        
        // Sort by sort_order (bottom to top for vertical stacking)
        return $components->sortBy('sort_order')->values();
    }
    
    /**
     * Calculate vertical (stacked) layout - most common for drawer banks
     * 
     * Components stack from bottom to top:
     * - Bottom reveal
     * - Component 1 (position = bottom_reveal)
     * - Gap
     * - Component 2 (position = prev_position + prev_consumed_height)
     * - ...
     * - Top reveal
     */
    public function calculateVerticalLayout(
        Collection $components,
        float $openingHeight,
        float $topReveal,
        float $bottomReveal,
        float $componentGap
    ): array {
        $currentPosition = $bottomReveal;
        $totalConsumedHeight = $bottomReveal + $topReveal;
        $positions = [];
        $count = $components->count();
        
        foreach ($components as $index => $item) {
            /** @var Model $model */
            $model = $item['model'];
            $componentHeight = $item['height'];
            
            // Calculate consumed height (height + gap, except for last component)
            $isLast = ($index === $count - 1);
            $consumedHeight = $isLast 
                ? $componentHeight 
                : $componentHeight + $componentGap;
            
            // Update model with position data
            $model->position_in_opening_inches = $currentPosition;
            $model->consumed_height_inches = $consumedHeight;
            $model->save();
            
            $positions[] = [
                'id' => $model->id,
                'type' => $item['type'],
                'position' => $currentPosition,
                'height' => $componentHeight,
                'consumed_height' => $consumedHeight,
            ];
            
            $currentPosition += $consumedHeight;
            $totalConsumedHeight += $consumedHeight;
        }
        
        return [
            'layout' => 'vertical',
            'consumed_height' => $totalConsumedHeight,
            'consumed_width' => 0, // Full width in vertical layout
            'positions' => $positions,
            'valid' => $totalConsumedHeight <= $openingHeight,
            'overflow' => max(0, $totalConsumedHeight - $openingHeight),
        ];
    }
    
    /**
     * Calculate horizontal (side-by-side) layout
     * Used for side-by-side doors or horizontal divisions
     */
    public function calculateHorizontalLayout(
        Collection $components,
        float $openingWidth,
        float $componentGap
    ): array {
        $currentPosition = 0;
        $totalConsumedWidth = 0;
        $positions = [];
        $count = $components->count();
        
        foreach ($components as $index => $item) {
            /** @var Model $model */
            $model = $item['model'];
            $componentWidth = $item['width'];
            
            // Calculate consumed width (width + gap, except for last component)
            $isLast = ($index === $count - 1);
            $consumedWidth = $isLast 
                ? $componentWidth 
                : $componentWidth + $componentGap;
            
            // Update model with position data
            $model->position_from_left_inches = $currentPosition;
            $model->consumed_width_inches = $consumedWidth;
            $model->save();
            
            $positions[] = [
                'id' => $model->id,
                'type' => $item['type'],
                'position_left' => $currentPosition,
                'width' => $componentWidth,
                'consumed_width' => $consumedWidth,
            ];
            
            $currentPosition += $consumedWidth;
            $totalConsumedWidth += $consumedWidth;
        }
        
        return [
            'layout' => 'horizontal',
            'consumed_height' => 0, // Full height in horizontal layout
            'consumed_width' => $totalConsumedWidth,
            'positions' => $positions,
            'valid' => $totalConsumedWidth <= $openingWidth,
            'overflow' => max(0, $totalConsumedWidth - $openingWidth),
        ];
    }
    
    /**
     * Calculate grid layout (for complex mixed configurations)
     */
    public function calculateGridLayout(
        Collection $components,
        float $openingWidth,
        float $openingHeight,
        float $componentGap
    ): array {
        // For grid, we'd need row/column assignments
        // For now, fall back to vertical
        return $this->calculateVerticalLayout(
            $components, 
            $openingHeight, 
            self::GAP_TOP_REVEAL_INCHES, 
            self::GAP_BOTTOM_REVEAL_INCHES, 
            $componentGap
        );
    }
    
    /**
     * Get consumed height for a specific component type
     */
    public function getComponentConsumedHeight(Model $component, string $type): float
    {
        return match ($type) {
            'drawer' => $component->front_height_inches ?? 0,
            'shelf' => self::MIN_SHELF_OPENING_HEIGHT_INCHES,
            'door' => $component->height_inches ?? 0,
            'pullout' => $component->height_inches ?? 0,
            default => 0,
        };
    }
    
    /**
     * Calculate remaining space in a section
     */
    public function getRemainingSpace(CabinetSection $section): array
    {
        $this->calculateSectionLayout($section);
        $section->refresh();
        
        return [
            'remaining_height' => $section->remaining_height_inches ?? 0,
            'remaining_width' => $section->remaining_width_inches ?? 0,
            'can_fit_drawer' => ($section->remaining_height_inches ?? 0) >= self::MIN_DRAWER_FRONT_HEIGHT_INCHES + self::GAP_BETWEEN_COMPONENTS_INCHES,
            'can_fit_shelf' => ($section->remaining_height_inches ?? 0) >= self::MIN_SHELF_OPENING_HEIGHT_INCHES + self::GAP_BETWEEN_COMPONENTS_INCHES,
        ];
    }
    
    /**
     * Check if a component can fit in the remaining space
     */
    public function canFitComponent(CabinetSection $section, string $type, float $height = 0): bool
    {
        $space = $this->getRemainingSpace($section);
        
        $requiredHeight = match ($type) {
            'drawer' => max($height, self::MIN_DRAWER_FRONT_HEIGHT_INCHES),
            'shelf' => self::MIN_SHELF_OPENING_HEIGHT_INCHES,
            'door' => $height,
            'pullout' => $height,
            default => $height,
        };
        
        // Add gap unless it's the first/only component
        $componentCount = $section->drawers()->count() 
            + $section->shelves()->count() 
            + $section->doors()->count() 
            + $section->pullouts()->count();
        
        if ($componentCount > 0) {
            $requiredHeight += self::GAP_BETWEEN_COMPONENTS_INCHES;
        }
        
        return ($space['remaining_height'] ?? 0) >= $requiredHeight;
    }
    
    /**
     * Convert decimal inches to fraction string
     */
    public function toFraction(float $decimal, int $precision = 16): string
    {
        $whole = floor($decimal);
        $fraction = $decimal - $whole;
        
        if ($fraction < 0.001) {
            return $whole > 0 ? (string) $whole : '0';
        }
        
        $numerator = round($fraction * $precision);
        $denominator = $precision;
        
        // Simplify fraction
        $gcd = $this->gcd($numerator, $denominator);
        $numerator /= $gcd;
        $denominator /= $gcd;
        
        if ($whole > 0) {
            return "{$whole}-{$numerator}/{$denominator}";
        }
        
        return "{$numerator}/{$denominator}";
    }
    
    /**
     * Greatest common divisor for fraction simplification
     */
    private function gcd(int $a, int $b): int
    {
        return $b == 0 ? $a : $this->gcd($b, $a % $b);
    }
}
