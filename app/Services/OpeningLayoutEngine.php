<?php

namespace App\Services;

use Webkul\Project\Models\CabinetSection;
use Illuminate\Support\Collection;

/**
 * Opening Layout Engine
 *
 * Provides auto-arrangement strategies for components within openings.
 *
 * Strategies:
 * - stack_from_bottom: Stack components from bottom up (drawer banks)
 * - stack_from_top: Stack components from top down (upper cabinet shelves)
 * - equal_distribution: Divide space equally among components
 * - weighted_distribution: Allocate space based on component type weights
 *
 * @see docs/OPENING_CONFIGURATOR_SYSTEM.md
 */
class OpeningLayoutEngine
{
    public function __construct(
        private OpeningConfiguratorService $configurator
    ) {}

    /**
     * Auto-arrange components in a section using the specified strategy
     */
    public function autoArrange(CabinetSection $section, string $strategy = 'stack_from_bottom'): array
    {
        $section->load(['drawers', 'shelves', 'doors', 'pullouts', 'falseFronts']);

        $components = $this->configurator->collectAllComponents($section);

        if ($components->isEmpty()) {
            return [
                'success' => true,
                'message' => 'No components to arrange',
                'components' => [],
                'positions' => [],
                'total_consumed' => 0,
                'remaining' => $section->opening_height_inches ?? 0,
                'overflow' => 0,
                'has_overflow' => false,
            ];
        }

        return match ($strategy) {
            'stack_from_top' => $this->stackFromTop($section, $components),
            'equal_distribution' => $this->equalDistribution($section, $components),
            'weighted_distribution' => $this->weightedDistribution($section, $components),
            default => $this->stackFromBottom($section, $components),
        };
    }

    /**
     * Stack components from bottom to top
     * Typical for drawer banks: bottom drawer at bottom, top drawer at top
     */
    public function stackFromBottom(CabinetSection $section, Collection $components): array
    {
        $openingHeight = $section->opening_height_inches ?? 0;
        $bottomReveal = $section->bottom_reveal_inches ?? OpeningConfiguratorService::GAP_BOTTOM_REVEAL_INCHES;
        $topReveal = $section->top_reveal_inches ?? OpeningConfiguratorService::GAP_TOP_REVEAL_INCHES;
        $gap = $section->component_gap_inches ?? OpeningConfiguratorService::GAP_BETWEEN_COMPONENTS_INCHES;

        // Sort by sort_order ascending (1, 2, 3 = bottom to top)
        $sorted = $components->sortBy('sort_order')->values();

        $currentPosition = $bottomReveal;
        $positions = [];
        $count = $sorted->count();

        foreach ($sorted as $index => $item) {
            $model = $item['model'];
            $height = $item['height'];

            $isLast = ($index === $count - 1);
            $gapAfter = $isLast ? 0 : $gap;

            $model->position_in_opening_inches = $currentPosition;
            $model->consumed_height_inches = $height + $gapAfter;
            $model->save();

            $positions[] = [
                'id' => $model->id,
                'type' => $item['type'],
                'position' => $currentPosition,
                'height' => $height,
            ];

            $currentPosition += $height + $gapAfter;
        }

        $totalUsed = $currentPosition + $topReveal;
        $overflow = max(0, $totalUsed - $openingHeight);
        $success = $totalUsed <= $openingHeight;

        // Update section totals
        $section->total_consumed_height_inches = $totalUsed;
        $section->remaining_height_inches = $openingHeight - $totalUsed;
        $section->save();

        $result = [
            'success' => $success,
            'strategy' => 'stack_from_bottom',
            'total_consumed' => $totalUsed,
            'remaining' => $openingHeight - $totalUsed,
            'overflow' => $overflow,
            'has_overflow' => $overflow > 0,
            'positions' => $positions,
        ];

        if (!$success && $overflow > 0) {
            $result['error'] = sprintf(
                'Components overflow opening height by %.4f" (%.4f" total in %.4f" opening)',
                $overflow,
                $totalUsed,
                $openingHeight
            );
        }

        return $result;
    }

