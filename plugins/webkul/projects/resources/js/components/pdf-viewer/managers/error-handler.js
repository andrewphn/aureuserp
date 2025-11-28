/**
 * Error Handler
 * Centralized error handling, logging, and user notification system
 *
 * Features:
 * - Consistent error logging with severity levels
 * - State error management
 * - User notifications (alerts/toasts)
 * - Development vs production modes
 * - Error tracking capability
 * - Retry logic for transient errors
 */

/**
 * Error severity levels
 */
export const ErrorSeverity = {
    INFO: 'info',
    WARNING: 'warning',
    ERROR: 'error',
    CRITICAL: 'critical'
};

/**
 * Error categories for better tracking
 */
export const ErrorCategory = {
    NETWORK: 'network',
    PDF_RENDER: 'pdf_render',
    ANNOTATION: 'annotation',
    VALIDATION: 'validation',
    STATE: 'state',
    PERMISSION: 'permission',
    UNKNOWN: 'unknown'
};

/**
 * Configuration for error handler
 */
const config = {
    // Development mode shows more detailed errors
    isDevelopment: (typeof import.meta !== 'undefined' && import.meta.env?.DEV) ||
                   (typeof process !== 'undefined' && process.env?.NODE_ENV !== 'production') ||
                   false,

    // Log all errors to console (even in production)
    enableConsoleLogging: true,

    // Show user notifications for errors
    enableUserNotifications: true,

    // Track errors (for future integration with error tracking service)
    enableErrorTracking: false,

    // Emoji prefixes for console logs
    severityPrefixes: {
        [ErrorSeverity.INFO]: 'ℹ️',
        [ErrorSeverity.WARNING]: '⚠️',
        [ErrorSeverity.ERROR]: '❌',
        [ErrorSeverity.CRITICAL]: '🔥'
    }
};

/**
 * Error history for debugging (limited to last 50 errors)
 */
const errorHistory = [];
const MAX_HISTORY_SIZE = 50;

/**
 * Main error handling function
 * @param {Object} params - Error parameters
 * @param {Error|String} params.error - Error object or message
 * @param {String} params.severity - Error severity (use ErrorSeverity enum)
 * @param {String} params.category - Error category (use ErrorCategory enum)
 * @param {String} params.context - Context where error occurred (e.g., 'loadAnnotations')
 * @param {Object} params.state - Component state to update (optional)
 * @param {Boolean} params.notify - Show user notification (default: true for ERROR/CRITICAL)
 * @param {Object} params.metadata - Additional metadata for tracking
 * @returns {Object} Normalized error object
 */
export function handleError({
    error,
    severity = ErrorSeverity.ERROR,
    category = ErrorCategory.UNKNOWN,
    context = '',
    state = null,
    notify = null,
    metadata = {}
}) {
    // Normalize error object
    const normalizedError = normalizeError(error);

    // Add context and metadata
    const errorRecord = {
        ...normalizedError,
        severity,
        category,
        context,
        timestamp: new Date().toISOString(),
        metadata: {
            ...metadata,
            userAgent: navigator.userAgent,
            url: window.location.href
        }
    };

    // Log to console
    if (config.enableConsoleLogging) {
        logToConsole(errorRecord);
    }

    // Update state if provided
    if (state) {
        updateStateError(state, errorRecord);
    }

    // Determine if we should notify user
    const shouldNotify = notify !== null ? notify :
        (severity === ErrorSeverity.ERROR || severity === ErrorSeverity.CRITICAL);

    // Show user notification
    if (config.enableUserNotifications && shouldNotify) {
        notifyUser(errorRecord);
    }

    // Track error (for future integration)
    if (config.enableErrorTracking) {
        trackError(errorRecord);
    }

    // Add to history
    addToHistory(errorRecord);

    return errorRecord;
}

/**
 * Normalize error to consistent format
 * @param {Error|String|Object} error - Error in any format
 * @returns {Object} Normalized error
 */
