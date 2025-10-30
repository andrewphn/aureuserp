/**
 * PDF Viewer Utilities
 * Shared helper functions used across PDF viewer modules
 */

/**
 * Throttle function execution using requestAnimationFrame
 * @param {Function} fn - Function to throttle
 * @param {Object} context - Context object with ticking flag
 * @param {String} tickingKey - Key in context object for ticking flag
 * @returns {Function} Throttled function
 */
export function throttleRAF(fn, context, tickingKey) {
    return function(...args) {
        if (context[tickingKey]) return;
        context[tickingKey] = true;

        window.requestAnimationFrame(() => {
            context[tickingKey] = false;
            fn.apply(context, args);
        });
    };
}

/**
 * Debounce function execution
 * @param {Function} fn - Function to debounce
 * @param {Number} delay - Delay in milliseconds
 * @returns {Function} Debounced function
 */
export function debounce(fn, delay) {
    let timeoutId;
    return function(...args) {
        clearTimeout(timeoutId);
        timeoutId = setTimeout(() => fn.apply(this, args), delay);
    };
}

/**
 * Generate unique temporary ID
 * @param {String} prefix - Prefix for the ID
 * @returns {String} Unique temporary ID
 */
export function generateTempId(prefix = 'temp') {
    return `${prefix}_${Date.now()}_${Math.random().toString(36).substr(2, 9)}`;
}

/**
 * Deep clone an object (simple implementation)
 * @param {Object} obj - Object to clone
 * @returns {Object} Cloned object
 */
export function deepClone(obj) {
    if (obj === null || typeof obj !== 'object') return obj;
    if (obj instanceof Date) return new Date(obj.getTime());
    if (obj instanceof Array) return obj.map(item => deepClone(item));

    const clonedObj = {};
    for (const key in obj) {
        if (obj.hasOwnProperty(key)) {
            clonedObj[key] = deepClone(obj[key]);
        }
    }
    return clonedObj;
}

/**
 * Calculate rectangle intersection
 * @param {Object} rect1 - First rectangle {left, top, right, bottom}
 * @param {Object} rect2 - Second rectangle {left, top, right, bottom}
 * @returns {Boolean} True if rectangles intersect
 */
export function rectanglesIntersect(rect1, rect2) {
    return !(
        rect1.right < rect2.left ||
        rect1.left > rect2.right ||
        rect1.bottom < rect2.top ||
        rect1.top > rect2.bottom
    );
}

/**
 * Clamp a value between min and max
 * @param {Number} value - Value to clamp
 * @param {Number} min - Minimum value
 * @param {Number} max - Maximum value
 * @returns {Number} Clamped value
 */
export function clamp(value, min, max) {
    return Math.min(Math.max(value, min), max);
}

/**
 * Format number as percentage
 * @param {Number} value - Value to format (0-1 range)
 * @returns {String} Formatted percentage (e.g., "75%")
 */
export function formatPercentage(value) {
    return `${Math.round(value * 100)}%`;
}

/**
 * Get CSRF token from meta tag
 * @returns {String} CSRF token
 */
export function getCsrfToken() {
    return document.querySelector('meta[name="csrf-token"]')?.content || '';
}

/**
 * Create SVG element with namespace
 * @param {String} tagName - SVG element tag name
 * @param {Object} attributes - Element attributes
 * @returns {SVGElement} Created SVG element
 */
export function createSVGElement(tagName, attributes = {}) {
    const element = document.createElementNS('http://www.w3.org/2000/svg', tagName);
    for (const [key, value] of Object.entries(attributes)) {
        element.setAttribute(key, value);
    }
    return element;
}

/**
 * Wait for condition to be true with timeout
 * @param {Function} condition - Condition function that returns boolean
 * @param {Number} timeout - Maximum wait time in milliseconds
 * @param {Number} interval - Check interval in milliseconds
 * @returns {Promise<Boolean>} True if condition met, false if timeout
 */
export async function waitForCondition(condition, timeout = 5000, interval = 100) {
    const startTime = Date.now();

    while (Date.now() - startTime < timeout) {
        if (condition()) {
            return true;
        }
        await new Promise(resolve => setTimeout(resolve, interval));
    }

    return false;
}

/**
 * Check if value is numeric
 * @param {*} value - Value to check
 * @returns {Boolean} True if numeric
 */
export function isNumeric(value) {
    return !isNaN(parseFloat(value)) && isFinite(value);
}

/**
 * Get effective CSS zoom factor
 * @returns {Number} Zoom factor (e.g., 0.9 for 90% zoom)
 */
export function getEffectiveZoom() {
    const computedStyle = window.getComputedStyle(document.documentElement);
    return parseFloat(computedStyle.zoom || '1');
}

/**
 * Scroll element into view smoothly
 * @param {HTMLElement} element - Element to scroll to
 * @param {Object} options - Scroll options
 */
export function scrollToElement(element, options = {}) {
    const defaults = {
        behavior: 'smooth',
        block: 'center',
        inline: 'center'
    };

    element.scrollIntoView({ ...defaults, ...options });
}
