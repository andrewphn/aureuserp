<?php

namespace Webkul\Support\Traits;

use Webkul\Support\Services\MeasurementFormatter;

/**
 * Trait for models that have dimension attributes.
 *
 * Provides standardized formatted dimension accessors that respect
 * the global measurement settings (imperial decimal, fraction, or metric).
 *
 * Used by: Cabinet, Door, Drawer, Shelf, Pullout, CabinetSection, CabinetRun
 *
 * Override the get*Field() methods in your model if using different column names.
 */
trait HasFormattedDimensions
{
    /**
     * Get the MeasurementFormatter instance.
     */
    protected function getMeasurementFormatter(): MeasurementFormatter
    {
        return app(MeasurementFormatter::class);
    }

    /**
     * Get formatted width dimension.
     */
    public function getFormattedWidthAttribute(): string
    {
        $field = $this->getWidthField();
        return $this->getMeasurementFormatter()->format($this->$field ?? null);
    }

    /**
     * Get formatted height dimension.
     */
    public function getFormattedHeightAttribute(): string
    {
        $field = $this->getHeightField();
        return $this->getMeasurementFormatter()->format($this->$field ?? null);
    }

    /**
     * Get formatted depth dimension.
     */
    public function getFormattedDepthAttribute(): string
    {
        $field = $this->getDepthField();
        return $this->getMeasurementFormatter()->format($this->$field ?? null);
    }

    /**
     * Get formatted length dimension.
     */
    public function getFormattedLengthAttribute(): string
    {
        $field = $this->getLengthField();
        return $this->getMeasurementFormatter()->format($this->$field ?? null);
    }

    /**
     * Get formatted dimensions string (W x H or W x H x D).
     * Override this method in your model for custom dimension combinations.
     */
    public function getFormattedDimensionsAttribute(): string
    {
        $formatter = $this->getMeasurementFormatter();

        $width = $this->{$this->getWidthField()} ?? null;
        $height = $this->{$this->getHeightField()} ?? null;
        $depth = $this->hasDepthField() ? ($this->{$this->getDepthField()} ?? null) : null;

        return $formatter->formatDimensions($width, $height, $depth);
    }

    /**
     * Get formatted linear feet.
     */
    public function getFormattedLinearFeetAttribute(): string
    {
        $formatter = $this->getMeasurementFormatter();

        // If model has linear_feet attribute, use it
        if (isset($this->attributes['linear_feet']) || property_exists($this, 'linear_feet')) {
            return $formatter->formatLinearFeet($this->linear_feet ?? 0);
        }

        // Otherwise calculate from length field
        $lengthField = $this->getLengthField();
        $length = $this->$lengthField ?? 0;
        $linearFeet = $length / 12;

        return $formatter->formatLinearFeet($linearFeet);
    }

    /**
     * Format a specific dimension field.
     *
     * @param string $field Field name to format
     * @param bool|null $showSymbol Override symbol display
     * @return string Formatted dimension
     */
    public function formatDimension(string $field, ?bool $showSymbol = null): string
    {
        return $this->getMeasurementFormatter()->format($this->$field ?? null, $showSymbol);
    }

    /**
     * Get the width field name for this model.
     * Override in model if using different field name.
     * Default: width_inches, falls back to length_inches for Cabinet
     */
    protected function getWidthField(): string
    {
        return 'width_inches';
    }

    /**
     * Get the height field name for this model.
     * Override in model if using different field name.
     */
    protected function getHeightField(): string
    {
        return 'height_inches';
    }

    /**
     * Get the depth field name for this model.
     * Override in model if using different field name.
     */
    protected function getDepthField(): string
    {
        return 'depth_inches';
    }

    /**
     * Get the length field name (for linear feet calculation).
     * Override in model if using different field name.
     */
    protected function getLengthField(): string
    {
        return 'length_inches';
    }

    /**
     * Check if model has a depth field.
     * Override in model if depth is not applicable.
     */
    protected function hasDepthField(): bool
    {
        $field = $this->getDepthField();
        return isset($this->attributes[$field]) || property_exists($this, $field);
    }

    /**
     * Get measurement settings as array (useful for JavaScript/Alpine.js).
     */
    public function getMeasurementSettingsAttribute(): array
    {
        return $this->getMeasurementFormatter()->getSettingsArray();
    }
}