function normalizeError(error) {
    if (error instanceof Error) {
        return {
            message: error.message,
            name: error.name,
            stack: error.stack
        };
    }

    if (typeof error === 'string') {
        return {
            message: error,
            name: 'Error',
            stack: null
        };
    }

    if (error && typeof error === 'object') {
        return {
            message: error.message || error.error || 'Unknown error',
            name: error.name || 'Error',
            stack: error.stack || null
        };
    }

    return {
        message: 'Unknown error occurred',
        name: 'Error',
        stack: null
    };
}

/**
 * Log error to console with formatting
 * @param {Object} errorRecord - Error record
 */
function logToConsole(errorRecord) {
    const prefix = config.severityPrefixes[errorRecord.severity] || '❓';
    const contextStr = errorRecord.context ? ` [${errorRecord.context}]` : '';
    const categoryStr = errorRecord.category !== ErrorCategory.UNKNOWN ? ` (${errorRecord.category})` : '';

    const logMessage = `${prefix}${contextStr}${categoryStr} ${errorRecord.message}`;

    // Use appropriate console method based on severity
    switch (errorRecord.severity) {
        case ErrorSeverity.INFO:
            console.info(logMessage);
            break;
        case ErrorSeverity.WARNING:
            console.warn(logMessage);
            break;
        case ErrorSeverity.CRITICAL:
            console.error(logMessage, errorRecord);
            if (errorRecord.stack && config.isDevelopment) {
                console.error('Stack trace:', errorRecord.stack);
            }
            break;
        case ErrorSeverity.ERROR:
        default:
            console.error(logMessage);
            if (errorRecord.stack && config.isDevelopment) {
                console.error('Stack trace:', errorRecord.stack);
            }
            break;
    }

    // Log metadata in development
    if (config.isDevelopment && Object.keys(errorRecord.metadata).length > 0) {
        console.log('Error metadata:', errorRecord.metadata);
    }
}

/**
 * Update component state with error
 * @param {Object} state - Component state
 * @param {Object} errorRecord - Error record
 */
function updateStateError(state, errorRecord) {
    state.error = errorRecord.message;
    state.errorDetails = config.isDevelopment ? errorRecord : null;
    state.lastErrorTimestamp = errorRecord.timestamp;
}

/**
 * Show user notification
 * @param {Object} errorRecord - Error record
 */
function notifyUser(errorRecord) {
    // Get user-friendly message based on severity
    let message = errorRecord.message;

    if (errorRecord.severity === ErrorSeverity.CRITICAL) {
        message = `Critical Error: ${message}\n\nPlease refresh the page and try again.`;
    } else if (!config.isDevelopment) {
        // Simplify error messages in production
        message = getUserFriendlyMessage(errorRecord);
    }

    // Use browser alert for now (can be replaced with toast/modal in future)
    alert(message);
}

/**
 * Get user-friendly error message
 * @param {Object} errorRecord - Error record
 * @returns {String} User-friendly message
 */
function getUserFriendlyMessage(errorRecord) {
    const { category, message } = errorRecord;

    // Category-specific messages
    switch (category) {
        case ErrorCategory.NETWORK:
            return 'Network error occurred. Please check your connection and try again.';
        case ErrorCategory.PDF_RENDER:
            return 'Failed to load PDF. Please try refreshing the page.';
        case ErrorCategory.ANNOTATION:
            return 'Failed to save annotations. Your changes may not be saved.';
        case ErrorCategory.VALIDATION:
            return message; // Validation errors are usually user-friendly already
        case ErrorCategory.PERMISSION:
            return 'You don\'t have permission to perform this action.';
        default:
            return 'An error occurred. Please try again or contact support if the problem persists.';
    }
}

/**
 * Track error (placeholder for future integration with error tracking service)
 * @param {Object} errorRecord - Error record
 */
function trackError(errorRecord) {
    // TODO: Integrate with error tracking service (e.g., Sentry, Rollbar, etc.)
    console.log('📊 Error tracked:', errorRecord);
}

/**
 * Add error to history
 * @param {Object} errorRecord - Error record
 */
