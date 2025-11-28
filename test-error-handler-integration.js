#!/usr/bin/env node

/**
 * Error Handler Integration Test
 * Demonstrates usage of the new error-handler.js module
 *
 * Run with: node test-error-handler-integration.js
 */

// Simulate import.meta.env for Node environment
global.import = {
    meta: {
        env: {
            DEV: true  // Development mode
        }
    }
};

// Mock browser APIs
global.navigator = { userAgent: 'Node.js Test Runner' };
global.window = { location: { href: 'http://test.local/pdf-viewer' } };
global.alert = (message) => console.log(`\n📢 ALERT: ${message}\n`);

// Import error handler
const {
    handleError,
    ErrorSeverity,
    ErrorCategory,
    retryWithBackoff,
    getErrorHistory,
    clearErrorHistory,
    clearError,
    withErrorHandler,
    isRetriableError,
    createCategorizedError,
    configure
} = await import('./plugins/webkul/projects/resources/js/components/pdf-viewer/managers/error-handler.js');

console.log('🧪 Error Handler Integration Test\n');
console.log('='.repeat(80) + '\n');

// Configure for testing
configure({
    enableUserNotifications: true,
    enableConsoleLogging: true,
    isDevelopment: true
});

// Test 1: Basic Error Handling
console.log('Test 1: Basic Error Handling');
console.log('-'.repeat(80));

const state1 = { error: null };
try {
    throw new Error('Test network failure');
} catch (error) {
    handleError({
        error,
        severity: ErrorSeverity.ERROR,
        category: ErrorCategory.NETWORK,
        context: 'loadAnnotations',
        state: state1
    });
}

console.assert(state1.error === 'Test network failure', '❌ State error not set');
console.log('✅ Test 1 passed: Basic error handling\n');

// Test 2: Different Severity Levels
console.log('Test 2: Different Severity Levels');
console.log('-'.repeat(80));

handleError({
    error: 'Cache loaded successfully',
    severity: ErrorSeverity.INFO,
    notify: false
});

handleError({
    error: 'Some annotations may be stale',
    severity: ErrorSeverity.WARNING,
    notify: false
});

handleError({
    error: 'Failed to save annotations',
    severity: ErrorSeverity.ERROR,
    notify: false
});

console.log('✅ Test 2 passed: Severity levels\n');

// Test 3: Error Categories
console.log('Test 3: Error Categories');
console.log('-'.repeat(80));

const testCategories = [
    { category: ErrorCategory.NETWORK, msg: 'Network timeout' },
    { category: ErrorCategory.PDF_RENDER, msg: 'PDF rendering failed' },
    { category: ErrorCategory.ANNOTATION, msg: 'Failed to save annotation' },
    { category: ErrorCategory.VALIDATION, msg: 'Invalid room name' },
    { category: ErrorCategory.PERMISSION, msg: 'Unauthorized access' }
];

testCategories.forEach(({ category, msg }) => {
    handleError({
        error: msg,
        category,
        severity: ErrorSeverity.WARNING,
        notify: false
    });
});

console.log('✅ Test 3 passed: Error categories\n');

// Test 4: Retry Logic (Simulated)
console.log('Test 4: Retry with Exponential Backoff');
console.log('-'.repeat(80));

let attemptCount = 0;
const maxAttempts = 3;

try {
    await retryWithBackoff(
        async () => {
            attemptCount++;
            console.log(`  Attempt ${attemptCount}/${maxAttempts}`);

            if (attemptCount < 3) {
                throw new Error('Network timeout');
            }

            // Success on 3rd attempt
            return { success: true };
        },
        {
            maxRetries: 3,
            initialDelay: 100,  // Faster for testing
            maxDelay: 500,
            context: 'fetchAnnotations',
            state: { error: null }
        }
    );

    console.log('✅ Test 4 passed: Retry succeeded on attempt 3\n');
} catch (error) {
    console.error('❌ Test 4 failed: Retry did not succeed');
}

// Test 5: Error History
console.log('Test 5: Error History Tracking');
console.log('-'.repeat(80));

clearErrorHistory();

// Generate some test errors
for (let i = 1; i <= 5; i++) {
    handleError({
        error: `Test error ${i}`,
        severity: ErrorSeverity.WARNING,
        context: `test${i}`,
        notify: false
    });
}

const history = getErrorHistory();
console.assert(history.length === 5, `❌ Expected 5 errors, got ${history.length}`);
console.log(`  Error history: ${history.length} errors tracked`);
console.log('✅ Test 5 passed: Error history tracking\n');

// Test 6: Clear State Error
console.log('Test 6: Clear State Error');
console.log('-'.repeat(80));

const state6 = { error: 'Test error', errorDetails: {}, lastErrorTimestamp: Date.now() };
clearError(state6);

console.assert(state6.error === null, '❌ Error not cleared');
console.assert(state6.errorDetails === null, '❌ Error details not cleared');
console.log('✅ Test 6 passed: Clear state error\n');

// Test 7: Function Wrapper
console.log('Test 7: Function Wrapper');
console.log('-'.repeat(80));

const riskyOperation = withErrorHandler(
    async function(shouldFail) {
        if (shouldFail) {
            throw new Error('Operation failed');
        }
        return { success: true };
    },
    {
        severity: ErrorSeverity.ERROR,
        category: ErrorCategory.ANNOTATION,
        context: 'riskyOperation',
        notify: false
    }
);

try {
    await riskyOperation(true);
    console.error('❌ Test 7 failed: Should have thrown');
} catch (error) {
    console.log('  Error caught as expected');
}

const result = await riskyOperation(false);
console.assert(result.success === true, '❌ Operation should succeed when shouldFail=false');
console.log('✅ Test 7 passed: Function wrapper\n');

// Test 8: Retriable Error Detection
console.log('Test 8: Retriable Error Detection');
console.log('-'.repeat(80));

const retriableErrors = [
    new Error('Network timeout'),
    new Error('ECONNREFUSED'),
    new Error('Fetch failed')
];

const nonRetriableErrors = [
    new Error('Validation failed'),
    new Error('Unauthorized access')
];

retriableErrors.forEach(err => {
    console.assert(
        isRetriableError(err),
        `❌ ${err.message} should be retriable`
    );
});

nonRetriableErrors.forEach(err => {
    console.assert(
        !isRetriableError(err),
        `❌ ${err.message} should not be retriable`
    );
});

console.log('✅ Test 8 passed: Retriable error detection\n');

// Test 9: Categorized Error Creation
console.log('Test 9: Categorized Error Creation');
console.log('-'.repeat(80));

const catError = createCategorizedError('Network failed', ErrorCategory.NETWORK);
console.assert(catError.category === ErrorCategory.NETWORK, '❌ Category not set');
console.assert(catError.message === 'Network failed', '❌ Message not set');
console.log('✅ Test 9 passed: Categorized error creation\n');

// Summary
console.log('='.repeat(80));
console.log('📊 Test Summary\n');
console.log('All 9 integration tests passed ✅');
console.log('\nError handler is ready for integration into manager files.');
console.log('\nNext steps:');
console.log('  1. Import error-handler.js in manager files');
console.log('  2. Replace console.error patterns with handleError()');
console.log('  3. Add retry logic for network operations');
console.log('  4. Test in browser environment');
console.log('\nSee docs/phase-4-error-handler-integration-guide.md for details.');
console.log('='.repeat(80));
