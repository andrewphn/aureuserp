<?php

namespace Webkul\Project\Traits;

use App\Services\OpeningConfiguratorService;

/**
 * Trait for components that can be positioned within a cabinet opening
 *
 * Provides shared position-related methods for:
 * - Drawer
 * - Shelf
 * - Door
 * - Pullout
 *
 * @see docs/OPENING_CONFIGURATOR_SYSTEM.md
 * @see database/migrations/2026_01_15_120002_add_position_fields_to_components.php
 *
 * @property float|null $position_in_opening_inches Distance from bottom of opening
 * @property float|null $consumed_height_inches Total vertical space consumed
 * @property float|null $position_from_left_inches Distance from left edge
 * @property float|null $consumed_width_inches Total horizontal space consumed
 */
trait HasOpeningPosition
{
    /**
     * Get the position fields that this trait adds
     */
    public static function getOpeningPositionFields(): array
    {
        return [
            'position_in_opening_inches',
            'consumed_height_inches',
            'position_from_left_inches',
            'consumed_width_inches',
        ];
    }

    /**
     * Get the position casts for this trait
     */
    public static function getOpeningPositionCasts(): array
    {
        return [
            'position_in_opening_inches' => 'float',
            'consumed_height_inches' => 'float',
            'position_from_left_inches' => 'float',
            'consumed_width_inches' => 'float',
        ];
    }

    /**
     * Get formatted position from bottom
     */
    public function getFormattedPositionAttribute(): string
    {
        if ($this->position_in_opening_inches === null) {
            return 'Not positioned';
        }

        $service = app(OpeningConfiguratorService::class);
        return $service->toFraction($this->position_in_opening_inches) . '" from bottom';
    }

    /**
     * Get formatted consumed height
     */
    public function getFormattedConsumedHeightAttribute(): string
    {
        if ($this->consumed_height_inches === null) {
            return 'Not calculated';
        }

        $service = app(OpeningConfiguratorService::class);
        return $service->toFraction($this->consumed_height_inches) . '"';
    }

    /**
     * Get formatted horizontal position
     */
    public function getFormattedPositionLeftAttribute(): string
    {
        if ($this->position_from_left_inches === null) {
            return 'Not positioned';
        }

        $service = app(OpeningConfiguratorService::class);
        return $service->toFraction($this->position_from_left_inches) . '" from left';
    }

    /**
     * Check if this component has been positioned
     */
    public function isPositioned(): bool
    {
        return $this->position_in_opening_inches !== null
            || $this->position_from_left_inches !== null;
    }

    /**
     * Get the top edge position (position + height)
     */
    public function getTopEdgePosition(): ?float
    {
        if ($this->position_in_opening_inches === null) {
            return null;
        }

        $height = $this->getComponentHeightForOpening();
        return $this->position_in_opening_inches + $height;
    }

    /**
     * Get the right edge position (position_left + width)
     */
    public function getRightEdgePosition(): ?float
    {
        if ($this->position_from_left_inches === null) {
            return null;
        }

        $width = $this->getComponentWidthForOpening();
        return $this->position_from_left_inches + $width;
    }

    /**
     * Get the height dimension for opening calculations
     * Override in model if using different field
     */
    public function getComponentHeightForOpening(): float
    {
        // Default: look for common height fields
        if (property_exists($this, 'front_height_inches') || isset($this->front_height_inches)) {
            return $this->front_height_inches ?? 0;
        }

        if (property_exists($this, 'height_inches') || isset($this->height_inches)) {
            return $this->height_inches ?? 0;
        }

        if (property_exists($this, 'thickness_inches') || isset($this->thickness_inches)) {
            // For shelves, the thickness is used but opening clearance is needed
            return OpeningConfiguratorService::MIN_SHELF_OPENING_HEIGHT_INCHES;
        }

        return 0;
    }

    /**
     * Get the width dimension for opening calculations
     * Override in model if using different field
     */
    public function getComponentWidthForOpening(): float
    {
        if (property_exists($this, 'front_width_inches') || isset($this->front_width_inches)) {
            return $this->front_width_inches ?? 0;
        }

        if (property_exists($this, 'width_inches') || isset($this->width_inches)) {
            return $this->width_inches ?? 0;
        }

        return 0;
    }

    /**
     * Check if this component overlaps with another (vertically)
     */
    public function overlapsVertically($other): bool
    {
        if (!$this->isPositioned() || !$other->isPositioned()) {
            return false;
        }

        $thisBottom = $this->position_in_opening_inches;
        $thisTop = $this->getTopEdgePosition();
        $otherBottom = $other->position_in_opening_inches;
        $otherTop = $other->getTopEdgePosition();

        // Overlap if one's bottom is below other's top AND one's top is above other's bottom
        return $thisBottom < $otherTop && $thisTop > $otherBottom;
    }

    /**
     * Check if this component overlaps with another (horizontally)
     */
    public function overlapsHorizontally($other): bool
    {
        if ($this->position_from_left_inches === null || $other->position_from_left_inches === null) {
            return false;
        }

        $thisLeft = $this->position_from_left_inches;
        $thisRight = $this->getRightEdgePosition();
        $otherLeft = $other->position_from_left_inches;
        $otherRight = $other->getRightEdgePosition();

        return $thisLeft < $otherRight && $thisRight > $otherLeft;
    }

    /**
     * Reset position data (useful when reorganizing)
     */
    public function resetPosition(): void
    {
        $this->position_in_opening_inches = null;
        $this->consumed_height_inches = null;
        $this->position_from_left_inches = null;
        $this->consumed_width_inches = null;
        $this->save();
    }

    /**
     * Set vertical position with consumed height calculation
     */
    public function setVerticalPosition(float $positionFromBottom, float $gapAfter = 0): void
    {
        $height = $this->getComponentHeightForOpening();

        $this->position_in_opening_inches = $positionFromBottom;
        $this->consumed_height_inches = $height + $gapAfter;
        $this->save();
    }

    /**
     * Set horizontal position with consumed width calculation
     */
    public function setHorizontalPosition(float $positionFromLeft, float $gapAfter = 0): void
    {
        $width = $this->getComponentWidthForOpening();

        $this->position_from_left_inches = $positionFromLeft;
        $this->consumed_width_inches = $width + $gapAfter;
        $this->save();
    }
}
