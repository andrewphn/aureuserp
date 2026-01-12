<?php

use Illuminate\Support\Number;

if (! function_exists('money')) {
    function money(float|Closure $amount, string|Closure|null $currency = null, int $divideBy = 0, string|Closure|null $locale = null): string
    {
        $amount = $amount instanceof Closure ? $amount() : $amount;

        $currency = $currency instanceof Closure ? $currency() : ($currency ?? config('app.currency'));

        $locale = $locale instanceof Closure ? $locale() : ($locale ?? config('app.locale'));

        if ($divideBy > 0) {
            $amount /= $divideBy;
        }

        return Number::currency($amount, $currency, $locale);
    }
}

if (! function_exists('format_dimension')) {
    /**
     * Format a dimension value (in inches) according to measurement settings.
     *
     * @param float|null $inches Value in inches
     * @param bool|null $showSymbol Override symbol display (null = use settings)
     * @return string Formatted dimension string
     */
    function format_dimension(?float $inches, ?bool $showSymbol = null): string
    {
        return app(\Webkul\Support\Services\MeasurementFormatter::class)
            ->format($inches, $showSymbol);
    }
}

if (! function_exists('format_dimensions')) {
    /**
     * Format multiple dimensions (W x H x D).
     *
     * @param float|null $width Width in inches
     * @param float|null $height Height in inches
     * @param float|null $depth Depth in inches (optional)
     * @return string Formatted dimensions string
     */
    function format_dimensions(?float $width, ?float $height, ?float $depth = null): string
    {
        return app(\Webkul\Support\Services\MeasurementFormatter::class)
            ->formatDimensions($width, $height, $depth);
    }
}

if (! function_exists('format_linear_feet')) {
    /**
     * Format linear feet value.
     *
     * @param float $linearFeet Linear feet value
     * @return string Formatted linear feet string
     */
    function format_linear_feet(float $linearFeet): string
    {
        return app(\Webkul\Support\Services\MeasurementFormatter::class)
            ->formatLinearFeet($linearFeet);
    }
}

if (! function_exists('measurement_settings')) {
    /**
     * Get measurement settings as array (useful for JavaScript).
     *
     * @return array Measurement settings
     */
    function measurement_settings(): array
    {
        return app(\Webkul\Support\Services\MeasurementFormatter::class)
            ->getSettingsArray();
    }
}
