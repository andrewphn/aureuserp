# Error Handler Integration Guide

## Overview

The new `error-handler.js` module provides centralized error handling with:
- Consistent error logging and severity levels
- State error management
- User notifications
- Retry logic with exponential backoff
- Development vs production modes
- Error history tracking

## Quick Start

### Basic Usage

```javascript
import { handleError, ErrorSeverity, ErrorCategory } from './error-handler.js';

// Simple error handling
try {
    await someOperation();
} catch (error) {
    handleError({
        error,
        severity: ErrorSeverity.ERROR,
        category: ErrorCategory.NETWORK,
        context: 'loadAnnotations',
        state: this // Alpine component state
    });
}
```

### Migration Examples

#### Before (annotation-manager.js:51-53)
```javascript
} catch (error) {
    console.error('Failed to load annotations:', error);
    state.error = error.message;
}
```

#### After
```javascript
import { handleError, ErrorSeverity, ErrorCategory } from './error-handler.js';

} catch (error) {
    handleError({
        error,
        severity: ErrorSeverity.ERROR,
        category: ErrorCategory.ANNOTATION,
        context: 'loadAnnotations',
        state
    });
}
```

## Features

### 1. Error Severity Levels

```javascript
import { ErrorSeverity } from './error-handler.js';

// Information message (logged with ℹ️)
handleError({
    error: 'Annotations cached successfully',
    severity: ErrorSeverity.INFO,
    notify: false
});

// Warning message (logged with ⚠️)
handleError({
    error: 'Some annotations could not be loaded',
    severity: ErrorSeverity.WARNING
});

// Error message (logged with ❌, shows user notification)
handleError({
    error: 'Failed to save annotations',
    severity: ErrorSeverity.ERROR
});

// Critical error (logged with 🔥, shows urgent notification)
handleError({
    error: 'PDF rendering system failed',
    severity: ErrorSeverity.CRITICAL
});
```

### 2. Error Categories

```javascript
import { ErrorCategory } from './error-handler.js';

// Provides user-friendly messages based on category
handleError({
    error: networkError,
    category: ErrorCategory.NETWORK  // "Network error occurred..."
});

handleError({
    error: pdfError,
    category: ErrorCategory.PDF_RENDER  // "Failed to load PDF..."
});

handleError({
    error: annotationError,
    category: ErrorCategory.ANNOTATION  // "Failed to save annotations..."
});
```

### 3. Retry with Exponential Backoff

```javascript
import { retryWithBackoff } from './error-handler.js';

// Automatically retries network operations
const annotations = await retryWithBackoff(
    async () => {
        const response = await fetch(`/api/pdf/page/${pdfPageId}/annotations`);
        if (!response.ok) throw new Error(`HTTP ${response.status}`);
        return response.json();
    },
    {
        maxRetries: 3,
        initialDelay: 1000,  // 1 second
        maxDelay: 10000,     // 10 seconds max
        context: 'loadAnnotations',
        state: this
    }
);
```

### 4. Function Wrapper

```javascript
import { withErrorHandler } from './error-handler.js';

// Wrap entire function with error handling
export const loadAnnotations = withErrorHandler(
    async function(state, refs, callbacks = {}) {
        const response = await fetch(`/api/pdf/page/${state.pdfPageId}/annotations`);
        return response.json();
    },
    {
        severity: ErrorSeverity.ERROR,
        category: ErrorCategory.ANNOTATION,
        context: 'loadAnnotations'
    }
);
```

### 5. Error History

```javascript
import { getErrorHistory, clearErrorHistory } from './error-handler.js';

// Get recent errors for debugging
const recentErrors = getErrorHistory();
console.log('Last 50 errors:', recentErrors);

// Clear history
clearErrorHistory();
```

### 6. Clear State Error

```javascript
import { clearError } from './error-handler.js';

// Clear error from component state
clearError(state);
```

### 7. Configuration

```javascript
import { configure } from './error-handler.js';

// Customize error handler behavior
configure({
    enableUserNotifications: false,  // Disable alerts
    enableErrorTracking: true,       // Enable tracking
    isDevelopment: true              // Show detailed errors
});
```

