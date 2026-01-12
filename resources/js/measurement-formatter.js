/**
 * MeasurementFormatter - JavaScript companion to PHP MeasurementFormatter
 *
 * Provides client-side measurement formatting that mirrors the PHP implementation.
 * Used for Alpine.js components and real-time display updates.
 */
window.MeasurementFormatter = {
    // Default settings (will be overridden by PHP settings)
    settings: {
        displayUnit: 'imperial_decimal',
        fractionPrecision: 8,
        showUnitSymbol: true,
        metricPrecision: 0,
        linearFeetPrecision: 2,
        inputStep: 0.125,
    },

    // Conversion constants
    INCHES_TO_MM: 25.4,
    INCHES_TO_CM: 2.54,
    INCHES_TO_M: 0.0254,
    INCHES_TO_FEET: 1 / 12,
    FEET_TO_INCHES: 12,
    INCHES_TO_YARDS: 1 / 36,
    YARDS_TO_INCHES: 36,
    SQ_INCHES_TO_SQ_FEET: 1 / 144,
    SQ_FEET_TO_SQ_INCHES: 144,
    CUBIC_INCHES_TO_CUBIC_FEET: 1 / 1728,
    CUBIC_FEET_TO_CUBIC_INCHES: 1728,

    /**
     * Initialize with settings from PHP.
     * Call this on page load with the settings from measurement_settings() helper.
     *
     * @param {Object} settings Settings object from PHP
     */
    init(settings) {
        if (settings) {
            this.settings = { ...this.settings, ...settings };
        }
    },

    /**
     * Format a dimension value in inches according to current settings.
     *
     * @param {number|null} inches Value in inches
     * @param {boolean|null} showSymbol Override symbol display
     * @returns {string} Formatted dimension string
     */
    format(inches, showSymbol = null) {
        if (inches === null || inches === undefined || isNaN(inches)) {
            return '-';
        }

        inches = parseFloat(inches);
        showSymbol = showSymbol ?? this.settings.showUnitSymbol;

        switch (this.settings.displayUnit) {
            case 'imperial_fraction':
                return this.formatFraction(inches, showSymbol);
            case 'metric':
                return this.formatMetric(inches, showSymbol);
            default:
                return this.formatDecimal(inches, showSymbol);
        }
    },

    /**
     * Format as imperial decimal (e.g., 24.5")
     */
    formatDecimal(inches, showSymbol = true) {
        let formatted = inches.toFixed(2).replace(/\.?0+$/, '');
        return showSymbol ? formatted + '"' : formatted;
    },

    /**
     * Format as imperial fraction (e.g., 24 1/2")
     */
    formatFraction(inches, showSymbol = true) {
        const precision = this.settings.fractionPrecision;
        const whole = Math.floor(inches);
        const decimal = inches - whole;

        // No fractional part
        if (Math.abs(decimal) < 0.001) {
            return showSymbol ? whole + '"' : String(whole);
        }

        // Convert decimal to fraction
        let numerator = Math.round(decimal * precision);

        // Rounded to 0
        if (numerator === 0) {
            return showSymbol ? whole + '"' : String(whole);
        }

        // Rounded up to full unit
        if (numerator === precision) {
            return showSymbol ? (whole + 1) + '"' : String(whole + 1);
        }

        // Reduce fraction
        const fraction = this.reduceFraction(numerator, precision);
        const result = whole > 0 ? `${whole} ${fraction}` : fraction;

        return showSymbol ? result + '"' : result;
    },

    /**
     * Format as metric millimeters (e.g., 622 mm)
     */
    formatMetric(inches, showSymbol = true) {
        const mm = inches * this.INCHES_TO_MM;
        const formatted = mm.toFixed(this.settings.metricPrecision);
        return showSymbol ? formatted + ' mm' : formatted;
    },

    /**
     * Format dimensions string (W x H or W x H x D)
     */
    formatDimensions(width, height, depth = null) {
        const parts = [
            this.format(width, false) + '"W',
            this.format(height, false) + '"H',
        ];

        if (depth !== null && depth !== undefined) {
            parts.push(this.format(depth, false) + '"D');
        }

        return parts.join(' x ');
    },

    /**
     * Format linear feet value
     */
    formatLinearFeet(linearFeet) {
        const precision = this.settings.linearFeetPrecision;
        return linearFeet.toFixed(precision) + ' LF';
    },

    /**
     * Convert inches to millimeters
     */
    inchesToMm(inches) {
        return inches * this.INCHES_TO_MM;
    },

    /**
     * Convert millimeters to inches
     */
    mmToInches(mm) {
        return mm / this.INCHES_TO_MM;
    },

    /**
     * Convert inches to feet
     */
    inchesToFeet(inches) {
        return inches * this.INCHES_TO_FEET;
    },

    /**
     * Convert feet to inches
     */
    feetToInches(feet) {
        return feet * this.FEET_TO_INCHES;
    },

    /**
     * Convert inches to centimeters
     */
    inchesToCm(inches) {
        return inches * this.INCHES_TO_CM;
    },

    /**
     * Convert centimeters to inches
     */
    cmToInches(cm) {
        return cm / this.INCHES_TO_CM;
    },

    /**
     * Convert inches to meters
     */
    inchesToMeters(inches) {
        return inches * this.INCHES_TO_M;
    },

    /**
     * Convert meters to inches
     */
    metersToInches(meters) {
        return meters / this.INCHES_TO_M;
    },

    /**
     * Convert inches to yards
     */
    inchesToYards(inches) {
        return inches * this.INCHES_TO_YARDS;
    },

    /**
     * Convert yards to inches
     */
    yardsToInches(yards) {
        return yards * this.YARDS_TO_INCHES;
    },

    /**
     * Convert square inches to square feet
     */
    sqInchesToSqFeet(sqInches) {
        return sqInches * this.SQ_INCHES_TO_SQ_FEET;
    },

    /**
     * Convert square feet to square inches
     */
    sqFeetToSqInches(sqFeet) {
        return sqFeet * this.SQ_FEET_TO_SQ_INCHES;
    },

    /**
     * Calculate square feet from width and height (in inches)
     */
    calculateSquareFeet(widthInches, heightInches) {
        const sqInches = widthInches * heightInches;
        return this.sqInchesToSqFeet(sqInches);
    },

    /**
     * Convert cubic inches to cubic feet
     */
    cubicInchesToCubicFeet(cubicInches) {
        return cubicInches * this.CUBIC_INCHES_TO_CUBIC_FEET;
    },

    /**
     * Convert cubic feet to cubic inches
     */
    cubicFeetToCubicInches(cubicFeet) {
        return cubicFeet * this.CUBIC_FEET_TO_CUBIC_INCHES;
    },

    /**
     * Calculate cubic feet from width, height, and depth (in inches)
     */
    calculateCubicFeet(widthInches, heightInches, depthInches) {
        const cubicInches = widthInches * heightInches * depthInches;
        return this.cubicInchesToCubicFeet(cubicInches);
    },

    /**
     * Calculate linear feet from inches
     */
    calculateLinearFeet(inches) {
        return this.inchesToFeet(inches);
    },

    /**
     * Format square feet value
     */
    formatSquareFeet(sqFeet, precision = 2) {
        return sqFeet.toFixed(precision) + ' sq ft';
    },

    /**
     * Format cubic feet value
     */
    formatCubicFeet(cubicFeet, precision = 2) {
        return cubicFeet.toFixed(precision) + ' cu ft';
    },

    /**
     * Reduce a fraction to lowest terms
     */
    reduceFraction(numerator, denominator) {
        const gcd = this.gcd(numerator, denominator);
        return `${numerator / gcd}/${denominator / gcd}`;
    },

    /**
     * Calculate greatest common divisor
     */
    gcd(a, b) {
        return b === 0 ? a : this.gcd(b, a % b);
    },

    /**
     * Get current input step value
     */
    getInputStep() {
        return this.settings.inputStep;
    },

    /**
     * Check if currently in fraction mode
     */
    isFractionMode() {
        return this.settings.displayUnit === 'imperial_fraction';
    },

    /**
     * Check if currently in metric mode
     */
    isMetricMode() {
        return this.settings.displayUnit === 'metric';
    },
};