function addToHistory(errorRecord) {
    errorHistory.unshift(errorRecord);

    // Keep only last MAX_HISTORY_SIZE errors
    if (errorHistory.length > MAX_HISTORY_SIZE) {
        errorHistory.pop();
    }
}

/**
 * Get error history
 * @returns {Array} Array of error records
 */
export function getErrorHistory() {
    return [...errorHistory];
}

/**
 * Clear error history
 */
export function clearErrorHistory() {
    errorHistory.length = 0;
}

/**
 * Clear error from state
 * @param {Object} state - Component state
 */
export function clearError(state) {
    state.error = null;
    state.errorDetails = null;
    state.lastErrorTimestamp = null;
}

/**
 * Retry function with exponential backoff
 * @param {Function} fn - Async function to retry
 * @param {Object} options - Retry options
 * @param {Number} options.maxRetries - Maximum retry attempts (default: 3)
 * @param {Number} options.initialDelay - Initial delay in ms (default: 1000)
 * @param {Number} options.maxDelay - Maximum delay in ms (default: 10000)
 * @param {String} options.context - Context for error logging
 * @param {Object} options.state - Component state for error updates
 * @returns {Promise<any>} Result of function
 * @throws {Error} Last error if all retries fail
 */
export async function retryWithBackoff(fn, {
    maxRetries = 3,
    initialDelay = 1000,
    maxDelay = 10000,
    context = 'retryWithBackoff',
    state = null
} = {}) {
    let lastError = null;
    let delay = initialDelay;

    for (let attempt = 1; attempt <= maxRetries; attempt++) {
        try {
            return await fn();
        } catch (error) {
            lastError = error;

            if (attempt === maxRetries) {
                // Last attempt failed - handle error
                handleError({
                    error,
                    severity: ErrorSeverity.ERROR,
                    category: ErrorCategory.NETWORK,
                    context: `${context} (failed after ${maxRetries} attempts)`,
                    state,
                    notify: true,
                    metadata: { attempts: maxRetries }
                });
                throw error;
            }

            // Log retry attempt
            console.log(`⏳ Retry attempt ${attempt}/${maxRetries} after ${delay}ms delay...`);

            // Wait before retrying
            await new Promise(resolve => setTimeout(resolve, delay));

            // Exponential backoff with jitter
            delay = Math.min(delay * 2 + Math.random() * 1000, maxDelay);
        }
    }

    throw lastError;
}

/**
 * Wrap async function with error handling
 * @param {Function} fn - Async function to wrap
 * @param {Object} errorConfig - Error handling configuration
 * @returns {Function} Wrapped function
 */
export function withErrorHandler(fn, errorConfig = {}) {
    return async function(...args) {
        try {
            return await fn(...args);
        } catch (error) {
            handleError({
                error,
                ...errorConfig
            });
            throw error;
        }
    };
}

/**
 * Check if error is retriable (network errors, timeouts, etc.)
 * @param {Error} error - Error to check
 * @returns {Boolean} True if error is retriable
 */
export function isRetriableError(error) {
    const retriableMessages = [
        'network',
        'timeout',
        'connection',
        'fetch',
        'ECONNREFUSED',
        'ETIMEDOUT',
        'ENOTFOUND'
    ];

    const message = error.message?.toLowerCase() || '';
    const name = error.name?.toLowerCase() || '';

    return retriableMessages.some(term =>
        message.includes(term) || name.includes(term)
    );
}

/**
 * Create error with category
 * @param {String} message - Error message
 * @param {String} category - Error category (use ErrorCategory enum)
 * @returns {Error} Error object with category
 */
export function createCategorizedError(message, category = ErrorCategory.UNKNOWN) {
    const error = new Error(message);
    error.category = category;
    return error;
}

/**
 * Update configuration
 * @param {Object} newConfig - New configuration values
 */
export function configure(newConfig) {
    Object.assign(config, newConfig);
}

/**
 * Get current configuration
 * @returns {Object} Current configuration
 */
export function getConfig() {
    return { ...config };
}