## Integration Checklist

### Phase 1: Import Error Handler

Add to each manager file that needs error handling:

```javascript
import {
    handleError,
    ErrorSeverity,
    ErrorCategory,
    retryWithBackoff,
    clearError
} from './error-handler.js';
```

### Phase 2: Replace Console.error Calls

Search for patterns like:
```javascript
console.error('Failed to...', error);
state.error = error.message;
```

Replace with:
```javascript
handleError({
    error,
    severity: ErrorSeverity.ERROR,
    category: ErrorCategory.ANNOTATION,  // Choose appropriate category
    context: 'functionName',
    state
});
```

### Phase 3: Add Retry Logic

For network operations, wrap with `retryWithBackoff`:

```javascript
// Before
const response = await fetch(url);

// After
const response = await retryWithBackoff(
    async () => {
        const res = await fetch(url);
        if (!res.ok) throw new Error(`HTTP ${res.status}`);
        return res;
    },
    { maxRetries: 3, context: 'fetchAnnotations', state }
);
```

### Phase 4: Test Integration

1. Verify error logging in console
2. Test user notifications
3. Check state.error updates
4. Test retry logic with network errors
5. Verify error history tracking

## Priority Files for Migration

Based on error frequency analysis:

1. **annotation-manager.js** (12 error handlers)
   - loadAnnotations
   - saveAnnotations
   - deleteAnnotation

2. **pdf-manager.js** (10 error handlers)
   - preloadPDF
   - renderPDF
   - initializePDFSystem

3. **autocomplete-manager.js** (4 error handlers)
   - createNewRoom
   - createNewLocation

4. **tree-manager.js** (1 error handler)
   - loadTree

## Best Practices

### 1. Choose Appropriate Severity

- **INFO**: Informational messages, no action needed
- **WARNING**: Potential issues, user can continue
- **ERROR**: Operation failed, user should retry
- **CRITICAL**: System-level failure, page refresh recommended

### 2. Choose Appropriate Category

- **NETWORK**: Fetch failures, timeouts
- **PDF_RENDER**: PDF.js rendering errors
- **ANNOTATION**: Annotation CRUD failures
- **VALIDATION**: User input validation
- **PERMISSION**: Authorization failures
- **STATE**: State management errors
- **UNKNOWN**: Uncategorized errors

### 3. Provide Context

Always include context (function name) for easier debugging:

```javascript
handleError({
    error,
    context: 'loadAnnotations',  // ✅ Good
    // vs
    context: '',                  // ❌ Bad
});
```

### 4. Control User Notifications

Only notify users for errors they can act on:

```javascript
// Silent error (logged but not shown to user)
handleError({
    error: 'Cache miss',
    severity: ErrorSeverity.INFO,
    notify: false
});

// User-facing error (shown to user)
handleError({
    error: 'Failed to save',
    severity: ErrorSeverity.ERROR,
    notify: true
});
```

## Future Enhancements

1. **Error Tracking Integration**
   - Sentry, Rollbar, or custom tracking service
   - Already scaffolded in `trackError()` function

2. **Toast Notifications**
   - Replace `alert()` with non-blocking toasts
   - Add to `notifyUser()` function

3. **Error Recovery Actions**
   - Automatic retry buttons
   - Rollback mechanisms

4. **Error Analytics Dashboard**
   - View error trends
   - Filter by category/severity

## Testing

```javascript
// Test error handler manually
import { handleError, ErrorSeverity, ErrorCategory } from './error-handler.js';

// Trigger test error
handleError({
    error: 'Test error message',
    severity: ErrorSeverity.ERROR,
    category: ErrorCategory.ANNOTATION,
    context: 'test',
    state: { error: null },
    metadata: { testRun: true }
});

// Check state was updated
console.assert(state.error === 'Test error message');

// Check history
const history = getErrorHistory();
console.assert(history.length > 0);
console.assert(history[0].message === 'Test error message');
```

## Support

- File issues in project repository
- See `error-handler.js` JSDoc for full API documentation
- Check error history in browser console: `getErrorHistory()`