// Register Alpine.js magic helpers when Alpine initializes (only once)
let measurementFormatterMagicRegistered = false;
document.addEventListener('alpine:init', () => {
    if (measurementFormatterMagicRegistered) return;
    measurementFormatterMagicRegistered = true;
    
    // Magic property: $dimension(inches) - format a single dimension
    Alpine.magic('dimension', () => {
        return (inches, showSymbol = null) => MeasurementFormatter.format(inches, showSymbol);
    });

    // Magic property: $dimensions(w, h, d) - format W x H x D
    Alpine.magic('dimensions', () => {
        return (width, height, depth = null) => MeasurementFormatter.formatDimensions(width, height, depth);
    });

    // Magic property: $linearFeet(lf) - format linear feet
    Alpine.magic('linearFeet', () => {
        return (lf) => MeasurementFormatter.formatLinearFeet(lf);
    });

    // Magic property: $measurementSettings - get current settings
    Alpine.magic('measurementSettings', () => MeasurementFormatter.settings);
}, { once: true });

// Global helper functions for non-Alpine contexts
window.formatDimension = (inches, showSymbol = null) => MeasurementFormatter.format(inches, showSymbol);
window.formatDimensions = (w, h, d = null) => MeasurementFormatter.formatDimensions(w, h, d);
window.formatLinearFeet = (lf) => MeasurementFormatter.formatLinearFeet(lf);