    /**
     * Stack components from top to bottom
     * Typical for shelves in upper cabinets
     */
    public function stackFromTop(CabinetSection $section, Collection $components): array
    {
        $openingHeight = $section->opening_height_inches ?? 0;
        $bottomReveal = $section->bottom_reveal_inches ?? OpeningConfiguratorService::GAP_BOTTOM_REVEAL_INCHES;
        $topReveal = $section->top_reveal_inches ?? OpeningConfiguratorService::GAP_TOP_REVEAL_INCHES;
        $gap = $section->component_gap_inches ?? OpeningConfiguratorService::GAP_BETWEEN_COMPONENTS_INCHES;

        // Sort by sort_order descending (1 = top, 2 = below, etc.)
        $sorted = $components->sortBy('sort_order')->values();

        // Calculate total component heights
        $totalComponentHeight = $sorted->sum('height');
        $totalGaps = ($sorted->count() - 1) * $gap;
        $totalNeeded = $totalComponentHeight + $totalGaps + $topReveal + $bottomReveal;

        // Start from top
        $currentPosition = $openingHeight - $topReveal;
        $positions = [];

        foreach ($sorted as $index => $item) {
            $model = $item['model'];
            $height = $item['height'];

            // Position is bottom of component, so subtract height
            $currentPosition -= $height;

            $gapAfter = ($index < $sorted->count() - 1) ? $gap : 0;

            $model->position_in_opening_inches = $currentPosition;
            $model->consumed_height_inches = $height + $gapAfter;
            $model->save();

            $positions[] = [
                'id' => $model->id,
                'type' => $item['type'],
                'position' => $currentPosition,
                'height' => $height,
            ];

            $currentPosition -= $gapAfter;
        }

        // Update section totals
        $section->total_consumed_height_inches = $totalNeeded;
        $section->remaining_height_inches = $openingHeight - $totalNeeded;
        $section->save();

        return [
            'success' => $totalNeeded <= $openingHeight,
            'strategy' => 'stack_from_top',
            'total_consumed' => $totalNeeded,
            'remaining' => $openingHeight - $totalNeeded,
            'overflow' => max(0, $totalNeeded - $openingHeight),
            'positions' => $positions,
        ];
    }

    /**
     * Distribute components with equal spacing
     */
    public function equalDistribution(CabinetSection $section, Collection $components): array
    {
        $openingHeight = $section->opening_height_inches ?? 0;
        $bottomReveal = $section->bottom_reveal_inches ?? OpeningConfiguratorService::GAP_BOTTOM_REVEAL_INCHES;
        $topReveal = $section->top_reveal_inches ?? OpeningConfiguratorService::GAP_TOP_REVEAL_INCHES;

        $count = $components->count();
        if ($count === 0) {
            return ['success' => true, 'positions' => []];
        }

        // Available space for components
        $availableHeight = $openingHeight - $topReveal - $bottomReveal;

        // Total height of all components
        $totalComponentHeight = $components->sum('height');

        // Remaining space to distribute as gaps
        $remainingSpace = $availableHeight - $totalComponentHeight;

        // Equal gaps (one between each component)
        $gapCount = $count - 1;
        $equalGap = $gapCount > 0 ? $remainingSpace / $gapCount : 0;
        $equalGap = max(0, $equalGap); // No negative gaps

        $sorted = $components->sortBy('sort_order')->values();
        $currentPosition = $bottomReveal;
        $positions = [];

        foreach ($sorted as $index => $item) {
            $model = $item['model'];
            $height = $item['height'];

            $model->position_in_opening_inches = $currentPosition;
            $model->consumed_height_inches = $height + ($index < $count - 1 ? $equalGap : 0);
            $model->save();

            $positions[] = [
                'id' => $model->id,
                'type' => $item['type'],
                'position' => $currentPosition,
                'height' => $height,
                'gap_after' => $index < $count - 1 ? $equalGap : 0,
            ];

            $currentPosition += $height + $equalGap;
        }

        $totalUsed = $currentPosition - $equalGap + $topReveal; // Remove last gap, add top reveal

        $section->total_consumed_height_inches = $totalUsed;
        $section->remaining_height_inches = $openingHeight - $totalUsed;
        $section->save();

        return [
            'success' => true,
            'strategy' => 'equal_distribution',
            'calculated_gap' => $equalGap,
            'total_consumed' => $totalUsed,
            'remaining' => 0, // By design, equal distribution uses all space
            'positions' => $positions,
        ];
    }

    /**
     * Distribute based on component type weights
     * Larger components (doors, big drawers) get proportionally more space
     */
    public function weightedDistribution(CabinetSection $section, Collection $components): array
    {
        $openingHeight = $section->opening_height_inches ?? 0;
        $bottomReveal = $section->bottom_reveal_inches ?? OpeningConfiguratorService::GAP_BOTTOM_REVEAL_INCHES;
        $topReveal = $section->top_reveal_inches ?? OpeningConfiguratorService::GAP_TOP_REVEAL_INCHES;
        $minGap = $section->component_gap_inches ?? OpeningConfiguratorService::GAP_BETWEEN_COMPONENTS_INCHES;

        $count = $components->count();
        if ($count === 0) {
            return ['success' => true, 'positions' => []];
        }

        // Calculate weights based on component heights
        $totalWeight = $components->sum('height');
        if ($totalWeight == 0) {
            return $this->equalDistribution($section, $components);
        }

        $availableHeight = $openingHeight - $topReveal - $bottomReveal - (($count - 1) * $minGap);

        $sorted = $components->sortBy('sort_order')->values();
        $currentPosition = $bottomReveal;
        $positions = [];

        foreach ($sorted as $index => $item) {
            $model = $item['model'];
            $originalHeight = $item['height'];

            // Calculate weighted height
            $weight = $originalHeight / $totalWeight;
            $allocatedHeight = $availableHeight * $weight;

            // Ensure minimum height
            $allocatedHeight = max($allocatedHeight, $originalHeight);

            $gapAfter = ($index < $count - 1) ? $minGap : 0;

            $model->position_in_opening_inches = $currentPosition;
            $model->consumed_height_inches = $allocatedHeight + $gapAfter;
            $model->save();

            $positions[] = [
                'id' => $model->id,
                'type' => $item['type'],
                'position' => $currentPosition,
                'original_height' => $originalHeight,
                'allocated_height' => $allocatedHeight,
            ];

            $currentPosition += $allocatedHeight + $gapAfter;
        }

        $totalUsed = $currentPosition + $topReveal;

        $section->total_consumed_height_inches = $totalUsed;
        $section->remaining_height_inches = $openingHeight - $totalUsed;
        $section->save();

        return [
            'success' => $totalUsed <= $openingHeight,
            'strategy' => 'weighted_distribution',
            'total_consumed' => $totalUsed,
            'remaining' => $openingHeight - $totalUsed,
            'positions' => $positions,
        ];
    }

    /**
     * Get available layout strategies
     */
    public static function getStrategies(): array
    {
        return [
            'stack_from_bottom' => 'Stack from Bottom (Drawer Banks)',
            'stack_from_top' => 'Stack from Top (Upper Shelves)',
            'equal_distribution' => 'Equal Spacing',
            'weighted_distribution' => 'Weighted by Size',
        ];
    }

    /**
     * Get available strategy names (alias for tests)
     */
    public function getAvailableStrategies(): array
    {
        return array_keys(self::getStrategies());
    }

    /**
     * Apply a layout strategy to a section (alias for autoArrange)
     */
    public function applyStrategy(CabinetSection $section, string $strategy): array
    {
        // Check for valid strategy
        if (!array_key_exists($strategy, self::getStrategies())) {
            return [
                'success' => false,
                'error' => "Unknown strategy: {$strategy}",
            ];
        }

        $result = $this->autoArrange($section, $strategy);

        // Add has_overflow flag
        $result['has_overflow'] = ($result['overflow'] ?? 0) > 0;

        return $result;
    }
}
